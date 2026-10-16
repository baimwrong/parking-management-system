<?php

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Models\ParkingZone;
use App\Models\User;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ParkingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Parking Management System');
    }

    public function test_user_can_authenticate_and_access_dashboard(): void
    {
        $admin = User::where('email', 'admin@parking.local')->first();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Operations Dashboard');
        $response->assertSee('Metropolis Grand Central Complex');
    }

    public function test_vehicle_checkin_creates_ticket_and_gate_log(): void
    {
        $admin = User::where('email', 'admin@parking.local')->first();
        $loc = ParkingLocation::first();
        $gate = Gate::where('gate_type', 'entry')->first();
        $vType = VehicleType::where('code', 'car')->first();

        $plate = 'B 9876 TST';

        $response = $this->actingAs($admin)->post('/gate/check-in', [
            'parking_location_id' => $loc->id,
            'entry_gate_id' => $gate->id,
            'vehicle_type_id' => $vType->id,
            'plate_number' => $plate,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('parking_tickets', [
            'plate_number' => $plate,
            'status' => 'parked',
        ]);
    }

    public function test_vehicle_checkout_calculates_fees_and_processes_payment(): void
    {
        $cashier = User::where('email', 'cashier@parking.local')->first();
        $ticket = ParkingTicket::where('status', 'parked')->first();
        $exitGate = Gate::where('gate_type', 'exit')->first();

        $this->assertNotNull($ticket);

        $response = $this->actingAs($cashier)->post('/gate/process-payment', [
            'ticket_id' => $ticket->id,
            'exit_gate_id' => $exitGate->id,
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('parking_tickets', [
            'id' => $ticket->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('payments', [
            'parking_ticket_id' => $ticket->id,
            'transaction_status' => 'success',
        ]);
    }

    public function test_admin_bulk_import_slots(): void
    {
        $admin = User::where('email', 'admin@parking.local')->first();
        $zone = ParkingZone::first();

        $csvContent = "zone_code,slot_number,vehicle_type,notes\n{$zone->code},TEST-BAY-99,car,Import Test Bay";
        $file = UploadedFile::fake()->createWithContent('slots.csv', $csvContent);

        $response = $this->actingAs($admin)->post('/admin/bulk-import', [
            'import_type' => 'slots',
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('parking_slots', [
            'slot_number' => 'TEST-BAY-99',
            'parking_zone_id' => $zone->id,
        ]);
    }

    public function test_role_middleware_restricts_unauthorized_users(): void
    {
        $cashier = User::where('email', 'cashier@parking.local')->first();

        // Cashier shouldn't have access to Admin Bulk Import
        $response = $this->actingAs($cashier)->get('/admin/bulk-import');
        $response->assertStatus(403);
    }

    public function test_exit_terminal_renders_ticket_with_billable_hours(): void
    {
        $cashier = User::where('email', 'cashier@parking.local')->first();
        $ticket = ParkingTicket::where('status', 'parked')->where('ticket_type', 'casual')->first();
        $this->assertNotNull($ticket);

        $response = $this->actingAs($cashier)->get('/gate/exit?search_term='.$ticket->ticket_number);
        $response->assertStatus(200);
        $response->assertSee('Billable Hours');
    }

    public function test_ticket_discount_is_retained_and_applied_during_checkout(): void
    {
        $admin = User::where('email', 'admin@parking.local')->first();
        $cashier = User::where('email', 'cashier@parking.local')->first();
        $ticket = ParkingTicket::where('status', 'parked')->where('ticket_type', 'casual')->first();
        $exitGate = Gate::where('gate_type', 'exit')->first();

        // Admin applies a discount
        $this->actingAs($admin)->post("/tickets/{$ticket->id}/discount", [
            'discount_amount' => 5.00,
            'reason' => 'VIP voucher stamp',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals(5.00, (float) $ticket->discount_amount);

        // Recalculating charges should retain discount
        $calc = $ticket->recalculateCharges();
        $this->assertEquals(5.00, (float) $calc['discount']);

        // Cashier settles ticket
        $totalDue = $calc['total'];
        $response = $this->actingAs($cashier)->post('/gate/process-payment', [
            'ticket_id' => $ticket->id,
            'exit_gate_id' => $exitGate->id,
            'payment_method' => 'cash',
            'amount_paid' => $totalDue,
        ]);

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals('completed', $ticket->status);
        $this->assertEquals(5.00, (float) $ticket->discount_amount);
        $this->assertEquals($totalDue, (float) $ticket->total_amount);
    }

    public function test_vehicle_checkin_with_designated_slot(): void
    {
        $admin = User::where('email', 'admin@parking.local')->first();
        $loc = ParkingLocation::first();
        $gate = Gate::where('gate_type', 'entry')->first();
        $vType = VehicleType::where('code', 'car')->first();
        $availableSlot = ParkingSlot::where('status', 'available')->first();
        $this->assertNotNull($availableSlot);

        $plate = 'B 7777 DES';

        $response = $this->actingAs($admin)->post('/gate/check-in', [
            'parking_location_id' => $loc->id,
            'entry_gate_id' => $gate->id,
            'vehicle_type_id' => $vType->id,
            'parking_zone_id' => $availableSlot->parking_zone_id,
            'parking_slot_id' => $availableSlot->id,
            'plate_number' => $plate,
        ]);

        $response->assertRedirect();
        $availableSlot->refresh();
        $this->assertEquals('occupied', $availableSlot->status);

        $this->assertDatabaseHas('parking_tickets', [
            'plate_number' => $plate,
            'parking_slot_id' => $availableSlot->id,
            'status' => 'parked',
        ]);
    }

    public function test_lost_ticket_process_resolves_existing_ticket_and_frees_slot(): void
    {
        $cashier = User::where('email', 'cashier@parking.local')->first();
        $parkedTicket = ParkingTicket::with('slot')->where('status', 'parked')->whereNotNull('parking_slot_id')->first();
        $this->assertNotNull($parkedTicket);

        $slot = $parkedTicket->slot;
        $plate = $parkedTicket->plate_number;
        $loc = ParkingLocation::first();
        $exitGate = Gate::where('gate_type', 'exit')->first();
        $vType = $parkedTicket->vehicleType;

        $response = $this->actingAs($cashier)->post('/gate/process-lost-ticket', [
            'parking_location_id' => $loc->id,
            'exit_gate_id' => $exitGate->id,
            'vehicle_type_id' => $vType->id,
            'plate_number' => $plate,
            'estimated_entry_time' => now()->subHours(3)->format('Y-m-d H:i:s'),
            'driver_id_number' => 'DL-987654321',
            'driver_phone' => '+1-555-1234',
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
        ]);

        $response->assertRedirect();

        // The slot must now be freed
        $slot->refresh();
        $this->assertEquals('available', $slot->status);

        // The original parked ticket must be marked 'lost'
        $parkedTicket->refresh();
        $this->assertEquals('lost', $parkedTicket->status);

        // A new completed lost ticket must exist
        $this->assertDatabaseHas('parking_tickets', [
            'plate_number' => $plate,
            'ticket_type' => 'lost',
            'status' => 'completed',
        ]);
    }
}

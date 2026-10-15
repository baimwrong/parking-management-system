<?php

namespace Tests\Feature;

use App\Models\Gate;
use App\Models\MemberPass;
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
}

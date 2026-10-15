<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Gate;
use App\Models\GateLog;
use App\Models\MemberPass;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Models\ParkingViolation;
use App\Models\ParkingZone;
use App\Models\Payment;
use App\Models\Tariff;
use App\Models\User;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Core Users (Admin, Manager, Cashier, Patrol)
        $admin = User::firstOrCreate(
            ['email' => 'admin@parking.local'],
            [
                'name' => 'Alexander Vance (Super Admin)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+1-555-0199',
                'badge_id' => 'BADGE-ADM-001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@parking.local'],
            [
                'name' => 'Sarah Jenkins (Site Operations Manager)',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '+1-555-0188',
                'badge_id' => 'BADGE-MGR-002',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $cashier = User::firstOrCreate(
            ['email' => 'cashier@parking.local'],
            [
                'name' => 'David Miller (Gate Cashier Lead)',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'phone' => '+1-555-0177',
                'badge_id' => 'BADGE-CSH-003',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $patrol = User::firstOrCreate(
            ['email' => 'patrol@parking.local'],
            [
                'name' => 'Marcus Thorne (Security & Enforcement Marshall)',
                'password' => Hash::make('password'),
                'role' => 'patrol',
                'phone' => '+1-555-0166',
                'badge_id' => 'BADGE-PTR-004',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Parking Locations
        $location = ParkingLocation::create([
            'name' => 'Metropolis Grand Central Complex',
            'code' => 'MGC-01',
            'address' => '742 Evergreen Avenue, Financial District',
            'city' => 'Metropolis',
            'total_capacity' => 450,
            'operating_hours' => '24 Hours / 7 Days',
            'contact_phone' => '+1-800-PARKING',
            'settings' => [
                'vat_percentage' => 10,
                'currency_symbol' => '$',
                'currency_code' => 'USD',
                'lost_ticket_flat_fee' => 50.00,
                'anpr_auto_open' => true,
                'grace_period_default_min' => 15,
            ],
            'is_active' => true,
        ]);

        $location2 = ParkingLocation::create([
            'name' => 'Apex Financial Galleria & Office Tower',
            'code' => 'AFG-02',
            'address' => '100 Wall Street Blvd, Commercial Zone',
            'city' => 'Metropolis',
            'total_capacity' => 300,
            'operating_hours' => '06:00 AM - 01:00 AM',
            'contact_phone' => '+1-800-APEXPARK',
            'settings' => [
                'vat_percentage' => 10,
                'currency_symbol' => '$',
                'currency_code' => 'USD',
                'lost_ticket_flat_fee' => 50.00,
            ],
            'is_active' => true,
        ]);

        // 3. Vehicle Types
        $vMotor = VehicleType::create([
            'code' => 'motorcycle',
            'name' => 'Motorcycle / Scooter',
            'icon' => 'bi-bicycle',
            'base_rate' => 2.00,
            'hourly_rate' => 1.00,
            'max_daily_rate' => 15.00,
            'grace_period_minutes' => 15,
            'lost_ticket_penalty' => 25.00,
            'is_active' => true,
        ]);

        $vCar = VehicleType::create([
            'code' => 'car',
            'name' => 'Sedan / Hatchback',
            'icon' => 'bi-car-front',
            'base_rate' => 5.00,
            'hourly_rate' => 3.00,
            'max_daily_rate' => 35.00,
            'grace_period_minutes' => 15,
            'lost_ticket_penalty' => 50.00,
            'is_active' => true,
        ]);

        $vSuv = VehicleType::create([
            'code' => 'suv',
            'name' => 'SUV / MPV / Van',
            'icon' => 'bi-truck-flatbed',
            'base_rate' => 6.00,
            'hourly_rate' => 4.00,
            'max_daily_rate' => 45.00,
            'grace_period_minutes' => 15,
            'lost_ticket_penalty' => 50.00,
            'is_active' => true,
        ]);

        $vEv = VehicleType::create([
            'code' => 'ev',
            'name' => 'Electric Vehicle (EV)',
            'icon' => 'bi-lightning-charge',
            'base_rate' => 4.00,
            'hourly_rate' => 2.50,
            'max_daily_rate' => 30.00,
            'grace_period_minutes' => 20,
            'lost_ticket_penalty' => 50.00,
            'is_active' => true,
        ]);

        $vTruck = VehicleType::create([
            'code' => 'truck',
            'name' => 'Commercial Delivery Truck',
            'icon' => 'bi-truck',
            'base_rate' => 10.00,
            'hourly_rate' => 6.00,
            'max_daily_rate' => 70.00,
            'grace_period_minutes' => 15,
            'lost_ticket_penalty' => 80.00,
            'is_active' => true,
        ]);

        $vBus = VehicleType::create([
            'code' => 'bus',
            'name' => 'Coach & Shuttle Bus',
            'icon' => 'bi-bus-front',
            'base_rate' => 15.00,
            'hourly_rate' => 10.00,
            'max_daily_rate' => 100.00,
            'grace_period_minutes' => 15,
            'lost_ticket_penalty' => 120.00,
            'is_active' => true,
        ]);

        // 4. Tariffs
        $vehicleTypes = [$vMotor, $vCar, $vSuv, $vEv, $vTruck, $vBus];
        foreach ([$location, $location2] as $loc) {
            foreach ($vehicleTypes as $vt) {
                Tariff::create([
                    'parking_location_id' => $loc->id,
                    'vehicle_type_id' => $vt->id,
                    'name' => "Standard {$vt->name} Tariff",
                    'tariff_type' => 'progressive',
                    'initial_duration_minutes' => 60,
                    'initial_rate' => $vt->base_rate,
                    'subsequent_rate_per_hour' => $vt->hourly_rate,
                    'max_daily_cap' => $vt->max_daily_rate,
                    'overnight_surcharge' => 15.00,
                    'lost_ticket_fee' => $vt->lost_ticket_penalty,
                    'grace_period_minutes' => $vt->grace_period_minutes,
                    'is_active' => true,
                ]);
            }
        }

        // 5. Parking Zones
        $zoneB1A = ParkingZone::create([
            'parking_location_id' => $location->id,
            'name' => 'Basement 1 - Sector Alpha (Cars & Sedans)',
            'code' => 'B1-A',
            'floor_level' => 'B1',
            'zone_type' => 'standard',
            'total_slots' => 20,
            'available_slots' => 14,
            'hourly_surcharge' => 0.00,
            'is_active' => true,
        ]);

        $zoneB1EV = ParkingZone::create([
            'parking_location_id' => $location->id,
            'name' => 'Basement 1 - EV Supercharge Bay',
            'code' => 'B1-EV',
            'floor_level' => 'B1',
            'zone_type' => 'ev_charging',
            'total_slots' => 10,
            'available_slots' => 8,
            'hourly_surcharge' => 1.50,
            'is_active' => true,
        ]);

        $zoneGFM = ParkingZone::create([
            'parking_location_id' => $location->id,
            'name' => 'Ground Floor - Two-Wheeler Plaza',
            'code' => 'GF-MOTOR',
            'floor_level' => 'GF',
            'zone_type' => 'motorcycle',
            'total_slots' => 30,
            'available_slots' => 26,
            'hourly_surcharge' => 0.00,
            'is_active' => true,
        ]);

        $zoneVIP = ParkingZone::create([
            'parking_location_id' => $location->id,
            'name' => 'Level 1 - Executive & VIP Valet Lounge',
            'code' => 'L1-VIP',
            'floor_level' => 'L1',
            'zone_type' => 'vip',
            'total_slots' => 10,
            'available_slots' => 7,
            'hourly_surcharge' => 5.00,
            'is_active' => true,
        ]);

        // 6. Slots Generation
        // Create B1-A Slots
        $slotsB1A = [];
        for ($i = 1; $i <= 20; $i++) {
            $num = sprintf('B1-A%02d', $i);
            $status = ($i <= 6) ? 'occupied' : (($i == 7) ? 'reserved' : (($i == 8) ? 'maintenance' : 'available'));
            $slot = ParkingSlot::create([
                'parking_zone_id' => $zoneB1A->id,
                'slot_number' => $num,
                'vehicle_type' => 'car',
                'status' => $status,
                'is_active' => true,
            ]);
            $slotsB1A[] = $slot;
        }

        // Create B1-EV Slots
        $slotsEV = [];
        for ($i = 1; $i <= 10; $i++) {
            $num = sprintf('EV-10%02d', $i);
            $status = ($i <= 2) ? 'occupied' : 'available';
            $slot = ParkingSlot::create([
                'parking_zone_id' => $zoneB1EV->id,
                'slot_number' => $num,
                'vehicle_type' => 'ev',
                'status' => $status,
                'notes' => '150kW CCS2 DC Fast Charger',
                'is_active' => true,
            ]);
            $slotsEV[] = $slot;
        }

        // Create GF-MOTOR Slots
        $slotsMotor = [];
        for ($i = 1; $i <= 30; $i++) {
            $num = sprintf('M-%02d', $i);
            $status = ($i <= 4) ? 'occupied' : 'available';
            $slot = ParkingSlot::create([
                'parking_zone_id' => $zoneGFM->id,
                'slot_number' => $num,
                'vehicle_type' => 'motorcycle',
                'status' => $status,
                'is_active' => true,
            ]);
            $slotsMotor[] = $slot;
        }

        // Create VIP Slots
        $slotsVIP = [];
        for ($i = 1; $i <= 10; $i++) {
            $num = sprintf('VIP-%02d', $i);
            $status = ($i <= 3) ? 'occupied' : 'available';
            $slot = ParkingSlot::create([
                'parking_zone_id' => $zoneVIP->id,
                'slot_number' => $num,
                'vehicle_type' => 'car',
                'status' => $status,
                'notes' => 'Reserved Valet Attendant Bay',
                'is_active' => true,
            ]);
            $slotsVIP[] = $slot;
        }

        // 7. Gates
        $gateIn1 = Gate::create([
            'parking_location_id' => $location->id,
            'name' => 'North Main Entrance Gate 01',
            'code' => 'GATE-IN-01',
            'gate_type' => 'entry',
            'ip_address' => '192.168.10.101',
            'barrier_status' => 'closed',
            'camera_anpr_enabled' => true,
            'is_active' => true,
        ]);

        $gateIn2 = Gate::create([
            'parking_location_id' => $location->id,
            'name' => 'South Express Entrance Gate 02',
            'code' => 'GATE-IN-02',
            'gate_type' => 'entry',
            'ip_address' => '192.168.10.102',
            'barrier_status' => 'closed',
            'camera_anpr_enabled' => true,
            'is_active' => true,
        ]);

        $gateOut1 = Gate::create([
            'parking_location_id' => $location->id,
            'name' => 'North Cashier Exit Gate 01',
            'code' => 'GATE-OUT-01',
            'gate_type' => 'exit',
            'ip_address' => '192.168.10.201',
            'barrier_status' => 'closed',
            'camera_anpr_enabled' => true,
            'is_active' => true,
        ]);

        $gateOut2 = Gate::create([
            'parking_location_id' => $location->id,
            'name' => 'South Self-Pay Kiosk Exit Gate 02',
            'code' => 'GATE-OUT-02',
            'gate_type' => 'exit',
            'ip_address' => '192.168.10.202',
            'barrier_status' => 'closed',
            'camera_anpr_enabled' => true,
            'is_active' => true,
        ]);

        // 8. Member Passes
        $membersData = [
            [
                'pass_number' => 'MEM-2026-001',
                'rfid_uid' => 'E280116060000204',
                'member_name' => 'Dr. Robert Sterling',
                'phone' => '+1-555-8821',
                'email' => 'robert.sterling@metrohealth.org',
                'plate_number' => 'B 1234 STER',
                'vehicle_type_id' => $vCar->id,
                'plan_type' => 'annual',
                'price_paid' => 1200.00,
                'valid_from' => Carbon::today()->subMonths(2),
                'valid_until' => Carbon::today()->addMonths(10),
                'status' => 'active',
                'notes' => 'Medical Tower Chief of Surgery',
            ],
            [
                'pass_number' => 'MEM-2026-002',
                'rfid_uid' => 'E280116060000205',
                'member_name' => 'Elena Rostova',
                'phone' => '+1-555-9932',
                'email' => 'elena.rostova@apexcap.com',
                'plate_number' => 'B 8888 ELN',
                'vehicle_type_id' => $vEv->id,
                'plan_type' => 'monthly',
                'price_paid' => 150.00,
                'valid_from' => Carbon::today()->subDays(10),
                'valid_until' => Carbon::today()->addDays(20),
                'status' => 'active',
                'notes' => 'Apex Capital VP Suite 400',
            ],
            [
                'pass_number' => 'MEM-2026-003',
                'rfid_uid' => 'E280116060000206',
                'member_name' => 'Michael Chen',
                'phone' => '+1-555-7743',
                'email' => 'mchen@innovatech.io',
                'plate_number' => 'B 5678 CHN',
                'vehicle_type_id' => $vSuv->id,
                'plan_type' => 'quarterly',
                'price_paid' => 400.00,
                'valid_from' => Carbon::today()->subDays(45),
                'valid_until' => Carbon::today()->addDays(45),
                'status' => 'active',
                'notes' => 'InnovaTech Floor 8 Staff',
            ],
            [
                'pass_number' => 'MEM-2026-004',
                'rfid_uid' => 'E280116060000207',
                'member_name' => 'Lucas Bradley',
                'phone' => '+1-555-4421',
                'email' => 'lucas.bradley@deliveryfast.com',
                'plate_number' => 'B 9012 FST',
                'vehicle_type_id' => $vMotor->id,
                'plan_type' => 'monthly',
                'price_paid' => 45.00,
                'valid_from' => Carbon::today()->subMonths(1),
                'valid_until' => Carbon::today()->subDays(2),
                'status' => 'expired',
                'notes' => 'Expired monthly pass',
            ],
        ];

        $createdMembers = [];
        foreach ($membersData as $m) {
            $createdMembers[] = MemberPass::create($m);
        }

        // 9. Active Parked Tickets
        $parkedVehicles = [
            ['plate' => 'B 1024 XYZ', 'vtype' => $vCar, 'slot' => $slotsB1A[0], 'zone' => $zoneB1A, 'mins' => 140, 'type' => 'casual'],
            ['plate' => 'B 3049 ABC', 'vtype' => $vCar, 'slot' => $slotsB1A[1], 'zone' => $zoneB1A, 'mins' => 75, 'type' => 'casual'],
            ['plate' => 'B 9912 KLS', 'vtype' => $vCar, 'slot' => $slotsB1A[2], 'zone' => $zoneB1A, 'mins' => 210, 'type' => 'casual'],
            ['plate' => 'B 7721 MNP', 'vtype' => $vCar, 'slot' => $slotsB1A[3], 'zone' => $zoneB1A, 'mins' => 45, 'type' => 'casual'],
            ['plate' => 'B 1234 STER', 'vtype' => $vCar, 'slot' => $slotsB1A[4], 'zone' => $zoneB1A, 'mins' => 180, 'type' => 'member', 'member' => $createdMembers[0]],
            ['plate' => 'B 6610 VXZ', 'vtype' => $vCar, 'slot' => $slotsB1A[5], 'zone' => $zoneB1A, 'mins' => 320, 'type' => 'casual'],
            ['plate' => 'B 8888 ELN', 'vtype' => $vEv, 'slot' => $slotsEV[0], 'zone' => $zoneB1EV, 'mins' => 95, 'type' => 'member', 'member' => $createdMembers[1]],
            ['plate' => 'B 2210 TES', 'vtype' => $vEv, 'slot' => $slotsEV[1], 'zone' => $zoneB1EV, 'mins' => 160, 'type' => 'casual'],
            ['plate' => 'B 4432 MOT', 'vtype' => $vMotor, 'slot' => $slotsMotor[0], 'zone' => $zoneGFM, 'mins' => 50, 'type' => 'casual'],
            ['plate' => 'B 5519 HON', 'vtype' => $vMotor, 'slot' => $slotsMotor[1], 'zone' => $zoneGFM, 'mins' => 190, 'type' => 'casual'],
            ['plate' => 'B 7001 YMH', 'vtype' => $vMotor, 'slot' => $slotsMotor[2], 'zone' => $zoneGFM, 'mins' => 110, 'type' => 'casual'],
            ['plate' => 'B 9002 VES', 'vtype' => $vMotor, 'slot' => $slotsMotor[3], 'zone' => $zoneGFM, 'mins' => 25, 'type' => 'casual'],
            ['plate' => 'B 5678 CHN', 'vtype' => $vSuv, 'slot' => $slotsVIP[0], 'zone' => $zoneVIP, 'mins' => 80, 'type' => 'member', 'member' => $createdMembers[2]],
            ['plate' => 'B 9999 VIP', 'vtype' => $vCar, 'slot' => $slotsVIP[1], 'zone' => $zoneVIP, 'mins' => 240, 'type' => 'vip'],
            ['plate' => 'B 1111 LUX', 'vtype' => $vCar, 'slot' => $slotsVIP[2], 'zone' => $zoneVIP, 'mins' => 135, 'type' => 'vip'],
        ];

        $ticketCounter = 1;
        foreach ($parkedVehicles as $pv) {
            $tktNum = sprintf('TKT-%s-%04d', date('Ymd'), $ticketCounter++);
            $entryTime = Carbon::now()->subMinutes($pv['mins']);

            $ticket = ParkingTicket::create([
                'ticket_uid' => (string) Str::uuid(),
                'ticket_number' => $tktNum,
                'barcode_hash' => strtoupper(Str::random(10)),
                'parking_location_id' => $location->id,
                'parking_zone_id' => $pv['zone']->id,
                'parking_slot_id' => $pv['slot']->id,
                'entry_gate_id' => $gateIn1->id,
                'vehicle_type_id' => $pv['vtype']->id,
                'plate_number' => $pv['plate'],
                'ticket_type' => $pv['type'],
                'member_pass_id' => isset($pv['member']) ? $pv['member']->id : null,
                'entry_time' => $entryTime,
                'status' => 'parked',
            ]);

            // Calculate current live charges
            $ticket->recalculateCharges();
            $ticket->save();

            // Log gate entry
            GateLog::create([
                'gate_id' => $gateIn1->id,
                'ticket_id' => $ticket->id,
                'action' => 'ticket_dispensed',
                'plate_number_detected' => $pv['plate'],
                'confidence_score' => 98.50,
                'operator_user_id' => $cashier->id,
                'reason' => 'Vehicle checked in successfully',
                'created_at' => $entryTime,
            ]);
        }

        // 10. Completed / Paid Tickets Today
        $completedVehicles = [
            ['plate' => 'B 3311 OKA', 'vtype' => $vCar, 'mins' => 110, 'pay' => 'qris_ewallet', 'paid' => 12.10],
            ['plate' => 'B 4422 BCD', 'vtype' => $vCar, 'mins' => 45, 'pay' => 'cash', 'paid' => 5.50],
            ['plate' => 'B 7733 MOT', 'vtype' => $vMotor, 'mins' => 180, 'pay' => 'cash', 'paid' => 4.40],
            ['plate' => 'B 8822 EVV', 'vtype' => $vEv, 'mins' => 70, 'pay' => 'credit_card', 'paid' => 7.15],
            ['plate' => 'B 1109 TRK', 'vtype' => $vTruck, 'mins' => 150, 'pay' => 'debit_card', 'paid' => 24.20],
        ];

        foreach ($completedVehicles as $cv) {
            $tktNum = sprintf('TKT-%s-%04d', date('Ymd'), $ticketCounter++);
            $entryTime = Carbon::now()->subMinutes($cv['mins'] + 20);
            $exitTime = Carbon::now()->subMinutes(rand(5, 15));

            $ticket = ParkingTicket::create([
                'ticket_uid' => (string) Str::uuid(),
                'ticket_number' => $tktNum,
                'barcode_hash' => strtoupper(Str::random(10)),
                'parking_location_id' => $location->id,
                'entry_gate_id' => $gateIn1->id,
                'exit_gate_id' => $gateOut1->id,
                'vehicle_type_id' => $cv['vtype']->id,
                'plate_number' => $cv['plate'],
                'ticket_type' => 'casual',
                'entry_time' => $entryTime,
                'exit_time' => $exitTime,
                'paid_at' => $exitTime->copy()->subMinutes(3),
                'status' => 'completed',
                'duration_minutes' => $cv['mins'],
                'subtotal' => round($cv['paid'] / 1.10, 2),
                'tax_amount' => round($cv['paid'] - ($cv['paid'] / 1.10), 2),
                'total_amount' => $cv['paid'],
                'payment_method' => $cv['pay'],
                'payment_reference' => 'TXN-' . strtoupper(Str::random(8)),
                'cashier_user_id' => $cashier->id,
            ]);

            Payment::create([
                'parking_ticket_id' => $ticket->id,
                'invoice_number' => 'INV-' . date('Ymd') . '-' . sprintf('%04d', $ticket->id),
                'payment_method' => $cv['pay'],
                'amount_due' => $cv['paid'],
                'amount_paid' => $cv['paid'],
                'change_given' => 0.00,
                'transaction_status' => 'success',
                'reference_no' => $ticket->payment_reference,
                'cashier_user_id' => $cashier->id,
                'paid_at' => $ticket->paid_at,
                'receipt_meta' => [
                    'plate_number' => $ticket->plate_number,
                    'duration' => $ticket->duration_minutes . ' mins',
                    'cashier' => $cashier->name,
                ],
            ]);

            GateLog::create([
                'gate_id' => $gateOut1->id,
                'ticket_id' => $ticket->id,
                'action' => 'exit_cleared',
                'plate_number_detected' => $ticket->plate_number,
                'confidence_score' => 99.20,
                'operator_user_id' => $cashier->id,
                'reason' => 'Payment verified, exit barrier opened',
                'created_at' => $exitTime,
            ]);
        }

        // 11. Parking Violations
        ParkingViolation::create([
            'parking_location_id' => $location->id,
            'parking_slot_id' => $slotsVIP[3]->id,
            'plate_number' => 'B 9911 WRG',
            'violation_type' => 'unauthorized_slot',
            'fine_amount' => 100.00,
            'status' => 'clamped',
            'officer_user_id' => $patrol->id,
            'notes' => 'Parked in marked VIP Executive bay without permit or valet ticket.',
        ]);

        ParkingViolation::create([
            'parking_location_id' => $location->id,
            'parking_slot_id' => null,
            'plate_number' => 'B 4410 BLK',
            'violation_type' => 'blocking_aisle',
            'fine_amount' => 75.00,
            'status' => 'issued',
            'officer_user_id' => $patrol->id,
            'notes' => 'Vehicle double parked obstructing access to Ramp B1.',
        ]);

        // 12. Audit Trail Initial Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'SYSTEM_INIT',
            'module' => 'SYSTEM',
            'description' => 'System seeded with default enterprise configuration, tariffs, zones, and facilities.',
            'created_at' => now(),
        ]);
    }
}

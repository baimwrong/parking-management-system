<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Parking Locations / Facilities
        Schema::create('parking_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g. LOC-01
            $table->string('address');
            $table->string('city')->default('Metropolis');
            $table->integer('total_capacity')->default(500);
            $table->string('operating_hours')->default('24/7');
            $table->string('contact_phone')->nullable();
            $table->json('settings')->nullable(); // fine amounts, tax percentages, currency, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Parking Zones / Floors / Sectors
        Schema::create('parking_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->index(); // e.g. B1-A, L1-VIP, GF-MOTOR
            $table->string('floor_level')->default('GF');
            $table->string('zone_type')->default('standard'); // standard, vip, motorcycle, ev_charging, disabled, loading_bay
            $table->integer('total_slots')->default(50);
            $table->integer('available_slots')->default(50);
            $table->decimal('hourly_surcharge', 8, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Vehicle Types
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // motorcycle, car, suv, truck, bus, ev
            $table->string('name');
            $table->string('icon')->default('bi-car-front');
            $table->decimal('base_rate', 10, 2)->default(5.00);
            $table->decimal('hourly_rate', 10, 2)->default(3.00);
            $table->decimal('max_daily_rate', 10, 2)->default(35.00);
            $table->integer('grace_period_minutes')->default(15);
            $table->decimal('lost_ticket_penalty', 10, 2)->default(50.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Parking Slots
        Schema::create('parking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_zone_id')->constrained('parking_zones')->cascadeOnDelete();
            $table->string('slot_number')->index(); // e.g. B1-01, VIP-01
            $table->string('vehicle_type')->default('car'); // car, motorcycle, truck, ev
            $table->string('status')->default('available')->index(); // available, occupied, reserved, maintenance
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['parking_zone_id', 'slot_number']);
        });

        // 5. Gates / Terminals
        Schema::create('gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique(); // GATE-IN-01, GATE-OUT-01
            $table->string('gate_type')->default('entry'); // entry, exit
            $table->string('ip_address')->nullable();
            $table->string('barrier_status')->default('closed'); // closed, open, error, maintenance
            $table->boolean('camera_anpr_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Tariffs / Pricing Rules
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('tariff_type')->default('progressive'); // progressive, flat, hourly, valet
            $table->integer('initial_duration_minutes')->default(60);
            $table->decimal('initial_rate', 10, 2)->default(5.00);
            $table->decimal('subsequent_rate_per_hour', 10, 2)->default(3.00);
            $table->decimal('max_daily_cap', 10, 2)->default(40.00);
            $table->decimal('overnight_surcharge', 10, 2)->default(20.00);
            $table->decimal('lost_ticket_fee', 10, 2)->default(50.00);
            $table->integer('grace_period_minutes')->default(15);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Member Passes / Subscriptions
        Schema::create('member_passes', function (Blueprint $table) {
            $table->id();
            $table->string('pass_number')->unique(); // MEM-2026-0001
            $table->string('rfid_uid')->nullable()->unique();
            $table->string('member_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('plate_number')->index();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->cascadeOnDelete();
            $table->string('plan_type')->default('monthly'); // monthly, quarterly, annual, vip_complimentary
            $table->decimal('price_paid', 10, 2)->default(0.00);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->string('status')->default('active')->index(); // active, expired, suspended, pending
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Parking Tickets (Session)
        Schema::create('parking_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_uid')->unique();
            $table->string('ticket_number')->unique()->index(); // e.g. TKT-20261015-0001
            $table->string('barcode_hash')->unique()->index();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->cascadeOnDelete();
            $table->foreignId('parking_zone_id')->nullable()->constrained('parking_zones')->nullOnDelete();
            $table->foreignId('parking_slot_id')->nullable()->constrained('parking_slots')->nullOnDelete();
            $table->foreignId('entry_gate_id')->nullable()->constrained('gates')->nullOnDelete();
            $table->foreignId('exit_gate_id')->nullable()->constrained('gates')->nullOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->cascadeOnDelete();
            $table->string('plate_number')->index();
            $table->string('ticket_type')->default('casual'); // casual, member, valet, vip, lost
            $table->foreignId('member_pass_id')->nullable()->constrained('member_passes')->nullOnDelete();
            $table->timestamp('entry_time')->index();
            $table->timestamp('exit_time')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('parked')->index(); // parked, paid, completed, cancelled, lost
            $table->integer('duration_minutes')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('penalty_amount', 10, 2)->default(0.00); // lost ticket or overstay penalty
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('payment_method')->nullable(); // cash, qris_ewallet, credit_card, debit_card, member_card, complimentary
            $table->string('payment_reference')->nullable();
            $table->foreignId('cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Payments / Invoices
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_ticket_id')->constrained('parking_tickets')->cascadeOnDelete();
            $table->string('invoice_number')->unique()->index();
            $table->string('payment_method');
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_given', 10, 2)->default(0.00);
            $table->string('transaction_status')->default('success'); // success, pending, failed, refunded
            $table->string('reference_no')->nullable();
            $table->foreignId('cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at');
            $table->json('receipt_meta')->nullable();
            $table->timestamps();
        });

        // 10. Parking Violations
        Schema::create('parking_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->cascadeOnDelete();
            $table->foreignId('parking_slot_id')->nullable()->constrained('parking_slots')->nullOnDelete();
            $table->string('plate_number')->index();
            $table->string('violation_type')->default('overstay'); // overstay, unauthorized_slot, blocking_aisle, clamped, no_ticket
            $table->decimal('fine_amount', 10, 2)->default(100.00);
            $table->string('status')->default('issued')->index(); // issued, paid, waived, clamped
            $table->foreignId('officer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. Gate Logs & ANPR
        Schema::create('gate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_id')->constrained('gates')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('parking_tickets')->nullOnDelete();
            $table->string('action'); // barrier_opened, barrier_closed, manual_override, ticket_dispensed, exit_cleared, sensor_alarm
            $table->string('plate_number_detected')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 12. Bulk Imports Log
        Schema::create('bulk_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('import_type'); // slots, members, tariffs, vehicles
            $table->string('filename');
            $table->integer('total_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status')->default('completed'); // completed, partial, failed
            $table->json('error_details')->nullable();
            $table->timestamps();
        });

        // 13. Audit Trail
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->string('record_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('bulk_imports');
        Schema::dropIfExists('gate_logs');
        Schema::dropIfExists('parking_violations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('parking_tickets');
        Schema::dropIfExists('member_passes');
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('gates');
        Schema::dropIfExists('parking_slots');
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('parking_zones');
        Schema::dropIfExists('parking_locations');
    }
};

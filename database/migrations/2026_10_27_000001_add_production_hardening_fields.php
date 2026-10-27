<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for production hardening.
     */
    public function up(): void
    {
        // 1. Anti-Passback & tracking fields for member passes
        Schema::table('member_passes', function (Blueprint $table) {
            $table->boolean('is_inside')->default(false)->index()->after('status');
            $table->timestamp('last_entry_at')->nullable()->after('is_inside');
            $table->timestamp('last_exit_at')->nullable()->after('last_entry_at');
        });

        // 2. ANPR Image Evidence URLs for tickets
        Schema::table('parking_tickets', function (Blueprint $table) {
            $table->string('entry_image_url')->nullable()->after('plate_number');
            $table->string('exit_image_url')->nullable()->after('entry_image_url');
        });

        // 3. Cashier Shift Management & Cash Drawer Balancing
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->cascadeOnDelete();
            $table->foreignId('gate_id')->nullable()->constrained('gates')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('opening_float', 10, 2)->default(0.00);
            $table->decimal('closing_cash_counted', 10, 2)->nullable();
            $table->decimal('system_calculated_cash', 10, 2)->default(0.00);
            $table->decimal('cash_variance', 10, 2)->default(0.00);
            $table->string('status')->default('open')->index(); // open, closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');

        Schema::table('parking_tickets', function (Blueprint $table) {
            $table->dropColumn(['entry_image_url', 'exit_image_url']);
        });

        Schema::table('member_passes', function (Blueprint $table) {
            $table->dropColumn(['is_inside', 'last_entry_at', 'last_exit_at']);
        });
    }
};

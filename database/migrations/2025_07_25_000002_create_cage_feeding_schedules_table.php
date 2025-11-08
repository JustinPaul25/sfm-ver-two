<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cage_feeding_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cage_id')->constrained('cages')->onDelete('cascade');
            $table->string('schedule_name')->default('Default Schedule');
            $table->time('feeding_time_1')->nullable(); // First feeding time
            $table->time('feeding_time_2')->nullable(); // Second feeding time
            $table->time('feeding_time_3')->nullable(); // Third feeding time
            $table->time('feeding_time_4')->nullable(); // Fourth feeding time
            $table->decimal('feeding_amount_1', 8, 2)->default(0); // Amount for first feeding (kg)
            $table->decimal('feeding_amount_2', 8, 2)->default(0); // Amount for second feeding (kg)
            $table->decimal('feeding_amount_3', 8, 2)->default(0); // Amount for third feeding (kg)
            $table->decimal('feeding_amount_4', 8, 2)->default(0); // Amount for fourth feeding (kg)
            $table->enum('frequency', ['daily', 'twice_daily', 'thrice_daily', 'four_times_daily'])->default('daily');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Note: Uniqueness for active schedules is handled in application logic
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cage_feeding_schedules');
    }
}; 
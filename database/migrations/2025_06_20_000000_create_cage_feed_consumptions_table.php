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
        Schema::create('cage_feed_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cage_id')->constrained('cages')->onDelete('cascade');
            $table->integer('day_number'); // Day from 1 to harvest
            $table->decimal('feed_amount', 8, 2); // Amount of feed consumed in kg
            $table->date('consumption_date'); // The actual date of consumption
            $table->text('notes')->nullable(); // Optional notes
            $table->timestamps();
            
            // Ensure unique combination of cage and day number
            $table->unique(['cage_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cage_feed_consumptions');
    }
}; 
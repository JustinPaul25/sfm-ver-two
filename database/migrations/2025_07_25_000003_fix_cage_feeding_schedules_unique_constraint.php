<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if the constraint exists before trying to drop it
        $driver = DB::getDriverName();
        
        try {
            if ($driver === 'pgsql') {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = current_schema() 
                    AND TABLE_NAME = 'cage_feeding_schedules' 
                    AND CONSTRAINT_NAME = 'unique_active_schedule_per_cage'
                ");
            } elseif ($driver === 'sqlite') {
                // SQLite doesn't have information_schema, check constraints differently
                $sql = "SELECT name FROM sqlite_master WHERE type='index' AND name='unique_active_schedule_per_cage'";
                $constraints = DB::select($sql);
            } else {
                $constraints = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'cage_feeding_schedules' 
                    AND CONSTRAINT_NAME = 'unique_active_schedule_per_cage'
                ");
            }
            
            if (!empty($constraints)) {
                Schema::table('cage_feeding_schedules', function (Blueprint $table) {
                    // Drop the problematic unique constraint only if it exists
                    $table->dropUnique('unique_active_schedule_per_cage');
                });
            }
        } catch (\Exception $e) {
            // If check fails, try to drop the constraint anyway (might not exist)
            try {
                Schema::table('cage_feeding_schedules', function (Blueprint $table) {
                    $table->dropUnique('unique_active_schedule_per_cage');
                });
            } catch (\Exception $e2) {
                // Constraint doesn't exist, that's fine
            }
        }
    }

    public function down(): void
    {
        Schema::table('cage_feeding_schedules', function (Blueprint $table) {
            // Re-add the constraint if rolling back
            $table->unique(['cage_id', 'is_active'], 'unique_active_schedule_per_cage');
        });
    }
}; 
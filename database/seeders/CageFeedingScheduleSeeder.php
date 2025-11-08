<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cage;
use App\Models\CageFeedingSchedule;

class CageFeedingScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all cages
        $cages = Cage::all();

        if ($cages->isEmpty()) {
            $this->command->warn('No cages found. Please run CageSeeder first.');
            return;
        }

        // Create feeding schedules for each cage
        foreach ($cages as $cage) {
            // Create different types of schedules based on cage characteristics
            $this->createScheduleForCage($cage);
        }

        $this->command->info('Cage feeding schedules seeded successfully!');
    }

    private function createScheduleForCage($cage)
    {
        // Check if cage already has an active schedule
        $existingActiveSchedule = CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', true)
            ->first();
            
        if ($existingActiveSchedule) {
            $this->command->info("Cage {$cage->id} already has an active schedule, skipping...");
            return;
        }

        // Determine schedule type based on number of fingerlings
        $fishCount = $cage->number_of_fingerlings;
        
        if ($fishCount >= 2000) {
            // Large cage - 4 times daily feeding
            CageFeedingSchedule::create([
                'cage_id' => $cage->id,
                'schedule_name' => 'High Frequency Schedule',
                'feeding_time_1' => '06:00',
                'feeding_time_2' => '10:00',
                'feeding_time_3' => '14:00',
                'feeding_time_4' => '18:00',
                'feeding_amount_1' => 2.5,
                'feeding_amount_2' => 2.0,
                'feeding_amount_3' => 2.0,
                'feeding_amount_4' => 1.5,
                'frequency' => 'four_times_daily',
                'is_active' => true,
                'notes' => 'High frequency feeding for large fish population',
            ]);
        } elseif ($fishCount >= 1000) {
            // Medium cage - 3 times daily feeding
            CageFeedingSchedule::create([
                'cage_id' => $cage->id,
                'schedule_name' => 'Standard Schedule',
                'feeding_time_1' => '07:00',
                'feeding_time_2' => '12:00',
                'feeding_time_3' => '17:00',
                'feeding_time_4' => null,
                'feeding_amount_1' => 2.0,
                'feeding_amount_2' => 2.5,
                'feeding_amount_3' => 2.0,
                'feeding_amount_4' => 0,
                'frequency' => 'thrice_daily',
                'is_active' => true,
                'notes' => 'Standard three-time feeding schedule',
            ]);
        } else {
            // Small cage - 2 times daily feeding
            CageFeedingSchedule::create([
                'cage_id' => $cage->id,
                'schedule_name' => 'Basic Schedule',
                'feeding_time_1' => '08:00',
                'feeding_time_2' => '16:00',
                'feeding_time_3' => null,
                'feeding_time_4' => null,
                'feeding_amount_1' => 1.5,
                'feeding_amount_2' => 1.5,
                'feeding_amount_3' => 0,
                'feeding_amount_4' => 0,
                'frequency' => 'twice_daily',
                'is_active' => true,
                'notes' => 'Basic two-time feeding schedule',
            ]);
        }

        // Create some inactive historical schedules
        $this->createHistoricalSchedules($cage);
    }

    private function createHistoricalSchedules($cage)
    {
        // Check if cage already has historical schedules
        $existingHistoricalCount = CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', false)
            ->count();
            
        if ($existingHistoricalCount >= 2) {
            $this->command->info("Cage {$cage->id} already has historical schedules, skipping...");
            return;
        }

        // Create a few inactive schedules to show history
        $historicalSchedules = [
            [
                'schedule_name' => 'Previous Morning Schedule',
                'feeding_time_1' => '06:30',
                'feeding_time_2' => '11:30',
                'feeding_time_3' => '16:30',
                'feeding_time_4' => null,
                'feeding_amount_1' => 1.8,
                'feeding_amount_2' => 2.2,
                'feeding_amount_3' => 1.8,
                'feeding_amount_4' => 0,
                'frequency' => 'thrice_daily',
                'is_active' => false,
                'notes' => 'Previous schedule with earlier feeding times',
            ],
            [
                'schedule_name' => 'Conservative Schedule',
                'feeding_time_1' => '09:00',
                'feeding_time_2' => '15:00',
                'feeding_time_3' => null,
                'feeding_time_4' => null,
                'feeding_amount_1' => 1.2,
                'feeding_amount_2' => 1.2,
                'feeding_amount_3' => 0,
                'feeding_amount_4' => 0,
                'frequency' => 'twice_daily',
                'is_active' => false,
                'notes' => 'Conservative feeding approach',
            ],
        ];

        foreach ($historicalSchedules as $scheduleData) {
            CageFeedingSchedule::create(array_merge($scheduleData, [
                'cage_id' => $cage->id,
            ]));
        }
    }
} 
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Cage 16 Feeding Schedule\n\n";

$cage = \App\Models\Cage::find(16);

if ($cage) {
    echo "Cage 16 exists\n";
    echo "Number of fingerlings: {$cage->number_of_fingerlings}\n";
    
    $schedule = \App\Models\CageFeedingSchedule::where('cage_id', 16)->where('is_active', true)->first();
    
    if ($schedule) {
        echo "\nActive Feeding Schedule:\n";
        echo "Schedule ID: {$schedule->id}\n";
        echo "Frequency: {$schedule->frequency}\n";
        echo "feeding_time_1: " . ($schedule->feeding_time_1 ? $schedule->feeding_time_1->format('H:i') : 'NULL') . "\n";
        echo "feeding_time_2: " . ($schedule->feeding_time_2 ? $schedule->feeding_time_2->format('H:i') : 'NULL') . "\n";
        echo "feeding_time_3: " . ($schedule->feeding_time_3 ? $schedule->feeding_time_3->format('H:i') : 'NULL') . "\n";
        echo "feeding_time_4: " . ($schedule->feeding_time_4 ? $schedule->feeding_time_4->format('H:i') : 'NULL') . "\n";
        echo "feeding_times accessor: " . json_encode($schedule->feeding_times) . "\n";
        echo "feeding_amount_1: {$schedule->feeding_amount_1}\n";
        echo "feeding_amount_2: {$schedule->feeding_amount_2}\n";
        echo "feeding_amount_3: {$schedule->feeding_amount_3}\n";
        echo "feeding_amount_4: {$schedule->feeding_amount_4}\n";
        echo "total_daily_amount: {$schedule->total_daily_amount}\n";
    } else {
        echo "\nNo active feeding schedule found for Cage 16\n";
    }
    
    // Check all schedules for cage 16
    echo "\nAll schedules for Cage 16:\n";
    $allSchedules = \App\Models\CageFeedingSchedule::where('cage_id', 16)->get();
    foreach ($allSchedules as $s) {
        echo "Schedule ID: {$s->id}, is_active: " . ($s->is_active ? 'true' : 'false') . ", frequency: {$s->frequency}\n";
    }
} else {
    echo "Cage 16 does not exist\n";
}

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Feeding Schedules in Database\n\n";

$schedules = \App\Models\CageFeedingSchedule::with('cage')->get();

foreach($schedules as $schedule) {
    echo "Cage ID: {$schedule->cage_id}\n";
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
    echo "is_active: " . ($schedule->is_active ? 'true' : 'false') . "\n";
    echo "----------------------------------------\n";
}

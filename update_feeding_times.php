<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Updating all feeding schedules to have 4 feeding times\n\n";

$schedules = \App\Models\CageFeedingSchedule::where('is_active', true)->get();

foreach($schedules as $schedule) {
    echo "Updating Cage {$schedule->cage_id} schedule...\n";
    
    // Update to four_times_daily frequency
    $schedule->frequency = 'four_times_daily';
    $schedule->feeding_time_1 = '06:00';
    $schedule->feeding_time_2 = '10:00';
    $schedule->feeding_time_3 = '14:00';
    $schedule->feeding_time_4 = '18:00';
    
    // Redistribute total daily amount across 4 feedings
    $totalAmount = $schedule->total_daily_amount;
    $amountPerFeeding = $totalAmount / 4;
    
    $schedule->feeding_amount_1 = round($amountPerFeeding, 2);
    $schedule->feeding_amount_2 = round($amountPerFeeding, 2);
    $schedule->feeding_amount_3 = round($amountPerFeeding, 2);
    $schedule->feeding_amount_4 = round($amountPerFeeding, 2);
    
    $schedule->save();
    
    echo "  Updated to 4 feeding times: 06:00, 10:00, 14:00, 18:00\n";
    echo "  Amount per feeding: {$schedule->feeding_amount_1} kg\n";
    echo "  Total daily amount: {$schedule->total_daily_amount} kg\n\n";
}

echo "All feeding schedules updated successfully!\n";

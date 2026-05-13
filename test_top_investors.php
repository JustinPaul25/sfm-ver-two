<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$startDate = now()->subDays(30);
$endDate = now();

echo "Testing Top Investors Query\n";
echo "Date Range: {$startDate} to {$endDate}\n\n";

// Test 1: Current approach (withSum on direct samples relationship)
echo "Test 1: Current approach (withSum on direct samples relationship)\n";
$topInvestors1 = \App\Models\Investor::withCount(['samplings' => function($query) use ($startDate, $endDate) {
    $query->whereBetween('date_sampling', [$startDate, $endDate]);
}])
->withSum(['samples' => function($query) use ($startDate, $endDate) {
    $query->whereHas('sampling', function($q) use ($startDate, $endDate) {
        $q->whereBetween('date_sampling', [$startDate, $endDate]);
    });
}], 'weight')
->whereNull('deleted_at')
->orderByDesc('samplings_count')
->limit(5)
->get();

foreach($topInvestors1 as $inv) {
    echo "  {$inv->name} - Samplings: {$inv->samplings_count} - Weight: " . ($inv->samples_sum_weight ?? 0) . "\n";
}

echo "\n";

// Test 2: Using raw query with joins
echo "Test 2: Using raw query with joins\n";
$topInvestors2 = \App\Models\Investor::select('investors.*')
    ->selectRaw('COUNT(DISTINCT samplings.id) as samplings_count')
    ->selectRaw('COALESCE(SUM(samples.weight), 0) as samples_sum_weight')
    ->leftJoin('samplings', function($join) use ($startDate, $endDate) {
        $join->on('investors.id', '=', 'samplings.investor_id')
             ->whereBetween('samplings.date_sampling', [$startDate, $endDate]);
    })
    ->leftJoin('samples', function($join) use ($startDate, $endDate) {
        $join->on('samplings.id', '=', 'samples.sampling_id');
    })
    ->whereNull('investors.deleted_at')
    ->groupBy('investors.id')
    ->orderByDesc('samplings_count')
    ->limit(5)
    ->get();

foreach($topInvestors2 as $inv) {
    echo "  {$inv->name} - Samplings: {$inv->samplings_count} - Weight: {$inv->samples_sum_weight}\n";
}

echo "\n";

// Test 3: Check if samples have correct sampling dates
echo "Test 3: Sample data with sampling dates\n";
$samples = \App\Models\Sample::with('sampling')->limit(5)->get();
foreach($samples as $s) {
    echo "  Sample ID: {$s->id}, Investor ID: {$s->investor_id}, Weight: {$s->weight}, Sampling Date: {$s->sampling->date_sampling}\n";
}

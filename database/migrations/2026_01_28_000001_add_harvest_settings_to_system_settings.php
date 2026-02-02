<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $rows = [
            [
                'key' => 'harvest_target_weight_grams',
                'value' => '500',
                'type' => 'float',
                'description' => 'Target harvest weight in grams. Fish are considered ready for harvest when average weight reaches this.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'harvest_default_growth_rate_g_per_day',
                'value' => '3',
                'type' => 'float',
                'description' => 'Default daily growth rate (g/day) when only one sampling exists. Used to estimate harvest date.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        foreach ($rows as $row) {
            if (DB::table('system_settings')->where('key', $row['key'])->doesntExist()) {
                DB::table('system_settings')->insert($row);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', ['harvest_target_weight_grams', 'harvest_default_growth_rate_g_per_day'])
            ->delete();
    }
};

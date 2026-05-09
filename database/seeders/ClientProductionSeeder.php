<?php

namespace Database\Seeders;

use App\Models\Cage;
use App\Models\CageFeedConsumption;
use App\Models\FeedType;
use App\Models\Investor;
use App\Models\Sample;
use App\Models\Sampling;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ClientProductionSeeder extends Seeder
{
    private const TZ = 'Asia/Manila';

    private const RANGE_START = '2026-03-13';

    private const RANGE_END = '2026-04-17';

    /**
     * Philippine holidays/non-working days in range (Proclamation 1006 / common listings).
     * Eid’l Fitr may be adjusted when NCMF publishes final dates — update if needed.
     */
    private const PH_HOLIDAYS = [
        '2026-03-20', // Eid’l Fitr (confirm with NCMF)
        '2026-04-02', // Maundy Thursday
        '2026-04-03', // Good Friday
        '2026-04-09', // Araw ng Kagitingan
    ];

    /** Pond index 0 = POND 1; cycles B, F, FB, FI */
    private const SCENARIO_CODES = ['B', 'F', 'FB', 'FI'];

    public function run(): void
    {
        $now = now();

        DB::transaction(function () use ($now) {
            Schema::disableForeignKeyConstraints();

            DB::table('samples')->delete();
            DB::table('samplings')->delete();
            DB::table('cage_feed_consumptions')->delete();
            DB::table('cage_feeding_schedules')->delete();
            DB::table('cages')->delete();

            User::query()->where('role', '!=', 'admin')->delete();

            FeedType::withTrashed()->get()->each->forceDelete();
            Investor::withTrashed()->get()->each->forceDelete();

            $this->seedFeedTypes($now);
            $investors = $this->seedInvestors();
            $cages = $this->seedCages($investors);

            $eligibleDates = $this->eligibleFeedingDates();

            foreach ($cages as $idx => $cageRow) {
                $scenario = self::SCENARIO_CODES[$idx % 4];
                $this->seedConsumptionsForCage($cageRow, $scenario, $eligibleDates);
            }

            foreach ($cages as $idx => $cageRow) {
                $scenario = self::SCENARIO_CODES[$idx % 4];
                $this->seedSamplingsAndSamples($cageRow, $scenario, $eligibleDates);
            }

            // Extra samplings in the rolling calendar window so investor dashboard charts
            // (default ~last 30 days from "today") have points — fixed March/April 2026 dates alone often fall outside that window.
            $this->seedDashboardWindowSamplings($cages);

            $this->seedInvestorUsers($investors);

            Schema::enableForeignKeyConstraints();
        });

        $this->command->info('Client production data seeded (investors, cages, consumptions, samplings, samples, investor users).');
        $this->command->info('Investor login: username pond1–pond11 / password investor123 (email pond{N}@sfm.local).');
    }

    private function seedFeedTypes(\DateTimeInterface $now): void
    {
        unset($now);

        $anchor = Carbon::parse('2026-01-06 08:15:00', self::TZ);

        $rows = [
            [
                'id' => 1,
                'feed_type' => 'Grower',
                'brand' => 'Aqua Best Grower Floater Pellet',
                'deleted_at' => '2026-03-30 04:34:24',
            ],
            [
                'id' => 2,
                'feed_type' => 'STARTER FEED',
                'brand' => 'Aqua Best Starter Floater Pellet',
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'feed_type' => 'GROWER',
                'brand' => 'Aqua best Starter Floater Pellet',
                'deleted_at' => null,
            ],
            [
                'id' => 4,
                'feed_type' => 'PRE STARTER',
                'brand' => 'Tateh Aquafeeds',
                'deleted_at' => null,
            ],
            [
                'id' => 5,
                'feed_type' => 'FINISHER',
                'brand' => 'Tateh aquafeeds',
                'deleted_at' => null,
            ],
            [
                'id' => 6,
                'feed_type' => 'FRY MASH',
                'brand' => 'Tateh Aquafeeds',
                'deleted_at' => null,
            ],
        ];

        foreach ($rows as $i => $row) {
            $dt = $this->clampToFarmDayHours(
                $anchor->copy()
                    ->addDays((int) floor($i * 1.7))
                    ->addSeconds(random_int(180, 14_400))
            )->format('Y-m-d H:i:s');
            $rows[$i]['created_at'] = $dt;
            $rows[$i]['updated_at'] = $dt;
        }

        DB::table('feed_types')->insert($rows);
    }

    /**
     * @return list<Investor>
     */
    private function seedInvestors(): array
    {
        $created = [];
        for ($i = 1; $i <= 11; $i++) {
            $investor = Investor::create([
                'name' => 'POND '.$i,
                'address' => 'NANYO BFAR',
                'phone' => '09514833263',
            ]);

            $setupDay = Carbon::parse(self::RANGE_START, self::TZ)->subDays(38)->addDays($i);
            $entered = $this->randomManilaTimeOnDate($setupDay, 8, 17);
            $investor->forceFill([
                'created_at' => $entered,
                'updated_at' => $entered,
            ])->saveQuietly();

            $created[] = $investor->fresh();
        }

        return $created;
    }

    /**
     * @param  list<Investor>  $investors
     * @return list<Cage>
     */
    private function seedCages(array $investors): array
    {
        $scenarios = self::SCENARIO_CODES;
        $stocks = ['B' => 12000, 'F' => 56300, 'FB' => 6500, 'FI' => 37500];
        $feedTypeIds = ['B' => 3, 'F' => 6, 'FB' => 4, 'FI' => 4];

        $cages = [];

        foreach ($investors as $idx => $investor) {
            $code = $scenarios[$idx % 4];
            $cage = Cage::create([
                'number_of_fingerlings' => $stocks[$code],
                'type' => null,
                'feed_types_id' => $feedTypeIds[$code],
                'investor_id' => $investor->id,
                'farmer_id' => null,
            ]);

            $invTs = $investor->created_at ?? Carbon::parse(self::RANGE_START, self::TZ);
            $cageTs = $this->clampToFarmDayHours(
                $invTs->copy()
                    ->timezone(self::TZ)
                    ->addMinutes(random_int(25, 210))
                    ->addSeconds(random_int(0, 59))
            );
            $cage->forceFill([
                'created_at' => $cageTs,
                'updated_at' => $cageTs,
            ])->saveQuietly();

            $cages[] = $cage->fresh();
        }

        return $cages;
    }

    /**
     * @return list<Carbon>
     */
    private function eligibleFeedingDates(): array
    {
        $tz = self::TZ;
        $start = Carbon::parse(self::RANGE_START, $tz)->startOfDay();
        $end = Carbon::parse(self::RANGE_END, $tz)->startOfDay();

        $eligible = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isWeekend()) {
                continue;
            }
            if (in_array($d->toDateString(), self::PH_HOLIDAYS, true)) {
                continue;
            }
            $eligible[] = $d->copy();
        }

        return $eligible;
    }

    /**
     * @param  list<Carbon>  $eligibleDates
     */
    private function seedConsumptionsForCage(Cage $cage, string $scenario, array $eligibleDates): void
    {
        $dayNum = 1;
        foreach ($eligibleDates as $date) {
            $month = (int) $date->month;
            $kg = $this->dailyKgForScenario($scenario, $month);

            $manilaMoment = $this->randomManilaFeedingTimestamp($date);

            $consumption = new CageFeedConsumption([
                'cage_id' => $cage->id,
                'day_number' => $dayNum,
                'feed_amount' => $kg,
                'consumption_date' => $date->toDateString(),
                'notes' => null,
            ]);
            $consumption->created_at = $manilaMoment;
            $consumption->updated_at = $manilaMoment;
            $consumption->save();

            $dayNum++;
        }
    }

    private function dailyKgForScenario(string $scenario, int $month): string
    {
        return match ($scenario) {
            'B' => '60.00',
            'F' => '0.71',
            'FB' => $month === 4 ? '23.40' : '19.50',
            'FI' => $month === 4 ? '3.20' : '15.00',
            default => '0.00',
        };
    }

    /** Farm-office window (Asia/Manila): not before 07:00, not after 18:00. */
    private function clampToFarmDayHours(Carbon $dt): Carbon
    {
        $tz = self::TZ;
        $c = $dt->copy()->timezone($tz);
        $day = $c->toDateString();
        $floor = Carbon::parse($day, $tz)->setTime(7, 0, 0);
        $ceil = Carbon::parse($day, $tz)->setTime(18, 0, 0);

        if ($c->lt($floor)) {
            return $floor->copy();
        }
        if ($c->gt($ceil)) {
            return $ceil->copy();
        }

        return $c;
    }

    /**
     * Random clock time on a calendar day (Asia/Manila).
     * Always within 07:00–18:00 inclusive; $startHour/$endHour are clamped to that window.
     */
    private function randomManilaTimeOnDate(Carbon $date, int $startHour, int $endHour): Carbon
    {
        $tz = self::TZ;
        $base = Carbon::parse($date->toDateString(), $tz)->startOfDay();

        $startHour = max(7, min($startHour, 18));
        $endHour = max(7, min($endHour, 18));
        if ($startHour > $endHour) {
            [$startHour, $endHour] = [$endHour, $startHour];
        }

        $low = max(7 * 3600, $startHour * 3600);
        $high = min(18 * 3600, $endHour * 3600 + 3599);

        return $base->copy()->addSeconds(random_int($low, max($low, $high)));
    }

    private function randomManilaFeedingTimestamp(Carbon $date): Carbon
    {
        return $this->randomManilaTimeOnDate($date, 7, 18);
    }

    /** Session start with headroom same day for staggered sample rows (still 07:00–18:00). */
    private function randomManilaSamplingSessionStart(Carbon $date): Carbon
    {
        return $this->randomManilaTimeOnDate($date, 7, 14);
    }

    /**
     * Weekday-only dates from roughly the last 45 days through today (Manila), for dashboard charts.
     *
     * @return list<Carbon>
     */
    private function dashboardWeekdayDates(): array
    {
        $tz = self::TZ;
        $end = Carbon::now($tz)->startOfDay();
        $start = Carbon::now($tz)->copy()->subDays(45)->startOfDay();

        $rangeStart = Carbon::parse(self::RANGE_START, $tz)->startOfDay();
        $rangeEnd = Carbon::parse(self::RANGE_END, $tz)->startOfDay();

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isWeekend()) {
                continue;
            }
            // Avoid duplicating the client's fixed feeding window on the same calendar dates.
            if ($d->gte($rangeStart) && $d->lte($rangeEnd)) {
                continue;
            }
            $days[] = $d->copy();
        }

        return $days;
    }

    /**
     * Several samplings per cage over the recent window so cage_performance / sampling_trends have rows under default period filters.
     *
     * @param  list<Cage>  $cages
     */
    private function seedDashboardWindowSamplings(array $cages): void
    {
        $weekdays = $this->dashboardWeekdayDates();
        $n = count($weekdays);
        if ($n === 0) {
            return;
        }

        $rawIndices = [
            0,
            (int) floor($n * 0.2),
            (int) floor($n * 0.4),
            (int) floor($n * 0.6),
            (int) floor($n * 0.8),
            $n - 1,
        ];
        $indices = array_values(array_unique($rawIndices));
        sort($indices);

        foreach ($cages as $idx => $cage) {
            $scenario = self::SCENARIO_CODES[$idx % 4];
            $visit = 1;

            foreach ($indices as $idxPick) {
                $idxPick = max(0, min($n - 1, $idxPick));
                $date = $weekdays[$idxPick];

                $doc = sprintf(
                    'DOC-%s-C%d-R%d',
                    $date->copy()->timezone(self::TZ)->format('Ymd'),
                    $cage->id,
                    $visit
                );

                $this->createSamplingWithSamples($cage, $scenario, $date, $doc);

                $visit++;
            }
        }
    }

    /**
     * One login per investor; user name matches investor record (POND N) for automatic linkage in the app.
     *
     * @param  list<Investor>  $investors
     */
    private function seedInvestorUsers(array $investors): void
    {
        foreach ($investors as $i => $investor) {
            $n = $i + 1;
            $user = User::create([
                'name' => $investor->name,
                'username' => 'pond'.$n,
                'email' => 'pond'.$n.'@sfm.local',
                'password' => Hash::make('investor123'),
                'role' => 'investor',
                'is_active' => true,
                'investor_id' => $investor->id,
                'email_verified_at' => now(),
                'phone' => null,
            ]);

            $acctDay = Carbon::now(self::TZ)->subDays(random_int(4, 35))->startOfDay();
            $acctTs = $this->randomManilaTimeOnDate($acctDay, 9, 17);
            $user->forceFill([
                'created_at' => $acctTs,
                'updated_at' => $acctTs,
                'email_verified_at' => $acctTs,
            ])->saveQuietly();
        }
    }

    private function seedSamplingsAndSamples(Cage $cage, string $scenario, array $eligibleDates): void
    {
        $tz = self::TZ;

        $n = count($eligibleDates);
        if ($n === 0) {
            return;
        }

        $rawIndices = [
            0,
            (int) floor($n / 3),
            (int) floor(2 * $n / 3),
            $n - 1,
        ];
        $indices = array_values(array_unique($rawIndices));
        sort($indices);

        $visit = 1;
        foreach ($indices as $idx) {
            $idx = max(0, min($n - 1, $idx));
            $date = $eligibleDates[$idx];

            $doc = sprintf(
                'DOC-%s-C%d-V%d',
                $date->copy()->timezone($tz)->format('Ymd'),
                $cage->id,
                $visit
            );

            $this->createSamplingWithSamples($cage, $scenario, $date, $doc);

            $visit++;
        }
    }

    /**
     * Persist sampling + samples with timestamps on date_sampling (Manila), staggered fish measurements.
     */
    private function createSamplingWithSamples(Cage $cage, string $scenario, Carbon $sampleCalendarDate, string $doc): void
    {
        $sessionStart = $this->clampToFarmDayHours(
            $this->randomManilaSamplingSessionStart($sampleCalendarDate)->copy()
        );

        $sampling = new Sampling([
            'investor_id' => $cage->investor_id,
            'date_sampling' => $sampleCalendarDate->toDateString(),
            'doc' => $doc,
            'cage_no' => $cage->id,
            'mortality' => 0,
            'feed_types_id' => $cage->feed_types_id,
        ]);
        $sampling->created_at = $sessionStart;
        $sampling->updated_at = $sessionStart;
        $sampling->save();

        $lastSampleAt = $this->seedSamplesForSampling($sampling, $cage, $scenario, $sampleCalendarDate, $sessionStart);
        $sampling->forceFill(['updated_at' => $this->clampToFarmDayHours($lastSampleAt)])->saveQuietly();
    }

    private function seedSamplesForSampling(Sampling $sampling, Cage $cage, string $scenario, Carbon $sampleDate, Carbon $sessionStart): Carbon
    {
        $count = 8;
        $baseAbw = $this->abwGramsForScenario($scenario, $sampleDate);

        $t = $this->clampToFarmDayHours($sessionStart->copy());
        $lastAt = $t->copy();

        for ($i = 1; $i <= $count; $i++) {
            $weight = $this->jitterAbw($baseAbw, $scenario);
            [$lengthCm, $widthCm] = $this->approximateLengthWidthCm((float) $weight, $scenario);

            $sample = new Sample([
                'investor_id' => $cage->investor_id,
                'sampling_id' => $sampling->id,
                'sample_no' => 'S'.$i,
                'weight' => $weight,
                'length' => $lengthCm,
                'width' => $widthCm,
            ]);
            $sample->created_at = $t;
            $sample->updated_at = $t;
            $sample->save();

            $lastAt = $t->copy();
            if ($i < $count) {
                $t->addSeconds(random_int(55, 420));
                $t = $this->clampToFarmDayHours($t);
            }
        }

        return $lastAt;
    }

    /**
     * Rough length (cm) and body width (cm) from weight (g), consistent with scenario scale (UI expects cm).
     */
    private function approximateLengthWidthCm(float $weightG, string $scenario): array
    {
        $weightG = max($weightG, 0.0001);
        $scale = match ($scenario) {
            'B' => 3.65,
            'FB' => 3.85,
            'FI' => 3.45,
            'F' => 2.95,
            default => 3.5,
        };

        $length = $scale * pow($weightG, 0.34);
        $length *= 1 + random_int(-40, 40) / 1000;

        $ratioPct = match ($scenario) {
            'B', 'FB' => random_int(240, 340),
            'FI' => random_int(220, 300),
            'F' => random_int(260, 380),
            default => random_int(240, 320),
        };
        $width = $length * ($ratioPct / 1000);

        return [
            round(max(0.01, $length), 2),
            round(max(0.01, $width), 2),
        ];
    }

    private function abwGramsForScenario(string $scenario, Carbon $date): float
    {
        $month = (int) $date->month;

        return match ($scenario) {
            'B' => 550.0,
            'F' => 0.04,
            'FB' => $month >= 4 ? 195.0 : 150.0,
            'FI' => $month >= 4 && $date->day >= 10 ? 4.2 : 4.0,
            default => 1.0,
        };
    }

    private function jitterAbw(float $base, string $scenario): float
    {
        if ($scenario === 'F') {
            // 0.03 – 0.05 g
            return round(random_int(30, 50) / 1000, 4);
        }

        if ($base >= 100) {
            $delta = random_int(-50, 50) / 10.0;

            return round(max(1.0, $base + $delta), 2);
        }

        if ($base <= 1) {
            return round(max(0.001, $base + (random_int(-20, 20) / 10000)), 4);
        }

        $delta = random_int(-30, 30) / 100.0;

        return round(max(0.01, $base + $delta), 2);
    }
}

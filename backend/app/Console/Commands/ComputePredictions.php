<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nightly linear regression on the last 30 days of ticket data.
 * Stores one Prediction row per (queue, day_of_week, hour_of_day).
 * peak_hour is marked via is_peak=true on the row with the highest predicted_volume for that day.
 *
 * Schedule: daily at 01:00 via routes/console.php
 */
class ComputePredictions extends Command
{
    protected $signature   = 'sigfa:predict';
    protected $description = 'Nightly linear regression — predict ticket volume by queue/hour/day-of-week';

    public function handle(): int
    {
        $queues = DB::table('queues')->get(['id', 'tenant_id']);

        foreach ($queues as $queue) {
            $this->processQueue($queue->id, $queue->tenant_id);
        }

        $this->info('Predictions computed for ' . $queues->count() . ' queue(s).');
        return self::SUCCESS;
    }

    private function processQueue(int $queueId, int $tenantId): void
    {
        // Aggregate last 30 days: count + avg service time per (day_of_week, hour_of_day, date)
        $rows = DB::table('tickets')
            ->where('queue_id', $queueId)
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(30))
            ->select([
                DB::raw('DAYOFWEEK(created_at) as dow'),   // 1=Sun … 7=Sat (MySQL)
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('AVG(NULLIF(TIMESTAMPDIFF(SECOND, called_at, served_at), 0)) as avg_svc'),
            ])
            ->groupBy('dow', 'hour', 'day')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        // Group by (dow, hour) → list of daily counts
        $grouped = [];
        foreach ($rows as $r) {
            $key = "{$r->dow}_{$r->hour}";
            $grouped[$key][] = ['cnt' => (int) $r->cnt, 'avg_svc' => (float) ($r->avg_svc ?? 0)];
        }

        // Delete old predictions for this queue then re-insert
        DB::table('predictions')->where('queue_id', $queueId)->delete();

        $inserts = [];
        foreach ($grouped as $key => $points) {
            [$dow, $hour] = explode('_', $key);

            $predictedVolume = max(0, (int) round($this->linearRegressionNext(array_column($points, 'cnt'))));
            $avgServiceSec   = max(0, (int) round(collect($points)->avg('avg_svc')));
            $predictedWait   = $predictedVolume > 0 && $avgServiceSec > 0
                ? round($predictedVolume * $avgServiceSec / 60, 2)
                : 0.0;

            $inserts["{$dow}_{$hour}"] = [
                'tenant_id'              => $tenantId,
                'queue_id'               => $queueId,
                'day_of_week'            => (int) $dow,
                'hour_of_day'            => (int) $hour,
                'predicted_volume'       => $predictedVolume,
                'predicted_wait_minutes' => $predictedWait,
                'is_peak'                => false,
                'computed_at'            => now(),
                'created_at'             => now(),
                'updated_at'             => now(),
            ];
        }

        // Mark peak per day-of-week: simple PHP loop — find key with highest volume per dow
        $peakKeys = [];
        foreach ($inserts as $key => $row) {
            $dow = $row['day_of_week'];
            if (!isset($peakKeys[$dow]) || $row['predicted_volume'] > $inserts[$peakKeys[$dow]]['predicted_volume']) {
                $peakKeys[$dow] = $key;
            }
        }
        foreach ($peakKeys as $key) {
            $inserts[$key]['is_peak'] = true;
        }

        if (!empty($inserts)) {
            DB::table('predictions')->insert(array_values($inserts));
        }
    }

    /** OLS linear regression — returns predicted value at position n (next after the series). */
    private function linearRegressionNext(array $y): float
    {
        $n = count($y);
        if ($n === 0) return 0.0;
        if ($n === 1) return (float) $y[0];

        $sumX = 0; $sumY = 0; $sumXY = 0.0; $sumX2 = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sumX  += $i;
            $sumY  += $y[$i];
            $sumXY += $i * $y[$i];
            $sumX2 += $i * $i;
        }

        $denom = $n * $sumX2 - $sumX ** 2;
        if ($denom == 0) return $sumY / $n;

        $slope     = ($n * $sumXY - $sumX * $sumY) / $denom;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return $intercept + $slope * $n;
    }
}

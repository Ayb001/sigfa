<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\Prediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    /** Return tomorrow's predictions, grouped by queue. */
    public function tomorrow(Request $request): JsonResponse
    {
        $tenantId     = auth('staff')->user()->tenant_id;
        $tomorrowDow  = (int) now()->addDay()->format('N') % 7 + 1; // PHP 1=Mon → MySQL 1=Sun

        $predictions = Prediction::where('tenant_id', $tenantId)
            ->where('day_of_week', $tomorrowDow)
            ->with('queue:id,name,prefix')
            ->orderBy('queue_id')
            ->orderBy('hour_of_day')
            ->get();

        $grouped = $predictions->groupBy('queue_id')->map(fn ($rows) => [
            'queue'            => $rows->first()->queue,
            'peak_hour'        => optional($rows->firstWhere('is_peak', true))->hour_of_day,
            'total_predicted'  => $rows->sum('predicted_volume'),
            'hourly'           => $rows->map(fn ($r) => [
                'hour'                   => $r->hour_of_day,
                'predicted_volume'       => $r->predicted_volume,
                'predicted_wait_minutes' => $r->predicted_wait_minutes,
                'is_peak'                => $r->is_peak,
            ])->values(),
        ])->values();

        return response()->json([
            'date'   => now()->addDay()->toDateString(),
            'queues' => $grouped,
        ]);
    }
}

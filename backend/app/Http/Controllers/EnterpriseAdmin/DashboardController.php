<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuichetSession;
use App\Models\Queue;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function tenantId(): int
    {
        return auth('staff')->user()->tenant_id;
    }

    public function kpis(): JsonResponse
    {
        $tenantId = $this->tenantId();
        $today    = today();

        $totalTicketsToday = Ticket::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->count();

        $servedToday = Ticket::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->where('status', 'served')
            ->count();

        $waitingNow = Ticket::where('tenant_id', $tenantId)
            ->where('status', 'waiting')
            ->count();

        $avgWaitMinutes = Ticket::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->where('status', 'served')
            ->whereNotNull('called_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, called_at)) / 60 as avg_wait')
            ->value('avg_wait') ?? 0;

        $activeQueues = Queue::where('tenant_id', $tenantId)->where('status', 'active')->count();

        $activeSessions = GuichetSession::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $totalEmployees = User::where('tenant_id', $tenantId)->where('role', 'employee')->count();

        return response()->json([
            'total_tickets_today'  => $totalTicketsToday,
            'served_today'         => $servedToday,
            'waiting_now'          => $waitingNow,
            'avg_wait_minutes'     => round($avgWaitMinutes, 1),
            'active_queues'        => $activeQueues,
            'active_sessions'      => $activeSessions,
            'total_employees'      => $totalEmployees,
        ]);
    }

    public function ticketsByHour(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();
        $date     = $request->date ?? today()->toDateString();

        $rows = Ticket::where('tenant_id', $tenantId)
            ->whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, status')
            ->groupBy('hour', 'status')
            ->orderBy('hour')
            ->get();

        return response()->json($rows);
    }

    public function employeePerformance(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();
        $from     = $request->from ?? today()->toDateString();
        $to       = $request->to   ?? today()->toDateString();

        $stats = GuichetSession::where('tenant_id', $tenantId)
            ->whereBetween('started_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->with('employee:id,first_name,last_name')
            ->withCount(['tickets as served_count' => fn ($q) => $q->where('status', 'served')])
            ->withAvg(['tickets as avg_idle_seconds' => fn ($q) => $q->whereNotNull('idle_time_seconds')], 'idle_time_seconds')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($sessions) {
                $employee = $sessions->first()->employee;
                return [
                    'employee'            => $employee,
                    'sessions'            => $sessions->count(),
                    'total_served'        => $sessions->sum('served_count'),
                    'avg_idle_seconds'    => round($sessions->avg('avg_idle_seconds') ?? 0),
                    'total_minutes_active' => round($sessions->sum('duration_seconds') / 60),
                ];
            })
            ->values();

        return response()->json($stats);
    }

    public function activeSessions(): JsonResponse
    {
        $sessions = GuichetSession::where('tenant_id', $this->tenantId())
            ->whereIn('status', ['active', 'paused'])
            ->with([
                'employee:id,first_name,last_name',
                'branch:id,name',
                'queue:id,name,prefix',
                'currentTicket.client:id,first_name,last_name',
            ])
            ->withCount(['tickets as served_today' => fn ($q) => $q->where('status', 'served')])
            ->get()
            ->map(function ($session) {
                return [
                    'id'              => $session->id,
                    'employee'        => $session->employee,
                    'branch'          => $session->branch,
                    'queue'           => $session->queue,
                    'status'          => $session->status,
                    'started_at'      => $session->started_at,
                    'duration_seconds'=> $session->duration_seconds,
                    'current_ticket'  => $session->currentTicket,
                    'served_today'    => $session->served_today,
                ];
            });

        return response()->json($sessions);
    }

    public function forceEndSession(GuichetSession $session): JsonResponse
    {
        if ($session->tenant_id !== $this->tenantId()) {
            abort(403);
        }

        $session->update(['status' => 'ended', 'ended_at' => now()]);

        return response()->json(['message' => 'Session terminée.', 'session' => $session]);
    }
}

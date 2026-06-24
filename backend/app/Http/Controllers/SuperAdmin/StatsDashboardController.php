<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Enterprise;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsDashboardController extends Controller
{
    public function overview(): JsonResponse
    {
        $enterprises = Enterprise::selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $totalTickets = Ticket::count();
        $servedToday  = Ticket::where('status', 'served')->whereDate('created_at', today())->count();

        $ticketsByDay = Ticket::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topEnterprises = Enterprise::withCount('tickets')
            ->where('status', 'active')
            ->orderByDesc('tickets_count')
            ->limit(5)
            ->get(['id', 'name', 'sector', 'tickets_count']);

        $sectorBreakdown = Enterprise::selectRaw('sector, COUNT(*) as count')
            ->where('status', 'active')
            ->groupBy('sector')
            ->get();

        return response()->json([
            'enterprises' => [
                'total'     => Enterprise::count(),
                'active'    => $enterprises['active']    ?? 0,
                'pending'   => $enterprises['pending']   ?? 0,
                'suspended' => $enterprises['suspended'] ?? 0,
            ],
            'users'       => [
                'total'               => User::whereIn('role', ['enterprise_admin', 'employee'])->count(),
                'enterprise_admins'   => User::where('role', 'enterprise_admin')->count(),
                'employees'           => User::where('role', 'employee')->count(),
            ],
            'clients'        => Client::count(),
            'total_tickets'  => $totalTickets,
            'served_today'   => $servedToday,
            'tickets_by_day' => $ticketsByDay,
            'top_enterprises'=> $topEnterprises,
            'sector_breakdown' => $sectorBreakdown,
        ]);
    }

    public function recentActivity(): JsonResponse
    {
        $recentEnterprises = Enterprise::latest()->limit(10)->get(['id', 'name', 'sector', 'status', 'created_at']);
        $recentTickets     = Ticket::with(['enterprise:id,name', 'queue:id,name'])
            ->latest()->limit(20)->get(['id', 'tenant_id', 'queue_id', 'ticket_number', 'status', 'created_at']);

        return response()->json([
            'recent_enterprises' => $recentEnterprises,
            'recent_tickets'     => $recentTickets,
        ]);
    }
}

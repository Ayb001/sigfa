<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\StaffAuthController;
use App\Http\Controllers\Agent\GuichetSessionController;
use App\Http\Controllers\Client\EnterpriseDirectoryController;
use App\Http\Controllers\Client\TicketController;
use App\Http\Controllers\EnterpriseAdmin\BranchController;
use App\Http\Controllers\EnterpriseAdmin\ClientListController;
use App\Http\Controllers\EnterpriseAdmin\DashboardController;
use App\Http\Controllers\EnterpriseAdmin\EmployeeController;
use App\Http\Controllers\EnterpriseAdmin\EnterpriseProfileController;
use App\Http\Controllers\EnterpriseAdmin\QueueController;
use App\Http\Controllers\EnterpriseAdmin\ReportController;
use App\Http\Controllers\SuperAdmin\EnterpriseController;
use App\Http\Controllers\SuperAdmin\StatsDashboardController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use App\Http\Controllers\SSE\QueueStreamController;
use Illuminate\Support\Facades\Route;

// ─── Staff Auth ────────────────────────────────────────────────────────────────
Route::prefix('staff/auth')->group(function () {
    Route::post('login', [StaffAuthController::class, 'login']);
    Route::middleware('auth.staff')->group(function () {
        Route::post('logout',    [StaffAuthController::class, 'logout']);
        Route::get('me',         [StaffAuthController::class, 'me']);
        Route::post('refresh',   [StaffAuthController::class, 'refresh']);
        Route::patch('language', [StaffAuthController::class, 'updateLanguage']);
    });
});

// ─── Client Auth ───────────────────────────────────────────────────────────────
Route::prefix('client/auth')->group(function () {
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('login',    [ClientAuthController::class, 'login']);
    Route::middleware('auth.client')->group(function () {
        Route::post('logout',       [ClientAuthController::class, 'logout']);
        Route::get('me',            [ClientAuthController::class, 'me']);
        Route::post('refresh',      [ClientAuthController::class, 'refresh']);
        Route::patch('language',    [ClientAuthController::class, 'updateLanguage']);
        Route::patch('fcm-token',   [ClientAuthController::class, 'updateFcmToken']);
    });
});

// ─── Public directory (no login required) ──────────────────────────────────────
Route::prefix('directory')->group(function () {
    Route::get('enterprises',                                          [EnterpriseDirectoryController::class, 'index']);
    Route::get('enterprises/{enterprise}',                             [EnterpriseDirectoryController::class, 'show']);
    Route::get('enterprises/{enterprise}/branches',                    [EnterpriseDirectoryController::class, 'branches']);
    Route::get('enterprises/{enterprise}/branches/{branch}/queues',    [EnterpriseDirectoryController::class, 'queues']);
});

// ─── Authenticated client ──────────────────────────────────────────────────────
Route::prefix('client')->middleware('auth.client')->group(function () {
    Route::post('tickets',                    [TicketController::class, 'take']);
    Route::get('tickets',                     [TicketController::class, 'myTickets']);
    Route::get('tickets/active',              [TicketController::class, 'activeTickets']);
    Route::get('tickets/{ticket}',            [TicketController::class, 'show']);
    Route::patch('tickets/{ticket}/cancel',   [TicketController::class, 'cancel']);
    Route::get('tickets/{ticketId}/stream',   [QueueStreamController::class, 'clientTicketStream']);
});

// ─── Super Admin ───────────────────────────────────────────────────────────────
Route::prefix('admin')->middleware(['auth.staff', 'role:super_admin'])->group(function () {
    Route::get('dashboard',                            [StatsDashboardController::class, 'overview']);
    Route::get('dashboard/activity',                   [StatsDashboardController::class, 'recentActivity']);
    Route::get('stats',                                [EnterpriseController::class, 'globalStats']);
    Route::apiResource('enterprises',                  EnterpriseController::class);
    Route::patch('enterprises/{enterprise}/approve',   [EnterpriseController::class, 'approve']);
    Route::patch('enterprises/{enterprise}/suspend',   [EnterpriseController::class, 'suspend']);
    Route::post('enterprises/{enterprise}/logo',       [EnterpriseController::class, 'uploadLogo']);
    Route::apiResource('users',                        UserManagementController::class)->except(['show']);
});

// ─── Enterprise Admin ──────────────────────────────────────────────────────────
Route::prefix('enterprise')->middleware(['auth.staff', 'role:enterprise_admin', 'tenant.scope'])->group(function () {
    // Dashboard
    Route::get('dashboard/kpis',                           [DashboardController::class, 'kpis']);
    Route::get('dashboard/tickets-by-hour',                [DashboardController::class, 'ticketsByHour']);
    Route::get('dashboard/employee-performance',           [DashboardController::class, 'employeePerformance']);
    Route::get('dashboard/active-sessions',                [DashboardController::class, 'activeSessions']);
    Route::patch('dashboard/sessions/{session}/force-end', [DashboardController::class, 'forceEndSession']);

    // Enterprise profile
    Route::get('profile',       [EnterpriseProfileController::class, 'show']);
    Route::patch('profile',     [EnterpriseProfileController::class, 'update']);
    Route::post('profile/logo', [EnterpriseProfileController::class, 'uploadLogo']);

    // Resources
    Route::apiResource('branches',  BranchController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('queues',    QueueController::class);

    // Clients (read-only, tenant-scoped)
    Route::get('clients',           [ClientListController::class, 'index']);
    Route::get('clients/{clientId}', [ClientListController::class, 'show']);

    // Reports & exports
    Route::get('reports/preview',              [ReportController::class, 'preview']);
    Route::get('reports/export/pdf',           [ReportController::class, 'exportPdf']);
    Route::get('reports/export/excel',         [ReportController::class, 'exportExcel']);
    Route::get('reports/export/performance-pdf', [ReportController::class, 'exportPerformancePdf']);

    // SSE: live sessions monitor
    Route::get('stream/sessions', [QueueStreamController::class, 'adminStream']);
});

// ─── Agent / Employee ──────────────────────────────────────────────────────────
Route::prefix('agent')->middleware(['auth.staff', 'role:employee', 'tenant.scope'])->group(function () {
    Route::get('session',                                    [GuichetSessionController::class, 'mySession']);
    Route::post('session/open',                              [GuichetSessionController::class, 'open']);
    Route::patch('session/{session}/pause',                  [GuichetSessionController::class, 'pause']);
    Route::patch('session/{session}/resume',                 [GuichetSessionController::class, 'resume']);
    Route::patch('session/{session}/close',                  [GuichetSessionController::class, 'close']);
    Route::post('session/{session}/call-next',               [GuichetSessionController::class, 'callNext']);
    Route::patch('session/{session}/tickets/{ticket}/serve', [GuichetSessionController::class, 'markServed']);
    Route::patch('session/{session}/tickets/{ticket}/skip',  [GuichetSessionController::class, 'skip']);
    Route::get('sessions/history',                           [GuichetSessionController::class, 'sessionHistory']);
    Route::get('stream',                                     [QueueStreamController::class, 'agentStream']);
});

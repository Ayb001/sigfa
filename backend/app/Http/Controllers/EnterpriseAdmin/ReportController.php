<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuichetSession;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportController extends Controller
{
    private function tenantId(): int
    {
        return auth('staff')->user()->tenant_id;
    }

    private function ticketQuery(Request $request)
    {
        $query = Ticket::where('tenant_id', $this->tenantId())
            ->with(['queue.branch', 'client']);

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('queue', fn ($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('queue_id')) {
            $query->where('queue_id', $request->queue_id);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function preview(Request $request)
    {
        $tickets = $this->ticketQuery($request)->paginate(50);

        $summary = Ticket::where('tenant_id', $this->tenantId())
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'),   fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereHas('queue', fn ($q2) => $q2->where('branch_id', $request->branch_id)))
            ->when($request->filled('queue_id'),  fn ($q) => $q->where('queue_id', $request->queue_id))
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = "served") as served,
                SUM(status = "skipped") as skipped,
                SUM(status = "cancelled") as cancelled,
                SUM(status = "waiting") as waiting,
                AVG(CASE WHEN called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, called_at) END) / 60 as avg_wait_minutes,
                AVG(CASE WHEN served_at IS NOT NULL AND called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, called_at, served_at) END) / 60 as avg_service_minutes
            ')
            ->first();

        return response()->json(['summary' => $summary, 'tickets' => $tickets]);
    }

    public function exportPdf(Request $request)
    {
        $tickets = $this->ticketQuery($request)->limit(500)->get();
        $enterprise = auth('staff')->user()->enterprise;
        $from = $request->from ?? 'début';
        $to   = $request->to   ?? 'aujourd\'hui';

        $data = compact('tickets', 'enterprise', 'from', 'to');

        $pdf = Pdf::loadView('reports.tickets', $data)
            ->setPaper('A4', 'landscape');

        return $pdf->download("rapport-tickets-{$from}-{$to}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $tickets    = $this->ticketQuery($request)->limit(2000)->get();
        $enterprise = auth('staff')->user()->enterprise;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets');

        // Header
        $headers = ['N° Ticket', 'File', 'Agence', 'Client', 'Priorité', 'Statut', 'Heure d\'arrivée', 'Heure d\'appel', 'Heure de service', 'Attente (min)', 'Service (min)', 'Temps mort (s)'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D9488');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
        }

        // Rows
        foreach ($tickets as $i => $ticket) {
            $row = $i + 2;
            $waitMin    = $ticket->wait_time_seconds    ? round($ticket->wait_time_seconds / 60, 1)    : '';
            $serviceMin = $ticket->service_time_seconds ? round($ticket->service_time_seconds / 60, 1) : '';

            $sheet->setCellValue("A{$row}", $ticket->ticket_number);
            $sheet->setCellValue("B{$row}", $ticket->queue?->name ?? '');
            $sheet->setCellValue("C{$row}", $ticket->queue?->branch?->name ?? '');
            $sheet->setCellValue("D{$row}", $ticket->client ? "{$ticket->client->first_name} {$ticket->client->last_name}" : '');
            $sheet->setCellValue("E{$row}", $ticket->priority === 'priority' ? 'Prioritaire' : 'Normal');
            $sheet->setCellValue("F{$row}", $ticket->status);
            $sheet->setCellValue("G{$row}", $ticket->created_at?->format('d/m/Y H:i') ?? '');
            $sheet->setCellValue("H{$row}", $ticket->called_at?->format('H:i:s') ?? '');
            $sheet->setCellValue("I{$row}", $ticket->served_at?->format('H:i:s') ?? '');
            $sheet->setCellValue("J{$row}", $waitMin);
            $sheet->setCellValue("K{$row}", $serviceMin);
            $sheet->setCellValue("L{$row}", $ticket->idle_time_seconds ?? '');
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'rapport-tickets-' . now()->format('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function exportPerformancePdf(Request $request)
    {
        $tenantId = $this->tenantId();
        $from = $request->from ?? today()->toDateString();
        $to   = $request->to   ?? today()->toDateString();

        $sessions = GuichetSession::where('tenant_id', $tenantId)
            ->whereBetween('started_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->with(['employee:id,first_name,last_name', 'queue:id,name', 'branch:id,name'])
            ->withCount(['tickets as served_count' => fn ($q) => $q->where('status', 'served')])
            ->withAvg(['tickets as avg_idle' => fn ($q) => $q->whereNotNull('idle_time_seconds')], 'idle_time_seconds')
            ->get();

        $enterprise = auth('staff')->user()->enterprise;
        $pdf = Pdf::loadView('reports.performance', compact('sessions', 'enterprise', 'from', 'to'));
        return $pdf->download("rapport-performance-{$from}-{$to}.pdf");
    }
}

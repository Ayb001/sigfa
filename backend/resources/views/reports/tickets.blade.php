<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; background: #fff; }
  .header { background: #0d9488; color: #fff; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
  .header h1 { font-size: 16px; font-weight: 700; }
  .header .meta { font-size: 9px; opacity: 0.8; }
  .summary { display: flex; gap: 12px; margin-bottom: 16px; padding: 0 4px; }
  .stat-box { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; text-align: center; }
  .stat-box .val { font-size: 18px; font-weight: 700; color: #0d9488; }
  .stat-box .lbl { font-size: 8px; color: #6b7280; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
  thead th { background: #0f766e; color: #fff; padding: 6px 8px; text-align: left; font-weight: 600; }
  tbody tr:nth-child(even) td { background: #f9fafb; }
  tbody td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
  .badge { display: inline-block; padding: 1px 6px; border-radius: 99px; font-size: 8px; font-weight: 600; }
  .badge-green  { background: #dcfce7; color: #15803d; }
  .badge-amber  { background: #fef9c3; color: #b45309; }
  .badge-red    { background: #fee2e2; color: #dc2626; }
  .badge-gray   { background: #f3f4f6; color: #374151; }
  .badge-teal   { background: #ccfbf1; color: #0f766e; }
  .footer { margin-top: 12px; text-align: center; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
  <div>
    <h1>Rapport des Tickets — {{ $enterprise->name }}</h1>
    <div class="meta">Période : {{ $from }} au {{ $to }} · Généré le {{ now()->format('d/m/Y à H:i') }}</div>
  </div>
  <div style="font-size: 20px; font-weight: 800; opacity: 0.3;">SIGFA</div>
</div>

@php
  $served    = $tickets->where('status', 'served')->count();
  $skipped   = $tickets->where('status', 'skipped')->count();
  $cancelled = $tickets->where('status', 'cancelled')->count();
  $waiting   = $tickets->where('status', 'waiting')->count();
  $avgWait   = $tickets->whereNotNull('called_at')->avg(fn($t) => $t->wait_time_seconds);
@endphp

<div class="summary">
  <div class="stat-box"><div class="val">{{ $tickets->count() }}</div><div class="lbl">Total tickets</div></div>
  <div class="stat-box"><div class="val">{{ $served }}</div><div class="lbl">Servis</div></div>
  <div class="stat-box"><div class="val">{{ $skipped }}</div><div class="lbl">Passés</div></div>
  <div class="stat-box"><div class="val">{{ $cancelled }}</div><div class="lbl">Annulés</div></div>
  <div class="stat-box"><div class="val">{{ $waiting }}</div><div class="lbl">En attente</div></div>
  <div class="stat-box"><div class="val">{{ $avgWait ? round($avgWait/60, 1).' min' : '—' }}</div><div class="lbl">Attente moy.</div></div>
</div>

<table>
  <thead>
    <tr>
      <th>N° Ticket</th>
      <th>File</th>
      <th>Agence</th>
      <th>Client</th>
      <th>Priorité</th>
      <th>Statut</th>
      <th>Arrivée</th>
      <th>Appelé</th>
      <th>Servi</th>
      <th>Attente</th>
      <th>Service</th>
    </tr>
  </thead>
  <tbody>
    @foreach($tickets as $ticket)
    @php
      $statusClass = match($ticket->status) { 'served' => 'badge-green', 'called' => 'badge-teal', 'skipped' => 'badge-amber', 'cancelled' => 'badge-red', default => 'badge-gray' };
      $statusLabel = match($ticket->status) { 'served' => 'Servi', 'called' => 'Appelé', 'skipped' => 'Passé', 'cancelled' => 'Annulé', 'waiting' => 'En attente', default => $ticket->status };
    @endphp
    <tr>
      <td><strong>{{ $ticket->ticket_number }}</strong></td>
      <td>{{ $ticket->queue?->name ?? '—' }}</td>
      <td>{{ $ticket->queue?->branch?->name ?? '—' }}</td>
      <td>{{ $ticket->client ? $ticket->client->first_name.' '.$ticket->client->last_name : '—' }}</td>
      <td>{{ $ticket->priority === 'priority' ? '⚑ Prioritaire' : 'Normal' }}</td>
      <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
      <td>{{ $ticket->created_at?->format('d/m H:i') ?? '—' }}</td>
      <td>{{ $ticket->called_at?->format('H:i') ?? '—' }}</td>
      <td>{{ $ticket->served_at?->format('H:i') ?? '—' }}</td>
      <td>{{ $ticket->wait_time_seconds ? round($ticket->wait_time_seconds/60, 1).' min' : '—' }}</td>
      <td>{{ $ticket->service_time_seconds ? round($ticket->service_time_seconds/60, 1).' min' : '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<div class="footer">SIGFA QueueSmart — {{ $enterprise->name }} · {{ $tickets->count() }} enregistrements</div>
</body>
</html>

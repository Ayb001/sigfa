<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1f2937; }
  .header { background: #0d9488; color: #fff; padding: 16px 20px; margin-bottom: 16px; }
  .header h1 { font-size: 16px; font-weight: 700; }
  .header .meta { font-size: 9px; opacity: 0.8; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 9px; }
  thead th { background: #0f766e; color: #fff; padding: 7px 10px; text-align: left; font-weight: 600; }
  tbody tr:nth-child(even) td { background: #f9fafb; }
  tbody td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; }
  .footer { margin-top: 12px; text-align: center; font-size: 8px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
  <h1>Rapport de Performance des Agents — {{ $enterprise->name }}</h1>
  <div class="meta">Période : {{ $from }} au {{ $to }} · Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>

<table>
  <thead>
    <tr>
      <th>Agent</th>
      <th>Agence</th>
      <th>File</th>
      <th>Début session</th>
      <th>Fin session</th>
      <th>Durée (min)</th>
      <th>Tickets servis</th>
      <th>Temps mort moy. (s)</th>
    </tr>
  </thead>
  <tbody>
    @foreach($sessions as $session)
    <tr>
      <td><strong>{{ $session->employee?->first_name }} {{ $session->employee?->last_name }}</strong></td>
      <td>{{ $session->branch?->name ?? '—' }}</td>
      <td>{{ $session->queue?->name ?? '—' }}</td>
      <td>{{ $session->started_at?->format('d/m H:i') ?? '—' }}</td>
      <td>{{ $session->ended_at?->format('H:i') ?? 'En cours' }}</td>
      <td>{{ $session->started_at ? round($session->duration_seconds / 60) : '—' }}</td>
      <td>{{ $session->served_count }}</td>
      <td>{{ $session->avg_idle ? round($session->avg_idle) : '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<div class="footer">SIGFA QueueSmart — {{ $sessions->count() }} sessions</div>
</body>
</html>

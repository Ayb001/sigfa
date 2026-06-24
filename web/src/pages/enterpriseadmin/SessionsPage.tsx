import { useTranslation } from 'react-i18next'
import { useSSE } from '../../hooks/useSSE'
import { Square, Wifi, WifiOff } from 'lucide-react'
import api from '../../services/api'

interface Session {
  id: number
  employee: { id: number; first_name: string; last_name: string }
  branch: { name: string }
  queue: { name: string; prefix: string }
  status: 'active' | 'paused'
  started_at: string
  duration_seconds: number
  current_ticket: { ticket_number: string; client: { first_name: string; last_name: string } } | null
  served_today: number
  avg_idle_time: number
}

function fmt(seconds: number) {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  return h > 0 ? `${h}h ${m}m` : `${m}m`
}

export default function SessionsPage() {
  const { t } = useTranslation()
  const { data, error } = useSSE<{ sessions: Session[]; ts: string }>('/enterprise/stream/sessions')

  const sessions = data?.sessions ?? []

  const forceEnd = async (id: number) => {
    if (!window.confirm('Terminer la session de force ?')) return
    await api.patch(`/enterprise/dashboard/sessions/${id}/force-end`)
  }

  return (
    <div>
      <div className="page-header">
        <h1>Sessions en direct</h1>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          {error
            ? <><WifiOff size={14} color="var(--red-500)" /><span style={{ fontSize: 12, color: 'var(--red-500)' }}>{error}</span></>
            : <><Wifi size={14} color="var(--teal-600)" /><span style={{ fontSize: 12, color: 'var(--teal-600)' }}>En direct</span></>
          }
          {data?.ts && <span className="text-muted" style={{ fontSize: 11 }}>Màj {new Date(data.ts).toLocaleTimeString('fr-FR')}</span>}
        </div>
      </div>

      {/* Summary bar */}
      <div style={{ display: 'flex', gap: 16, marginBottom: 20 }}>
        <div className="kpi-card" style={{ flex: 1 }}>
          <div className="kpi-value">{sessions.length}</div>
          <div className="kpi-label">Sessions ouvertes</div>
        </div>
        <div className="kpi-card" style={{ flex: 1 }}>
          <div className="kpi-value">{sessions.filter((s) => s.status === 'active').length}</div>
          <div className="kpi-label">Actives</div>
        </div>
        <div className="kpi-card" style={{ flex: 1 }}>
          <div className="kpi-value">{sessions.filter((s) => s.status === 'paused').length}</div>
          <div className="kpi-label">En pause</div>
        </div>
        <div className="kpi-card" style={{ flex: 1 }}>
          <div className="kpi-value">{sessions.reduce((acc, s) => acc + s.served_today, 0)}</div>
          <div className="kpi-label">Tickets servis (total)</div>
        </div>
      </div>

      <div className="card">
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>Agent</th>
                <th>Agence</th>
                <th>File</th>
                <th>Ticket en cours</th>
                <th>Servis</th>
                <th>Maquis moy.</th>
                <th>Durée</th>
                <th>Statut</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {sessions.map((s) => (
                <tr key={s.id} style={{ opacity: s.status === 'paused' ? 0.65 : 1 }}>
                  <td style={{ fontWeight: 500 }}>{s.employee.first_name} {s.employee.last_name}</td>
                  <td className="text-muted">{s.branch.name}</td>
                  <td>
                    <span style={{ fontSize: 11, fontWeight: 700, background: 'var(--teal-50)', color: 'var(--teal-700)', borderRadius: 4, padding: '2px 6px' }}>
                      {s.queue.prefix}
                    </span>{' '}
                    {s.queue.name}
                  </td>
                  <td>
                    {s.current_ticket
                      ? <><strong style={{ color: 'var(--teal-700)' }}>{s.current_ticket.ticket_number}</strong>{' — '}{s.current_ticket.client.first_name} {s.current_ticket.client.last_name}</>
                      : <span className="text-muted">Aucun</span>}
                  </td>
                  <td>
                    <span className="badge badge-green">{s.served_today}</span>
                  </td>
                  <td className="text-muted">
                    {s.avg_idle_time > 0 ? `${Math.round(s.avg_idle_time / 60)} min` : '—'}
                  </td>
                  <td className="text-muted">{fmt(s.duration_seconds)}</td>
                  <td>
                    <span className={`badge ${s.status === 'active' ? 'badge-green' : 'badge-amber'}`}>
                      {s.status === 'active' ? '● Actif' : '⏸ Pause'}
                    </span>
                  </td>
                  <td>
                    <button className="btn btn-danger btn-sm btn-icon" onClick={() => forceEnd(s.id)} title="Terminer de force">
                      <Square size={12} />
                    </button>
                  </td>
                </tr>
              ))}
              {!sessions.length && (
                <tr>
                  <td colSpan={9} className="empty-state">
                    Aucune session active en ce moment.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

import { useTranslation } from 'react-i18next'
import { useSSE } from '../../../hooks/useSSE'
import { Square, Pause } from 'lucide-react'
import api from '../../../services/api'

interface Session {
  id: number
  employee: { first_name: string; last_name: string }
  branch: { name: string }
  queue: { name: string; prefix: string }
  status: 'active' | 'paused'
  started_at: string
  duration_seconds: number
  current_ticket: { ticket_number: string; client: { first_name: string; last_name: string } } | null
  served_today: number
}

export default function LiveSessionsTable() {
  const { t } = useTranslation()
  const { data } = useSSE<{ sessions: Session[]; ts: string }>('/enterprise/stream/sessions')

  const sessions = data?.sessions ?? []

  const forceEnd = async (id: number) => {
    await api.patch(`/enterprise/dashboard/sessions/${id}/force-end`)
  }

  const formatDuration = (seconds: number) => {
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    return h > 0 ? `${h}h ${m}m` : `${m}m`
  }

  if (!sessions.length) {
    return <div className="empty-state">Aucune session active pour le moment.</div>
  }

  return (
    <div className="table-wrap">
      <table className="table">
        <thead>
          <tr>
            <th>Agent</th>
            <th>{t('nav.branches')}</th>
            <th>File</th>
            <th>Ticket en cours</th>
            <th>Servis</th>
            <th>Durée</th>
            <th>{t('common.status')}</th>
            <th>{t('common.actions')}</th>
          </tr>
        </thead>
        <tbody>
          {sessions.map((s) => (
            <tr key={s.id}>
              <td style={{ fontWeight: 500 }}>{s.employee.first_name} {s.employee.last_name}</td>
              <td>{s.branch.name}</td>
              <td>{s.queue.name}</td>
              <td>
                {s.current_ticket
                  ? <><strong>{s.current_ticket.ticket_number}</strong> — {s.current_ticket.client.first_name} {s.current_ticket.client.last_name}</>
                  : <span className="text-muted">—</span>}
              </td>
              <td><span className="badge badge-green">{s.served_today}</span></td>
              <td className="text-muted">{formatDuration(s.duration_seconds)}</td>
              <td>
                <span className={`badge ${s.status === 'active' ? 'badge-green' : 'badge-amber'}`}>
                  {s.status === 'active' ? <><span style={{ width: 6, height: 6, borderRadius: '50%', background: 'currentColor', display: 'inline-block' }} /> {t('session.status.active')}</> : t('session.status.paused')}
                </span>
              </td>
              <td>
                <button className="btn btn-danger btn-sm" onClick={() => forceEnd(s.id)} title="Terminer la session">
                  <Square size={12} />
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import api from '../../services/api'

interface Session {
  id: number
  queue: { name: string; prefix: string }
  branch: { name: string }
  status: 'active' | 'paused' | 'ended'
  started_at: string
  ended_at: string | null
  duration_seconds: number
  served_count: number
  avg_idle_time: number
}

function fmt(seconds: number) {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  return h > 0 ? `${h}h ${m}m` : `${m}m`
}

export default function HistoryPage() {
  const { t } = useTranslation()
  const [sessions, setSessions] = useState<Session[]>([])
  const [meta, setMeta] = useState<{ current_page: number; last_page: number } | null>(null)
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(true)

  const load = (p: number) => {
    setLoading(true)
    api.get('/agent/sessions/history', { params: { page: p } })
      .then((r) => {
        setSessions(r.data.data ?? r.data)
        setMeta({ current_page: r.data.current_page, last_page: r.data.last_page })
      })
      .finally(() => setLoading(false))
  }

  useEffect(() => { load(page) }, [page])

  return (
    <div>
      <div className="page-header">
        <h1>Historique de mes sessions</h1>
      </div>

      <div className="card">
        <div className="table-wrap">
          {loading
            ? <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><div className="spinner" /></div>
            : (
              <table className="table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Agence</th>
                    <th>File</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Durée</th>
                    <th>Servis</th>
                    <th>Maquis moy.</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  {sessions.map((s) => (
                    <tr key={s.id}>
                      <td className="text-muted" style={{ fontSize: 12 }}>
                        {new Date(s.started_at).toLocaleDateString('fr-FR')}
                      </td>
                      <td>{s.branch.name}</td>
                      <td>
                        <span style={{ fontSize: 11, fontWeight: 700, background: 'var(--teal-50)', color: 'var(--teal-700)', borderRadius: 4, padding: '2px 6px' }}>
                          {s.queue.prefix}
                        </span>{' '}
                        {s.queue.name}
                      </td>
                      <td className="text-muted" style={{ fontSize: 12 }}>
                        {new Date(s.started_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                      </td>
                      <td className="text-muted" style={{ fontSize: 12 }}>
                        {s.ended_at
                          ? new Date(s.ended_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
                          : '—'}
                      </td>
                      <td>{fmt(s.duration_seconds)}</td>
                      <td>
                        <span className="badge badge-green">{s.served_count}</span>
                      </td>
                      <td className="text-muted">
                        {s.avg_idle_time > 0 ? `${Math.round(s.avg_idle_time / 60)} min` : '—'}
                      </td>
                      <td>
                        <span className={`badge ${s.status === 'ended' ? 'badge-gray' : s.status === 'active' ? 'badge-green' : 'badge-amber'}`}>
                          {{ ended: 'Terminée', active: 'Active', paused: 'En pause' }[s.status]}
                        </span>
                      </td>
                    </tr>
                  ))}
                  {!sessions.length && <tr><td colSpan={9} className="empty-state">{t('common.noData')}</td></tr>}
                </tbody>
              </table>
            )
          }
        </div>

        {meta && meta.last_page > 1 && (
          <div style={{ display: 'flex', gap: 8, justifyContent: 'center', padding: '12px 0' }}>
            <button className="btn btn-secondary btn-sm" disabled={page <= 1} onClick={() => setPage(page - 1)}>‹ Précédent</button>
            <span style={{ padding: '4px 12px', fontSize: 13 }}>{meta.current_page} / {meta.last_page}</span>
            <button className="btn btn-secondary btn-sm" disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Suivant ›</button>
          </div>
        )}
      </div>
    </div>
  )
}

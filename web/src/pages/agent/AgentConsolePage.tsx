import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Play, Pause, Square, ChevronRight, Check, SkipForward, Clock } from 'lucide-react'
import { useSSE } from '../../hooks/useSSE'
import api from '../../services/api'

interface Queue { id: number; name: string; prefix: string }
interface Ticket { id: number; ticket_number: string; priority: string; client: { first_name: string; last_name: string; phone: string } }
interface Session {
  id: number; status: 'active' | 'paused' | 'ended'
  queue: Queue; branch: { name: string }
  current_ticket: Ticket | null; served_today: number; started_at: string
}

export default function AgentConsolePage() {
  const { t } = useTranslation()
  const [session, setSession]   = useState<Session | null>(null)
  const [queues, setQueues]     = useState<Queue[]>([])
  const [queueId, setQueueId]   = useState('')
  const [loading, setLoading]   = useState(true)
  const [actionMsg, setMsg]     = useState('')

  const { data: sseData } = useSSE<{ session: Session; queue_length: number }>(
    session ? '/agent/stream' : null
  )

  const liveSession     = sseData?.session ?? session
  const liveQueueLength = sseData?.queue_length ?? 0

  useEffect(() => {
    Promise.all([
      api.get('/agent/session'),
      api.get('/enterprise/queues'),
    ]).then(([s, q]) => {
      setSession(s.data)
      setQueues(q.data?.data ?? q.data)
    }).finally(() => setLoading(false))
  }, [])

  const action = async (fn: () => Promise<any>, msg?: string) => {
    try {
      const r = await fn()
      setSession(r.data?.session ?? r.data)
      if (msg) { setMsg(msg); setTimeout(() => setMsg(''), 2500) }
    } catch (e: any) {
      setMsg(e?.response?.data?.message ?? t('common.error'))
      setTimeout(() => setMsg(''), 3000)
    }
  }

  const openSession  = () => action(() => api.post('/agent/session/open', { queue_id: Number(queueId) }))
  const pauseSession = () => action(() => api.patch(`/agent/session/${liveSession!.id}/pause`))
  const resumeSession= () => action(() => api.patch(`/agent/session/${liveSession!.id}/resume`))
  const closeSession = () => action(() => api.patch(`/agent/session/${liveSession!.id}/close`))
  const callNext = async () => {
    try {
      const r = await api.post(`/agent/session/${liveSession!.id}/call-next`)
      setSession((s) => s ? { ...s, current_ticket: r.data.ticket } : s)
    } catch (e: any) {
      const msg = e?.response?.data?.message ?? t('common.error')
      setMsg(msg); setTimeout(() => setMsg(''), 3000)
    }
  }
  const markServed   = () => action(() => api.patch(`/agent/session/${liveSession!.id}/tickets/${liveSession!.current_ticket?.id}/serve`), 'Ticket marqué servi')
  const skipTicket   = () => action(() => api.patch(`/agent/session/${liveSession!.id}/tickets/${liveSession!.current_ticket?.id}/skip`), 'Ticket passé')

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><div className="spinner" /></div>

  const elapsed = liveSession?.started_at
    ? Math.floor((Date.now() - new Date(liveSession.started_at).getTime()) / 60000)
    : 0

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.mySession')}</h1>
        {actionMsg && (
          <span className={`badge ${actionMsg.includes('rre') ? 'badge-red' : 'badge-green'}`}>{actionMsg}</span>
        )}
      </div>

      {!liveSession ? (
        /* ── No session: open one ── */
        <div className="card" style={{ maxWidth: 440, padding: 32 }}>
          <h2 style={{ marginBottom: 16 }}>{t('session.selectQueue')}</h2>
          <div className="form-group" style={{ marginBottom: 16 }}>
            <label className="form-label">File d'attente</label>
            <select className="form-select" value={queueId} onChange={(e) => setQueueId(e.target.value)}>
              <option value="">— Sélectionner —</option>
              {queues.map((q) => <option key={q.id} value={q.id}>{q.name} ({q.prefix})</option>)}
            </select>
          </div>
          <button className="btn btn-primary btn-lg" disabled={!queueId} onClick={openSession}>
            <Play size={16} /> {t('session.open')}
          </button>
        </div>
      ) : (
        /* ── Active session ── */
        <div className="agent-console">
          {/* Left: current ticket */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            <div className="ticket-display">
              {liveSession.current_ticket ? (
                <>
                  <div className="ticket-number-big">{liveSession.current_ticket.ticket_number}</div>
                  <div className="ticket-meta">
                    <strong>{liveSession.current_ticket.client.first_name} {liveSession.current_ticket.client.last_name}</strong>
                    <br />{liveSession.current_ticket.client.phone}
                    {liveSession.current_ticket.priority === 'priority' && (
                      <span className="badge badge-amber" style={{ marginTop: 8 }}>Prioritaire</span>
                    )}
                  </div>
                  <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
                    <button className="btn btn-primary btn-lg" onClick={callNext} style={{ background: 'var(--green-500)' }}>
                      <Check size={16} /> {t('session.markServed')}
                    </button>
                    <button className="btn btn-secondary btn-lg" onClick={skipTicket}>
                      <SkipForward size={16} /> {t('session.skip')}
                    </button>
                  </div>
                </>
              ) : (
                <>
                  <div style={{ color: 'var(--gray-300)', marginBottom: 16 }}>
                    <ChevronRight size={48} />
                  </div>
                  <p style={{ color: 'var(--gray-500)', marginBottom: 20 }}>
                    {liveQueueLength > 0
                      ? `${liveQueueLength} ticket${liveQueueLength > 1 ? 's' : ''} en attente`
                      : t('session.noTickets')}
                  </p>
                  <button
                    className="btn btn-primary btn-lg"
                    onClick={callNext}
                    disabled={liveSession.status !== 'active' || liveQueueLength === 0}
                  >
                    <ChevronRight size={16} /> {t('session.callNext')}
                  </button>
                </>
              )}
            </div>

            {/* Session action bar */}
            <div style={{ display: 'flex', gap: 8 }}>
              {liveSession.status === 'active' ? (
                <button className="btn btn-secondary" onClick={pauseSession}><Pause size={14} /> {t('session.pause')}</button>
              ) : (
                <button className="btn btn-primary" onClick={resumeSession}><Play size={14} /> {t('session.resume')}</button>
              )}
              <button className="btn btn-danger" onClick={closeSession}><Square size={14} /> {t('session.close')}</button>
            </div>
          </div>

          {/* Right: stats panel */}
          <div className="session-panel">
            <div className="card card-body" style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
              <div className="text-muted">File d'attente</div>
              <div style={{ fontWeight: 600 }}>{liveSession.queue.name} <span className="badge badge-teal">{liveSession.queue.prefix}</span></div>
              <div className="text-muted" style={{ marginTop: 4 }}>{liveSession.branch.name}</div>
            </div>

            <div className="session-stat">
              <div>
                <div className="value">{liveSession.served_today}</div>
                <div className="label">{t('session.servedToday')}</div>
              </div>
              <Check size={20} color="var(--green-500)" />
            </div>

            <div className="session-stat">
              <div>
                <div className="value">{liveQueueLength}</div>
                <div className="label">{t('queue.waitingCount')}</div>
              </div>
              <ChevronRight size={20} color="var(--teal-500)" />
            </div>

            <div className="session-stat">
              <div>
                <div className="value">{elapsed}m</div>
                <div className="label">{t('session.duration')}</div>
              </div>
              <Clock size={20} color="var(--gray-400)" />
            </div>

            <div className="card card-body">
              <div className="text-muted">Statut session</div>
              <span className={`badge ${liveSession.status === 'active' ? 'badge-green' : 'badge-amber'}`} style={{ marginTop: 6 }}>
                {t(`session.status.${liveSession.status}`)}
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

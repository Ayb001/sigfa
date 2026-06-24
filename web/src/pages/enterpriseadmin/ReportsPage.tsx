import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { FileDown, FileSpreadsheet, FileText, Search } from 'lucide-react'
import api from '../../services/api'

interface Queue    { id: number; name: string }
interface Employee { id: number; first_name: string; last_name: string }

interface Summary {
  total: number; served: number; cancelled: number; waiting: number
  avg_wait_minutes: number; avg_service_minutes: number
}
interface TicketRow {
  id: number; ticket_number: string; status: string; priority: string
  created_at: string; called_at: string | null; served_at: string | null
  queue: { name: string }; employee: { first_name: string; last_name: string } | null
  client: { first_name: string; last_name: string }
}
interface Preview {
  summary: Summary
  tickets: { data: TicketRow[]; current_page: number; last_page: number; total: number }
}

const today = new Date().toISOString().slice(0, 10)
const monthAgo = new Date(Date.now() - 30 * 86400_000).toISOString().slice(0, 10)

const STATUS_BADGE: Record<string, string> = {
  served: 'badge-green', waiting: 'badge-amber', cancelled: 'badge-red', called: 'badge-blue',
}
const STATUS_LABEL: Record<string, string> = {
  served: 'Servi', waiting: 'En attente', cancelled: 'Annulé', called: 'Appelé',
}

export default function ReportsPage() {
  const { t } = useTranslation()

  const [queues, setQueues]       = useState<Queue[]>([])
  const [employees, setEmployees] = useState<Employee[]>([])

  const [from, setFrom]         = useState(monthAgo)
  const [to, setTo]             = useState(today)
  const [queueId, setQueueId]   = useState('')
  const [empId, setEmpId]       = useState('')
  const [status, setStatus]     = useState('')
  const [page, setPage]         = useState(1)

  const [preview, setPreview]   = useState<Preview | null>(null)
  const [loading, setLoading]   = useState(false)

  useEffect(() => {
    Promise.all([
      api.get('/enterprise/queues'),
      api.get('/enterprise/employees'),
    ]).then(([q, e]) => {
      setQueues(q.data?.data ?? q.data)
      setEmployees(e.data?.data ?? e.data)
    })
  }, [])

  const params = () => ({
    from, to,
    queue_id: queueId || undefined,
    employee_id: empId || undefined,
    status: status || undefined,
    page,
  })

  const loadPreview = async (p = page) => {
    setLoading(true)
    try {
      const r = await api.get('/enterprise/reports/preview', { params: { ...params(), page: p } })
      setPreview(r.data)
    } finally { setLoading(false) }
  }

  const download = (url: string, filename: string) => {
    const token = localStorage.getItem('sigfa_token')
    const p = params()
    const qs = new URLSearchParams(
      Object.fromEntries(Object.entries({ ...p, token }).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]))
    ).toString()
    const a = document.createElement('a')
    a.href = `/api${url}?${qs}`
    a.download = filename
    a.click()
  }

  const changePage = (p: number) => { setPage(p); loadPreview(p) }

  return (
    <div>
      <div className="page-header">
        <h1>Rapports & Exports</h1>
        <div style={{ display: 'flex', gap: 8 }}>
          <button className="btn btn-secondary" onClick={() => download('/enterprise/reports/export/excel', `rapport_${from}_${to}.xlsx`)}>
            <FileSpreadsheet size={14} /> Excel
          </button>
          <button className="btn btn-secondary" onClick={() => download('/enterprise/reports/export/pdf', `rapport_${from}_${to}.pdf`)}>
            <FileText size={14} /> PDF tickets
          </button>
          <button className="btn btn-secondary" onClick={() => download('/enterprise/reports/export/performance-pdf', `performance_${from}_${to}.pdf`)}>
            <FileDown size={14} /> PDF performance
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="card mb-4">
        <div className="card-body" style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'flex-end' }}>
          <div className="form-group" style={{ marginBottom: 0, flex: '1 1 140px' }}>
            <label className="form-label">Du</label>
            <input className="form-input" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
          </div>
          <div className="form-group" style={{ marginBottom: 0, flex: '1 1 140px' }}>
            <label className="form-label">Au</label>
            <input className="form-input" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
          </div>
          <div className="form-group" style={{ marginBottom: 0, flex: '1 1 180px' }}>
            <label className="form-label">File d'attente</label>
            <select className="form-select" value={queueId} onChange={(e) => setQueueId(e.target.value)}>
              <option value="">Toutes</option>
              {queues.map((q) => <option key={q.id} value={q.id}>{q.name}</option>)}
            </select>
          </div>
          <div className="form-group" style={{ marginBottom: 0, flex: '1 1 180px' }}>
            <label className="form-label">Agent</label>
            <select className="form-select" value={empId} onChange={(e) => setEmpId(e.target.value)}>
              <option value="">Tous</option>
              {employees.map((e) => <option key={e.id} value={e.id}>{e.first_name} {e.last_name}</option>)}
            </select>
          </div>
          <div className="form-group" style={{ marginBottom: 0, flex: '1 1 140px' }}>
            <label className="form-label">Statut</label>
            <select className="form-select" value={status} onChange={(e) => setStatus(e.target.value)}>
              <option value="">Tous</option>
              <option value="served">Servi</option>
              <option value="waiting">En attente</option>
              <option value="cancelled">Annulé</option>
            </select>
          </div>
          <button className="btn btn-primary" onClick={() => { setPage(1); loadPreview(1) }} disabled={loading}>
            <Search size={14} />{loading ? 'Chargement…' : 'Aperçu'}
          </button>
        </div>
      </div>

      {preview && (
        <>
          {/* Summary */}
          <div style={{ display: 'flex', gap: 12, marginBottom: 20, flexWrap: 'wrap' }}>
            {[
              { label: 'Total', value: preview.summary.total },
              { label: 'Servis', value: preview.summary.served },
              { label: 'Annulés', value: preview.summary.cancelled },
              { label: 'En attente', value: preview.summary.waiting },
              { label: 'Att. moy.', value: `${Math.round(preview.summary.avg_wait_minutes)} min` },
              { label: 'Service moy.', value: `${Math.round(preview.summary.avg_service_minutes)} min` },
            ].map((kpi) => (
              <div key={kpi.label} className="kpi-card" style={{ flex: '1 1 120px' }}>
                <div className="kpi-value" style={{ fontSize: 20 }}>{kpi.value}</div>
                <div className="kpi-label">{kpi.label}</div>
              </div>
            ))}
          </div>

          {/* Table */}
          <div className="card">
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>#Ticket</th><th>Client</th><th>File</th><th>Agent</th>
                    <th>Priorité</th><th>Statut</th><th>Créé</th><th>Att.</th><th>Servi en</th>
                  </tr>
                </thead>
                <tbody>
                  {preview.tickets.data.map((t) => {
                    const wait = t.called_at ? Math.round((new Date(t.called_at).getTime() - new Date(t.created_at).getTime()) / 60000) : null
                    const svc  = t.served_at && t.called_at ? Math.round((new Date(t.served_at).getTime() - new Date(t.called_at).getTime()) / 60000) : null
                    return (
                      <tr key={t.id}>
                        <td style={{ fontWeight: 700, color: 'var(--teal-700)' }}>{t.ticket_number}</td>
                        <td>{t.client.first_name} {t.client.last_name}</td>
                        <td className="text-muted">{t.queue.name}</td>
                        <td>{t.employee ? `${t.employee.first_name} ${t.employee.last_name}` : '—'}</td>
                        <td>{t.priority === 'priority' ? <span className="badge badge-red">Prioritaire</span> : <span className="badge badge-gray">Normal</span>}</td>
                        <td><span className={`badge ${STATUS_BADGE[t.status] ?? 'badge-gray'}`}>{STATUS_LABEL[t.status] ?? t.status}</span></td>
                        <td className="text-muted" style={{ fontSize: 12 }}>{new Date(t.created_at).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })}</td>
                        <td>{wait !== null ? `${wait} min` : '—'}</td>
                        <td>{svc !== null ? `${svc} min` : '—'}</td>
                      </tr>
                    )
                  })}
                  {!preview.tickets.data.length && <tr><td colSpan={9} className="empty-state">{t('common.noData')}</td></tr>}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {preview.tickets.last_page > 1 && (
              <div style={{ display: 'flex', gap: 8, justifyContent: 'center', padding: '12px 0' }}>
                <button className="btn btn-secondary btn-sm" disabled={page <= 1} onClick={() => changePage(page - 1)}>‹</button>
                <span style={{ padding: '4px 12px', fontSize: 13 }}>{page} / {preview.tickets.last_page}</span>
                <button className="btn btn-secondary btn-sm" disabled={page >= preview.tickets.last_page} onClick={() => changePage(page + 1)}>›</button>
              </div>
            )}
          </div>
        </>
      )}

      {!preview && !loading && (
        <div className="empty-state card" style={{ padding: 48 }}>
          Sélectionnez une période et cliquez sur <strong>Aperçu</strong> pour prévisualiser les données ou télécharger un export.
        </div>
      )}
    </div>
  )
}

import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus, Pencil, Trash2, X } from 'lucide-react'
import api from '../../services/api'

interface Branch { id: number; name: string }
interface Queue {
  id: number; name: string; prefix: string
  avg_service_time: number; status: 'active' | 'inactive'
  branch: Branch | null; waiting_count?: number
}

const emptyForm = { name: '', branch_id: '', prefix: 'A', avg_service_time: 5, status: 'active', priority_rules: { enabled: false } }

export default function QueuesPage() {
  const { t } = useTranslation()
  const [queues, setQueues]     = useState<Queue[]>([])
  const [branches, setBranches] = useState<Branch[]>([])
  const [modal, setModal]       = useState<null | 'add' | Queue>(null)
  const [form, setForm]         = useState<typeof emptyForm>(emptyForm)
  const [saving, setSaving]     = useState(false)
  const [error, setError]       = useState('')

  const load = () =>
    Promise.all([api.get('/enterprise/queues'), api.get('/enterprise/branches')])
      .then(([q, b]) => {
        setQueues(q.data?.data ?? q.data)
        setBranches(b.data?.data ?? b.data)
      })

  useEffect(() => { load() }, [])

  const openAdd  = () => { setForm(emptyForm); setError(''); setModal('add') }
  const openEdit = (q: Queue) => {
    setForm({ name: q.name, branch_id: q.branch?.id?.toString() ?? '', prefix: q.prefix, avg_service_time: q.avg_service_time, status: q.status, priority_rules: { enabled: false } })
    setError(''); setModal(q)
  }

  const save = async () => {
    setSaving(true); setError('')
    const payload = { ...form, branch_id: Number(form.branch_id) }
    try {
      if (modal === 'add') await api.post('/enterprise/queues', payload)
      else await api.put(`/enterprise/queues/${(modal as Queue).id}`, payload)
      await load(); setModal(null)
    } catch (e: any) {
      const msgs = e?.response?.data?.errors
      setError(msgs ? Object.values(msgs).flat().join(' ') : (e?.response?.data?.message ?? t('common.error')))
    } finally { setSaving(false) }
  }

  const remove = async (id: number) => {
    if (!window.confirm("Supprimer cette file d'attente ?")) return
    await api.delete(`/enterprise/queues/${id}`)
    await load()
  }

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.queues')}</h1>
        <button className="btn btn-primary" onClick={openAdd}><Plus size={15} /> {t('queue.addQueue')}</button>
      </div>

      <div className="card">
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>{t('queue.name')}</th>
                <th>{t('queue.branch')}</th>
                <th>{t('queue.prefix')}</th>
                <th>{t('queue.avgServiceTime')}</th>
                <th>{t('queue.waitingCount')}</th>
                <th>{t('common.status')}</th>
                <th>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {queues.map((q) => (
                <tr key={q.id}>
                  <td style={{ fontWeight: 500 }}>{q.name}</td>
                  <td>{q.branch?.name || '—'}</td>
                  <td><span className="badge badge-teal">{q.prefix}</span></td>
                  <td>{q.avg_service_time} min</td>
                  <td>
                    <span className={`badge ${(q.waiting_count ?? 0) > 0 ? 'badge-amber' : 'badge-gray'}`}>
                      {q.waiting_count ?? 0}
                    </span>
                  </td>
                  <td><span className={`badge ${q.status === 'active' ? 'badge-green' : 'badge-gray'}`}>{q.status === 'active' ? t('common.active') : t('common.inactive')}</span></td>
                  <td style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm btn-icon" onClick={() => openEdit(q)}><Pencil size={13} /></button>
                    <button className="btn btn-danger btn-sm btn-icon" onClick={() => remove(q.id)}><Trash2 size={13} /></button>
                  </td>
                </tr>
              ))}
              {!queues.length && <tr><td colSpan={7} className="empty-state">{t('common.noData')}</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {modal && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setModal(null)}>
          <div className="modal">
            <div className="modal-header">
              <h2>{modal === 'add' ? t('queue.addQueue') : t('queue.editQueue')}</h2>
              <button className="btn btn-ghost btn-icon" onClick={() => setModal(null)}><X size={16} /></button>
            </div>
            <div className="modal-body">
              <div className="form-group">
                <label className="form-label">{t('queue.name')} *</label>
                <input className="form-input" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('queue.branch')} *</label>
                  <select className="form-select" value={form.branch_id} onChange={(e) => setForm({ ...form, branch_id: e.target.value })}>
                    <option value="">— Sélectionner —</option>
                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                  </select>
                </div>
                <div className="form-group">
                  <label className="form-label">{t('queue.prefix')} *</label>
                  <input className="form-input" value={form.prefix} maxLength={5} onChange={(e) => setForm({ ...form, prefix: e.target.value.toUpperCase() })} />
                </div>
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('queue.avgServiceTime')} *</label>
                  <input className="form-input" type="number" min={1} value={form.avg_service_time} onChange={(e) => setForm({ ...form, avg_service_time: Number(e.target.value) })} />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('common.status')}</label>
                  <select className="form-select" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                    <option value="active">{t('common.active')}</option>
                    <option value="inactive">{t('common.inactive')}</option>
                  </select>
                </div>
              </div>
              {error && <p className="form-error">{error}</p>}
            </div>
            <div className="modal-footer">
              <button className="btn btn-secondary" onClick={() => setModal(null)}>{t('common.cancel')}</button>
              <button className="btn btn-primary" onClick={save} disabled={saving}>
                {saving ? <div className="spinner" style={{ width: 14, height: 14 }} /> : null}
                {t('common.save')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

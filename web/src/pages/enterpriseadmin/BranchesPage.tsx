import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus, Pencil, Trash2, X } from 'lucide-react'
import api from '../../services/api'

interface Branch {
  id: number
  name: string
  address: string | null
  city: string | null
  phone: string | null
  status: 'active' | 'inactive'
  queues_count?: number
  employees_count?: number
}

const empty: Omit<Branch, 'id'> = { name: '', address: '', city: '', phone: '', status: 'active' }

export default function BranchesPage() {
  const { t } = useTranslation()
  const [branches, setBranches] = useState<Branch[]>([])
  const [modal, setModal]       = useState<null | 'add' | Branch>(null)
  const [form, setForm]         = useState<typeof empty>(empty)
  const [saving, setSaving]     = useState(false)
  const [error, setError]       = useState('')

  const load = () => api.get('/enterprise/branches').then((r) => setBranches(r.data))
  useEffect(() => { load() }, [])

  const openAdd  = () => { setForm(empty); setError(''); setModal('add') }
  const openEdit = (b: Branch) => { setForm({ name: b.name, address: b.address ?? '', city: b.city ?? '', phone: b.phone ?? '', status: b.status }); setError(''); setModal(b) }

  const save = async () => {
    setSaving(true); setError('')
    try {
      if (modal === 'add') await api.post('/enterprise/branches', form)
      else await api.put(`/enterprise/branches/${(modal as Branch).id}`, form)
      await load(); setModal(null)
    } catch (e: any) {
      setError(e?.response?.data?.message ?? t('common.error'))
    } finally { setSaving(false) }
  }

  const remove = async (id: number) => {
    if (!window.confirm('Supprimer cette agence ?')) return
    await api.delete(`/enterprise/branches/${id}`)
    await load()
  }

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.branches')}</h1>
        <button className="btn btn-primary" onClick={openAdd}>
          <Plus size={15} /> {t('branch.addBranch')}
        </button>
      </div>

      <div className="card">
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>{t('branch.name')}</th>
                <th>{t('branch.city')}</th>
                <th>{t('branch.phone')}</th>
                <th>Files</th>
                <th>Agents</th>
                <th>{t('common.status')}</th>
                <th>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {branches.map((b) => (
                <tr key={b.id}>
                  <td style={{ fontWeight: 500 }}>{b.name}</td>
                  <td>{b.city || '—'}</td>
                  <td>{b.phone || '—'}</td>
                  <td>{b.queues_count ?? '—'}</td>
                  <td>{b.employees_count ?? '—'}</td>
                  <td>
                    <span className={`badge ${b.status === 'active' ? 'badge-green' : 'badge-gray'}`}>
                      {b.status === 'active' ? t('common.active') : t('common.inactive')}
                    </span>
                  </td>
                  <td style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm btn-icon" onClick={() => openEdit(b)}><Pencil size={13} /></button>
                    <button className="btn btn-danger btn-sm btn-icon" onClick={() => remove(b.id)}><Trash2 size={13} /></button>
                  </td>
                </tr>
              ))}
              {!branches.length && (
                <tr><td colSpan={7} className="empty-state">{t('common.noData')}</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {modal && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setModal(null)}>
          <div className="modal">
            <div className="modal-header">
              <h2>{modal === 'add' ? t('branch.addBranch') : t('branch.editBranch')}</h2>
              <button className="btn btn-ghost btn-icon" onClick={() => setModal(null)}><X size={16} /></button>
            </div>
            <div className="modal-body">
              <div className="form-group">
                <label className="form-label">{t('branch.name')} *</label>
                <input className="form-input" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('branch.address')}</label>
                  <input className="form-input" value={form.address ?? ''} onChange={(e) => setForm({ ...form, address: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('branch.city')}</label>
                  <input className="form-input" value={form.city ?? ''} onChange={(e) => setForm({ ...form, city: e.target.value })} />
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">{t('branch.phone')}</label>
                <input className="form-input" value={form.phone ?? ''} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
              </div>
              <div className="form-group">
                <label className="form-label">{t('common.status')}</label>
                <select className="form-select" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value as any })}>
                  <option value="active">{t('common.active')}</option>
                  <option value="inactive">{t('common.inactive')}</option>
                </select>
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

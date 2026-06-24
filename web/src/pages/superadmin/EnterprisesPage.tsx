import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus, CheckCircle, XCircle, Eye, X } from 'lucide-react'
import api from '../../services/api'

interface Enterprise {
  id: number; name: string; sector: string; city: string | null
  contact_email: string | null; status: 'pending' | 'active' | 'suspended'
  branches_count?: number; tickets_count?: number
}

const empty = { name: '', sector: 'banque', address: '', city: '', contact_email: '', contact_phone: '', default_language: 'fr' }

export default function EnterprisesPage() {
  const { t } = useTranslation()
  const [enterprises, setEnterprises] = useState<Enterprise[]>([])
  const [modal, setModal]             = useState<null | 'add' | Enterprise>(null)
  const [form, setForm]               = useState<typeof empty>(empty)
  const [saving, setSaving]           = useState(false)
  const [filter, setFilter]           = useState('')

  const load = () =>
    api.get('/admin/enterprises', { params: { status: filter || undefined } })
      .then((r) => setEnterprises(r.data.data ?? r.data))

  useEffect(() => { load() }, [filter])

  const openAdd = () => { setForm(empty); setModal('add') }

  const save = async () => {
    setSaving(true)
    try {
      if (modal === 'add') await api.post('/admin/enterprises', form)
      else await api.put(`/admin/enterprises/${(modal as Enterprise).id}`, form)
      await load(); setModal(null)
    } finally { setSaving(false) }
  }

  const changeStatus = async (id: number, action: 'approve' | 'suspend') => {
    await api.patch(`/admin/enterprises/${id}/${action}`)
    await load()
  }

  const sectorLabel: Record<string, string> = { banque: 'Banque', hopital: 'Hôpital', administration: 'Administration', autre: 'Autre' }
  const statusBadge: Record<string, string> = { active: 'badge-green', pending: 'badge-amber', suspended: 'badge-red' }
  const statusLabel: Record<string, string> = { active: t('enterprise.status.active'), pending: t('enterprise.status.pending'), suspended: t('enterprise.status.suspended') }

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.enterprises')}</h1>
        <div style={{ display: 'flex', gap: 10 }}>
          <select className="form-select" style={{ width: 160 }} value={filter} onChange={(e) => setFilter(e.target.value)}>
            <option value="">{t('common.all')}</option>
            <option value="pending">{t('enterprise.status.pending')}</option>
            <option value="active">{t('enterprise.status.active')}</option>
            <option value="suspended">{t('enterprise.status.suspended')}</option>
          </select>
          <button className="btn btn-primary" onClick={openAdd}><Plus size={15} /> {t('enterprise.addEnterprise')}</button>
        </div>
      </div>

      <div className="card">
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>{t('enterprise.name')}</th>
                <th>{t('enterprise.sector')}</th>
                <th>{t('branch.city')}</th>
                <th>Email</th>
                <th>Agences</th>
                <th>{t('common.status')}</th>
                <th>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {enterprises.map((e) => (
                <tr key={e.id}>
                  <td style={{ fontWeight: 500 }}>{e.name}</td>
                  <td>{sectorLabel[e.sector] ?? e.sector}</td>
                  <td>{e.city || '—'}</td>
                  <td className="text-muted">{e.contact_email || '—'}</td>
                  <td>{e.branches_count ?? '—'}</td>
                  <td><span className={`badge ${statusBadge[e.status]}`}>{statusLabel[e.status]}</span></td>
                  <td style={{ display: 'flex', gap: 6 }}>
                    {e.status === 'pending' && (
                      <button className="btn btn-sm" style={{ background: 'var(--green-50)', color: 'var(--green-500)', border: '1px solid #bbf7d0' }} onClick={() => changeStatus(e.id, 'approve')}>
                        <CheckCircle size={12} /> {t('enterprise.approve')}
                      </button>
                    )}
                    {e.status === 'active' && (
                      <button className="btn btn-danger btn-sm" onClick={() => changeStatus(e.id, 'suspend')}>
                        <XCircle size={12} /> {t('enterprise.suspend')}
                      </button>
                    )}
                    {e.status === 'suspended' && (
                      <button className="btn btn-sm" style={{ background: 'var(--blue-50)', color: 'var(--blue-500)', border: '1px solid #bfdbfe' }} onClick={() => changeStatus(e.id, 'approve')}>
                        <CheckCircle size={12} /> Réactiver
                      </button>
                    )}
                  </td>
                </tr>
              ))}
              {!enterprises.length && (
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
              <h2>{modal === 'add' ? t('enterprise.addEnterprise') : t('enterprise.editEnterprise')}</h2>
              <button className="btn btn-ghost btn-icon" onClick={() => setModal(null)}><X size={16} /></button>
            </div>
            <div className="modal-body">
              <div className="form-group">
                <label className="form-label">{t('enterprise.name')} *</label>
                <input className="form-input" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('enterprise.sector')}</label>
                  <select className="form-select" value={form.sector} onChange={(e) => setForm({ ...form, sector: e.target.value })}>
                    {['banque', 'hopital', 'administration', 'autre'].map((s) => (
                      <option key={s} value={s}>{t(`enterprise.sectors.${s}`)}</option>
                    ))}
                  </select>
                </div>
                <div className="form-group">
                  <label className="form-label">{t('enterprise.defaultLanguage')}</label>
                  <select className="form-select" value={form.default_language} onChange={(e) => setForm({ ...form, default_language: e.target.value })}>
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                  </select>
                </div>
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('enterprise.address')}</label>
                  <input className="form-input" value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('branch.city')}</label>
                  <input className="form-input" value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
                </div>
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('enterprise.contactEmail')}</label>
                  <input className="form-input" type="email" value={form.contact_email} onChange={(e) => setForm({ ...form, contact_email: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('enterprise.contactPhone')}</label>
                  <input className="form-input" value={form.contact_phone} onChange={(e) => setForm({ ...form, contact_phone: e.target.value })} />
                </div>
              </div>
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

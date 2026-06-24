import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus, Pencil, Trash2, X } from 'lucide-react'
import api from '../../services/api'

interface Enterprise { id: number; name: string }
interface User {
  id: number; first_name: string; last_name: string; email: string
  phone: string | null; role: string; status: string
  enterprise?: { id: number; name: string } | null
}

const emptyForm = { first_name: '', last_name: '', email: '', phone: '', password: '', role: 'enterprise_admin', tenant_id: '', status: 'active' }

export default function UsersPage() {
  const { t } = useTranslation()
  const [users, setUsers]         = useState<User[]>([])
  const [enterprises, setEnts]    = useState<Enterprise[]>([])
  const [modal, setModal]         = useState<null | 'add' | User>(null)
  const [form, setForm]           = useState(emptyForm)
  const [saving, setSaving]       = useState(false)
  const [error, setError]         = useState('')
  const [filterRole, setRole]     = useState('')

  const load = () =>
    Promise.all([
      api.get('/admin/users', { params: { role: filterRole || undefined } }),
      api.get('/admin/enterprises', { params: { status: 'active' } }),
    ]).then(([u, e]) => {
      setUsers(u.data?.data ?? u.data)
      setEnts((e.data?.data ?? e.data).map((x: any) => ({ id: x.id, name: x.name })))
    })

  useEffect(() => { load() }, [filterRole])

  const openAdd  = () => { setForm(emptyForm); setError(''); setModal('add') }
  const openEdit = (u: User) => {
    setForm({ first_name: u.first_name, last_name: u.last_name, email: u.email, phone: u.phone ?? '', password: '', role: u.role, tenant_id: u.enterprise?.id?.toString() ?? '', status: u.status })
    setError(''); setModal(u)
  }

  const save = async () => {
    setSaving(true); setError('')
    const payload: any = { ...form, tenant_id: Number(form.tenant_id) || undefined }
    if (!payload.password && modal !== 'add') delete payload.password
    try {
      if (modal === 'add') await api.post('/admin/users', payload)
      else await api.put(`/admin/users/${(modal as User).id}`, payload)
      await load(); setModal(null)
    } catch (e: any) {
      const msgs = e?.response?.data?.errors
      setError(msgs ? Object.values(msgs).flat().join(' ') : (e?.response?.data?.message ?? t('common.error')))
    } finally { setSaving(false) }
  }

  const remove = async (id: number) => {
    if (!window.confirm('Supprimer cet utilisateur ?')) return
    await api.delete(`/admin/users/${id}`)
    await load()
  }

  const roleLabel: Record<string, string> = { enterprise_admin: 'Admin Entreprise', employee: 'Employé' }
  const roleBadge: Record<string, string> = { enterprise_admin: 'badge-teal', employee: 'badge-blue' }

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.users')}</h1>
        <div style={{ display: 'flex', gap: 10 }}>
          <select className="form-select" style={{ width: 180 }} value={filterRole} onChange={(e) => setRole(e.target.value)}>
            <option value="">Tous les rôles</option>
            <option value="enterprise_admin">Admins entreprise</option>
            <option value="employee">Employés</option>
          </select>
          <button className="btn btn-primary" onClick={openAdd}><Plus size={15} /> Ajouter un utilisateur</button>
        </div>
      </div>

      <div className="card">
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>Nom</th><th>Email</th><th>Téléphone</th><th>Rôle</th>
                <th>Entreprise</th><th>{t('common.status')}</th><th>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id}>
                  <td style={{ fontWeight: 500 }}>{u.first_name} {u.last_name}</td>
                  <td className="text-muted">{u.email}</td>
                  <td>{u.phone || '—'}</td>
                  <td><span className={`badge ${roleBadge[u.role] ?? 'badge-gray'}`}>{roleLabel[u.role] ?? u.role}</span></td>
                  <td>{u.enterprise?.name || '—'}</td>
                  <td><span className={`badge ${u.status === 'active' ? 'badge-green' : 'badge-gray'}`}>{u.status === 'active' ? 'Actif' : 'Inactif'}</span></td>
                  <td style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm btn-icon" onClick={() => openEdit(u)}><Pencil size={13} /></button>
                    <button className="btn btn-danger btn-sm btn-icon" onClick={() => remove(u.id)}><Trash2 size={13} /></button>
                  </td>
                </tr>
              ))}
              {!users.length && <tr><td colSpan={7} className="empty-state">{t('common.noData')}</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {modal && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setModal(null)}>
          <div className="modal">
            <div className="modal-header">
              <h2>{modal === 'add' ? 'Ajouter un utilisateur' : 'Modifier l\'utilisateur'}</h2>
              <button className="btn btn-ghost btn-icon" onClick={() => setModal(null)}><X size={16} /></button>
            </div>
            <div className="modal-body">
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">Prénom *</label>
                  <input className="form-input" value={form.first_name} onChange={(e) => setForm({ ...form, first_name: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">Nom *</label>
                  <input className="form-input" value={form.last_name} onChange={(e) => setForm({ ...form, last_name: e.target.value })} />
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">E-mail *</label>
                <input className="form-input" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">Téléphone</label>
                  <input className="form-input" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">Mot de passe {modal !== 'add' && '(vide = inchangé)'}</label>
                  <input className="form-input" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
                </div>
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">Rôle *</label>
                  <select className="form-select" value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })}>
                    <option value="enterprise_admin">Admin Entreprise</option>
                    <option value="employee">Employé</option>
                  </select>
                </div>
                <div className="form-group">
                  <label className="form-label">Entreprise *</label>
                  <select className="form-select" value={form.tenant_id} onChange={(e) => setForm({ ...form, tenant_id: e.target.value })}>
                    <option value="">— Sélectionner —</option>
                    {enterprises.map((e) => <option key={e.id} value={e.id}>{e.name}</option>)}
                  </select>
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">Statut</label>
                <select className="form-select" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                  <option value="active">Actif</option>
                  <option value="inactive">Inactif</option>
                </select>
              </div>
              {error && <p className="form-error">{error}</p>}
            </div>
            <div className="modal-footer">
              <button className="btn btn-secondary" onClick={() => setModal(null)}>{t('common.cancel')}</button>
              <button className="btn btn-primary" onClick={save} disabled={saving}>
                {saving && <div className="spinner" style={{ width: 14, height: 14 }} />}
                {t('common.save')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

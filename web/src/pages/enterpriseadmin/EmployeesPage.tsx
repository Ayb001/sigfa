import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Plus, Pencil, Trash2, X } from 'lucide-react'
import api from '../../services/api'

interface Branch { id: number; name: string }
interface Employee {
  id: number; first_name: string; last_name: string
  email: string; phone: string | null; branch: Branch | null; status: 'active' | 'inactive'
}

const emptyForm = { first_name: '', last_name: '', email: '', phone: '', password: '', branch_id: '', status: 'active' }

export default function EmployeesPage() {
  const { t } = useTranslation()
  const [employees, setEmployees] = useState<Employee[]>([])
  const [branches, setBranches]   = useState<Branch[]>([])
  const [modal, setModal]         = useState<null | 'add' | Employee>(null)
  const [form, setForm]           = useState(emptyForm)
  const [saving, setSaving]       = useState(false)
  const [error, setError]         = useState('')

  const load = () =>
    Promise.all([
      api.get('/enterprise/employees'),
      api.get('/enterprise/branches'),
    ]).then(([e, b]) => {
      setEmployees(e.data?.data ?? e.data)
      setBranches(b.data?.data ?? b.data)
    })

  useEffect(() => { load() }, [])

  const openAdd  = () => { setForm(emptyForm); setError(''); setModal('add') }
  const openEdit = (emp: Employee) => {
    setForm({ first_name: emp.first_name, last_name: emp.last_name, email: emp.email, phone: emp.phone ?? '', password: '', branch_id: emp.branch?.id?.toString() ?? '', status: emp.status })
    setError(''); setModal(emp)
  }

  const save = async () => {
    setSaving(true); setError('')
    const payload = { ...form, branch_id: Number(form.branch_id) }
    if (!payload.password && modal !== 'add') delete (payload as any).password
    try {
      if (modal === 'add') await api.post('/enterprise/employees', payload)
      else await api.put(`/enterprise/employees/${(modal as Employee).id}`, payload)
      await load(); setModal(null)
    } catch (e: any) {
      const msgs = e?.response?.data?.errors
      setError(msgs ? Object.values(msgs).flat().join(' ') : (e?.response?.data?.message ?? t('common.error')))
    } finally { setSaving(false) }
  }

  const remove = async (id: number) => {
    if (!window.confirm('Supprimer cet employé ?')) return
    await api.delete(`/enterprise/employees/${id}`)
    await load()
  }

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.employees')}</h1>
        <button className="btn btn-primary" onClick={openAdd}><Plus size={15} /> {t('employee.addEmployee')}</button>
      </div>

      <div className="card">
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>{t('employee.branch')}</th>
                <th>{t('common.status')}</th>
                <th>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {employees.map((emp) => (
                <tr key={emp.id}>
                  <td style={{ fontWeight: 500 }}>{emp.first_name} {emp.last_name}</td>
                  <td className="text-muted">{emp.email}</td>
                  <td>{emp.phone || '—'}</td>
                  <td>{emp.branch?.name || '—'}</td>
                  <td><span className={`badge ${emp.status === 'active' ? 'badge-green' : 'badge-gray'}`}>{emp.status === 'active' ? t('common.active') : t('common.inactive')}</span></td>
                  <td style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm btn-icon" onClick={() => openEdit(emp)}><Pencil size={13} /></button>
                    <button className="btn btn-danger btn-sm btn-icon" onClick={() => remove(emp.id)}><Trash2 size={13} /></button>
                  </td>
                </tr>
              ))}
              {!employees.length && <tr><td colSpan={6} className="empty-state">{t('common.noData')}</td></tr>}
            </tbody>
          </table>
        </div>
      </div>

      {modal && (
        <div className="modal-overlay" onClick={(e) => e.target === e.currentTarget && setModal(null)}>
          <div className="modal">
            <div className="modal-header">
              <h2>{modal === 'add' ? t('employee.addEmployee') : t('employee.editEmployee')}</h2>
              <button className="btn btn-ghost btn-icon" onClick={() => setModal(null)}><X size={16} /></button>
            </div>
            <div className="modal-body">
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('employee.firstName')} *</label>
                  <input className="form-input" value={form.first_name} onChange={(e) => setForm({ ...form, first_name: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('employee.lastName')} *</label>
                  <input className="form-input" value={form.last_name} onChange={(e) => setForm({ ...form, last_name: e.target.value })} />
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">{t('employee.email')} *</label>
                <input className="form-input" type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('employee.phone')}</label>
                  <input className="form-input" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
                </div>
                <div className="form-group">
                  <label className="form-label">{t('employee.password')} {modal === 'add' ? '*' : '(laisser vide pour ne pas changer)'}</label>
                  <input className="form-input" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
                </div>
              </div>
              <div className="grid-2">
                <div className="form-group">
                  <label className="form-label">{t('employee.branch')} *</label>
                  <select className="form-select" value={form.branch_id} onChange={(e) => setForm({ ...form, branch_id: e.target.value })}>
                    <option value="">— Sélectionner —</option>
                    {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                  </select>
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

import { useEffect, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Upload, Save } from 'lucide-react'
import api from '../../services/api'

interface Profile {
  id: number; name: string; sector: string; phone: string | null
  email: string | null; address: string | null; description: string | null
  logo_url: string | null; status: string
}

const SECTORS = [
  { value: 'banque',          label: 'Banque / Finance' },
  { value: 'hopital',         label: 'Hôpital / Santé' },
  { value: 'administration',  label: 'Administration' },
  { value: 'autre',           label: 'Autre' },
]

export default function SettingsPage() {
  const { t } = useTranslation()
  const [profile, setProfile] = useState<Profile | null>(null)
  const [form, setForm] = useState({
    name: '', sector: '', phone: '', email: '', address: '', description: '',
  })
  const [saving, setSaving] = useState(false)
  const [msg, setMsg] = useState<{ type: 'ok' | 'err'; text: string } | null>(null)
  const fileRef = useRef<HTMLInputElement>(null)
  const [logoPreview, setLogoPreview] = useState<string | null>(null)

  useEffect(() => {
    api.get('/enterprise/profile').then((r) => {
      const p: Profile = r.data.enterprise ?? r.data
      setProfile(p)
      setForm({
        name:        p.name ?? '',
        sector:      p.sector ?? '',
        phone:       p.phone ?? '',
        email:       p.email ?? '',
        address:     p.address ?? '',
        description: p.description ?? '',
      })
      setLogoPreview(p.logo_url)
    })
  }, [])

  const save = async () => {
    setSaving(true); setMsg(null)
    try {
      await api.patch('/enterprise/profile', form)
      setMsg({ type: 'ok', text: 'Profil mis à jour avec succès.' })
    } catch (e: any) {
      const errs = e?.response?.data?.errors
      setMsg({ type: 'err', text: errs ? Object.values(errs).flat().join(' ') : (e?.response?.data?.message ?? t('common.error')) })
    } finally { setSaving(false) }
  }

  const uploadLogo = async (file: File) => {
    const fd = new FormData()
    fd.append('logo', file)
    try {
      const r = await api.post('/enterprise/profile/logo', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      setLogoPreview(r.data.logo_url)
      setMsg({ type: 'ok', text: 'Logo mis à jour.' })
    } catch {
      setMsg({ type: 'err', text: 'Échec du téléversement du logo.' })
    }
  }

  const f = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
    setForm({ ...form, [k]: e.target.value })

  if (!profile) return <div style={{ display: 'flex', justifyContent: 'center', padding: 60 }}><div className="spinner" /></div>

  return (
    <div>
      <div className="page-header">
        <h1>Paramètres — Profil entreprise</h1>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 24, maxWidth: 900 }}>
        {/* Logo card */}
        <div className="card" style={{ padding: 24, textAlign: 'center' }}>
          <div style={{
            width: 120, height: 120, borderRadius: 12, margin: '0 auto 16px',
            background: 'var(--gray-100)', display: 'flex', alignItems: 'center', justifyContent: 'center',
            overflow: 'hidden', border: '2px dashed var(--gray-300)',
          }}>
            {logoPreview
              ? <img src={logoPreview} alt="logo" style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
              : <span style={{ fontSize: 36, color: 'var(--gray-400)' }}>🏢</span>
            }
          </div>
          <input ref={fileRef} type="file" accept="image/*" style={{ display: 'none' }}
            onChange={(e) => { if (e.target.files?.[0]) uploadLogo(e.target.files[0]) }} />
          <button className="btn btn-secondary" onClick={() => fileRef.current?.click()}>
            <Upload size={14} /> Changer le logo
          </button>
          <p className="text-muted" style={{ fontSize: 11, marginTop: 8 }}>PNG/JPG, max 2 Mo</p>

          <div style={{ marginTop: 24, textAlign: 'left' }}>
            <div style={{ fontSize: 12, color: 'var(--gray-500)', marginBottom: 4 }}>Statut</div>
            <span className={`badge ${profile.status === 'active' ? 'badge-green' : profile.status === 'pending' ? 'badge-amber' : 'badge-red'}`}>
              {{ active: 'Actif', pending: 'En attente', suspended: 'Suspendu' }[profile.status] ?? profile.status}
            </span>
          </div>
        </div>

        {/* Form card */}
        <div className="card" style={{ padding: 24 }}>
          {msg && (
            <div style={{
              padding: '10px 14px', borderRadius: 8, marginBottom: 20, fontSize: 13,
              background: msg.type === 'ok' ? 'var(--teal-50)' : '#fef2f2',
              color: msg.type === 'ok' ? 'var(--teal-700)' : '#dc2626',
              border: `1px solid ${msg.type === 'ok' ? 'var(--teal-200)' : '#fecaca'}`,
            }}>{msg.text}</div>
          )}

          <div className="grid-2">
            <div className="form-group">
              <label className="form-label">Nom de l'entreprise *</label>
              <input className="form-input" value={form.name} onChange={f('name')} />
            </div>
            <div className="form-group">
              <label className="form-label">Secteur *</label>
              <select className="form-select" value={form.sector} onChange={f('sector')}>
                <option value="">— Choisir —</option>
                {SECTORS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
              </select>
            </div>
          </div>

          <div className="grid-2">
            <div className="form-group">
              <label className="form-label">Téléphone</label>
              <input className="form-input" value={form.phone} onChange={f('phone')} placeholder="+213..." />
            </div>
            <div className="form-group">
              <label className="form-label">E-mail de contact</label>
              <input className="form-input" type="email" value={form.email} onChange={f('email')} />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Adresse</label>
            <input className="form-input" value={form.address} onChange={f('address')} />
          </div>

          <div className="form-group">
            <label className="form-label">Description</label>
            <textarea className="form-input" rows={3} value={form.description} onChange={f('description')}
              style={{ resize: 'vertical' }} />
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
            <button className="btn btn-primary" onClick={save} disabled={saving}>
              {saving && <div className="spinner" style={{ width: 14, height: 14 }} />}
              <Save size={14} /> Enregistrer
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { Layers } from 'lucide-react'
import { useAuthStore } from '../../store/authStore'
import api from '../../services/api'
import LanguageSelector from '../../components/layout/LanguageSelector'
import i18n from '../../i18n'

export default function LoginPage() {
  const { t } = useTranslation()
  const { setAuth } = useAuthStore()
  const navigate = useNavigate()

  const [email, setEmail]       = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading]   = useState(false)
  const [error, setError]       = useState('')

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')
    try {
      const res = await api.post('/staff/auth/login', { email, password })
      const { token, user } = res.data

      // Persist language preference
      if (user.language_preference) {
        i18n.changeLanguage(user.language_preference)
        localStorage.setItem('sigfa_lang', user.language_preference)
      }

      setAuth(token, user)

      if (user.role === 'super_admin')       navigate('/admin/dashboard')
      else if (user.role === 'enterprise_admin') navigate('/ea/dashboard')
      else navigate('/agent/console')
    } catch (err: any) {
      setError(err?.response?.data?.errors?.email?.[0] ?? t('auth.invalidCredentials'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-page">
      <div className="login-card">
        <div style={{ position: 'fixed', top: 16, right: 16 }}>
          <LanguageSelector />
        </div>

        <div className="login-logo">
          <Layers size={22} color="#fff" />
        </div>
        <h1 className="login-title">{t('auth.loginTitle')}</h1>
        <p className="login-subtitle">{t('auth.loginSubtitle')}</p>

        <form className="login-form" onSubmit={submit}>
          <div className="form-group">
            <label className="form-label">{t('auth.email')}</label>
            <input
              type="email"
              className="form-input"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoFocus
              placeholder="admin@entreprise.fr"
            />
          </div>
          <div className="form-group">
            <label className="form-label">{t('auth.password')}</label>
            <input
              type="password"
              className="form-input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder="••••••••"
            />
          </div>
          {error && <p className="form-error">{error}</p>}
          <button type="submit" className="btn btn-primary btn-lg" disabled={loading}>
            {loading ? <div className="spinner" style={{ width: 14, height: 14 }} /> : null}
            {t('auth.login')}
          </button>
        </form>

        <p style={{ textAlign: 'center', marginTop: 20, color: 'var(--gray-400)', fontSize: 12 }}>
          SIGFA — QueueSmart © {new Date().getFullYear()}
        </p>
      </div>
    </div>
  )
}

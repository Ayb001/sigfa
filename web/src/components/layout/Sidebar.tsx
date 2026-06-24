import { NavLink, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  LayoutDashboard, Building2, GitBranch, Users, ListOrdered,
  FileBarChart2, Settings, LogOut, Activity, Monitor, Layers
} from 'lucide-react'
import { useAuthStore } from '../../store/authStore'
import api from '../../services/api'
import LanguageSelector from './LanguageSelector'

export default function Sidebar() {
  const { t } = useTranslation()
  const { user, clearAuth } = useAuthStore()
  const navigate = useNavigate()

  const logout = async () => {
    try { await api.post('/staff/auth/logout') } catch {}
    clearAuth()
    navigate('/login')
  }

  const initials = user
    ? `${user.first_name[0]}${user.last_name[0]}`.toUpperCase()
    : '?'

  return (
    <aside className="sidebar">
      <div className="sidebar-logo">
        <div className="sidebar-logo-icon">
          <Layers size={16} color="#fff" />
        </div>
        <span className="sidebar-brand">SIG<span>FA</span></span>
      </div>

      <nav className="sidebar-nav">
        {user?.role === 'super_admin' && <SuperAdminNav t={t} />}
        {user?.role === 'enterprise_admin' && <EnterpriseAdminNav t={t} />}
        {user?.role === 'employee' && <AgentNav t={t} />}
      </nav>

      <div className="sidebar-footer">
        <div className="sidebar-user">
          <div className="sidebar-avatar">{initials}</div>
          <div className="sidebar-user-info" style={{ flex: 1, minWidth: 0 }}>
            <div className="name" style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
              {user?.first_name} {user?.last_name}
            </div>
            <div className="role">{t(`roles.${user?.role}`)}</div>
          </div>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: 6 }}>
          <LanguageSelector dark />
          <button className="btn btn-ghost btn-sm" onClick={logout} style={{ color: 'var(--gray-400)' }}>
            <LogOut size={14} />
            <span>{t('auth.logout')}</span>
          </button>
        </div>
      </div>
    </aside>
  )
}

function NavItem({ to, icon: Icon, label }: { to: string; icon: React.ElementType; label: string }) {
  return (
    <NavLink to={to} className={({ isActive }) => `sidebar-link${isActive ? ' active' : ''}`}>
      <Icon size={15} />
      {label}
    </NavLink>
  )
}

function SuperAdminNav({ t }: { t: (k: string) => string }) {
  return (
    <>
      <div className="sidebar-section">
        <NavItem to="/admin/dashboard" icon={LayoutDashboard} label={t('nav.dashboard')} />
        <NavItem to="/admin/enterprises" icon={Building2} label={t('nav.enterprises')} />
        <NavItem to="/admin/users" icon={Users} label={t('nav.users')} />
      </div>
    </>
  )
}

function EnterpriseAdminNav({ t }: { t: (k: string) => string }) {
  return (
    <>
      <div className="sidebar-section">
        <NavItem to="/ea/dashboard" icon={LayoutDashboard} label={t('nav.dashboard')} />
        <NavItem to="/ea/sessions" icon={Monitor} label={t('nav.sessions')} />
      </div>
      <div className="sidebar-section">
        <div className="sidebar-section-label">Gestion</div>
        <NavItem to="/ea/branches"  icon={GitBranch}    label={t('nav.branches')} />
        <NavItem to="/ea/employees" icon={Users}        label={t('nav.employees')} />
        <NavItem to="/ea/queues"    icon={ListOrdered}  label={t('nav.queues')} />
      </div>
      <div className="sidebar-section">
        <NavItem to="/ea/reports"  icon={FileBarChart2} label={t('nav.reports')} />
        <NavItem to="/ea/settings" icon={Settings}      label={t('nav.settings')} />
      </div>
    </>
  )
}

function AgentNav({ t }: { t: (k: string) => string }) {
  return (
    <div className="sidebar-section">
      <NavItem to="/agent/console" icon={Activity}    label={t('nav.mySession')} />
      <NavItem to="/agent/history" icon={ListOrdered} label="Historique" />
    </div>
  )
}

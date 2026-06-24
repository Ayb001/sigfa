import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { Suspense, lazy } from 'react'
import AppLayout from './components/layout/AppLayout'
import LoginPage from './pages/auth/LoginPage'
import { useAuthStore } from './store/authStore'

// Super Admin
const SADashboard      = lazy(() => import('./pages/superadmin/DashboardPage'))
const EnterprisesPage  = lazy(() => import('./pages/superadmin/EnterprisesPage'))
const UsersPage        = lazy(() => import('./pages/superadmin/UsersPage'))

// Enterprise Admin
const EADashboard    = lazy(() => import('./pages/enterpriseadmin/DashboardPage'))
const SessionsPage   = lazy(() => import('./pages/enterpriseadmin/SessionsPage'))
const BranchesPage   = lazy(() => import('./pages/enterpriseadmin/BranchesPage'))
const EmployeesPage  = lazy(() => import('./pages/enterpriseadmin/EmployeesPage'))
const QueuesPage     = lazy(() => import('./pages/enterpriseadmin/QueuesPage'))
const ReportsPage    = lazy(() => import('./pages/enterpriseadmin/ReportsPage'))
const SettingsPage   = lazy(() => import('./pages/enterpriseadmin/SettingsPage'))

// Agent
const AgentConsolePage = lazy(() => import('./pages/agent/AgentConsolePage'))
const HistoryPage      = lazy(() => import('./pages/agent/HistoryPage'))

function RoleRedirect() {
  const { user } = useAuthStore()
  if (!user) return <Navigate to="/login" replace />
  if (user.role === 'super_admin')      return <Navigate to="/admin/dashboard" replace />
  if (user.role === 'enterprise_admin') return <Navigate to="/ea/dashboard" replace />
  return <Navigate to="/agent/console" replace />
}

function Spinner() {
  return <div style={{ display: 'flex', justifyContent: 'center', padding: 60 }}><div className="spinner" /></div>
}

export default function App() {
  return (
    <BrowserRouter>
      <Suspense fallback={<Spinner />}>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/"     element={<RoleRedirect />} />

          <Route element={<AppLayout />}>
            {/* Super Admin */}
            <Route path="/admin/dashboard"   element={<SADashboard />} />
            <Route path="/admin/enterprises" element={<EnterprisesPage />} />
            <Route path="/admin/users"       element={<UsersPage />} />

            {/* Enterprise Admin */}
            <Route path="/ea/dashboard" element={<EADashboard />} />
            <Route path="/ea/sessions"  element={<SessionsPage />} />
            <Route path="/ea/branches"  element={<BranchesPage />} />
            <Route path="/ea/employees" element={<EmployeesPage />} />
            <Route path="/ea/queues"    element={<QueuesPage />} />
            <Route path="/ea/reports"   element={<ReportsPage />} />
            <Route path="/ea/settings"  element={<SettingsPage />} />

            {/* Agent */}
            <Route path="/agent/console" element={<AgentConsolePage />} />
            <Route path="/agent/history" element={<HistoryPage />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  )
}

import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { Suspense, lazy } from 'react'
import AppLayout from './components/layout/AppLayout'
import LoginPage from './pages/auth/LoginPage'
import { useAuthStore } from './store/authStore'

const DashboardPage    = lazy(() => import('./pages/enterpriseadmin/DashboardPage'))
const BranchesPage     = lazy(() => import('./pages/enterpriseadmin/BranchesPage'))
const EmployeesPage    = lazy(() => import('./pages/enterpriseadmin/EmployeesPage'))
const QueuesPage       = lazy(() => import('./pages/enterpriseadmin/QueuesPage'))
const EnterprisesPage  = lazy(() => import('./pages/superadmin/EnterprisesPage'))
const AgentConsolePage = lazy(() => import('./pages/agent/AgentConsolePage'))

function RoleRedirect() {
  const { user } = useAuthStore()
  if (!user) return <Navigate to="/login" replace />
  if (user.role === 'super_admin')      return <Navigate to="/admin/dashboard" replace />
  if (user.role === 'enterprise_admin') return <Navigate to="/ea/dashboard" replace />
  return <Navigate to="/agent/console" replace />
}

function Stub({ title }: { title: string }) {
  return <div className="page-header"><h1>{title}</h1></div>
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
            <Route path="/admin/dashboard"   element={<Stub title="Tableau de bord — Super Admin" />} />
            <Route path="/admin/enterprises" element={<EnterprisesPage />} />
            <Route path="/admin/users"       element={<Stub title="Gestion des utilisateurs" />} />

            {/* Enterprise Admin */}
            <Route path="/ea/dashboard" element={<DashboardPage />} />
            <Route path="/ea/sessions"  element={<Stub title="Sessions en cours" />} />
            <Route path="/ea/branches"  element={<BranchesPage />} />
            <Route path="/ea/employees" element={<EmployeesPage />} />
            <Route path="/ea/queues"    element={<QueuesPage />} />
            <Route path="/ea/reports"   element={<Stub title="Rapports & Exports" />} />
            <Route path="/ea/settings"  element={<Stub title="Paramètres" />} />

            {/* Agent */}
            <Route path="/agent/console" element={<AgentConsolePage />} />
            <Route path="/agent/history" element={<Stub title="Historique des sessions" />} />
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  )
}

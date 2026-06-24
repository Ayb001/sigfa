import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { TicketCheck, Clock, Users, Activity, ListOrdered, Monitor, Timer } from 'lucide-react'
import api from '../../services/api'
import LiveSessionsTable from './components/LiveSessionsTable'

interface KPIs {
  total_tickets_today: number
  served_today: number
  waiting_now: number
  avg_wait_minutes: number
  active_queues: number
  active_sessions: number
  total_employees: number
}

export default function DashboardPage() {
  const { t } = useTranslation()
  const [kpis, setKpis]     = useState<KPIs | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/enterprise/dashboard/kpis')
      .then((r) => setKpis(r.data))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><div className="spinner" /></div>

  return (
    <div>
      <div className="page-header">
        <h1>{t('nav.dashboard')}</h1>
        <span className="text-muted">{new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
      </div>

      <div className="grid-kpi mb-4">
        <KpiCard icon={TicketCheck} color="teal"  value={kpis?.total_tickets_today ?? 0} label={t('dashboard.totalTickets')} />
        <KpiCard icon={Activity}    color="green" value={kpis?.served_today ?? 0}        label={t('dashboard.servedToday')} />
        <KpiCard icon={Clock}       color="amber" value={kpis?.waiting_now ?? 0}         label={t('dashboard.waitingNow')} />
        <KpiCard icon={Timer}       color="blue"  value={`${kpis?.avg_wait_minutes ?? 0} min`} label={t('dashboard.avgWait')} />
        <KpiCard icon={ListOrdered} color="teal"  value={kpis?.active_queues ?? 0}       label={t('dashboard.activeQueues')} />
        <KpiCard icon={Monitor}     color="teal"  value={kpis?.active_sessions ?? 0}     label={t('dashboard.activeSessions')} />
        <KpiCard icon={Users}       color="gray"  value={kpis?.total_employees ?? 0}     label={t('dashboard.totalEmployees')} />
      </div>

      <div className="card">
        <div className="card-header">
          <h2>{t('dashboard.activeSessions_title')}</h2>
        </div>
        <LiveSessionsTable />
      </div>
    </div>
  )
}

function KpiCard({ icon: Icon, color, value, label }: { icon: React.ElementType; color: string; value: number | string; label: string }) {
  return (
    <div className="kpi-card">
      <div className={`kpi-icon kpi-icon-${color}`}><Icon size={18} /></div>
      <div>
        <div className="kpi-value">{value}</div>
        <div className="kpi-label">{label}</div>
      </div>
    </div>
  )
}

import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Building2, Users, TicketCheck, UserCheck, Clock, TrendingUp } from 'lucide-react'
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'
import api from '../../services/api'

interface Overview {
  enterprises: { total: number; active: number; pending: number; suspended: number }
  users:       { total: number; enterprise_admins: number; employees: number }
  clients:     number
  total_tickets: number
  served_today:  number
  tickets_by_day: { date: string; count: number }[]
  top_enterprises: { id: number; name: string; sector: string; tickets_count: number }[]
  sector_breakdown: { sector: string; count: number }[]
}

const SECTOR_COLORS: Record<string, string> = {
  banque: '#0d9488', hopital: '#2563eb', administration: '#7c3aed', autre: '#d97706',
}
const SECTOR_LABELS: Record<string, string> = {
  banque: 'Banque', hopital: 'Hôpital', administration: 'Administration', autre: 'Autre',
}

export default function SuperAdminDashboard() {
  const { t } = useTranslation()
  const [data, setData]       = useState<Overview | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/admin/dashboard').then((r) => setData(r.data)).finally(() => setLoading(false))
  }, [])

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 60 }}><div className="spinner" /></div>
  if (!data)   return null

  return (
    <div>
      <div className="page-header">
        <h1>Tableau de bord — Super Admin</h1>
        <span className="text-muted">{new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
      </div>

      {/* KPIs */}
      <div className="grid-kpi mb-4">
        <KpiCard icon={Building2} color="teal"  label="Entreprises actives"  value={data.enterprises.active} />
        <KpiCard icon={Building2} color="amber" label="En attente d'approbation" value={data.enterprises.pending} />
        <KpiCard icon={Users}     color="blue"  label="Admins d'entreprise"   value={data.users.enterprise_admins} />
        <KpiCard icon={UserCheck} color="teal"  label="Agents / Employés"     value={data.users.employees} />
        <KpiCard icon={UserCheck} color="green" label="Clients inscrits"      value={data.clients} />
        <KpiCard icon={TicketCheck} color="teal" label="Total tickets"        value={data.total_tickets} />
        <KpiCard icon={Clock}     color="green" label="Servis aujourd'hui"    value={data.served_today} />
      </div>

      <div className="grid-2">
        {/* Tickets 30 derniers jours */}
        <div className="card">
          <div className="card-header"><h2>Tickets — 30 derniers jours</h2></div>
          <div className="card-body">
            <ResponsiveContainer width="100%" height={200}>
              <BarChart data={data.tickets_by_day} margin={{ top: 4, right: 8, left: -20, bottom: 0 }}>
                <XAxis dataKey="date" tick={{ fontSize: 10 }} tickFormatter={(d) => d.slice(5)} />
                <YAxis tick={{ fontSize: 10 }} allowDecimals={false} />
                <Tooltip formatter={(v: number) => [v, 'Tickets']} labelFormatter={(l) => l} />
                <Bar dataKey="count" fill="#0d9488" radius={[2, 2, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Secteurs */}
        <div className="card">
          <div className="card-header"><h2>Répartition par secteur</h2></div>
          <div className="card-body" style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
            <ResponsiveContainer width="50%" height={180}>
              <PieChart>
                <Pie data={data.sector_breakdown} dataKey="count" nameKey="sector" cx="50%" cy="50%" outerRadius={70}>
                  {data.sector_breakdown.map((entry) => (
                    <Cell key={entry.sector} fill={SECTOR_COLORS[entry.sector] ?? '#9ca3af'} />
                  ))}
                </Pie>
                <Tooltip formatter={(v: number, n: string) => [v, SECTOR_LABELS[n] ?? n]} />
              </PieChart>
            </ResponsiveContainer>
            <div style={{ flex: 1 }}>
              {data.sector_breakdown.map((s) => (
                <div key={s.sector} style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                  <span style={{ width: 10, height: 10, borderRadius: '50%', background: SECTOR_COLORS[s.sector] ?? '#9ca3af', flexShrink: 0 }} />
                  <span style={{ fontSize: 13 }}>{SECTOR_LABELS[s.sector] ?? s.sector}</span>
                  <span className="text-muted" style={{ marginLeft: 'auto' }}>{s.count}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Top enterprises */}
      <div className="card mt-4">
        <div className="card-header"><h2>Top 5 entreprises (volume de tickets)</h2></div>
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr><th>#</th><th>Entreprise</th><th>Secteur</th><th>Tickets total</th></tr>
            </thead>
            <tbody>
              {data.top_enterprises.map((e, i) => (
                <tr key={e.id}>
                  <td style={{ color: 'var(--gray-400)', fontWeight: 700 }}>{i + 1}</td>
                  <td style={{ fontWeight: 500 }}>{e.name}</td>
                  <td><span className="badge badge-teal">{SECTOR_LABELS[e.sector] ?? e.sector}</span></td>
                  <td>{e.tickets_count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

function KpiCard({ icon: Icon, color, label, value }: { icon: React.ElementType; color: string; label: string; value: number }) {
  return (
    <div className="kpi-card">
      <div className={`kpi-icon kpi-icon-${color}`}><Icon size={18} /></div>
      <div>
        <div className="kpi-value">{value.toLocaleString('fr-FR')}</div>
        <div className="kpi-label">{label}</div>
      </div>
    </div>
  )
}

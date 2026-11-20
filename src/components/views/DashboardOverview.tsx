import { Building2, Users, Clock, Crown, TrendingUp, TrendingDown } from 'lucide-react';
import type { BloodBank } from '@/types';

interface MetricCardProps {
  label: string;
  value: number;
  growth: number;
  icon: typeof Building2;
  accent: 'brand' | 'blue' | 'amber' | 'green';
}

const accentMap = {
  brand: { bg: 'bg-brand-50 dark:bg-brand-950/30', icon: 'bg-brand-500', text: 'text-brand-700 dark:text-brand-300' },
  blue: { bg: 'bg-blue-50 dark:bg-blue-950/30', icon: 'bg-blue-500', text: 'text-blue-700 dark:text-blue-300' },
  amber: { bg: 'bg-amber-50 dark:bg-amber-950/30', icon: 'bg-amber-500', text: 'text-amber-700 dark:text-amber-300' },
  green: { bg: 'bg-green-50 dark:bg-green-950/30', icon: 'bg-green-500', text: 'text-green-700 dark:text-green-300' },
};

function MetricCard({ label, value, growth, icon: Icon, accent }: MetricCardProps) {
  const a = accentMap[accent];
  const isPositive = growth >= 0;
  return (
    <div className="group relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5">
      <div className="flex items-start justify-between mb-4">
        <div className={`w-12 h-12 rounded-xl ${a.icon} flex items-center justify-center shadow-lg`}>
          <Icon className="w-6 h-6 text-white" />
        </div>
        <div className={`flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg ${a.bg} ${a.text}`}>
          {isPositive ? <TrendingUp className="w-3.5 h-3.5" /> : <TrendingDown className="w-3.5 h-3.5" />}
          {isPositive ? '+' : ''}{growth}%
        </div>
      </div>
      <p className="text-3xl font-bold text-gray-900 dark:text-white tabular-nums">{value.toLocaleString()}</p>
      <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{label}</p>
    </div>
  );
}

interface DashboardOverviewProps {
  bloodBanks: BloodBank[];
  loading: boolean;
}

export function DashboardOverview({ bloodBanks, loading }: DashboardOverviewProps) {
  const total = bloodBanks.length;
  const freeUsers = bloodBanks.filter((b) => b.subscription_status === 'free' && b.is_active).length;
  const trialUsers = bloodBanks.filter((b) => b.subscription_status === 'trial' && b.is_active).length;
  const proUsers = bloodBanks.filter((b) => b.subscription_status === 'pro' && b.is_active).length;

  const totalDonors = bloodBanks.reduce((sum, b) => sum + b.donor_count, 0);
  const totalVolunteers = bloodBanks.reduce((sum, b) => sum + b.volunteer_count, 0);
  const activeCount = bloodBanks.filter((b) => b.is_active).length;
  const suspendedCount = total - activeCount;

  // Distribution data
  const distribution = [
    { label: 'Pro', value: proUsers, color: 'bg-brand-500' },
    { label: 'Trial', value: trialUsers, color: 'bg-amber-500' },
    { label: 'Free', value: freeUsers, color: 'bg-gray-400' },
  ];
  const totalDist = distribution.reduce((s, d) => s + d.value, 0) || 1;

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Metric cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <MetricCard label="Total Blood Banks" value={total} growth={12.5} icon={Building2} accent="brand" />
        <MetricCard label="Active Free Users" value={freeUsers} growth={8.3} icon={Users} accent="blue" />
        <MetricCard label="Trial Users" value={trialUsers} growth={15.2} icon={Clock} accent="amber" />
        <MetricCard label="Pro Subscribers" value={proUsers} growth={-3.1} icon={Crown} accent="green" />
      </div>

      {/* Secondary stats */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Distribution card */}
        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
          <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">Subscription Distribution</h3>
          {/* Stacked bar */}
          <div className="flex h-3 rounded-full overflow-hidden mb-4">
            {distribution.map((d) => (
              <div
                key={d.label}
                className={`${d.color} transition-all duration-500`}
                style={{ width: `${(d.value / totalDist) * 100}%` }}
              />
            ))}
          </div>
          <div className="space-y-2.5">
            {distribution.map((d) => (
              <div key={d.label} className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className={`w-2.5 h-2.5 rounded-full ${d.color}`} />
                  <span className="text-sm text-gray-600 dark:text-gray-400">{d.label}</span>
                </div>
                <span className="text-sm font-semibold text-gray-900 dark:text-white tabular-nums">{d.value}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Donors & Volunteers */}
        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
          <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">Platform Resources</h3>
          <div className="space-y-4">
            <div className="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
              <div>
                <p className="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{totalDonors.toLocaleString()}</p>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Total Donors</p>
              </div>
              <div className="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-950/30 flex items-center justify-center">
                <Users className="w-5 h-5 text-brand-500" />
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{totalVolunteers.toLocaleString()}</p>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Total Volunteers</p>
              </div>
              <div className="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center">
                <Users className="w-5 h-5 text-blue-500" />
              </div>
            </div>
          </div>
        </div>

        {/* Active / Suspended */}
        <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
          <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-4">Account Status</h3>
          <div className="space-y-4">
            <div className="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
              <div className="flex items-center gap-3">
                <span className="w-2.5 h-2.5 rounded-full bg-green-500" />
                <span className="text-sm text-gray-600 dark:text-gray-400">Active</span>
              </div>
              <span className="text-lg font-bold text-gray-900 dark:text-white tabular-nums">{activeCount}</span>
            </div>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <span className="w-2.5 h-2.5 rounded-full bg-gray-400" />
                <span className="text-sm text-gray-600 dark:text-gray-400">Suspended</span>
              </div>
              <span className="text-lg font-bold text-gray-900 dark:text-white tabular-nums">{suspendedCount}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Recent activity table */}
      <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
          <h3 className="text-sm font-semibold text-gray-900 dark:text-white">Recently Provisioned Tenants</h3>
        </div>
        {loading ? (
          <div className="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">Loading...</div>
        ) : (
          <div className="overflow-x-auto scrollbar-thin">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-gray-50 dark:bg-gray-800/50 text-left text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500">
                  <th className="px-5 py-3 font-semibold">Organization</th>
                  <th className="px-5 py-3 font-semibold">Subdomain</th>
                  <th className="px-5 py-3 font-semibold">Status</th>
                  <th className="px-5 py-3 font-semibold">Donors</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                {bloodBanks.slice(0, 5).map((bank) => (
                  <tr key={bank.id} className="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                    <td className="px-5 py-3.5">
                      <div className="font-medium text-gray-900 dark:text-white">{bank.org_name}</div>
                      <div className="text-xs text-gray-500 dark:text-gray-400">{bank.admin_name}</div>
                    </td>
                    <td className="px-5 py-3.5 text-gray-600 dark:text-gray-300 font-mono text-xs">
                      {bank.subdomain}.bloodapp.com
                    </td>
                    <td className="px-5 py-3.5">
                      <span className={`inline-flex items-center gap-1.5 text-xs font-semibold ${
                        bank.is_active
                          ? 'text-green-600 dark:text-green-400'
                          : 'text-gray-400 dark:text-gray-500'
                      }`}>
                        <span className={`w-1.5 h-1.5 rounded-full ${bank.is_active ? 'bg-green-500' : 'bg-gray-400'}`} />
                        {bank.is_active ? 'Active' : 'Suspended'}
                      </span>
                    </td>
                    <td className="px-5 py-3.5 text-gray-900 dark:text-white font-medium tabular-nums">
                      {bank.donor_count.toLocaleString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

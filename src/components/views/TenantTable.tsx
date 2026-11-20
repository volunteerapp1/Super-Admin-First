import { useEffect, useRef, useState } from 'react';
import { MoreVertical, CheckCircle, Pause, Crown, Trash2, ExternalLink } from 'lucide-react';
import type { BloodBank, SubscriptionStatus } from '@/types';
import { SubscriptionBadge } from '@/components/ui/SubscriptionBadge';

interface TenantTableProps {
  bloodBanks: BloodBank[];
  loading: boolean;
  onActivate: (id: string) => void;
  onSuspend: (id: string) => void;
  onUpgradeToPro: (id: string) => void;
  onDelete: (id: string) => void;
}

type Action = 'activate' | 'suspend' | 'upgrade' | 'delete';

interface ActionsMenuProps {
  bank: BloodBank;
  onAction: (action: Action, bank: BloodBank) => void;
}

function ActionsMenu({ bank, onAction }: ActionsMenuProps) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    if (open) document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const items: { action: Action; label: string; icon: typeof CheckCircle; color: string; show: boolean }[] = [
    { action: 'activate', label: 'Activate', icon: CheckCircle, color: 'text-green-600 dark:text-green-400', show: !bank.is_active },
    { action: 'suspend', label: 'Suspend', icon: Pause, color: 'text-amber-600 dark:text-amber-400', show: bank.is_active },
    { action: 'upgrade', label: 'Upgrade to Pro', icon: Crown, color: 'text-brand-600 dark:text-brand-400', show: bank.subscription_status !== 'pro' },
    { action: 'delete', label: 'Delete', icon: Trash2, color: 'text-red-600 dark:text-red-400', show: true },
  ];

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen(!open)}
        className="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
      >
        <MoreVertical className="w-4 h-4" />
      </button>
      {open && (
        <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 py-1.5 z-30 animate-slide-down">
          {items.filter((i) => i.show).map((item) => {
            const Icon = item.icon;
            return (
              <button
                key={item.action}
                onClick={() => {
                  onAction(item.action, bank);
                  setOpen(false);
                }}
                className={`w-full flex items-center gap-2.5 px-3 py-2 text-sm ${item.color} hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left`}
              >
                <Icon className="w-4 h-4 shrink-0" />
                {item.label}
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}

interface DeleteConfirmProps {
  bank: BloodBank;
  onConfirm: () => void;
  onCancel: () => void;
}

function DeleteConfirmModal({ bank, onConfirm, onCancel }: DeleteConfirmProps) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm animate-fade-in" onClick={onCancel} />
      <div className="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full p-6 animate-slide-up">
        <div className="w-12 h-12 rounded-full bg-red-100 dark:bg-red-950/40 flex items-center justify-center mb-4">
          <Trash2 className="w-6 h-6 text-red-600 dark:text-red-400" />
        </div>
        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">Delete blood bank?</h3>
        <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
          You are about to permanently delete <span className="font-semibold text-gray-900 dark:text-white">{bank.org_name}</span> and all its associated data. This action cannot be undone.
        </p>
        <div className="flex gap-3">
          <button
            onClick={onCancel}
            className="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={onConfirm}
            className="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-medium shadow-lg shadow-red-600/20 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  );
}

export function TenantTable({
  bloodBanks,
  loading,
  onActivate,
  onSuspend,
  onUpgradeToPro,
  onDelete,
}: TenantTableProps) {
  const [filter, setFilter] = useState<SubscriptionStatus | 'all'>('all');
  const [search, setSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<BloodBank | null>(null);

  const filtered = bloodBanks.filter((b) => {
    if (filter !== 'all' && b.subscription_status !== filter) return false;
    if (search) {
      const q = search.toLowerCase();
      return (
        b.org_name.toLowerCase().includes(q) ||
        b.subdomain.toLowerCase().includes(q) ||
        b.admin_email.toLowerCase().includes(q) ||
        b.admin_name.toLowerCase().includes(q)
      );
    }
    return true;
  });

  const handleAction = (action: Action, bank: BloodBank) => {
    switch (action) {
      case 'activate': onActivate(bank.id); break;
      case 'suspend': onSuspend(bank.id); break;
      case 'upgrade': onUpgradeToPro(bank.id); break;
      case 'delete': setDeleteTarget(bank); break;
    }
  };

  const filterTabs: { id: SubscriptionStatus | 'all'; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'pro', label: 'Pro' },
    { id: 'trial', label: 'Trial' },
    { id: 'free', label: 'Free' },
  ];

  return (
    <div className="space-y-4 animate-fade-in">
      {/* Filter bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div className="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
          {filterTabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setFilter(tab.id)}
              className={`px-3 sm:px-4 py-1.5 rounded-lg text-sm font-medium transition-all ${
                filter === tab.id
                  ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>
        <input
          type="text"
          placeholder="Search blood banks..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 w-full sm:w-64"
        />
      </div>

      {/* Table */}
      <div className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        {loading ? (
          <div className="p-12 text-center">
            <div className="inline-block w-8 h-8 border-3 border-brand-500 border-t-transparent rounded-full animate-spin" />
            <p className="text-sm text-gray-400 dark:text-gray-500 mt-3">Loading blood banks...</p>
          </div>
        ) : filtered.length === 0 ? (
          <div className="p-12 text-center text-gray-400 dark:text-gray-500 text-sm">
            No blood banks found matching your criteria.
          </div>
        ) : (
          <div className="overflow-x-auto scrollbar-thin">
            <table className="w-full text-sm min-w-[700px]">
              <thead>
                <tr className="bg-gray-50 dark:bg-gray-800/50 text-left text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500">
                  <th className="px-5 py-3.5 font-semibold">Organization</th>
                  <th className="px-5 py-3.5 font-semibold">Subdomain</th>
                  <th className="px-5 py-3.5 font-semibold">Admin</th>
                  <th className="px-5 py-3.5 font-semibold">Subscription</th>
                  <th className="px-5 py-3.5 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                {filtered.map((bank) => (
                  <tr key={bank.id} className="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors group">
                    {/* Org name */}
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
                          {bank.org_name.slice(0, 2).toUpperCase()}
                        </div>
                        <div className="min-w-0">
                          <div className="font-semibold text-gray-900 dark:text-white truncate">{bank.org_name}</div>
                          <div className="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                            <span className={`w-1.5 h-1.5 rounded-full ${bank.is_active ? 'bg-green-500' : 'bg-gray-400'}`} />
                            {bank.is_active ? 'Active' : 'Suspended'}
                          </div>
                        </div>
                      </div>
                    </td>
                    {/* Subdomain */}
                    <td className="px-5 py-4">
                      <a
                        href={`https://${bank.subdomain}.bloodapp.com`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300 font-mono text-xs hover:text-brand-600 dark:hover:text-brand-400 transition-colors group/link"
                      >
                        {bank.subdomain}.bloodapp.com
                        <ExternalLink className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                      </a>
                    </td>
                    {/* Admin */}
                    <td className="px-5 py-4">
                      <div className="text-gray-900 dark:text-white font-medium">{bank.admin_name}</div>
                      <div className="text-xs text-gray-500 dark:text-gray-400">{bank.admin_email}</div>
                    </td>
                    {/* Subscription */}
                    <td className="px-5 py-4">
                      <SubscriptionBadge status={bank.subscription_status} />
                    </td>
                    {/* Actions */}
                    <td className="px-5 py-4 text-right">
                      <ActionsMenu bank={bank} onAction={handleAction} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Footer count */}
        {!loading && filtered.length > 0 && (
          <div className="px-5 py-3 border-t border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
            Showing {filtered.length} of {bloodBanks.length} blood banks
          </div>
        )}
      </div>

      {/* Delete confirmation */}
      {deleteTarget && (
        <DeleteConfirmModal
          bank={deleteTarget}
          onConfirm={() => {
            onDelete(deleteTarget.id);
            setDeleteTarget(null);
          }}
          onCancel={() => setDeleteTarget(null)}
        />
      )}
    </div>
  );
}

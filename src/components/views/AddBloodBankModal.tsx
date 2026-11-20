import { useEffect, useState, type FormEvent } from 'react';
import { X, Droplet, Building2, AtSign, User, Mail, Lock, Package } from 'lucide-react';
import type { NewBloodBankInput, SubscriptionStatus } from '@/types';

interface AddBloodBankModalProps {
  open: boolean;
  onClose: () => void;
  onCreate: (input: NewBloodBankInput) => Promise<boolean>;
}

interface FormState {
  org_name: string;
  subdomain: string;
  admin_name: string;
  admin_email: string;
  password: string;
  subscription_status: SubscriptionStatus;
}

const initialForm: FormState = {
  org_name: '',
  subdomain: '',
  admin_name: '',
  admin_email: '',
  password: '',
  subscription_status: 'free',
};

const packageOptions: { value: SubscriptionStatus; label: string; desc: string }[] = [
  { value: 'free', label: 'Free', desc: 'Up to 100 donors & 5 volunteers' },
  { value: 'trial', label: 'Trial', desc: 'Up to 500 donors & 25 volunteers' },
  { value: 'pro', label: 'Pro', desc: 'Unlimited donors & volunteers' },
];

export function AddBloodBankModal({ open, onClose, onCreate }: AddBloodBankModalProps) {
  const [form, setForm] = useState<FormState>(initialForm);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [subdomainError, setSubdomainError] = useState(false);

  useEffect(() => {
    if (!open) {
      setForm(initialForm);
      setError(null);
      setSubdomainError(false);
      setSubmitting(false);
    }
  }, [open]);

  if (!open) return null;

  const validateSubdomain = (value: string) => {
    const cleaned = value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    setSubdomainError(cleaned.length < 3 && cleaned.length > 0);
    return cleaned;
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!form.org_name || !form.subdomain || !form.admin_name || !form.admin_email || !form.password) {
      setError('Please fill in all required fields.');
      return;
    }
    if (form.subdomain.length < 3) {
      setError('Subdomain must be at least 3 characters.');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.admin_email)) {
      setError('Please enter a valid email address.');
      return;
    }
    if (form.password.length < 6) {
      setError('Password must be at least 6 characters.');
      return;
    }

    setSubmitting(true);
    const success = await onCreate({
      org_name: form.org_name,
      subdomain: form.subdomain,
      admin_name: form.admin_name,
      admin_email: form.admin_email,
      password: form.password,
      subscription_status: form.subscription_status,
    });

    if (success) {
      onClose();
    } else {
      setError('Failed to create blood bank. The subdomain may already be taken.');
    }
    setSubmitting(false);
  };

  const inputClass = 'w-full pl-10 pr-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm animate-fade-in" onClick={onClose} />

      <div className="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto scrollbar-thin animate-slide-up">
        {/* Header */}
        <div className="sticky top-0 bg-white dark:bg-gray-900 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 z-10">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center shadow-lg shadow-brand-500/20">
              <Droplet className="w-5 h-5 text-white" fill="white" />
            </div>
            <div>
              <h3 className="font-bold text-gray-900 dark:text-white">Add New Blood Bank</h3>
              <p className="text-xs text-gray-500 dark:text-gray-400">Provision a new tenant organization</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {/* Org name */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Organization Name</label>
            <div className="relative">
              <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                type="text"
                value={form.org_name}
                onChange={(e) => setForm({ ...form, org_name: e.target.value })}
                placeholder="e.g. City Health Blood Bank"
                className={inputClass}
              />
            </div>
          </div>

          {/* Subdomain */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subdomain Prefix</label>
            <div className="relative">
              <AtSign className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                type="text"
                value={form.subdomain}
                onChange={(e) => setForm({ ...form, subdomain: validateSubdomain(e.target.value) })}
                placeholder="cityhealth"
                className={`${inputClass} ${subdomainError ? 'border-red-500' : ''}`}
              />
            </div>
            <p className="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
              URL: <span className="font-mono text-brand-600 dark:text-brand-400">{form.subdomain || 'name'}.bloodapp.com</span>
            </p>
          </div>

          {/* Admin name */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin Name</label>
            <div className="relative">
              <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                type="text"
                value={form.admin_name}
                onChange={(e) => setForm({ ...form, admin_name: e.target.value })}
                placeholder="John Carter"
                className={inputClass}
              />
            </div>
          </div>

          {/* Admin email */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Admin Email</label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                type="email"
                value={form.admin_email}
                onChange={(e) => setForm({ ...form, admin_email: e.target.value })}
                placeholder="admin@bloodbank.org"
                className={inputClass}
              />
            </div>
          </div>

          {/* Password */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                type="password"
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                placeholder="Minimum 6 characters"
                className={inputClass}
              />
            </div>
          </div>

          {/* Package type */}
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <span className="flex items-center gap-1.5">
                <Package className="w-4 h-4" />
                Initial Package Type
              </span>
            </label>
            <div className="grid grid-cols-3 gap-2.5">
              {packageOptions.map((opt) => (
                <button
                  key={opt.value}
                  type="button"
                  onClick={() => setForm({ ...form, subscription_status: opt.value })}
                  className={`
                    relative px-3 py-3 rounded-xl border text-left transition-all
                    ${form.subscription_status === opt.value
                      ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/30 ring-2 ring-brand-500/20'
                      : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                    }
                  `}
                >
                  <span className={`block text-sm font-bold ${form.subscription_status === opt.value ? 'text-brand-700 dark:text-brand-300' : 'text-gray-900 dark:text-white'}`}>
                    {opt.label}
                  </span>
                  <span className="block text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-tight">{opt.desc}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Error */}
          {error && (
            <div className="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-sm text-red-600 dark:text-red-400">
              {error}
            </div>
          )}

          {/* Actions */}
          <div className="flex gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submitting}
              className="flex-1 px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 disabled:opacity-60 text-white text-sm font-medium shadow-lg shadow-brand-500/20 transition-all hover:shadow-brand-500/30 active:scale-95 disabled:active:scale-100"
            >
              {submitting ? 'Creating...' : 'Provision Tenant'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

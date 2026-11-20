import { useEffect, useState } from 'react';
import { Save, Package, Users, HandHeart, Check, AlertCircle } from 'lucide-react';
import type { PackageLimit, PackageType } from '@/types';

interface PackageLimitsViewProps {
  limits: PackageLimit[];
  loading: boolean;
  onUpdate: (
    packageType: PackageType,
    updates: { max_donors: number | null; max_volunteers: number | null },
  ) => Promise<boolean>;
}

interface LimitFormState {
  max_donors: string;
  max_volunteers: string;
}

const packageMeta: Record<PackageType, { label: string; color: string; bg: string; ring: string }> = {
  free: { label: 'Free', color: 'text-gray-700 dark:text-gray-300', bg: 'bg-gray-100 dark:bg-gray-800', ring: 'ring-gray-400' },
  trial: { label: 'Trial', color: 'text-amber-700 dark:text-amber-300', bg: 'bg-amber-100 dark:bg-amber-900/30', ring: 'ring-amber-400' },
  pro: { label: 'Pro', color: 'text-brand-700 dark:text-brand-300', bg: 'bg-brand-100 dark:bg-brand-950/30', ring: 'ring-brand-500' },
};

export function PackageLimitsView({ limits, loading, onUpdate }: PackageLimitsViewProps) {
  const [forms, setForms] = useState<Record<PackageType, LimitFormState>>({
    free: { max_donors: '', max_volunteers: '' },
    trial: { max_donors: '', max_volunteers: '' },
    pro: { max_donors: '', max_volunteers: '' },
  });
  const [saving, setSaving] = useState<PackageType | null>(null);
  const [savedPkg, setSavedPkg] = useState<PackageType | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!loading && limits.length > 0) {
      setForms({
        free: buildForm(limits, 'free'),
        trial: buildForm(limits, 'trial'),
        pro: buildForm(limits, 'pro'),
      });
    }
  }, [limits, loading]);

  const buildForm = (arr: PackageLimit[], type: PackageType): LimitFormState => {
    const limit = arr.find((l) => l.package_type === type);
    return {
      max_donors: limit?.max_donors?.toString() ?? '',
      max_volunteers: limit?.max_volunteers?.toString() ?? '',
    };
  };

  const handleSave = async (type: PackageType) => {
    setError(null);
    setSaving(type);
    const form = forms[type];
    const parseOrNull = (val: string): number | null => {
      const trimmed = val.trim();
      if (trimmed === '' || trimmed === '0') return null;
      const n = parseInt(trimmed, 10);
      return isNaN(n) ? null : n;
    };
    const success = await onUpdate(type, {
      max_donors: parseOrNull(form.max_donors),
      max_volunteers: parseOrNull(form.max_volunteers),
    });
    if (success) {
      setSavedPkg(type);
      setTimeout(() => setSavedPkg(null), 2500);
    } else {
      setError(`Failed to update ${packageMeta[type].label} limits.`);
    }
    setSaving(null);
  };

  if (loading) {
    return (
      <div className="p-12 text-center">
        <div className="inline-block w-8 h-8 border-3 border-brand-500 border-t-transparent rounded-full animate-spin" />
        <p className="text-sm text-gray-400 dark:text-gray-500 mt-3">Loading package limits...</p>
      </div>
    );
  }

  const packageTypes: PackageType[] = ['free', 'trial', 'pro'];

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Info banner */}
      <div className="flex items-start gap-3 p-4 rounded-xl bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900">
        <AlertCircle className="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
        <p className="text-sm text-blue-700 dark:text-blue-300">
          Configure the maximum number of donors and volunteers each package tier allows. Leave a field empty for unlimited access. Changes apply to all new and existing tenants immediately.
        </p>
      </div>

      {/* Package cards */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {packageTypes.map((type) => {
          const meta = packageMeta[type];
          const form = forms[type];
          const isSaving = saving === type;
          const isSaved = savedPkg === type;

          return (
            <div key={type} className="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
              {/* Card header */}
              <div className="flex items-center justify-between mb-5">
                <div className="flex items-center gap-3">
                  <div className={`w-10 h-10 rounded-xl ${meta.bg} flex items-center justify-center`}>
                    <Package className={`w-5 h-5 ${meta.color}`} />
                  </div>
                  <div>
                    <h3 className={`font-bold ${meta.color}`}>{meta.label} Package</h3>
                    <p className="text-xs text-gray-500 dark:text-gray-400">Tenant limits</p>
                  </div>
                </div>
                {isSaved && (
                  <div className="flex items-center gap-1 text-xs font-semibold text-green-600 dark:text-green-400 animate-fade-in">
                    <Check className="w-4 h-4" />
                    Saved
                  </div>
                )}
              </div>

              {/* Fields */}
              <div className="space-y-4">
                {/* Max donors */}
                <div>
                  <label className="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    <Users className="w-4 h-4 text-gray-400" />
                    Max Donors
                  </label>
                  <input
                    type="number"
                    min={0}
                    value={form.max_donors}
                    onChange={(e) =>
                      setForms({ ...forms, [type]: { ...form, max_donors: e.target.value } })
                    }
                    placeholder="Unlimited"
                    className="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all tabular-nums"
                  />
                </div>

                {/* Max volunteers */}
                <div>
                  <label className="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    <HandHeart className="w-4 h-4 text-gray-400" />
                    Max Volunteers
                  </label>
                  <input
                    type="number"
                    min={0}
                    value={form.max_volunteers}
                    onChange={(e) =>
                      setForms({ ...forms, [type]: { ...form, max_volunteers: e.target.value } })
                    }
                    placeholder="Unlimited"
                    className="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all tabular-nums"
                  />
                </div>
              </div>

              {/* Save button */}
              <button
                onClick={() => handleSave(type)}
                disabled={isSaving}
                className="w-full mt-5 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 disabled:opacity-60 text-white text-sm font-medium shadow-lg shadow-brand-500/20 transition-all active:scale-95 disabled:active:scale-100"
              >
                <Save className="w-4 h-4" />
                {isSaving ? 'Saving...' : 'Save Changes'}
              </button>
            </div>
          );
        })}
      </div>

      {/* Error */}
      {error && (
        <div className="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-sm text-red-600 dark:text-red-400">
          {error}
        </div>
      )}
    </div>
  );
}

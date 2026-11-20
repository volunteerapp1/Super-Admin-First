import type { SubscriptionStatus } from '@/types';

const config: Record<SubscriptionStatus, { label: string; classes: string; dot: string }> = {
  free: {
    label: 'Free',
    classes: 'bg-gray-100 text-gray-700 dark:bg-gray-700/40 dark:text-gray-300',
    dot: 'bg-gray-400',
  },
  trial: {
    label: 'Trial',
    classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    dot: 'bg-amber-500',
  },
  pro: {
    label: 'Pro',
    classes: 'bg-brand-100 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300',
    dot: 'bg-brand-500',
  },
};

export function SubscriptionBadge({ status }: { status: SubscriptionStatus }) {
  const c = config[status];
  return (
    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${c.classes}`}>
      <span className={`w-1.5 h-1.5 rounded-full ${c.dot}`} />
      {c.label}
    </span>
  );
}

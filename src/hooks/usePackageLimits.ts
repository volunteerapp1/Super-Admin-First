import { useCallback, useEffect, useState } from 'react';
import { supabase } from '@/lib/supabase';
import type { PackageLimit, PackageLimitInput, PackageType } from '@/types';

export function usePackageLimits() {
  const [limits, setLimits] = useState<PackageLimit[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    setError(null);
    const { data, error: err } = await supabase
      .from('package_limits')
      .select('*')
      .order('package_type', { ascending: true });

    if (err) {
      setError(err.message);
    } else {
      setLimits(data as PackageLimit[]);
    }
    setLoading(false);
  }, []);

  useEffect(() => {
    fetchAll();
  }, [fetchAll]);

  const updateLimit = async (
    packageType: PackageType,
    updates: { max_donors: number | null; max_volunteers: number | null },
  ): Promise<boolean> => {
    const { error: err } = await supabase
      .from('package_limits')
      .update({
        max_donors: updates.max_donors,
        max_volunteers: updates.max_volunteers,
      })
      .eq('package_type', packageType);

    if (err) {
      setError(err.message);
      return false;
    }

    setLimits((prev) =>
      prev.map((l) =>
        l.package_type === packageType
          ? { ...l, max_donors: updates.max_donors, max_volunteers: updates.max_volunteers }
          : l,
      ),
    );
    return true;
  };

  const getLimit = (packageType: PackageType): PackageLimit | undefined =>
    limits.find((l) => l.package_type === packageType);

  return {
    limits,
    loading,
    error,
    refresh: fetchAll,
    updateLimit,
    getLimit,
  };
}

export type { PackageLimitInput };

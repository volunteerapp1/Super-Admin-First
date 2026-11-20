import { useCallback, useEffect, useState } from 'react';
import { supabase } from '@/lib/supabase';
import type { BloodBank, NewBloodBankInput, SubscriptionStatus } from '@/types';

export function useBloodBanks() {
  const [bloodBanks, setBloodBanks] = useState<BloodBank[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    setError(null);
    const { data, error: err } = await supabase
      .from('blood_banks')
      .select('*')
      .order('created_at', { ascending: false });

    if (err) {
      setError(err.message);
    } else {
      setBloodBanks(data as BloodBank[]);
    }
    setLoading(false);
  }, []);

  useEffect(() => {
    fetchAll();
  }, [fetchAll]);

  const createBloodBank = async (input: NewBloodBankInput): Promise<BloodBank | null> => {
    const insertData = {
      org_name: input.org_name,
      subdomain: input.subdomain.toLowerCase().trim(),
      admin_name: input.admin_name,
      admin_email: input.admin_email,
      subscription_status: input.subscription_status,
      is_active: true,
      donor_count: 0,
      volunteer_count: 0,
    };

    const { data, error: err } = await supabase
      .from('blood_banks')
      .insert(insertData)
      .select()
      .single();

    if (err) {
      setError(err.message);
      return null;
    }

    const newBank = data as BloodBank;
    setBloodBanks((prev) => [newBank, ...prev]);
    return newBank;
  };

  const updateSubscription = async (id: string, status: SubscriptionStatus): Promise<boolean> => {
    const { error: err } = await supabase
      .from('blood_banks')
      .update({ subscription_status: status })
      .eq('id', id);

    if (err) {
      setError(err.message);
      return false;
    }

    setBloodBanks((prev) =>
      prev.map((b) => (b.id === id ? { ...b, subscription_status: status } : b)),
    );
    return true;
  };

  const toggleActive = async (id: string, active: boolean): Promise<boolean> => {
    const { error: err } = await supabase
      .from('blood_banks')
      .update({ is_active: active })
      .eq('id', id);

    if (err) {
      setError(err.message);
      return false;
    }

    setBloodBanks((prev) =>
      prev.map((b) => (b.id === id ? { ...b, is_active: active } : b)),
    );
    return true;
  };

  const deleteBloodBank = async (id: string): Promise<boolean> => {
    const { error: err } = await supabase
      .from('blood_banks')
      .delete()
      .eq('id', id);

    if (err) {
      setError(err.message);
      return false;
    }

    setBloodBanks((prev) => prev.filter((b) => b.id !== id));
    return true;
  };

  return {
    bloodBanks,
    loading,
    error,
    refresh: fetchAll,
    createBloodBank,
    updateSubscription,
    toggleActive,
    deleteBloodBank,
  };
}

export type SubscriptionStatus = 'free' | 'trial' | 'pro';
export type PackageType = 'free' | 'trial' | 'pro';

export interface BloodBank {
  id: string;
  org_name: string;
  subdomain: string;
  admin_name: string;
  admin_email: string;
  subscription_status: SubscriptionStatus;
  is_active: boolean;
  donor_count: number;
  volunteer_count: number;
  created_at: string;
  updated_at: string;
}

export interface PackageLimit {
  id: string;
  package_type: PackageType;
  max_donors: number | null;
  max_volunteers: number | null;
  updated_at: string;
}

export interface NewBloodBankInput {
  org_name: string;
  subdomain: string;
  admin_name: string;
  admin_email: string;
  password: string;
  subscription_status: SubscriptionStatus;
}

export interface PackageLimitInput {
  package_type: PackageType;
  max_donors: number | null;
  max_volunteers: number | null;
}

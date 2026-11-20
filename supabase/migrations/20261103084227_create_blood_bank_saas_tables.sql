/*
# Create blood_banks and package_limits tables for Blood Bank SaaS Super Admin Dashboard

## Overview
This migration creates the core schema for a multi-tenant blood bank SaaS platform's
Super Admin dashboard. It provisions two tables: one for tenant (blood bank) records
and one for global package limit configuration.

## New Tables

### 1. `blood_banks`
Stores information about each tenant organization (blood bank) on the platform.
- `id` (uuid, primary key)
- `org_name` (text, not null) — Organization display name
- `subdomain` (text, unique, not null) — Subdomain prefix (e.g. "cityhealth" → cityhealth.bloodapp.com)
- `admin_name` (text, not null) — Name of the tenant admin
- `admin_email` (text, not null) — Email of the tenant admin
- `subscription_status` (text, not null, default 'free') — One of: 'free', 'trial', 'pro'
- `is_active` (boolean, not null, default true) — Whether the tenant account is active
- `donor_count` (integer, not null, default 0) — Current number of donors in the tenant
- `volunteer_count` (integer, not null, default 0) — Current number of volunteers in the tenant
- `created_at` (timestamptz, default now())
- `updated_at` (timestamptz, default now())

### 2. `package_limits`
Stores global package limit configuration (single set of rows for free/trial/pro).
- `id` (uuid, primary key)
- `package_type` (text, unique, not null) — One of: 'free', 'trial', 'pro'
- `max_donors` (integer) — Maximum donors allowed (null = unlimited)
- `max_volunteers` (integer) — Maximum volunteers allowed (null = unlimited)
- `updated_at` (timestamptz, default now())

## Security
- Both tables have RLS enabled.
- Both tables grant full CRUD to `anon` and `authenticated` since this is a Super Admin
  dashboard with no per-user auth (the admin panel is a shared tool). Data is intentionally
  accessible to the panel's API client.
- All policies use `USING (true)` / `WITH CHECK (true)` because the data is shared admin
  configuration, not per-user private data.

## Notes
1. The `blood_banks` table uses `gen_random_uuid()` for IDs — no sequence grants needed.
2. `package_limits` is seeded with default rows for 'free', 'trial', and 'pro' packages.
3. A trigger updates `updated_at` automatically on row modification for `blood_banks`.
*/

-- ============================================
-- blood_banks table
-- ============================================
CREATE TABLE IF NOT EXISTS blood_banks (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  org_name text NOT NULL,
  subdomain text UNIQUE NOT NULL,
  admin_name text NOT NULL,
  admin_email text NOT NULL,
  subscription_status text NOT NULL DEFAULT 'free' CHECK (subscription_status IN ('free', 'trial', 'pro')),
  is_active boolean NOT NULL DEFAULT true,
  donor_count integer NOT NULL DEFAULT 0,
  volunteer_count integer NOT NULL DEFAULT 0,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

ALTER TABLE blood_banks ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT, UPDATE, DELETE ON blood_banks TO anon, authenticated;

DROP POLICY IF EXISTS "anon_select_blood_banks" ON blood_banks;
CREATE POLICY "anon_select_blood_banks" ON blood_banks FOR SELECT
  TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "anon_insert_blood_banks" ON blood_banks;
CREATE POLICY "anon_insert_blood_banks" ON blood_banks FOR INSERT
  TO anon, authenticated WITH CHECK (true);

DROP POLICY IF EXISTS "anon_update_blood_banks" ON blood_banks;
CREATE POLICY "anon_update_blood_banks" ON blood_banks FOR UPDATE
  TO anon, authenticated USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "anon_delete_blood_banks" ON blood_banks;
CREATE POLICY "anon_delete_blood_banks" ON blood_banks FOR DELETE
  TO anon, authenticated USING (true);

-- ============================================
-- package_limits table
-- ============================================
CREATE TABLE IF NOT EXISTS package_limits (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  package_type text UNIQUE NOT NULL CHECK (package_type IN ('free', 'trial', 'pro')),
  max_donors integer,
  max_volunteers integer,
  updated_at timestamptz DEFAULT now()
);

ALTER TABLE package_limits ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT, UPDATE, DELETE ON package_limits TO anon, authenticated;

DROP POLICY IF EXISTS "anon_select_package_limits" ON package_limits;
CREATE POLICY "anon_select_package_limits" ON package_limits FOR SELECT
  TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "anon_insert_package_limits" ON package_limits;
CREATE POLICY "anon_insert_package_limits" ON package_limits FOR INSERT
  TO anon, authenticated WITH CHECK (true);

DROP POLICY IF EXISTS "anon_update_package_limits" ON package_limits;
CREATE POLICY "anon_update_package_limits" ON package_limits FOR UPDATE
  TO anon, authenticated USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "anon_delete_package_limits" ON package_limits;
CREATE POLICY "anon_delete_package_limits" ON package_limits FOR DELETE
  TO anon, authenticated USING (true);

-- ============================================
-- Seed default package limits
-- ============================================
INSERT INTO package_limits (package_type, max_donors, max_volunteers) VALUES
  ('free', 100, 5),
  ('trial', 500, 25),
  ('pro', NULL, NULL)
ON CONFLICT (package_type) DO NOTHING;

-- ============================================
-- Seed sample blood banks for demo
-- ============================================
INSERT INTO blood_banks (org_name, subdomain, admin_name, admin_email, subscription_status, is_active, donor_count, volunteer_count) VALUES
  ('City Health Blood Bank', 'cityhealth', 'John Carter', 'john@cityhealth.org', 'pro', true, 1240, 48),
  ('Red Cross Metro', 'redcrossmetro', 'Sarah Williams', 'sarah@redcross.org', 'pro', true, 980, 32),
  ('Community Blood Center', 'communityblood', 'Michael Reyes', 'mike@community.org', 'trial', true, 320, 12),
  ('St. Mary Hospital Blood Bank', 'stmary', 'Emily Chen', 'emily@stmary.org', 'free', true, 45, 3),
  ('Regional Blood Services', 'regionalblood', 'David Okafor', 'david@regional.org', 'trial', true, 210, 10),
  ('Northside Blood Bank', 'northside', 'Jessica Patel', 'jessica@northside.org', 'pro', false, 750, 28),
  ('Lifeline Blood Trust', 'lifeline', 'Robert Kim', 'robert@lifeline.org', 'free', true, 78, 4),
  ('Green Valley Blood Bank', 'greenvalley', 'Anna Martinez', 'anna@greenvalley.org', 'trial', true, 410, 18)
ON CONFLICT (subdomain) DO NOTHING;

-- ============================================
-- Auto-update updated_at trigger
-- ============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = now();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_blood_banks_updated_at ON blood_banks;
CREATE TRIGGER trigger_blood_banks_updated_at
  BEFORE UPDATE ON blood_banks
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS trigger_package_limits_updated_at ON package_limits;
CREATE TRIGGER trigger_package_limits_updated_at
  BEFORE UPDATE ON package_limits
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

# Project memory

## Product intent & audience

- Enterprise-level multi-tenant Blood Bank SaaS (PHP 8.1+ / PDO / MySQL/MariaDB).
- Multi-phase build; current scope is Phase 1: DB schema, Super Admin panel, foundation security, public landing page.
- Tenants are independent blood bank organizations, provisioned/managed by a Super Admin.
- Public visitors can search donors, browse active blood banks, and self-request free org registration (feeds Super Admin review queue).
- Admins: Super Admins (platform-level, OTP login) and tenant admins (per-organization).

## Brand personality & voice

- Administrative/enterprise tooling tone: clear, concise, operational.
- Public landing page expected to be modern and approachable for donor search/registration.
- No explicit brand assets provided yet.

## Visual design & UX direction

- Public landing page: responsive, donor search (name/phone + blood group filter), active blood bank directory with contact info, self-registration form.
- Super Admin UI: dashboard with live counts, pending-registration queue (approve/reject), tenant list with per-tenant SMS & Wallet dialog/modal.
- SMS wallet indicators color-coded: red when empty, amber when low.
- Two-step Super Admin login: password verification then 6-digit OTP gate before dashboard access.
- OTP currently shown on login screen for testing (SMS/email delivery not yet wired).
- Tenant donor/volunteer metrics detected via per-tenant `donors`/`volunteers` tables by subdomain; sync to `blood_banks` counts.

## Decisions, constraints & rationale

- MariaDB 11.4 compatibility; SQL file must import directly into cPanel phpMyAdmin — no `CREATE DATABASE`/`USE` statements.
- Secure PDO connection with utf8mb4; config via `config/db.php` or `BB_DB_*` env vars.
- Passwords hashed (bcrypt via `password_hash`/`password_verify`); no hardcoded placeholder hashes (generated via seed script).
- Added `registration_requests` table beyond specified four tables to support landing-page sign-ups and Super Admin approval flow.
- Seeded `package_limits`: free 100 donors/5 volunteers, trial 500/25, pro NULL/NULL.
- Seeded Super Admin (`superadmin` / `Admin@1234`) and three demo tenants (`Tenant@1234`); credentials must be changed before production.
- Audit logging for Super Admin actions.
- Per-tenant SMS config: enable toggle, API key, sender ID, Unicode flag, prepaid wallet balance with recharge.
- Subdomain-based tenant isolation implied for future tenant portals.
- SQL import could not be live-tested in sandbox (no DB server available); PHP files passed syntax checks.

## Open questions & assumptions

- OTP delivery channel (SMS/email) not implemented; assumed acceptable to display OTP during Phase 1 testing.
- Tenant `donors`/`volunteers` tables assumed created in Phase 2; metrics sync depends on their existence.
- Real cPanel DB credentials assumed to be supplied before import/run.
- Production credential rotation assumed handled by operator.
- Subdomain routing/tenant portal implementation deferred to later phases.
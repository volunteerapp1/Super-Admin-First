-- =====================================================================
-- BloodBank SaaS - Phase 1 Schema
-- Compatible with MariaDB 11.4 / MySQL 8 (utf8mb4 full unicode)
-- Import directly into cPanel phpMyAdmin (no CREATE DATABASE / USE)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table: blood_banks  (one row per tenant)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blood_banks` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_name`      VARCHAR(190) NOT NULL,
  `subdomain`     VARCHAR(100) NOT NULL,
  `admin_name`    VARCHAR(150) NOT NULL,
  `admin_email`   VARCHAR(190) NOT NULL,
  `admin_phone`   VARCHAR(25)  NOT NULL,
  `admin_password` VARCHAR(255) NOT NULL,
  `package_type`  ENUM('free','trial','pro') NOT NULL DEFAULT 'free',
  `subscription_status` ENUM('free','trial','pro') NOT NULL DEFAULT 'free',
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sms_enabled`   TINYINT(1) NOT NULL DEFAULT 0,
  `sms_api_key`   VARCHAR(255) NULL DEFAULT NULL,
  `sms_sender_id` VARCHAR(100) NULL DEFAULT NULL,
  `sms_unicode`   TINYINT(1) NOT NULL DEFAULT 1,
  `sms_balance`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `donor_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `volunteer_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blood_banks_subdomain` (`subdomain`),
  UNIQUE KEY `uq_blood_banks_admin_email` (`admin_email`),
  KEY `idx_blood_banks_active` (`is_active`),
  KEY `idx_blood_banks_package` (`subscription_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: package_limits  (per-package caps, pro = unlimited via NULL)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `package_limits` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_type`   ENUM('free','trial','pro') NOT NULL,
  `max_donors`     INT UNSIGNED NULL DEFAULT NULL,
  `max_volunteers` INT UNSIGNED NULL DEFAULT NULL,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_package_limits_type` (`package_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `package_limits` (`package_type`,`max_donors`,`max_volunteers`) VALUES
  ('free',  100,  5),
  ('trial', 500,  25),
  ('pro',   NULL, NULL)
ON DUPLICATE KEY UPDATE `package_type` = VALUES(`package_type`);

-- ---------------------------------------------------------------------
-- Table: admins  (Super Admins - OTP protected)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(100) NOT NULL,
  `email`        VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `otp_secret`   VARCHAR(64) NOT NULL,
  `role`         ENUM('super_admin','staff') NOT NULL DEFAULT 'super_admin',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Super Admin: username `superadmin`, password `Admin@1234`
-- OTP secret is a Base32 TOTP secret. Change it in production and enrol
-- it in your authenticator app (or rotate it from the panel after login).
-- Login falls back to a dev OTP of the last 6 digits of the secret when
-- SMS/Email OTP delivery is not yet configured - see includes/auth.php.
INSERT INTO `admins` (`username`,`email`,`password_hash`,`otp_secret`,`role`) VALUES
  ('superadmin','admin@bloodbank.local','$2y$12$zc.zpCioNOduSbSLo0AWbeVsizJxEVNnKH1u4oo5BrTav1.8MEv1q',
   'JBSWY3DPEHPK3PXP','super_admin')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- ---------------------------------------------------------------------
-- Table: audit_logs  (who did what, when, from where)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type`  ENUM('super_admin','tenant_admin') NOT NULL,
  `user_id`    INT UNSIGNED NULL DEFAULT NULL,
  `username`   VARCHAR(190) NULL DEFAULT NULL,
  `action`     VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user` (`user_type`,`user_id`),
  KEY `idx_audit_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: registration_requests  (self-signup requests from landing page)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_requests` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_name`     VARCHAR(190) NOT NULL,
  `contact_name` VARCHAR(150) NOT NULL,
  `email`        VARCHAR(190) NOT NULL,
  `phone`        VARCHAR(25)  NOT NULL,
  `city`         VARCHAR(120) NULL DEFAULT NULL,
  `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_at`  TIMESTAMP NULL DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reg_requests_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo tenants for the panel/landing page (safe to delete after review)
-- Tenant admin password for all three demo rows is `Tenant@1234`
INSERT INTO `blood_banks`
  (`org_name`,`subdomain`,`admin_name`,`admin_email`,`admin_phone`,
   `admin_password`,`package_type`,`subscription_status`,`is_active`,
   `sms_enabled`,`sms_api_key`,`sms_sender_id`,`sms_unicode`,`sms_balance`,
   `donor_count`,`volunteer_count`)
VALUES
  ('City Central Blood Bank','city-central','Ayesha Khan','ayesha@citycentral.test','+8801711000001',
   '$2y$12$AJWz/E8v2adZUwT/FNe3EueTumkpNvJsTf4WW0scscaz3soQYjP4y','free','free',1,0,NULL,NULL,1,0.00,64,3),
  ('Green Life Blood Center','green-life','Rafiq Ahmed','rafiq@greenlife.test','+8801711000002',
   '$2y$12$AJWz/E8v2adZUwT/FNe3EueTumkpNvJsTf4WW0scscaz3soQYjP4y','trial','trial',1,1,'demo-api-key-001','8809876543222',1,45.50,210,12),
  ('Lifeline Trust Bank','lifeline-trust','Nusrat Jahan','nusrat@lifeline.test','+8801711000003',
   '$2y$12$AJWz/E8v2adZUwT/FNe3EueTumkpNvJsTf4WW0scscaz3soQYjP4y','pro','pro',1,1,'demo-api-key-002','8809876543223',1,1200.00,1450,88)
ON DUPLICATE KEY UPDATE `org_name` = VALUES(`org_name`);

SET FOREIGN_KEY_CHECKS = 1;

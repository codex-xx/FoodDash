-- Security Auditing and Intrusion Detection schema for FoodDash (MySQL)
-- Compatible with XAMPP MySQL/MariaDB

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `ip_address_hash` CHAR(64) NULL,
  `device_hash` CHAR(64) NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `event_description` TEXT NOT NULL,
  `severity` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `event_meta_encrypted` LONGTEXT NULL,
  `event_meta_hash` CHAR(64) NULL,
  `prev_hash` CHAR(64) NULL,
  `record_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user_id` (`user_id`),
  KEY `idx_audit_logs_event_type` (`event_type`),
  KEY `idx_audit_logs_severity` (`severity`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  KEY `idx_audit_logs_ip_address_hash` (`ip_address_hash`),
  KEY `idx_audit_logs_device_hash` (`device_hash`),
  KEY `idx_audit_logs_event_meta_hash` (`event_meta_hash`),
  KEY `idx_audit_logs_prev_hash` (`prev_hash`),
  KEY `idx_audit_logs_record_hash` (`record_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `intrusion_alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_log_id` BIGINT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `ip_address_hash` CHAR(64) NULL,
  `alert_type` VARCHAR(80) NOT NULL,
  `status` ENUM('open','acknowledged','resolved') NOT NULL DEFAULT 'open',
  `severity` ENUM('medium','high','critical') NOT NULL DEFAULT 'high',
  `alert_message` TEXT NOT NULL,
  `trigger_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `triggered_at` DATETIME NOT NULL,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_intrusion_alerts_type` (`alert_type`),
  KEY `idx_intrusion_alerts_status` (`status`),
  KEY `idx_intrusion_alerts_severity` (`severity`),
  KEY `idx_intrusion_alerts_triggered_at` (`triggered_at`),
  KEY `idx_intrusion_alerts_ip_address_hash` (`ip_address_hash`),
  KEY `idx_intrusion_alerts_user_id` (`user_id`),
  CONSTRAINT `fk_intrusion_alerts_audit` FOREIGN KEY (`audit_log_id`) REFERENCES `audit_logs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address_hash` CHAR(64) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `blocked_at` DATETIME NOT NULL,
  `blocked_until` DATETIME NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_blocked_ips_hash` (`ip_address_hash`),
  KEY `idx_blocked_ips_active` (`is_active`),
  KEY `idx_blocked_ips_blocked_until` (`blocked_until`),
  KEY `idx_blocked_ips_blocked_at` (`blocked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

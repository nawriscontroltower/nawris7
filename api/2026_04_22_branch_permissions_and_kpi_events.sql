-- Branch RBAC + KPI events for API shipment processing
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS employee_branch_permissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_username VARCHAR(120) NOT NULL,
  branch_name VARCHAR(190) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_branch (employee_username, branch_name),
  KEY idx_employee_username (employee_username),
  KEY idx_branch_name (branch_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipment_kpi_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tracking_code VARCHAR(128) NOT NULL,
  employee_username VARCHAR(120) NOT NULL,
  old_status VARCHAR(64) NULL,
  new_status VARCHAR(64) NOT NULL,
  delay_days INT NULL,
  processed_at DATETIME NOT NULL,
  processing_minutes INT NOT NULL DEFAULT 0,
  source VARCHAR(32) NOT NULL DEFAULT 'api',
  metadata_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_kpi_employee_processed (employee_username, processed_at),
  KEY idx_kpi_tracking_code (tracking_code),
  KEY idx_kpi_new_status (new_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipment_sync_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_uuid CHAR(36) NOT NULL,
  payload_json JSON NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  available_at DATETIME NOT NULL,
  reserved_at DATETIME NULL,
  completed_at DATETIME NULL,
  last_error TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_job_uuid (job_uuid),
  KEY idx_jobs_status_available (status, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

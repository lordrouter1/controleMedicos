CREATE DATABASE IF NOT EXISTS controle_medicos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_medicos;

CREATE TABLE IF NOT EXISTS professionals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    company VARCHAR(150) NOT NULL,
    workload_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    cbo VARCHAR(20) NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_observations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professional_id INT NOT NULL,
    observation TEXT NOT NULL,
    hour_change DECIMAL(8,2) NOT NULL,
    observation_month CHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_observations_professional FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE,
    INDEX idx_professional_month (professional_id, observation_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_controls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professional_id INT NOT NULL,
    reference_month CHAR(7) NOT NULL,
    is_paid TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_professional_month (professional_id, reference_month),
    CONSTRAINT fk_monthly_professional FOREIGN KEY (professional_id) REFERENCES professionals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

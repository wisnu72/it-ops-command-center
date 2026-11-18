CREATE DATABASE IF NOT EXISTS it_ops_monitor;
USE it_ops_monitor;

-- 1. Tabel Daftar Layanan/Server yang Dipantau
CREATE TABLE monitored_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    host_ip VARCHAR(100) NOT NULL,
    port INT NOT NULL,
    status ENUM('UP', 'DOWN', 'WARNING') DEFAULT 'UP',
    latency_ms INT DEFAULT 0,
    last_checked DATETIME NULL
);

-- 2. Tabel Log Insiden & Penanganan SOP (Alert Handling)
CREATE TABLE incident_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    alert_message VARCHAR(255) NOT NULL,
    severity ENUM('CRITICAL', 'WARNING') NOT NULL,
    status ENUM('OPEN', 'RESOLVED') DEFAULT 'OPEN',
    sop_action_taken TEXT NULL,
    operator_shift VARCHAR(50) DEFAULT 'Morning Shift',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY (service_id) REFERENCES monitored_services(id) ON DELETE CASCADE
);

-- 3. Tabel Riwayat Backup Database
CREATE TABLE backup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(150) NOT NULL,
    file_size_kb DECIMAL(10,2) NOT NULL,
    backup_status ENUM('SUCCESS', 'FAILED') NOT NULL,
    created_by VARCHAR(50) DEFAULT 'IT Ops Engineer',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Data Awal untuk Simulasi Pemantauan
INSERT INTO monitored_services (service_name, host_ip, port) VALUES
('Core Web Server (Local Apache)', '127.0.0.1', 80),
('Primary Database (MySQL)', '127.0.0.1', 3306),
('Public DNS Gateway (Google)', '8.8.8.8', 53),
('Simulated Payment Gateway API', '127.0.0.1', 9999); -- Port mati untuk simulasi Alert
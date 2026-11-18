<?php
require_once '../config/database.php';

$stmt = $pdo->query("SELECT * FROM monitored_services");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$logFile = '../logs/app_system.log';

foreach ($services as $svc) {
    $startTime = microtime(true);
    // Mengecek koneksi TCP ke IP dan Port (timeout 2 detik)
    $connection = @fsockopen($svc['host_ip'], $svc['port'], $errno, $errstr, 2);
    $endTime = microtime(true);
    
    $latency = round(($endTime - $startTime) * 1000);

    if (is_resource($connection)) {
        fclose($connection);
        $status = ($latency > 500) ? 'WARNING' : 'UP';
        
        $update = $pdo->prepare("UPDATE monitored_services SET status = ?, latency_ms = ?, last_checked = NOW() WHERE id = ?");
        $update->execute([$status, $latency, $svc['id']]);
        
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] INFO: {$svc['service_name']} ({$svc['host_ip']}:{$svc['port']}) is UP ({$latency}ms)\n", FILE_APPEND);
    } else {
        $status = 'DOWN';
        $update = $pdo->prepare("UPDATE monitored_services SET status = ?, latency_ms = 0, last_checked = NOW() WHERE id = ?");
        $update->execute([$status, $svc['id']]);

        // Catat ke file log sistem
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERROR: {$svc['service_name']} ({$svc['host_ip']}:{$svc['port']}) unreachable - $errstr\n", FILE_APPEND);

        // Cek apakah sudah ada insiden OPEN untuk service ini agar tidak spam alert
        $checkIncident = $pdo->prepare("SELECT id FROM incident_logs WHERE service_id = ? AND status = 'OPEN'");
        $checkIncident->execute([$svc['id']]);

        if ($checkIncident->rowCount() === 0) {
            $msg = "Service {$svc['service_name']} ({$svc['host_ip']}:{$svc['port']}) DOWN. Error: $errstr";
            $insertIncident = $pdo->prepare("INSERT INTO incident_logs (service_id, alert_message, severity) VALUES (?, ?, 'CRITICAL')");
            $insertIncident->execute([$svc['id'], $msg]);
        }
    }
}

header("Location: ../index.php?msg=Health+check+selesai+dijalankan");
exit;
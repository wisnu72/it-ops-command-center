<?php
require_once '../config/database.php';

$backupDir = '../backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$fileName = 'backup_it_ops_' . date('Ymd_His') . '.sql';
$filePath = $backupDir . $fileName;

// Sesuaikan nama folder mysql-8.0.xx dengan yang ada di C:\laragon\bin\mysql\
$mysqldumpPath = 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe'; 

$command = "\"{$mysqldumpPath}\" --user=root --host=127.0.0.1 it_ops_monitor > \"{$filePath}\"";
exec($command, $output, $returnVar);

if ($returnVar === 0 && file_exists($filePath) && filesize($filePath) > 0) {
    $sizeKb = round(filesize($filePath) / 1024, 2);
    $stmt = $pdo->prepare("INSERT INTO backup_history (file_name, file_size_kb, backup_status) VALUES (?, ?, 'SUCCESS')");
    $stmt->execute([$fileName, $sizeKb]);
    $msg = "Backup+database+berhasil+($fileName)";
} else {
    $stmt = $pdo->prepare("INSERT INTO backup_history (file_name, file_size_kb, backup_status) VALUES (?, 0, 'FAILED')");
    $stmt->execute([$fileName]);
    $msg = "Backup+gagal.+Cek+path+mysqldump.exe";
}

header("Location: ../index.php?msg=" . $msg);
exit;
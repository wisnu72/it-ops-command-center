<?php
require_once 'config/database.php';

// Ambil data layanan
$services = $pdo->query("SELECT * FROM monitored_services ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Ambil data insiden (OPEN di atas)
$incidents = $pdo->query("
    SELECT i.*, s.service_name 
    FROM incident_logs i 
    JOIN monitored_services s ON i.service_id = s.id 
    ORDER BY FIELD(i.status, 'OPEN', 'RESOLVED'), i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil riwayat backup
$backups = $pdo->query("SELECT * FROM backup_history ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Baca 10 baris terakhir dari app_system.log
$logFile = 'logs/app_system.log';
$recentLogs = file_exists($logFile) ? array_slice(file($logFile), -10) : ["Belum ada aktivitas log."];

// Metrik dasar sistem Windows (Disk Space C:)
$freeDisk = round(disk_free_space("C:") / (1024 * 1024 * 1024), 2);
$totalDisk = round(disk_total_space("C:") / (1024 * 1024 * 1024), 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT-Ops Command Center | Monitoring & Incident Response</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 font-sans antialiased">

    <!-- Navbar -->
    <nav class="bg-slate-800 border-b border-slate-700 px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-emerald-400">IT-Ops Command Center</h1>
            <p class="text-xs text-slate-400">System Monitoring, Alert Handling & Backup Operations</p>
        </div>
        <div class="flex gap-3">
            <a href="actions/check_services.php" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Run Health Check (Ping/Port)
            </a>
            <a href="actions/backup_db.php" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Trigger DB Backup
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6 space-y-6">
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 text-emerald-200 px-4 py-3 rounded-lg text-sm">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Ringkasan Host Windows -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                <p class="text-xs text-slate-400 uppercase">Host OS Environment</p>
                <p class="text-lg font-semibold mt-1"><?= php_uname('s') . ' ' . php_uname('r') ?></p>
            </div>
            <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                <p class="text-xs text-slate-400 uppercase">Storage Availability (Drive C:)</p>
                <p class="text-lg font-semibold mt-1"><?= $freeDisk ?> GB Free <span class="text-xs text-slate-400">/ <?= $totalDisk ?> GB</span></p>
            </div>
            <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                <p class="text-xs text-slate-400 uppercase">Active Open Incidents</p>
                <?php $openCount = count(array_filter($incidents, fn($i) => $i['status'] === 'OPEN')); ?>
                <p class="text-lg font-semibold mt-1 <?= $openCount > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">
                    <?= $openCount ?> Alert(s) Need Action
                </p>
            </div>
        </div>

        <!-- Tabel Status Layanan & Port -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h2 class="font-semibold text-lg">Service & Network Port Status</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-700/50 text-slate-300">
                        <tr>
                            <th class="px-6 py-3">Service Name</th>
                            <th class="px-6 py-3">IP Address / Host</th>
                            <th class="px-6 py-3">Port</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Latency</th>
                            <th class="px-6 py-3">Last Checked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <?php foreach ($services as $s): ?>
                        <tr>
                            <td class="px-6 py-3 font-medium"><?= htmlspecialchars($s['service_name']) ?></td>
                            <td class="px-6 py-3 font-mono text-slate-300"><?= htmlspecialchars($s['host_ip']) ?></td>
                            <td class="px-6 py-3 font-mono"><?= $s['port'] ?></td>
                            <td class="px-6 py-3">
                                <?php if ($s['status'] === 'UP'): ?>
                                    <span class="bg-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-full text-xs font-semibold">UP</span>
                                <?php elseif ($s['status'] === 'WARNING'): ?>
                                    <span class="bg-amber-500/20 text-amber-400 px-2.5 py-1 rounded-full text-xs font-semibold">HIGH LATENCY</span>
                                <?php else: ?>
                                    <span class="bg-rose-500/20 text-rose-400 px-2.5 py-1 rounded-full text-xs font-semibold">DOWN</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-3"><?= $s['latency_ms'] ?> ms</td>
                            <td class="px-6 py-3 text-slate-400"><?= $s['last_checked'] ?? 'Belum dicek' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Incident & SOP Handling -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700">
                <h2 class="font-semibold text-lg">Incident Alerts & SOP Resolution Log</h2>
            </div>
            <div class="p-6 space-y-4">
                <?php if (empty($incidents)): ?>
                    <p class="text-sm text-slate-400">Tidak ada riwayat insiden tercatat.</p>
                <?php endif; ?>
                
                <?php foreach ($incidents as $inc): ?>
                    <div class="p-4 rounded-lg border <?= $inc['status'] === 'OPEN' ? 'bg-rose-950/30 border-rose-700' : 'bg-slate-700/30 border-slate-600' ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs font-bold px-2 py-0.5 rounded <?= $inc['status'] === 'OPEN' ? 'bg-rose-600 text-white' : 'bg-emerald-600 text-white' ?>">
                                    <?= $inc['status'] ?>
                                </span>
                                <span class="text-xs text-slate-400 ml-2"><?= $inc['created_at'] ?></span>
                                <p class="font-medium mt-2"><?= htmlspecialchars($inc['alert_message']) ?></p>
                            </div>
                        </div>

                        <?php if ($inc['status'] === 'OPEN'): ?>
                            <form action="actions/resolve_alert.php" method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                                <input type="hidden" name="incident_id" value="<?= $inc['id'] ?>">
                                <select name="operator_shift" class="bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm">
                                    <option value="Morning Shift (06:00-15:00)">Morning Shift</option>
                                    <option value="Afternoon Shift (14:00-23:00)">Afternoon Shift</option>
                                    <option value="Night Shift (22:00-07:00)">Night Shift</option>
                                </select>
                                <input type="text" name="sop_action" required placeholder="Catat langkah SOP / eskalasi yang dilakukan..." class="md:col-span-2 bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium px-4 py-2 rounded">
                                    Resolve Incident
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="text-xs text-slate-300 mt-2">
                                <strong>Handled by:</strong> <?= htmlspecialchars($inc['operator_shift']) ?> | 
                                <strong>SOP Action:</strong> <?= htmlspecialchars($inc['sop_action_taken']) ?> (Resolved: <?= $inc['resolved_at'] ?>)
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid Bawah: System Logs & Backup History -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Live Log Viewer -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h2 class="font-semibold text-lg mb-3">System Operational Logs (Tail -10)</h2>
                <div class="bg-slate-950 p-4 rounded-lg font-mono text-xs text-emerald-400 space-y-1 overflow-x-auto">
                    <?php foreach ($recentLogs as $line): ?>
                        <div><?= htmlspecialchars($line) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Database Backup History -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h2 class="font-semibold text-lg mb-3">Database Backup History (MySQL Dump)</h2>
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 border-b border-slate-700">
                        <tr>
                            <th class="pb-2">File Name</th>
                            <th class="pb-2">Size</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <?php foreach ($backups as $b): ?>
                        <tr>
                            <td class="py-2 font-mono"><?= htmlspecialchars($b['file_name']) ?></td>
                            <td class="py-2"><?= $b['file_size_kb'] ?> KB</td>
                            <td class="py-2 text-emerald-400 font-semibold"><?= $b['backup_status'] ?></td>
                            <td class="py-2 text-slate-400"><?= $b['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>
# DOKUMENTASI TEKNIS PROYEK & STANDAR OPERASIONAL (SOP)

**Nama Sistem:** IT-Ops Command Center (Server, Network & Database Monitoring System)  
**Disusun oleh:** Wisnu Suryo Nugroho  
**Teknologi:** PHP Native (PDO), MySQL, Tailwind CSS, Windows Server/Host Environment  

---

## 1. Ringkasan Eksekutif & Tujuan Sistem
**IT-Ops Command Center** adalah aplikasi pemantauan operasional infrastruktur TI terpusat yang dirancang untuk mendukung ketersediaan layanan kritis (*high availability*) 24/7, seperti pada lingkungan operasional perbankan. Sistem ini mengotomatisasi pengecekan kesehatan jaringan (*health check*), deteksi dini insiden (*alerting*), pencatatan tindakan pemulihan berbasis *Standard Operating Procedure* (SOP) antar-*shift*, serta pencadangan basis data (*database backup*) secara berkala.

### Kesesuaian dengan Fungsi Utama IT Operations:
* **Service & Network Availability Monitoring:** Memantau status konektivitas TCP/IP, ketersediaan port layanan penting (HTTP, MySQL, DNS, dan Payment Gateway API), serta waktu respons (*latency*).
* **Automated Alerting & Incident Management:** Mengubah kegagalan layanan (*service down*) menjadi tiket insiden aktif secara otomatis dan mencegah duplikasi peringatan (*alert fatigue*).
* **Shift Handover & SOP Compliance:** Mendokumentasikan setiap langkah mitigasi dan eskalasi oleh operator pada masing-masing jadwal *shift* (Pagi, Siang, Malam).
* **Log Auditing & Disaster Recovery:** Menyediakan pembaca log sistem secara *real-time* (*tail log*) serta eksekusi *backup* basis data MySQL ke dalam berkas `.sql` siap *restore*.

---

## 2. Arsitektur Sistem & Alur Kerja (*Operational Pipeline*)

Sistem bekerja dengan alur empat tahap yang saling terintegrasi:

1. **Probing Layer (`actions/check_services.php`):** Melakukan *socket connection* (`fsockopen`) ke setiap alamat IP dan port target dengan batas waktu (*timeout*) 2 detik untuk mengukur status (`UP`, `WARNING`, `DOWN`) dan latensi dalam milidetik (`ms`).
2. **Logging Layer (`logs/app_system.log`):** Setiap hasil pengecekan dicatat ke dalam berkas log datar (*flat-file log*) dengan format standar `[YYYY-MM-DD HH:MM:SS] [LEVEL]: [DETAIL]` untuk memudahkan audit dan penelusuran akar masalah (*root cause analysis*).
3. **Alerting & Ticketing Layer (`incident_logs`):** Apabila suatu layanan terdeteksi `DOWN`, sistem memeriksa apakah masih terdapat tiket berstatus `OPEN` untuk layanan tersebut. Jika belum ada, sistem menerbitkan tiket insiden baru dengan tingkat keparahan `CRITICAL`.
4. **Recovery & Backup Layer (`actions/resolve_alert.php` & `actions/backup_db.php`):** Operator mencatat langkah penyelesaian SOP untuk menutup tiket insiden menjadi `RESOLVED`, serta mengeksekusi utilitas `mysqldump` guna mengamankan integritas data.

---

## 3. Desain Basis Data (*Database Schema*)

Sistem menggunakan basis data relasional `it_ops_monitor` yang terdiri dari tiga tabel utama:

| Nama Tabel | Kolom Utama | Fungsi Operasional |
| :--- | :--- | :--- |
| `monitored_services` | `id`, `service_name`, `host_ip`, `port`, `status`, `latency_ms`, `last_checked` | Menyimpan daftar aset/layanan yang dipantau beserta status kesehatan dan latensi terakhir. |
| `incident_logs` | `id`, `service_id`, `alert_message`, `severity`, `status`, `sop_action_taken`, `operator_shift`, `created_at`, `resolved_at` | Mencatat riwayat gangguan sistem, status penanganan (`OPEN`/`RESOLVED`), petugas *shift*, dan tindakan SOP yang diambil. |
| `backup_history` | `id`, `file_name`, `file_size_kb`, `backup_status`, `created_by`, `created_at` | Mencatat jejak audit pencadangan database otomatis beserta ukuran berkas dan status keberhasilannya. |

---

## 4. Standard Operating Procedure (SOP) Operasional Harian

### A. SOP Pengecekan Rutin & Serah Terima Shift (*Shift Handover*)
Setiap pergantian jadwal operasional (**Morning Shift: 06.00–15.00**, **Afternoon Shift: 14.00–23.00**, **Night Shift: 22.00–07.00**), operator wajib menjalankan prosedur berikut:

1. **Verifikasi Awal Shift:** Menjalankan *Health Check* pada dashboard dan memastikan seluruh layanan inti berstatus **`UP`** dengan latensi di bawah ambang batas (`< 500 ms`).
2. **Pemeriksaan Kapasitas Host:** Memastikan sisa ruang penyimpanan (*Storage Availability*) pada server host berada dalam batas aman (`> 20%` kapasitas bebas).
3. **Audit System Logs:** Memeriksa 10 baris log terakhir pada panel *System Operational Logs* untuk mendeteksi anomali berulang sebelum terjadi kegagalan total.
4. **Serah Terima Tiket Terbuka:** Memastikan tidak ada tiket berstatus `OPEN` yang terlewat tanpa catatan eskalasi saat pergantian *shift*.

### B. SOP Penanganan Insiden (*Incident & Alert Handling*)
Jika muncul peringatan berstatus **`OPEN`** pada panel *Incident Alerts*:

1. **Identifikasi & Validasi (0–5 Menit):** Catat nama layanan, alamat IP, nomor port, dan pesan galat (*error message*) yang muncul pada tiket.
2. **Diagnosa Dasar L1:** Lakukan verifikasi jaringan dasar (`ping` ke IP host, pengecekan status *service/daemon* lokal, atau pemeriksaan port yang digunakan).
3. **Tindakan Pemulihan / Eskalasi (5–15 Menit):**
   * Jika gangguan berasal dari layanan lokal yang berhenti (*stopped*), lakukan *restart service* sesuai otorisasi.
   * Jika gangguan terjadi pada layanan pihak ketiga atau API eksternal (contoh: *Payment Gateway API port 9999*), segera lakukan eskalasi ke tim teknis Level 2 (*Backend/Network Engineer*).
4. **Penutupan Tiket (*Resolution*):** Pilih nama *shift* aktif, tuliskan kronologi tindakan penanganan secara jelas pada kolom *SOP Action*, dan klik **Resolve Incident** agar tercatat di basis data.

### C. SOP Pencadangan & Pemulihan Basis Data (*Backup & Restore*)
1. **Prosedur Backup:** Jalankan *Trigger DB Backup* pada akhir setiap *shift* atau sebelum dilakukan pemeliharaan sistem (*maintenance*). Pastikan status pada tabel *Database Backup History* tercatat **`SUCCESS`** dan ukuran berkas `.sql` lebih besar dari `0 KB`.
2. **Prosedur Restore (Pemulihan Darurat):** Apabila terjadi kerusakan data (*data corruption*), lakukan pemulihan dari berkas cadangan terakhir di direktori `/backups/` melalui perintah CLI MySQL:

```bash
mysql -u root -p it_ops_monitor < backups/backup_it_ops_YYYYMMDD_HHMMSS.sql

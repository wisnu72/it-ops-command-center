<?php
$host = '127.0.0.1';
$db   = 'it_ops_monitor';
$user = 'root';
$pass = ''; // Sesuaikan jika MySQL Anda menggunakan password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
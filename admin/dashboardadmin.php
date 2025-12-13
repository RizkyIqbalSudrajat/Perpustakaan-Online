<?php
session_start();
include '../koneksi.php';

// Cek Keamanan
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            padding: 20px;
            position: fixed;
            height: 100%;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            margin-bottom: 40px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding-bottom: 20px;
        }

        .menu a {
            display: block;
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .menu a:hover,
        .menu a.active {
            background-color: var(--active-item);
            padding-left: 20px;
        }

        .content {
            margin-left: 250px;
            padding: 40px;
            width: 100%;
            color: var(--text-color);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand">Admin Panel</div>
        <div class="menu">
            <a href="dashboardadmin.php" class="active">Dashboard</a>
            <a href="tambahbuku.php">Tambah Buku</a>
            <a href="listbuku.php">List Buku</a>
            <a href="riwayatpeminjaman.php">Riwayat Peminjaman</a>
            <a href="kelolaruangan.php">Kelola Ruangan</a>
            <a href="listusers.php">List Users</a>
            <a href="detailakun.php" style="margin-top: 20px; background-color: #4e342e;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Selamat Datang, <?= $_SESSION['name']; ?></h2>
        </div>
        <div class="card">
            <h3>Statistik Perpustakaan</h3>
            <p>Halo Admin! Sistem siap digunakan.</p>
            <p>Silakan pilih menu di sebelah kiri untuk mengelola buku dan melihat riwayat.</p>
        </div>
    </div>

</body>

</html>
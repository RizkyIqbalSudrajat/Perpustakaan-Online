<?php
session_start();
include '../koneksi.php';

// Cek Keamanan
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// ==========================================
// 1. LOGIKA STATISTIK (FETCH DATA)
// ==========================================

// A. Jumlah Buku (PERBAIKAN: Menghitung jumlah judul/baris data, bukan total stok)
$queryBuku = mysqli_query($conn, "SELECT COUNT(*) as total_buku FROM books");
$dataBuku = mysqli_fetch_assoc($queryBuku);
$totalBuku = $dataBuku['total_buku'];

// B. Jumlah User (Member)
$queryUser = mysqli_query($conn, "SELECT COUNT(*) as total_user FROM users WHERE role='member'");
$dataUser = mysqli_fetch_assoc($queryUser);
$totalUser = $dataUser['total_user'];

// C. Statistik Ruangan
// Ruangan Kosong
$queryRoomEmpty = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE status='available'");
$dataRoomEmpty = mysqli_fetch_assoc($queryRoomEmpty);
$roomEmpty = $dataRoomEmpty['total'];

// Ruangan Dibooking
$queryRoomBooked = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE status='booked'");
$dataRoomBooked = mysqli_fetch_assoc($queryRoomBooked);
$roomBooked = $dataRoomBooked['total'];

// D. Statistik Peminjaman
// Sedang Berlangsung (Dipinjam)
$queryLoanActive = mysqli_query($conn, "SELECT COUNT(*) as active FROM loans WHERE status='dipinjam'");
$dataLoanActive = mysqli_fetch_assoc($queryLoanActive);
$loanActive = $dataLoanActive['active'];

// Terlambat
$queryLoanLate = mysqli_query($conn, "SELECT COUNT(*) as late FROM loans WHERE status='terlambat'");
$dataLoanLate = mysqli_fetch_assoc($queryLoanLate);
$loanLate = $dataLoanLate['late'];

// Selesai (Dikembalikan)
$queryLoanDone = mysqli_query($conn, "SELECT COUNT(*) as done FROM loans WHERE status='dikembalikan'");
$dataLoanDone = mysqli_fetch_assoc($queryLoanDone);
$loanDone = $dataLoanDone['done'];

// E. Total Denda (Semua Denda)
$queryFine = mysqli_query($conn, "SELECT SUM(amount) as total_denda FROM fines");
$dataFine = mysqli_fetch_assoc($queryFine);
$totalDenda = $dataFine['total_denda'] ? $dataFine['total_denda'] : 0;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 5px solid var(--sidebar-bg);
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .stat-info p {
            color: #777;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 40px;
            opacity: 0.2;
        }

        .card-blue { border-left-color: #1e88e5; }
        .card-green { border-left-color: #43a047; }
        .card-orange { border-left-color: #fb8c00; }
        .card-red { border-left-color: #e53935; }
        .card-purple { border-left-color: #8e24aa; }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            margin-top: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            display: inline-block;
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
            <a href="../logout.php" style="background-color: #d32f2f;">Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Selamat Datang, <?= $_SESSION['name']; ?></h2>
        </div>

        <div class="section-title">Ringkasan Umum</div>
        <div class="stats-grid">
            
            <div class="stat-card card-blue">
                <div class="stat-info">
                    <h3><?= $totalBuku ?></h3>
                    <p>Jumlah Judul Buku</p>
                </div>
                <div class="stat-icon" style="color: #1e88e5;">
                    <i class="fas fa-book"></i>
                </div>
            </div>

            <div class="stat-card card-green">
                <div class="stat-info">
                    <h3><?= $totalUser ?></h3>
                    <p>Total Member</p>
                </div>
                <div class="stat-icon" style="color: #43a047;">
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <div class="stat-card card-red">
                <div class="stat-info">
                    <h3>Rp <?= number_format($totalDenda, 0, ',', '.') ?></h3>
                    <p>Total Denda Belum di Bayar</p>
                </div>
                <div class="stat-icon" style="color: #e53935;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>

        </div>

        <div class="section-title">Status Sirkulasi</div>
        <div class="stats-grid">

            <div class="stat-card card-orange">
                <div class="stat-info">
                    <h3><?= $loanActive ?></h3>
                    <p>Sedang Dipinjam</p>
                </div>
                <div class="stat-icon" style="color: #fb8c00;">
                    <i class="fas fa-book-reader"></i>
                </div>
            </div>

            <div class="stat-card card-red">
                <div class="stat-info">
                    <h3><?= $loanLate ?></h3>
                    <p>Terlambat Kembali</p>
                </div>
                <div class="stat-icon" style="color: #e53935;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>

            <div class="stat-card card-green">
                <div class="stat-info">
                    <h3><?= $loanDone ?></h3>
                    <p>Selesai (Dikembalikan)</p>
                </div>
                <div class="stat-icon" style="color: #43a047;">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

        </div>

        <div class="section-title">Fasilitas Ruangan</div>
        <div class="stats-grid">

            <div class="stat-card card-green">
                <div class="stat-info">
                    <h3><?= $roomEmpty ?></h3>
                    <p>Ruangan Kosong</p>
                </div>
                <div class="stat-icon" style="color: #43a047;">
                    <i class="fas fa-door-open"></i>
                </div>
            </div>

            <div class="stat-card card-purple">
                <div class="stat-info">
                    <h3><?= $roomBooked ?></h3>
                    <p>Ruangan Dibooking</p>
                </div>
                <div class="stat-icon" style="color: #8e24aa;">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>

        </div>

    </div>

</body>
</html>

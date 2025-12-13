<?php
session_start();
require '../koneksi.php';

// 1. Cek Login & Role
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'member') {
    header("Location: ../index.php");
    exit;
}

// Ambil ID User dari Session
$id_user = $_SESSION['user_id'];
$nama_user = $_SESSION['name'];

// --- LOGIKA FETCH DATA DARI DATABASE ---

// A. Menghitung Jumlah Peminjaman Aktif
// Status 'dipinjam' ATAU 'terlambat' dianggap masih aktif meminjam
$query_pinjam = mysqli_query($conn, "SELECT loan_id FROM loans WHERE user_id='$id_user' AND (status='dipinjam' OR status='terlambat')");
$jumlah_pinjam = mysqli_num_rows($query_pinjam);

// B. Menghitung Jumlah Wishlist
$query_wishlist = mysqli_query($conn, "SELECT wishlist_id FROM wishlist WHERE user_id='$id_user'");
$jumlah_wishlist = mysqli_num_rows($query_wishlist);

// C. Menghitung Total Denda Belum Dibayar
// Menggunakan fungsi SUM() di SQL untuk menjumlahkan nominal
$query_denda = mysqli_query($conn, "SELECT SUM(amount) AS total_tagihan FROM fines WHERE user_id='$id_user' AND status='unpaid'");
$data_denda = mysqli_fetch_assoc($query_denda);
// Jika hasil null (tidak ada denda), set ke 0
$total_denda = $data_denda['total_tagihan'] ? $data_denda['total_tagihan'] : 0;

// D. Menghitung Booking Tempat (LOGIKA BARU)
// Menghitung jumlah ruangan yang STATUS-nya 'available' (Kosong/Ready)
// Ini memberi info ke user ada berapa ruangan yang bisa mereka booking sekarang.
$query_booking = mysqli_query($conn, "SELECT room_id FROM rooms WHERE status='available'");
$jumlah_ready = mysqli_num_rows($query_booking);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Member - Perpus</title>
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
            --accent-color: #8d6e63;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; color: var(--text-color); }
        
        /* SIDEBAR */
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 40px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; font-size: 14px; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        
        /* CONTENT */
        .content { margin-left: 250px; padding: 40px; width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { font-family: 'Playfair Display', serif; font-size: 28px; }

        /* WELCOME CARD */
        .welcome-card {
            background: linear-gradient(135deg, #5d4037, #8d6e63);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(93, 64, 55, 0.2);
            position: relative;
            overflow: hidden;
        }
        .welcome-card h3 { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 10px; position: relative; z-index: 1; }
        .welcome-card p { opacity: 0.9; font-weight: 300; position: relative; z-index: 1; }
        
        /* Hiasan background card */
        .welcome-card::after {
            content: '\f518'; /* Icon buku fontawesome */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 150px;
            opacity: 0.1;
            color: #fff;
            transform: rotate(-15deg);
        }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        
        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 5px solid var(--sidebar-bg);
            transition: transform 0.3s;
            position: relative;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-title { font-size: 14px; color: #888; margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 32px; font-weight: 700; color: var(--text-color); }
        .stat-desc { font-size: 12px; color: #aaa; margin-top: 5px; }
        
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 30px;
            opacity: 0.2;
        }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Member Area</div>
        <div class="menu">
            <a href="dashboardmember.php" class="active">🏠 Dashboard</a>
            <a href="listbuku_member.php">📚 List Buku</a> 
            <a href="wishlist.php">❤️ Wishlist</a>
            <a href="peminjaman.php">📖 Peminjaman</a>
            <a href="booking.php">📅 Booking Tempat</a>
            <a href="denda.php">💰 Denda</a>
            
            <a href="settings.php" style="margin-top: 30px; background-color: #3e2723;">⚙ Settings</a>
            <a href="../logout.php" style="background-color: #d32f2f;">🚪 Logout</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Dashboard</h2>
            <div style="background: white; padding: 8px 15px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                Halo, <b><?= htmlspecialchars($nama_user) ?></b>
            </div>
        </div>

        <div class="welcome-card">
            <h3>Selamat Datang di Perpustakaan Digital!</h3>
            <p>Akses koleksi buku, pantau peminjaman, dan kelola denda Anda dalam satu tempat.</p>
        </div>

        <div class="stats-grid">
            
            <a href="peminjaman.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="border-left-color: #2e7d32;">
                    <div class="stat-title">Peminjaman Aktif</div>
                    <div class="stat-value"><?= $jumlah_pinjam ?></div>
                    <div class="stat-desc">Buku sedang dipinjam / terlambat</div>
                    <i class="fas fa-book-reader stat-icon" style="color: #2e7d32;"></i>
                </div>
            </a>

            <a href="denda.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="border-left-color: #d32f2f;">
                    <div class="stat-title">Total Denda</div>
                    <div class="stat-value" style="color: <?= $total_denda > 0 ? '#d32f2f' : '#2e7d32' ?>;">
                        Rp <?= number_format($total_denda, 0, ',', '.') ?>
                    </div>
                    <div class="stat-desc">Tagihan belum dibayar</div>
                    <i class="fas fa-money-bill-wave stat-icon" style="color: #d32f2f;"></i>
                </div>
            </a>

            <a href="wishlist.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="border-left-color: #fbc02d;">
                    <div class="stat-title">Wishlist</div>
                    <div class="stat-value"><?= $jumlah_wishlist ?></div>
                    <div class="stat-desc">Buku favorit tersimpan</div>
                    <i class="fas fa-heart stat-icon" style="color: #fbc02d;"></i>
                </div>
            </a>

            <a href="booking.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="border-left-color: #1976d2;">
                    <div class="stat-title">Booking Tempat</div>
                    <div class="stat-value"><?= $jumlah_ready ?></div>
                    <div class="stat-desc">Ruangan Kosong / Ready</div>
                    <i class="fas fa-calendar-check stat-icon" style="color: #1976d2;"></i>
                </div>
            </a>

        </div>

    </div>

</body>
</html>
<?php
session_start();
include '../koneksi.php';

// Cek Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: listusers.php");
    exit;
}

$id_user = $_GET['id'];

// 1. AMBIL DATA USER
$queryUser = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($queryUser);

// Jika user tidak ditemukan
if (!$user) {
    echo "<script>alert('User tidak ditemukan'); window.location='listusers.php';</script>";
    exit;
}

// Logika Foto Profil (Sama seperti di listusers.php)
$fotoName = !empty($user['photo']) ? $user['photo'] : 'default_user.png';
$fotoPath = "../uploads/" . $fotoName;
$fallbackAvatar = "https://ui-avatars.com/api/?name=" . urlencode($user['full_name']) . "&background=random&size=128";

// 2. AMBIL RIWAYAT PEMINJAMAN (JOIN books & authors)
$queryLoans = "SELECT 
                l.*, 
                b.title, 
                b.cover,
                a.name AS author_name
              FROM loans l 
              JOIN books b ON l.book_id = b.book_id 
              LEFT JOIN authors a ON b.author_id = a.author_id
              WHERE l.user_id = '$id_user' 
              ORDER BY l.loan_date DESC";

$resultLoans = mysqli_query($conn, $queryLoans);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User - <?= htmlspecialchars($user['full_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg-color: #efebe9; 
            --sidebar-bg: #5d4037; 
            --sidebar-text: #ffffff; 
            --active-item: #3e2723; 
            --text-color: #4e342e; 
            --card-bg: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; color: var(--text-color); }
        
        /* SIDEBAR */
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; z-index: 100; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        
        /* CONTENT */
        .content { margin-left: 250px; padding: 40px; width: 100%; }

        /* PROFILE CARD STYLE */
        .profile-header {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border-left: 5px solid var(--sidebar-bg);
        }
        
        .avatar-large {
            width: 110px; 
            height: 110px; 
            border-radius: 50%; 
            object-fit: cover;
            border: 4px solid #efebe9;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .profile-info h2 { 
            font-family: 'Playfair Display', serif; 
            font-size: 28px; 
            margin-bottom: 5px;
        }
        .profile-info p { 
            color: #777; 
            margin-bottom: 5px; 
            font-size: 14px;
        }
        .badge-role {
            background-color: #3e2723;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title { 
            margin-bottom: 20px; 
            font-weight: 600; 
            font-size: 20px;
            color: var(--sidebar-bg);
            border-bottom: 2px solid #d7ccc8;
            padding-bottom: 10px;
            display: inline-block;
        }

        /* TABLE STYLE */
        .card-table {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background-color: #f5f5f5; color: var(--text-color); font-weight: 600; font-size: 14px; }
        
        /* Book Info in Table */
        .book-flex { display: flex; align-items: center; gap: 12px; }
        .book-cover { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .book-meta div { font-weight: 600; font-size: 14px; }
        .book-meta small { color: #888; font-size: 12px; }

        /* Status Badges */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-dipinjam { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }
        .status-dikembalikan { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .status-terlambat { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        .btn-back { 
            display: inline-flex; align-items: center; 
            margin-bottom: 20px; text-decoration: none; 
            color: var(--sidebar-bg); font-weight: 600; 
            transition: 0.2s;
        }
        .btn-back:hover { transform: translateX(-5px); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="menu">
            <h2 style="text-align:center; margin-bottom:20px; color:white; font-family:'Playfair Display'">Admin Panel</h2>
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="listusers.php" class="active">Kembali ke List User</a>
        </div>
    </div>

    <div class="content">
        <a href="listusers.php" class="btn-back">← Kembali ke Daftar</a>

        <div class="profile-header">
            <img src="<?= $fotoPath ?>" 
                 class="avatar-large" 
                 alt="Foto Profil"
                 onerror="this.onerror=null; this.src='<?= $fallbackAvatar ?>';">
            
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                <p>Email: <?= htmlspecialchars($user['email']) ?></p>
                <p>
                    <span class="badge-role"><?= strtoupper($user['role']) ?></span>
                    &nbsp; • &nbsp; Terdaftar: <?= date('d F Y', strtotime($user['created_at'])) ?>
                </p>
            </div>
        </div>

        <h3 class="section-title">Riwayat Peminjaman Buku</h3>
        
        <div class="card-table">
            <?php if(mysqli_num_rows($resultLoans) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="35%">Detail Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tenggat</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while($loan = mysqli_fetch_assoc($resultLoans)): ?>
                            <?php
                                // Tentukan Class CSS Status
                                $statusClass = '';
                                $statusLabel = '';
                                
                                if ($loan['status'] == 'dipinjam') {
                                    $statusClass = 'status-dipinjam';
                                    $statusLabel = 'Dipinjam';
                                } elseif ($loan['status'] == 'terlambat') {
                                    $statusClass = 'status-terlambat';
                                    $statusLabel = 'Terlambat';
                                } else {
                                    $statusClass = 'status-dikembalikan';
                                    $statusLabel = 'Dikembalikan';
                                }
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div class="book-flex">
                                        <img src="../uploads/<?= htmlspecialchars($loan['cover']) ?>" class="book-cover" onerror="this.src='../uploads/default.jpg'">
                                        <div class="book-meta">
                                            <div><?= htmlspecialchars($loan['title']) ?></div>
                                            <small><?= htmlspecialchars($loan['author_name']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= date('d/m/Y', strtotime($loan['loan_date'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($loan['due_date'])) ?></td>
                                <td>
                                    <?= $loan['return_date'] ? date('d/m/Y', strtotime($loan['return_date'])) : '-' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#888;">
                    <p>User ini belum pernah meminjam buku sama sekali.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
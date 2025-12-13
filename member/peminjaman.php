<?php
session_start();
require '../koneksi.php';

// 1. Cek Login & Role
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'member') {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$nama_user = $_SESSION['name'];

// 2. Query Data Peminjaman (JOIN tables)
$query = "SELECT 
            l.loan_id, 
            l.loan_date, 
            l.due_date, 
            l.status,
            b.title, 
            b.cover, 
            a.name AS author_name 
          FROM loans l 
          JOIN books b ON l.book_id = b.book_id 
          LEFT JOIN authors a ON b.author_id = a.author_id
          WHERE l.user_id = ? 
          ORDER BY l.loan_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Member</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* --- COPY DARI STYLE DASHBOARD --- */
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
            --accent-color: #8d6e63;
            /* Tambahan warna status */
            --success: #2e7d32;
            --warning: #ef6c00;
            --danger: #c62828;
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

        /* --- STYLE KHUSUS TABEL (Disesuaikan dengan tema) --- */
        .card-table {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-top: 5px solid var(--accent-color); /* Aksen atas */
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: middle; }
        th { color: var(--accent-color); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        tr:hover { background-color: #fafafa; }

        /* Book Info */
        .book-info { display: flex; align-items: center; gap: 15px; }
        .book-cover { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .book-title { font-weight: 600; display: block; margin-bottom: 4px; color: var(--text-color); }
        .book-author { font-size: 12px; color: #888; }

        /* Status Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        
        .status-dipinjam { background-color: #fff3e0; color: var(--warning); border: 1px solid #ffe0b2; }
        .status-dikembalikan { background-color: #e8f5e9; color: var(--success); border: 1px solid #c8e6c9; }
        .status-terlambat { background-color: #ffebee; color: var(--danger); border: 1px solid #ffcdd2; }

        .denda-text { font-size: 11px; color: var(--danger); display: block; margin-top: 5px; font-weight: 600; }
        .empty-state { text-align: center; padding: 50px 20px; color: #888; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Perpus Online</div>
        <div class="menu">
            <a href="dashboardmember.php">🏠 Dashboard</a>
            <a href="listbuku_member.php">📚 List Buku</a> 
            <a href="wishlist.php">❤️ Wishlist</a>
            <a href="peminjaman.php" class="active">📖 Peminjaman</a>
            <a href="booking.php">📅 Booking Tempat</a>
            <a href="denda.php">💰 Denda</a>
            
            <a href="settings.php" style="margin-top: 30px; background-color: #3e2723;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Riwayat Peminjaman</h2>
            <div>User: <b><?= htmlspecialchars($nama_user) ?></b></div>
        </div>

        <div class="card-table">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="40%">Detail Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Tenggat</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                // --- LOGIKA STATUS DATABASE ---
                                $status = $row['status'];
                                $badge_class = '';
                                $badge_label = '';
                                $extra_info = '';
                                $due_date_style = '';

                                if ($status == 'dikembalikan') {
                                    $badge_class = 'status-dikembalikan';
                                    $badge_label = 'Dikembalikan';
                                } elseif ($status == 'terlambat') {
                                    $badge_class = 'status-terlambat';
                                    $badge_label = 'Terlambat';
                                    $extra_info = '<span class="denda-text">⚠ Cek menu Denda</span>';
                                    $due_date_style = 'color: var(--danger); font-weight: bold;';
                                } else {
                                    // Default 'dipinjam'
                                    $badge_class = 'status-dipinjam';
                                    $badge_label = 'Dipinjam';
                                }
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <div class="book-info">
                                        <img src="../uploads/<?= htmlspecialchars($row['cover']); ?>" 
                                             alt="Cover" class="book-cover"
                                             onerror="this.src='../uploads/default.jpg'">
                                        
                                        <div>
                                            <span class="book-title"><?= htmlspecialchars($row['title']); ?></span>
                                            <span class="book-author">Oleh: <?= htmlspecialchars($row['author_name']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= date('d M Y', strtotime($row['loan_date'])); ?></td>
                                <td style="<?= $due_date_style ?>">
                                    <?= date('d M Y', strtotime($row['due_date'])); ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= $badge_label ?>
                                    </span>
                                    <?= $extra_info ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Belum ada riwayat peminjaman.</h3>
                    <p>Silakan cari buku di menu <a href="listbuku_member.php" style="color: var(--accent-color); font-weight:600;">List Buku</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
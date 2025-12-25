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

// 2. Hitung Total Tagihan (Belum Lunas)
$query_total = mysqli_query($conn, "SELECT SUM(amount) as total FROM fines WHERE user_id = '$id_user' AND status = 'unpaid'");
$data_total = mysqli_fetch_assoc($query_total);
$total_tagihan = $data_total['total'] ?? 0;

// 3. Ambil Daftar History Denda
$query = "SELECT 
            f.*, 
            b.title, 
            b.cover,
            l.due_date,
            l.loan_id
          FROM fines f
          JOIN loans l ON f.loan_id = l.loan_id
          JOIN books b ON l.book_id = b.book_id
          WHERE f.user_id = ? 
          ORDER BY f.created_at DESC";

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
    <title>Riwayat Denda - Member</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
            --border-color: #d7ccc8;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; color: var(--text-color); }
        
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 40px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; font-size: 14px; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        
        .content { margin-left: 250px; padding: 40px; width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        /* SUMMARY CARD */
        .summary-box {
            background: linear-gradient(135deg, #c62828, #e53935);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(198, 40, 40, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .summary-box.clean {
            background: linear-gradient(135deg, #2e7d32, #43a047);
            box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
        }
        .sum-title { font-size: 14px; opacity: 0.9; }
        .sum-value { font-size: 28px; font-weight: 700; margin-top: 5px; }

        /* TABLE */
        .card-table {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; vertical-align: middle; }
        th { background-color: var(--bg-color); color: var(--text-color); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        
        .book-info { display: flex; align-items: center; gap: 15px; }
        .book-cover { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; }
        .book-title { font-weight: 600; display: block; margin-bottom: 4px; color: #3e2723; }
        
        /* BADGES */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-unpaid { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .badge-paid { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }

        /* BUTTON */
        .btn-pay {
            background-color: #c62828;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-pay:hover { background-color: #b71c1c; }
        
        .empty-state { text-align: center; padding: 40px; color: #888; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Perpus Online</div>
        <div class="menu">
            <a href="dashboardmember.php">🏠 Dashboard</a>
            <a href="listbuku_member.php">📚 List Buku</a> 
            <a href="wishlist.php">❤️ Wishlist</a>
            <a href="peminjaman.php">📖 Peminjaman</a>
            <a href="booking.php">📅 Booking Tempat</a>
            <a href="denda.php" class="active">💰 Denda</a>
            <a href="settings.php" style="margin-top: 30px; background-color: #3e2723;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Info Denda</h2>
            <div>User: <b><?= htmlspecialchars($nama_user) ?></b></div>
        </div>

        <?php if ($total_tagihan > 0): ?>
            <div class="summary-box">
                <div>
                    <div class="sum-title">Total Tagihan Anda</div>
                    <div class="sum-value">Rp <?= number_format($total_tagihan, 0, ',', '.') ?></div>
                </div>
                <div style="font-size: 40px; opacity: 0.5;">⚠️</div>
            </div>
        <?php else: ?>
            <div class="summary-box clean">
                <div>
                    <div class="sum-title">Status Akun</div>
                    <div class="sum-value">Bebas Denda</div>
                </div>
                <div style="font-size: 40px; opacity: 0.5;">✅</div>
            </div>
        <?php endif; ?>

        <div class="card-table">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Buku Terkait</th>
                            <th>Tgl Tenggat</th>
                            <th>Jumlah Denda</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <div class="book-info">
                                        <img src="../uploads/<?= htmlspecialchars($row['cover']); ?>" alt="Cover" class="book-cover">
                                        <div>
                                            <span class="book-title"><?= htmlspecialchars($row['title']); ?></span>
                                            <small style="color:#888;">ID Pinjam: #<?= $row['loan_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                                <td style="font-weight: bold; color: #3e2723;">
                                    Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'unpaid'): ?>
                                        <span class="badge badge-unpaid">Belum Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-paid">Lunas</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'unpaid'): ?>
                                        <a href="pembayaran.php?id=<?= $row['fine_id'] ?>" class="btn-pay">Bayar</a>
                                    <?php else: ?>
                                        <span style="color: #aaa; font-size: 12px;">Selesai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Tidak ada riwayat denda.</h3>
                    <p>Terima kasih telah mengembalikan buku tepat waktu!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

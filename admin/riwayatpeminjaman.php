<?php
session_start();
include '../koneksi.php';

// 1. Cek Sesi Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// ---------------------------------------------------------------------
// LOGIKA: UPDATE STATUS, STOK, & DENDA
// ---------------------------------------------------------------------
if (isset($_POST['aksi_status'])) {
    $loan_id = $_POST['loan_id'];
    $new_status = $_POST['status_baru'];

    // Ambil data peminjaman lama untuk tahu book_id, quantity, user_id, dan status lama
    $cek_loan = mysqli_query($conn, "SELECT * FROM loans WHERE loan_id = '$loan_id'");
    $data_loan = mysqli_fetch_assoc($cek_loan);
    $old_status = $data_loan['status'];
    $book_id = $data_loan['book_id'];
    $qty_pinjam = $data_loan['quantity'];
    $user_id = $data_loan['user_id'];

    // JIKA STATUS DIUBAH JADI 'DIKEMBALIKAN'
    if ($new_status == 'dikembalikan') {
        // 1. Update status loan & tanggal kembali
        $tgl_kembali = date('Y-m-d');
        mysqli_query($conn, "UPDATE loans SET status='dikembalikan', return_date='$tgl_kembali' WHERE loan_id='$loan_id'");

        // 2. Tambah Stok Buku (Hanya jika sebelumnya belum dikembalikan)
        if ($old_status != 'dikembalikan') {
            mysqli_query($conn, "UPDATE books SET stock = stock + $qty_pinjam WHERE book_id='$book_id'");
        }

        // 3. HAPUS DENDA (LOGIKA BARU)
        // Jika sebelumnya 'terlambat', maka hapus tagihan di tabel fines
        if ($old_status == 'terlambat') {
            mysqli_query($conn, "DELETE FROM fines WHERE loan_id='$loan_id'");
        }
    }
    // JIKA STATUS DIUBAH JADI 'TERLAMBAT'
    elseif ($new_status == 'terlambat') {
        // 1. Update status loan
        mysqli_query($conn, "UPDATE loans SET status='terlambat' WHERE loan_id='$loan_id'");

        // 2. Cek apakah sudah ada denda (biar tidak dobel)
        $cek_denda = mysqli_query($conn, "SELECT fine_id FROM fines WHERE loan_id='$loan_id'");
        if (mysqli_num_rows($cek_denda) == 0) {
            // 3. Masukkan Denda Rp 20.000
            $denda = 20000;
            mysqli_query($conn, "INSERT INTO fines (loan_id, user_id, amount, status) VALUES ('$loan_id', '$user_id', '$denda', 'unpaid')");
        }
    }

    // Refresh halaman agar data terupdate
    header("Location: riwayatpeminjaman.php?tab=" . $_GET['tab']);
    exit;
}

// ---------------------------------------------------------------------
// LOGIKA TABS (FILTER DATA)
// ---------------------------------------------------------------------
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dipinjam'; // Default tab 'dipinjam'

// Query dasar join tabel
$query_base = "SELECT loans.*, users.full_name, books.title, books.cover 
               FROM loans 
               JOIN users ON loans.user_id = users.id 
               JOIN books ON loans.book_id = books.book_id";

// Filter berdasarkan Tab
if ($tab == 'dipinjam') {
    $query_final = "$query_base WHERE loans.status = 'dipinjam' ORDER BY loans.loan_date DESC";
    $heading = "Daftar Sedang Dipinjam";
} elseif ($tab == 'terlambat') {
    $query_final = "$query_base WHERE loans.status = 'terlambat' ORDER BY loans.loan_date DESC";
    $heading = "Daftar Terlambat (Denda Aktif)";
} elseif ($tab == 'dikembalikan') {
    $query_final = "$query_base WHERE loans.status = 'dikembalikan' ORDER BY loans.return_date DESC";
    $heading = "Riwayat Pengembalian";
} else {
    $query_final = "$query_base ORDER BY loans.loan_date DESC";
    $heading = "Semua Data";
}

$result = mysqli_query($conn, $query_final);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peminjaman - Admin</title>
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
            color: var(--text-color);
        }

        /* Sidebar & Layout */
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
        }

        /* Tabs Style */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-link {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #5d4037;
            background: #d7ccc8;
            transition: 0.3s;
        }

        .tab-link.active {
            background: #5d4037;
            color: #fff;
            box-shadow: 0 4px 10px rgba(93, 64, 55, 0.3);
        }

        /* Card & Table */
        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        th {
            background-color: #f5f5f5;
            color: #3e2723;
            font-weight: 600;
            font-size: 14px;
        }

        /* Buttons */
        .btn-action {
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: white;
            transition: 0.2s;
        }

        .btn-green {
            background-color: #2e7d32;
        }

        .btn-red {
            background-color: #c62828;
        }

        .btn-green:hover {
            background-color: #1b5e20;
        }

        .btn-red:hover {
            background-color: #b71c1c;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .bg-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .bg-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .bg-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="brand">Admin Panel</div>
        <div class="menu">
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="tambahbuku.php">Tambah Buku</a>
            <a href="listbuku.php">List Buku</a>
            <a href="riwayatpeminjaman.php" class="active">Riwayat Peminjaman</a>
            <a href="kelolaruangan.php">Kelola Ruangan</a>
            <a href="listusers.php">List Users</a>
            <a href="detailakun.php" style="margin-top: 20px; background-color: #4e342e;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div style="margin-bottom: 30px;">
            <h2>Kelola Peminjaman</h2>
            <p style="color: #888;">Kelola status peminjaman, pengembalian, dan denda.</p>
        </div>

        <div class="tabs">
            <a href="?tab=dipinjam" class="tab-link <?= $tab == 'dipinjam' ? 'active' : '' ?>">Sedang Dipinjam</a>
            <a href="?tab=terlambat" class="tab-link <?= $tab == 'terlambat' ? 'active' : '' ?>">Terlambat (Denda)</a>
            <a href="?tab=dikembalikan" class="tab-link <?= $tab == 'dikembalikan' ? 'active' : '' ?>">Riwayat Selesai</a>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 20px;"><?= $heading ?></h3>

            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>User</th>
                        <th>Buku</th>
                        <th>Qty</th>
                        <th>Tgl Pinjam</th>
                        <th>Tenggat</th>
                        <th>Status</th>
                        <?php if ($tab != 'dikembalikan'): ?>
                            <th width="20%">Aksi Admin</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['full_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td><?= date('d/m/Y', strtotime($row['loan_date'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['due_date'])) ?></td>

                                <td>
                                    <?php if ($row['status'] == 'dipinjam'): ?>
                                        <span class="badge bg-warning">Dipinjam</span>
                                    <?php elseif ($row['status'] == 'terlambat'): ?>
                                        <span class="badge bg-danger">Terlambat</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Dikembalikan</span>
                                        <br><small style="color:#888"><?= date('d/m/Y', strtotime($row['return_date'])) ?></small>
                                    <?php endif; ?>
                                </td>

                                <?php if ($tab != 'dikembalikan'): ?>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <form method="POST" onsubmit="return confirm('Yakin buku sudah dikembalikan? Stok bertambah & Denda dihapus (jika ada).');">
                                                <input type="hidden" name="loan_id" value="<?= $row['loan_id'] ?>">
                                                <input type="hidden" name="status_baru" value="dikembalikan">
                                                <button type="submit" name="aksi_status" class="btn-action btn-green">
                                                    ✅ Kembali
                                                </button>
                                            </form>

                                            <?php if ($row['status'] != 'terlambat'): ?>
                                                <form method="POST" onsubmit="return confirm('Set status Terlambat? User akan kena denda Rp 20.000.');">
                                                    <input type="hidden" name="loan_id" value="<?= $row['loan_id'] ?>">
                                                    <input type="hidden" name="status_baru" value="terlambat">
                                                    <button type="submit" name="aksi_status" class="btn-action btn-red">
                                                        ⚠️ Telat
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>

                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                Tidak ada data pada kategori ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
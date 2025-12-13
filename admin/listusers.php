<?php
session_start();
include '../koneksi.php';

// Cek Admin
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
    <title>List Users - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* --- CSS VARIABLES --- */
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
            --muted-text: #8d6e63;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            padding: 20px;
            position: fixed;
            height: 100%;
            z-index: 100;
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

        /* CONTENT */
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

        /* GRID SYSTEM UNTUK USER */
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .user-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-top: 5px solid transparent;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(93, 64, 55, 0.15);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid #efebe9;
            object-fit: cover; /* Agar gambar tidak gepeng */
        }

        .user-name {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
            color: var(--text-color);
            word-break: break-word; /* Mencegah nama panjang merusak layout */
        }

        .user-email {
            font-size: 13px;
            color: var(--muted-text);
            margin-bottom: 15px;
            word-break: break-all;
        }

        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin { background-color: #5d4037; color: white; }
        .role-member { background-color: #81c784; color: #1b5e20; }
        .card-admin { border-top-color: #5d4037; }
        .card-member { border-top-color: #81c784; }

        /* BUTTONS */
        .card-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            width: 100%;
            justify-content: center;
        }

        .btn-action {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            transition: 0.3s;
            flex: 1;
        }

        .btn-detail { background-color: #4e342e; color: white; border: 1px solid #4e342e; }
        .btn-detail:hover { background-color: #3e2723; }

        .btn-delete { background-color: white; color: #d32f2f; border: 1px solid #d32f2f; }
        .btn-delete:hover { background-color: #d32f2f; color: white; }

        .btn-disabled { background-color: #eee; color: #aaa; border: 1px solid #eee; cursor: not-allowed; pointer-events: none; }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand">Admin Panel</div>
        <div class="menu">
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="tambahbuku.php">Tambah Buku</a>
            <a href="listbuku.php">List Buku</a>
            <a href="riwayatpeminjaman.php">Riwayat Peminjaman</a>
            <a href="kelolaruangan.php">Kelola Ruangan</a>
            <a href="listusers.php" class="active">List Users</a>
            <a href="detailakun.php" style="margin-top: 20px; background-color: #4e342e;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Daftar Pengguna</h2>
        </div>

        <div class="user-grid">
            <?php
            // Mengambil semua user
            $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");

            while ($u = mysqli_fetch_assoc($users)):
                // Tentukan style card berdasarkan role
                $badgeClass = ($u['role'] == 'admin') ? 'role-admin' : 'role-member';
                $cardClass = ($u['role'] == 'admin') ? 'card-admin' : 'card-member';
                
                // Logika Foto Profil
                // 1. Cek apakah ada nama file di database
                // 2. Jika kosong, pakai default_user.png
                $fotoName = !empty($u['photo']) ? $u['photo'] : 'default_user.png';
                $fotoPath = "../uploads/" . $fotoName;
                
                // URL untuk fallback jika gambar rusak/hilang (UI Avatars)
                $fallbackAvatar = "https://ui-avatars.com/api/?name=" . urlencode($u['full_name']) . "&background=random";
            ?>

                <div class="user-card <?= $cardClass ?>">
                    <img src="<?= $fotoPath ?>" 
                         alt="Avatar" 
                         class="user-avatar"
                         onerror="this.onerror=null; this.src='<?= $fallbackAvatar ?>';">

                    <div class="user-name"><?= htmlspecialchars($u['full_name']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>

                    <span class="role-badge <?= $badgeClass ?>">
                        <?= $u['role'] ?>
                    </span>

                    <div class="card-actions">
                        <a href="detailuser.php?id=<?= $u['id'] ?>" class="btn-action btn-detail">Detail</a>

                        <?php if ($u['role'] != 'admin'): ?>
                            <a href="hapususer.php?id=<?= $u['id'] ?>"
                               class="btn-action btn-delete"
                               onclick="return confirm('Hapus user <?= $u['full_name'] ?>? Data peminjaman user ini mungkin akan error jika dihapus.')">
                                Hapus
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn-action btn-disabled">Locked</a>
                        <?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>
        </div>

    </div>

</body>

</html>
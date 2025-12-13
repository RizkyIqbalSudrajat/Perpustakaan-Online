<?php
session_start();
include '../koneksi.php';

// 1. Cek Login & Role
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'member') {
    header("Location: ../index.php");
    exit;
}

// 2. Cek Parameter ID di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Buku tidak ditemukan!'); window.location='listbuku_member.php';</script>";
    exit;
}

$id_buku = $_GET['id'];
$id_user = $_SESSION['user_id']; // Ambil ID User untuk cek wishlist

// 3. Query Detail Buku (JOIN ke semua tabel terkait)
$query = "
    SELECT books.*, 
           authors.name AS author_name, 
           categories.name AS category_name,
           publishers.name AS publisher_name,
           shelves.location AS shelf_location
    FROM books 
    LEFT JOIN authors ON books.author_id = authors.author_id 
    LEFT JOIN categories ON books.category_id = categories.category_id 
    LEFT JOIN publishers ON books.publisher_id = publishers.publisher_id 
    LEFT JOIN shelves ON books.shelf_id = shelves.shelf_id 
    WHERE books.book_id = '$id_buku'
";

$result = mysqli_query($conn, $query);

// Jika buku tidak ada di database
if (mysqli_num_rows($result) < 1) {
    echo "<script>alert('Data buku tidak ditemukan!'); window.location='listbuku_member.php';</script>";
    exit;
}

$b = mysqli_fetch_assoc($result);
$gambar = !empty($b['cover']) ? $b['cover'] : 'default.jpg';
$is_available = $b['stock'] > 0;

// 4. Cek Status Wishlist User Ini
$cek_wishlist = mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = '$id_user' AND book_id = '$id_buku'");
$is_wishlist = mysqli_num_rows($cek_wishlist) > 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - <?= $b['title'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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

        /* SIDEBAR (Sama seperti sebelumnya) */
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 40px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; font-size: 14px; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }

        /* CONTENT */
        .content { margin-left: 250px; padding: 40px; width: 100%; }
        
        /* TOMBOL KEMBALI */
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; color: #5d4037; font-weight: 600;
            margin-bottom: 20px; transition: 0.3s;
        }
        .btn-back:hover { color: #3e2723; transform: translateX(-5px); }

        /* DETAIL CONTAINER */
        .detail-card {
            background: white; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex; overflow: hidden;
            border: 1px solid #d7ccc8;
        }

        /* BAGIAN KIRI (GAMBAR) */
        .detail-img {
            width: 350px; min-width: 350px;
            background-color: #f5f5f5;
            display: flex; align-items: center; justify-content: center;
            border-right: 1px solid #eee;
        }
        .detail-img img {
            width: 100%; height: 100%; object-fit: cover;
        }

        /* BAGIAN KANAN (INFO) */
        .detail-info { padding: 40px; flex-grow: 1; }

        .book-title {
            font-family: 'Playfair Display', serif; font-size: 32px;
            color: #3e2723; margin-bottom: 5px; line-height: 1.2;
        }
        .book-author { font-size: 16px; color: #757575; font-style: italic; margin-bottom: 20px; }

        /* BADGE STOK */
        .badge {
            display: inline-block; padding: 5px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; margin-bottom: 25px;
        }
        .bg-green { background-color: #e8f5e9; color: #2e7d32; }
        .bg-red { background-color: #ffebee; color: #c62828; }

        /* TABEL INFO KECIL */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .meta-table td { padding: 8px 0; font-size: 14px; vertical-align: top; }
        .meta-label { width: 120px; color: #8d6e63; font-weight: 500; }
        .meta-val { color: #333; font-weight: 600; }

        /* SINOPSIS */
        .synopsis-area {
            background-color: #fffbf8; padding: 20px; border-radius: 8px;
            border-left: 4px solid #8d6e63; margin-bottom: 30px;
        }
        .synopsis-title { font-weight: 700; font-size: 14px; margin-bottom: 10px; color: #5d4037; }
        .synopsis-text { font-size: 14px; line-height: 1.6; color: #555; text-align: justify; }

        /* ACTION BUTTONS */
        .action-buttons { display: flex; gap: 15px; }
        
        .btn-action {
            padding: 12px 25px; border-radius: 8px; text-decoration: none;
            font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px;
            transition: 0.3s; border: none; cursor: pointer;
        }

        .btn-pinjam { background-color: #2e7d32; color: white; }
        .btn-pinjam:hover { background-color: #1b5e20; transform: translateY(-3px); box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3); }

        .btn-wishlist { 
            background-color: #fff; border: 2px solid #f8bbd0; color: #d81b60; 
        }
        .btn-wishlist:hover { background-color: #fce4ec; }
        
        .btn-wishlist.active {
            background-color: #d81b60; color: white; border-color: #d81b60;
        }

        .btn-disabled { background-color: #bdbdbd; color: #fff; cursor: not-allowed; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .detail-card { flex-direction: column; }
            .detail-img { width: 100%; height: 300px; }
            .content { margin-left: 0; width: 100%; padding: 20px; padding-bottom: 80px; }
            .sidebar { width: 100%; height: auto; bottom: 0; position: fixed; flex-direction: row; overflow-x: auto; padding: 10px; }
            .brand { display: none; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Perpus Online</div>
        <div class="menu">
            <a href="dashboardmember.php">🏠 Dashboard</a>
            <a href="listbuku_member.php" class="active">📚 List Buku</a> 
            <a href="wishlist.php">❤️ Wishlist</a>
            <a href="peminjaman.php">📖 Peminjaman</a>
            <a href="booking.php">📅 Booking Tempat</a>
            <a href="denda.php">💰 Denda</a>
            <a href="settings.php" style="margin-top: 30px; background-color: #3e2723;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        
        <a href="listbuku_member.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Buku
        </a>

        <div class="detail-card">
            <div class="detail-img">
                <img src="../uploads/<?= $gambar ?>" alt="Cover Buku">
            </div>

            <div class="detail-info">
                <h1 class="book-title"><?= $b['title'] ?></h1>
                <div class="book-author">Oleh: <?= $b['author_name'] ? $b['author_name'] : 'Tidak diketahui' ?></div>

                <span class="badge <?= $is_available ? 'bg-green' : 'bg-red' ?>">
                    <?= $is_available ? 'Stok Tersedia: ' . $b['stock'] : 'Stok Habis' ?>
                </span>

                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Kategori</td>
                        <td class="meta-val">: <?= $b['category_name'] ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Penerbit</td>
                        <td class="meta-val">: <?= $b['publisher_name'] ? $b['publisher_name'] : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tahun Terbit</td>
                        <td class="meta-val">: <?= $b['year'] ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label">Lokasi Rak</td>
                        <td class="meta-val">: <?= $b['shelf_location'] ?></td>
                    </tr>
                </table>

                <div class="synopsis-area">
                    <div class="synopsis-title">📖 Sinopsis</div>
                    <div class="synopsis-text">
                        <?= $b['synopsis'] ? nl2br($b['synopsis']) : 'Belum ada sinopsis untuk buku ini.' ?>
                    </div>
                </div>

                <div class="action-buttons">
                    <?php if($is_available): ?>
                        <a href="pinjam_form.php?id=<?= $b['book_id'] ?>" class="btn-action btn-pinjam">
                            <i class="fas fa-book-reader"></i> Ajukan Peminjaman
                        </a>
                    <?php else: ?>
                        <button class="btn-action btn-disabled">Stok Habis</button>
                    <?php endif; ?>

                    <button onclick="toggleWishlist(<?= $b['book_id'] ?>, this)" class="btn-action btn-wishlist <?= $is_wishlist ? 'active' : '' ?>">
                        <i class="fas <?= $is_wishlist ? 'fa-check' : 'fa-heart' ?>"></i> 
                        <span id="wishlist-text"><?= $is_wishlist ? 'Disimpan' : 'Simpan ke Wishlist' ?></span>
                    </button>
                </div>

            </div>
        </div>

    </div>

    <script>
        function toggleWishlist(bookId, btnElement) {
            var icon = btnElement.querySelector('i');
            var textSpan = btnElement.querySelector('#wishlist-text');

            var formData = new FormData();
            formData.append('book_id', bookId);

            fetch('process_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'added') {
                    btnElement.classList.add('active');
                    icon.classList.remove('fa-heart');
                    icon.classList.add('fa-check');
                    textSpan.textContent = "Disimpan";
                } else if (data.status === 'removed') {
                    btnElement.classList.remove('active');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-heart');
                    textSpan.textContent = "Simpan ke Wishlist";
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi.');
            });
        }
    </script>

</body>
</html>
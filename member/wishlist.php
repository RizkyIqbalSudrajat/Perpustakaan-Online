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

// 2. Query Data Wishlist (JOIN dengan tabel books dan authors)
$query = "SELECT 
            w.created_at AS tgl_simpan,
            b.*, 
            a.name AS author_name,
            c.name AS category_name
          FROM wishlist w
          JOIN books b ON w.book_id = b.book_id
          LEFT JOIN authors a ON b.author_id = a.author_id
          LEFT JOIN categories c ON b.category_id = c.category_id
          WHERE w.user_id = '$id_user'
          ORDER BY w.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist Saya - Member</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- MENGGUNAKAN STYLE YANG SAMA DENGAN LIST BUKU --- */
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
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
        .header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { font-family: 'Playfair Display', serif; font-size: 28px; }

        /* --- STYLE CARD BUKU --- */
        .book-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
            gap: 25px; 
        }
        
        .book-card {
            background: white; border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s; border: 1px solid #eee;
            display: flex; flex-direction: column; overflow: hidden;
            position: relative;
        }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
        
        .book-cover {
            width: 100%; height: 260px; object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .book-details { padding: 15px; display: flex; flex-direction: column; flex-grow: 1; }
        .b-title { font-weight: 700; font-size: 15px; margin-bottom: 4px; line-height: 1.3; color: #212121; }
        .b-author { font-size: 13px; color: #757575; margin-bottom: 8px; font-style: italic; }
        
        /* Stock Label */
        .badge-stock { 
            font-size: 11px; padding: 3px 8px; border-radius: 10px; 
            align-self: flex-start; margin-bottom: 15px; font-weight: 600;
        }
        .in-stock { background-color: #e8f5e9; color: #2e7d32; }
        .out-stock { background-color: #ffebee; color: #c62828; }

        /* Actions Buttons */
        .action-area { margin-top: auto; display: flex; gap: 8px; }
        
        .btn-card {
            flex: 1; text-align: center; padding: 10px 0; border-radius: 6px;
            text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: inline-flex; justify-content: center; align-items: center;
        }
        
        .btn-detail { background-color: #3e2723; color: white; }
        .btn-detail:hover { background-color: #5d4037; }
        
        .btn-pinjam { background-color: #2e7d32; color: white; }
        .btn-pinjam:hover { background-color: #1b5e20; }
        
        .btn-disabled { background-color: #bdbdbd; color: #757575; cursor: not-allowed; pointer-events: none; }

        /* --- TOMBOL WISHLIST --- */
        .btn-wishlist {
            background-color: #fce4ec; 
            color: #d81b60; 
            border: 1px solid #f8bbd0;
            max-width: 45px;
        }
        .btn-wishlist:hover { background-color: #f8bbd0; }
        
        /* Karena ini halaman wishlist, defaultnya pasti AKTIF (Merah) */
        .btn-wishlist.active {
            background-color: #d81b60; 
            color: white; 
            border-color: #ad1457;
        }

        .empty-state {
            text-align: center; padding: 60px; background: white; border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%;
        }
        .empty-state i { font-size: 50px; color: #d7ccc8; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Perpus Online</div>
        <div class="menu">
            <a href="dashboardmember.php">🏠 Dashboard</a>
            <a href="listbuku_member.php">📚 List Buku</a> 
            <a href="wishlist.php" class="active">❤️ Wishlist</a>
            <a href="peminjaman.php">📖 Peminjaman</a>
            <a href="booking.php">📅 Booking Tempat</a>
            <a href="denda.php">💰 Denda</a>
            
            <a href="settings.php" style="margin-top: 30px; background-color: #3e2723;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <div>
                <h2>Buku Favorit Saya</h2>
                <p style="color: #666; font-size: 14px;">Koleksi buku yang Anda simpan.</p>
            </div>
            <div style="background: #fff; padding: 10px 20px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                User: <b><?= htmlspecialchars($nama_user) ?></b>
            </div>
        </div>

        <?php if (mysqli_num_rows($result) > 0): ?>
            
            <div class="book-grid">
                <?php while ($b = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $gambar = !empty($b['cover']) ? $b['cover'] : 'default.jpg';
                        $is_available = $b['stock'] > 0;
                        
                        // Karena ini halaman wishlist, buku yang tampil PASTI ada di wishlist
                        // Maka class tombol kita set langsung ke 'active'
                    ?>
                    
                    <div class="book-card" id="card-<?= $b['book_id'] ?>">
                        <img src="../uploads/<?= htmlspecialchars($gambar) ?>" class="book-cover" alt="Cover" onerror="this.src='../uploads/default.jpg'">
                        
                        <div class="book-details">
                            <div class="b-title"><?= htmlspecialchars($b['title']) ?></div>
                            <div class="b-author"><?= htmlspecialchars($b['author_name']) ?></div>
                            
                            <div class="badge-stock <?= $is_available ? 'in-stock' : 'out-stock' ?>">
                                <?= $is_available ? 'Stok: '.$b['stock'] : 'Habis' ?>
                            </div>

                            <div style="font-size: 11px; color: #999; margin-bottom: 10px;">
                                Disimpan: <?= date('d M Y', strtotime($b['tgl_simpan'])) ?>
                            </div>
                            
                            <div class="action-area">
                                <a href="detailbuku_member.php?id=<?= $b['book_id'] ?>" class="btn-card btn-detail">
                                    <i class="fas fa-info-circle"></i>
                                </a>
                                
                                <?php if($is_available): ?>
                                    <a href="pinjam_form.php?id=<?= $b['book_id'] ?>" class="btn-card btn-pinjam">
                                        <i class="fas fa-book-reader"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn-card btn-disabled">Habis</button>
                                <?php endif; ?>

                                <button onclick="removeFromWishlist(<?= $b['book_id'] ?>, this)" class="btn-card btn-wishlist active" title="Hapus dari Wishlist">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>Wishlist Masih Kosong</h3>
                <p>Anda belum menyimpan buku apapun.</p>
                <a href="listbuku_member.php" style="color: #8d6e63; font-weight: 600; margin-top: 10px; display: inline-block;">Cari Buku Sekarang &rarr;</a>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Fungsi Hapus Wishlist (AJAX)
        // Sama seperti di listbuku, tapi disini jika dihapus, CARD-nya kita hilangkan
        function removeFromWishlist(bookId, btnElement) {
            
            if(!confirm('Hapus buku ini dari daftar favorit?')) return;

            var formData = new FormData();
            formData.append('book_id', bookId);

            fetch('process_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'removed') {
                    // Animasi menghapus card
                    var card = document.getElementById('card-' + bookId);
                    card.style.transition = "all 0.5s";
                    card.style.opacity = "0";
                    card.style.transform = "scale(0.8)";
                    
                    setTimeout(() => {
                        card.remove();
                        // Jika tidak ada card tersisa, reload agar muncul pesan kosong
                        if(document.querySelectorAll('.book-card').length === 0) {
                            location.reload(); 
                        }
                    }, 500);
                } else {
                    alert('Gagal menghapus data.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>

</body>
</html>
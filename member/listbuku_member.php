<?php
session_start();
include '../koneksi.php';

// 1. Cek Login & Role Member
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'member') {
    header("Location: ../index.php");
    exit;
}

// 2. AMBIL ID USER DARI SESSION (Sesuai login.php Anda)
$id_user_login = $_SESSION['user_id']; 

// 3. AMBIL DATA WISHLIST USER INI
// Tujuannya: Agar tombol wishlist tahu mana buku yang sudah dilike sebelumnya
$my_wishlist = [];
$query_wishlist = mysqli_query($conn, "SELECT book_id FROM wishlist WHERE user_id = '$id_user_login'");

// Cek jika query berhasil agar tidak error
if ($query_wishlist) {
    while($row_w = mysqli_fetch_assoc($query_wishlist)){
        $my_wishlist[] = $row_w['book_id'];
    }
}

// 4. Ambil data rak (KECUALI GUDANG)
$query_shelves = "SELECT * FROM shelves WHERE location NOT LIKE '%Gudang%' ORDER BY location ASC";
$all_shelves = mysqli_query($conn, $query_shelves);
$shelves_array = [];
while ($s = mysqli_fetch_assoc($all_shelves)) {
    $shelves_array[] = $s;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku - Member</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
            --shelf-header: #8d6e63;
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
        .header { margin-bottom: 30px; }
        .header h2 { font-family: 'Playfair Display', serif; font-size: 28px; }

        /* SHELF ACCORDION */
        .rack-container {
            background-color: #fff;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #d7ccc8;
        }

        .rack-header {
            background-color: var(--sidebar-bg);
            color: #fff;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.3s;
        }
        .rack-header:hover { background-color: #4e342e; }
        .rack-title { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .toggle-icon { transition: transform 0.3s ease; }
        .rotate { transform: rotate(180deg); }

        .rack-body { 
            padding: 25px; 
            background: #fffbf8; 
            display: none; 
            border-top: 1px solid #eee;
        }
        .rack-body.show { display: block; animation: fadeIn 0.5s; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* KATEGORI */
        .category-section { margin-bottom: 30px; }
        .category-title {
            font-size: 15px; font-weight: 600; color: var(--shelf-header);
            border-bottom: 2px solid #d7ccc8; padding-bottom: 5px; margin-bottom: 15px;
            text-transform: uppercase; letter-spacing: 1px;
        }

        /* BOOK CARD */
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        
        .book-card {
            background: white; border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s; border: 1px solid #eee;
            display: flex; flex-direction: column; overflow: hidden;
        }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
        
        .book-cover {
            width: 100%; height: 240px; object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .book-details { padding: 12px; display: flex; flex-direction: column; flex-grow: 1; }
        .b-title { font-weight: 700; font-size: 14px; margin-bottom: 4px; line-height: 1.3; color: #212121; }
        .b-author { font-size: 12px; color: #757575; margin-bottom: 8px; font-style: italic; }
        
        /* Stock Label */
        .badge-stock { 
            font-size: 11px; padding: 3px 8px; border-radius: 10px; 
            align-self: flex-start; margin-bottom: 10px; font-weight: 600;
        }
        .in-stock { background-color: #e8f5e9; color: #2e7d32; }
        .out-stock { background-color: #ffebee; color: #c62828; }

        /* Actions Buttons */
        .action-area { margin-top: auto; padding-top: 10px; display: flex; gap: 8px; }
        
        .btn-card {
            flex: 1; text-align: center; padding: 8px 0; border-radius: 5px;
            text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.2s; border: none; cursor: pointer; display: inline-flex; justify-content: center; align-items: center;
        }
        
        .btn-detail { background-color: #3e2723; color: white; }
        .btn-detail:hover { background-color: #5d4037; }
        
        .btn-pinjam { background-color: #2e7d32; color: white; }
        .btn-pinjam:hover { background-color: #1b5e20; }
        
        .btn-disabled { background-color: #bdbdbd; color: #757575; cursor: not-allowed; pointer-events: none; }

        /* --- STYLE KHUSUS TOMBOL WISHLIST --- */
        .btn-wishlist {
            background-color: #fce4ec; /* Pink muda */
            color: #d81b60; /* Merah muda tua */
            border: 1px solid #f8bbd0;
            max-width: 40px; /* Kotak kecil untuk ikon */
        }
        .btn-wishlist:hover {
            background-color: #f8bbd0;
        }
        
        /* Style saat buku SUDAH di wishlist (Aktif) */
        .btn-wishlist.active {
            background-color: #d81b60; /* Jadi merah full */
            color: white; /* Icon jadi putih */
            border-color: #ad1457;
        }

        .empty-alert { color: #aaa; font-style: italic; text-align: center; padding: 20px; }
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
        <div class="header">
            <h2>Katalog Buku Perpustakaan</h2>
            <p>Klik nama rak untuk melihat koleksi buku di dalamnya.</p>
        </div>

        <?php 
        if (count($shelves_array) > 0):
            foreach ($shelves_array as $index => $shelf): 
                $current_shelf_id = $shelf['shelf_id'];
                $current_location = $shelf['location'];
                
                // Query Buku
                $query_books = "
                    SELECT books.*, authors.name AS author_name, categories.name AS category_name 
                    FROM books 
                    LEFT JOIN authors ON books.author_id = authors.author_id 
                    LEFT JOIN categories ON books.category_id = categories.category_id 
                    WHERE books.shelf_id = '$current_shelf_id' 
                    ORDER BY categories.name ASC, books.title ASC
                ";
                
                $result_books = mysqli_query($conn, $query_books);
                $books_by_category = [];
                while($row = mysqli_fetch_assoc($result_books)){
                    $cat_name = $row['category_name'] ? $row['category_name'] : 'Tanpa Kategori';
                    $books_by_category[$cat_name][] = $row;
                }
                
                $total_books_in_shelf = mysqli_num_rows($result_books);
        ?>
            
            <div class="rack-container">
                <div class="rack-header" onclick="toggleRack('rack-<?= $index ?>', 'icon-<?= $index ?>')">
                    <span class="rack-title">
                        <i class="fas fa-layer-group"></i> <?= $current_location ?> 
                        <span style="font-size: 12px; opacity: 0.7; margin-left: 10px;">(<?= $total_books_in_shelf ?> Buku)</span>
                    </span>
                    <i id="icon-<?= $index ?>" class="fas fa-chevron-down toggle-icon"></i>
                </div>

                <div id="rack-<?= $index ?>" class="rack-body">
                    <?php if (empty($books_by_category)): ?>
                        <div class="empty-alert">Belum ada buku di rak ini.</div>
                    <?php else: ?>
                        
                        <?php foreach($books_by_category as $category_name => $books_list): ?>
                            
                            <div class="category-section">
                                <div class="category-title">📂 <?= $category_name ?></div>
                                
                                <div class="book-grid">
                                    <?php foreach($books_list as $b): 
                                        $gambar = !empty($b['cover']) ? $b['cover'] : 'default.jpg';
                                        $is_available = $b['stock'] > 0;
                                        
                                        // --- LOGIC WISHLIST ---
                                        // Cek apakah ID buku ini ada di array $my_wishlist
                                        $is_in_wishlist = in_array($b['book_id'], $my_wishlist);
                                        
                                        // Tentukan Class CSS dan Icon
                                        $btn_class = $is_in_wishlist ? 'btn-wishlist active' : 'btn-wishlist';
                                        $icon_class = $is_in_wishlist ? 'fa-check' : 'fa-heart';
                                        // ----------------------
                                    ?>
                                        <div class="book-card">
                                            <img src="../uploads/<?= $gambar ?>" class="book-cover" alt="Cover">
                                            <div class="book-details">
                                                <div class="b-title"><?= $b['title'] ?></div>
                                                <div class="b-author"><?= $b['author_name'] ?></div> 
                                                
                                                <div class="badge-stock <?= $is_available ? 'in-stock' : 'out-stock' ?>">
                                                    <?= $is_available ? 'Stok: '.$b['stock'] : 'Habis' ?>
                                                </div>
                                                
                                                <div class="action-area">
                                                    <a href="detailbuku_member.php?id=<?= $b['book_id'] ?>" class="btn-card btn-detail">
                                                        <i class="fas fa-info-circle"></i> Detail
                                                    </a>
                                                    
                                                    <?php if($is_available): ?>
                                                        <a href="pinjam_form.php?id=<?= $b['book_id'] ?>" class="btn-card btn-pinjam">
                                                            <i class="fas fa-book-reader"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn-card btn-disabled">Habis</button>
                                                    <?php endif; ?>

                                                    <button onclick="toggleWishlist(<?= $b['book_id'] ?>, this)" class="btn-card <?= $btn_class ?>" title="Simpan ke Wishlist">
                                                        <i class="fas <?= $icon_class ?>"></i>
                                                    </button>

                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; 
        else: ?>
            <p>Tidak ada rak yang tersedia.</p>
        <?php endif; ?>

    </div>

    <script>
        // Fungsi Buka Tutup Rak
        function toggleRack(elementId, iconId) {
            var content = document.getElementById(elementId);
            var icon = document.getElementById(iconId);
            
            if (content.style.display === "block") {
                content.style.display = "none";
                icon.classList.remove("rotate");
            } else {
                content.style.display = "block";
                icon.classList.add("rotate");
            }
        }

        // --- FUNGSI AJAX WISHLIST ---
        function toggleWishlist(bookId, btnElement) {
            // Ambil elemen icon di dalam tombol
            var icon = btnElement.querySelector('i');
            
            // Siapkan data untuk dikirim
            var formData = new FormData();
            formData.append('book_id', bookId);

            // Kirim ke process_wishlist.php tanpa reload halaman
            fetch('process_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Ubah respon jadi JSON
            .then(data => {
                if (data.status === 'added') {
                    // Jika berhasil DITAMBAH: Ubah jadi merah & icon centang
                    btnElement.classList.add('active');
                    icon.classList.remove('fa-heart');
                    icon.classList.add('fa-check');
                } else if (data.status === 'removed') {
                    // Jika berhasil DIHAPUS: Balik jadi pink & icon hati
                    btnElement.classList.remove('active');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-heart');
                } else {
                    // Jika ada error lain (misal belum login)
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
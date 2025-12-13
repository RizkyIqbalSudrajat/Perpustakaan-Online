<?php
session_start();
include '../koneksi.php';

// Cek Login
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// --- BACKEND LOGIC ---

// 1. Logic Tambah Rak
if (isset($_POST['add_shelf'])) {
    $location = htmlspecialchars($_POST['location']);
    $cek = mysqli_query($conn, "SELECT * FROM shelves WHERE location = '$location'");
    if (mysqli_num_rows($cek) == 0) {
        mysqli_query($conn, "INSERT INTO shelves (location) VALUES ('$location')");
    }
    header("Location: listbuku.php");
    exit;
}

// 2. Logic Hapus Rak
if (isset($_GET['delete_shelf'])) {
    $shelf_id_to_delete = $_GET['delete_shelf'];

    // Cari ID 'Gudang'
    $gudang_query = mysqli_query($conn, "SELECT shelf_id FROM shelves WHERE location = 'Gudang' LIMIT 1");
    $gudang_data = mysqli_fetch_assoc($gudang_query);

    if (!$gudang_data) {
        echo "<script>alert('Error: Rak bernama Gudang tidak ditemukan!'); window.location='listbuku.php';</script>";
        exit;
    }

    $gudang_id = $gudang_data['shelf_id'];

    if ($shelf_id_to_delete != $gudang_id) {
        // Pindahkan buku ke Gudang
        mysqli_query($conn, "UPDATE books SET shelf_id = '$gudang_id' WHERE shelf_id = '$shelf_id_to_delete'");
        // Hapus Rak
        mysqli_query($conn, "DELETE FROM shelves WHERE shelf_id = '$shelf_id_to_delete'");
    } else {
        echo "<script>alert('Rak Gudang Utama tidak boleh dihapus!'); window.location='listbuku.php';</script>";
    }
    header("Location: listbuku.php");
    exit;
}

// 3. Logic Pindah Rak
if (isset($_POST['move_book'])) {
    $book_id = $_POST['book_id'];
    $target_shelf = $_POST['target_shelf'];
    mysqli_query($conn, "UPDATE books SET shelf_id = '$target_shelf' WHERE book_id = '$book_id'");
    header("Location: listbuku.php");
    exit;
}

// Ambil semua data rak
$all_shelves = mysqli_query($conn, "SELECT * FROM shelves ORDER BY location ASC");
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
    <title>List Buku per Rak - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f1ea;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --card-bg: #ffffff;
            --text-color: #3e2723;
            --shelf-color: #8d6e63;
            /* Warna kayu rak */
            --category-bg: #efebe9;
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
            background-color: #3e2723;
            padding-left: 20px;
        }

        /* CONTENT */
        .content {
            margin-left: 250px;
            padding: 40px;
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
        }

        /* INPUT RAK BARU */
        .add-rack-form {
            display: flex;
            gap: 10px;
        }

        .input-rack {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
        }

        .btn-add {
            background-color: #2e7d32;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        /* --- DESAIN RAK (SHELF UI) --- */
        .rack-container {
            background-color: #fff;
            border: 1px solid #d7ccc8;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .rack-header {
            background-color: var(--sidebar-bg);
            color: #fff;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .rack-title {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-del-rack {
            background-color: #d32f2f;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            transition: 0.2s;
        }

        .btn-del-rack:hover {
            background-color: #b71c1c;
        }

        .rack-body {
            padding: 25px;
            background: #fffbf8;
        }

        /* --- KATEGORI SUB-SECTION --- */
        .category-section {
            margin-bottom: 30px;
        }

        .category-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--shelf-color);
            border-bottom: 2px solid #d7ccc8;
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* --- BOOK CARD GRID --- */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .book-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .book-cover {
            width: 100%;
            height: 240px;
            object-fit: cover;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            border-bottom: 1px solid #eee;
        }

        .book-details {
            padding: 12px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .b-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 4px;
            line-height: 1.3;
            color: #212121;
        }

        .b-author {
            font-size: 12px;
            color: #757575;
            margin-bottom: 8px;
            font-style: italic;
        }

        .b-stock {
            font-size: 11px;
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 4px;
            align-self: flex-start;
            margin-bottom: 10px;
        }

        /* Actions (Move, Edit, Delete) */
        .action-area {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }

        .move-form {
            display: flex;
            gap: 5px;
            margin-bottom: 8px;
        }

        .move-select {
            font-size: 11px;
            padding: 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        .btn-move {
            background: #5d4037;
            color: white;
            border: none;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-group {
            display: flex;
            gap: 5px;
        }

        .btn-act {
            flex: 1;
            text-align: center;
            font-size: 11px;
            padding: 5px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }

        .btn-edit {
            background: #fbc02d;
            color: #3e2723;
        }

        .btn-del {
            background: #ef5350;
        }

        .empty-alert {
            color: #aaa;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand">Admin Panel</div>
        <div class="menu">
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="tambahbuku.php">Tambah Buku</a>
            <a href="listbuku.php" class="active">List Buku</a>
            <a href="riwayatpeminjaman.php">Riwayat Peminjaman</a>
            <a href="kelolaruangan.php">Kelola Ruangan</a>
            <a href="listusers.php">List Users</a>
            <a href="detailakun.php" style="margin-top: 20px; background-color: #3e2723;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Manajemen Rak Perpustakaan</h2>
            <form method="POST" class="add-rack-form">
                <input type="text" name="location" class="input-rack" placeholder="Nama Rak Baru..." required>
                <button type="submit" name="add_shelf" class="btn-add">+ Rak</button>
            </form>
        </div>

        <?php
        // LOOP UTAMA: Menampilkan Setiap Rak
        foreach ($shelves_array as $shelf):
            $current_shelf_id = $shelf['shelf_id'];
            $current_location = $shelf['location'];

            // QUERY UTAMA: Ambil buku di rak ini + JOIN Author + JOIN Category
            // Diurutkan berdasarkan Kategori dulu, baru Judul (PENTING untuk grouping)
            $query_books = "
                SELECT books.*, authors.name AS author_name, categories.name AS category_name 
                FROM books 
                LEFT JOIN authors ON books.author_id = authors.author_id 
                LEFT JOIN categories ON books.category_id = categories.category_id 
                WHERE books.shelf_id = '$current_shelf_id' 
                ORDER BY categories.name ASC, books.title ASC
            ";

            $result_books = mysqli_query($conn, $query_books);

            // LOGIC GROUPING: Masukkan hasil query ke array multidimensi [NamaKategori][ListBuku]
            $books_by_category = [];
            while ($row = mysqli_fetch_assoc($result_books)) {
                $cat_name = $row['category_name'] ? $row['category_name'] : 'Tanpa Kategori';
                $books_by_category[$cat_name][] = $row;
            }
        ?>

            <div class="rack-container">
                <div class="rack-header">
                    <span class="rack-title">📍 <?= $current_location ?></span>
                    <?php if (strtolower($current_location) != 'gudang'): ?>
                        <a href="listbuku.php?delete_shelf=<?= $current_shelf_id ?>"
                            class="btn-del-rack"
                            onclick="return confirm('Hapus <?= $current_location ?>? Semua buku akan dipindah ke GUDANG.')">
                            Hapus Rak
                        </a>
                    <?php endif; ?>
                </div>

                <div class="rack-body">
                    <?php if (empty($books_by_category)): ?>
                        <div class="empty-alert">Rak ini masih kosong. Belum ada buku.</div>
                    <?php else: ?>

                        <?php foreach ($books_by_category as $category_name => $books_list): ?>

                            <div class="category-section">
                                <div class="category-title">📂 Kategori: <?= $category_name ?></div>

                                <div class="book-grid">
                                    <?php foreach ($books_list as $b):
                                        $gambar = !empty($b['cover']) ? $b['cover'] : 'default.jpg';
                                    ?>
                                        <div class="book-card">
                                            <img src="../uploads/<?= $gambar ?>" class="book-cover" alt="Cover">
                                            <div class="book-details">
                                                <div class="b-title"><?= $b['title'] ?></div>
                                                <div class="b-author"><?= $b['author_name'] ?></div>
                                                <div class="b-stock">Stok: <?= $b['stock'] ?></div>

                                                <div class="action-area">
                                                    <form method="POST" class="move-form">
                                                        <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                                                        <select name="target_shelf" class="move-select">
                                                            <?php foreach ($shelves_array as $s_opt): ?>
                                                                <option value="<?= $s_opt['shelf_id'] ?>" <?= ($s_opt['shelf_id'] == $current_shelf_id) ? 'selected' : '' ?>>
                                                                    Pindah ke: <?= $s_opt['location'] ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" name="move_book" class="btn-move">Go</button>
                                                    </form>

                                                    <div class="btn-group">
                                                        <a href="editbuku.php?id=<?= $b['book_id'] ?>" class="btn-act btn-edit">Edit</a>
                                                        <a href="hapusbuku.php?id=<?= $b['book_id'] ?>" class="btn-act btn-del" onclick="return confirm('Hapus buku permanen?')">Hapus</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; // End Loop Buku 
                                    ?>
                                </div>
                            </div>

                        <?php endforeach; // End Loop Kategori 
                        ?>

                    <?php endif; ?>
                </div>
            </div>

        <?php endforeach; // End Loop Rak 
        ?>

        <?php if (count($shelves_array) == 0): ?>
            <p>Belum ada rak. Silakan buat rak baru di atas.</p>
        <?php endif; ?>

    </div>

</body>

</html>
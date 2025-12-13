<?php
session_start();
include '../koneksi.php';

// Cek Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// --- LOGIKA 1: TAMBAH DATA MASTER (QUICK ADD) ---

// 1. Quick Add Kategori
if (isset($_POST['quick_add_category'])) {
    $cat_name = htmlspecialchars($_POST['cat_name']);
    mysqli_query($conn, "INSERT INTO categories (name) VALUES ('$cat_name')");
    echo "<script>alert('Kategori berhasil ditambah!'); window.location='tambahbuku.php';</script>";
}

// 2. Quick Add Rak
if (isset($_POST['quick_add_shelf'])) {
    $shelf_loc = htmlspecialchars($_POST['shelf_loc']);
    mysqli_query($conn, "INSERT INTO shelves (location) VALUES ('$shelf_loc')");
    echo "<script>alert('Rak berhasil ditambah!'); window.location='tambahbuku.php';</script>";
}

// 3. Quick Add Penerbit
if (isset($_POST['quick_add_publisher'])) {
    $pub_name = htmlspecialchars($_POST['pub_name']);
    mysqli_query($conn, "INSERT INTO publishers (name) VALUES ('$pub_name')");
    echo "<script>alert('Penerbit berhasil ditambah!'); window.location='tambahbuku.php';</script>";
}

// 4. Quick Add Penulis
if (isset($_POST['quick_add_author'])) {
    $auth_name = htmlspecialchars($_POST['auth_name']);
    mysqli_query($conn, "INSERT INTO authors (name) VALUES ('$auth_name')");
    echo "<script>alert('Penulis berhasil ditambah!'); window.location='tambahbuku.php';</script>";
}

// --- LOGIKA 2: TAMBAH BUKU UTAMA ---
if (isset($_POST['tambah_buku'])) {
    $judul       = htmlspecialchars($_POST['judul']);
    // --- UPDATE: Ambil data sinopsis ---
    $sinopsis    = htmlspecialchars($_POST['sinopsis']);

    $tahun       = htmlspecialchars($_POST['tahun']);
    $stok        = htmlspecialchars($_POST['stok']);

    $author_id    = $_POST['author_id'];
    $publisher_id = $_POST['publisher_id'];
    $category_id  = $_POST['category_id'];
    $shelf_id     = $_POST['shelf_id'];

    // Upload Cover
    $cover = "default.jpg";
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
        $target_dir = "../uploads/";
        $file_name  = basename($_FILES["cover"]["name"]);
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_name = time() . '_' . rand(100, 999) . '.' . $file_ext;
            if (move_uploaded_file($_FILES["cover"]["tmp_name"], $target_dir . $new_name)) {
                $cover = $new_name;
            }
        }
    }

    // --- UPDATE: Masukkan synopsis ke query INSERT ---
    $query = "INSERT INTO books (title, synopsis, author_id, publisher_id, category_id, shelf_id, year, stock, cover) 
              VALUES ('$judul', '$sinopsis', '$author_id', '$publisher_id', '$category_id', '$shelf_id', '$tahun', '$stok', '$cover')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Buku berhasil ditambahkan!'); window.location='listbuku.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah buku: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku - Admin</title>
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
        }

        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            padding: 20px;
            position: fixed;
            height: 100%;
            z-index: 10;
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
            color: var(--text-color);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        /* Update CSS agar Textarea juga terkena style */
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fff;
            font-family: 'Poppins', sans-serif;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .btn-plus {
            background-color: #81c784;
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
        }

        .btn-plus:hover {
            background-color: #66bb6a;
        }

        .btn-submit {
            background-color: var(--sidebar-bg);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background-color: var(--active-item);
        }

        /* --- MODAL STYLE --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .modal h3 {
            margin-bottom: 15px;
            color: var(--sidebar-bg);
        }

        .btn-close {
            background-color: #d32f2f;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand">Admin Panel</div>
        <div class="menu">
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="tambahbuku.php" class="active">Tambah Buku</a>
            <a href="listbuku.php">List Buku</a>
            <a href="riwayatpeminjaman.php">Riwayat Peminjaman</a>
            <a href="kelolaruangan.php">Kelola Ruangan</a>
            <a href="listusers.php">List Users</a>
            <a href="detailakun.php" style="margin-top: 20px; background-color: #4e342e;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Tambah Buku Baru</h2>
        </div>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Judul Buku</label>
                    <input type="text" name="judul" required>
                </div>

                <div class="form-group">
                    <label>Sinopsis</label>
                    <textarea name="sinopsis" rows="4" placeholder="Masukkan ringkasan cerita..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Penulis</label>
                    <div class="input-group">
                        <select name="author_id" required>
                            <option value="">-- Pilih Penulis --</option>
                            <?php
                            $auth = mysqli_query($conn, "SELECT * FROM authors ORDER BY name ASC");
                            while ($row = mysqli_fetch_assoc($auth)) {
                                echo "<option value='{$row['author_id']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                        <button type="button" class="btn-plus" onclick="openModal('modalAuthor')">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Penerbit</label>
                    <div class="input-group">
                        <select name="publisher_id" required>
                            <option value="">-- Pilih Penerbit --</option>
                            <?php
                            $pub = mysqli_query($conn, "SELECT * FROM publishers ORDER BY name ASC");
                            while ($row = mysqli_fetch_assoc($pub)) {
                                echo "<option value='{$row['publisher_id']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                        <button type="button" class="btn-plus" onclick="openModal('modalPublisher')">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <div class="input-group">
                        <select name="category_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php
                            $cat = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
                            while ($row = mysqli_fetch_assoc($cat)) {
                                echo "<option value='{$row['category_id']}'>{$row['name']}</option>";
                            }
                            ?>
                        </select>
                        <button type="button" class="btn-plus" onclick="openModal('modalCategory')">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Lokasi Rak</label>
                    <div class="input-group">
                        <select name="shelf_id" required>
                            <option value="">-- Pilih Rak --</option>
                            <?php
                            $shelf = mysqli_query($conn, "SELECT * FROM shelves ORDER BY location ASC");
                            while ($row = mysqli_fetch_assoc($shelf)) {
                                echo "<option value='{$row['shelf_id']}'>{$row['location']}</option>";
                            }
                            ?>
                        </select>
                        <button type="button" class="btn-plus" onclick="openModal('modalShelf')">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <input type="number" name="tahun" required>
                </div>

                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" required>
                </div>

                <div class="form-group">
                    <label>Upload Cover Buku</label>
                    <input type="file" name="cover" accept="image/*">
                </div>

                <button type="submit" name="tambah_buku" class="btn-submit">Simpan Buku</button>
            </form>
        </div>
    </div>

    <div id="modalAuthor" class="modal">
        <div class="modal-content">
            <h3>Tambah Penulis Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="auth_name" placeholder="Nama Penulis" required>
                </div>
                <button type="submit" name="quick_add_author" class="btn-submit">Simpan</button>
                <button type="button" class="btn-submit btn-close" onclick="closeModal('modalAuthor')">Batal</button>
            </form>
        </div>
    </div>

    <div id="modalPublisher" class="modal">
        <div class="modal-content">
            <h3>Tambah Penerbit Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="pub_name" placeholder="Nama Penerbit" required>
                </div>
                <button type="submit" name="quick_add_publisher" class="btn-submit">Simpan</button>
                <button type="button" class="btn-submit btn-close" onclick="closeModal('modalPublisher')">Batal</button>
            </form>
        </div>
    </div>

    <div id="modalCategory" class="modal">
        <div class="modal-content">
            <h3>Tambah Kategori Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="cat_name" placeholder="Nama Kategori" required>
                </div>
                <button type="submit" name="quick_add_category" class="btn-submit">Simpan</button>
                <button type="button" class="btn-submit btn-close" onclick="closeModal('modalCategory')">Batal</button>
            </form>
        </div>
    </div>

    <div id="modalShelf" class="modal">
        <div class="modal-content">
            <h3>Tambah Lokasi Rak</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="shelf_loc" placeholder="Lokasi (e.g., Rak A1)" required>
                </div>
                <button type="submit" name="quick_add_shelf" class="btn-submit">Simpan</button>
                <button type="button" class="btn-submit btn-close" onclick="closeModal('modalShelf')">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>

</body>

</html>
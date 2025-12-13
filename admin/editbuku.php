<?php
session_start();
include '../koneksi.php';

// Cek Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

$id = $_GET['id'];

// Ambil data buku saat ini
$data = mysqli_query($conn, "SELECT * FROM books WHERE book_id = $id");
$buku = mysqli_fetch_assoc($data);

// Ambil data untuk Dropdown (Author, Publisher, Category, Shelf)
$authors    = mysqli_query($conn, "SELECT * FROM authors ORDER BY name ASC");
$publishers = mysqli_query($conn, "SELECT * FROM publishers ORDER BY name ASC");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$shelves    = mysqli_query($conn, "SELECT * FROM shelves ORDER BY location ASC");

// LOGIKA UPDATE
if (isset($_POST['update_buku'])) {
    $judul        = htmlspecialchars($_POST['judul']);
    $sinopsis     = htmlspecialchars($_POST['sinopsis']); // Tambahan Sinopsis
    $tahun        = htmlspecialchars($_POST['tahun']);
    $stok         = htmlspecialchars($_POST['stok']);
    
    // Ambil ID dari dropdown
    $author_id    = htmlspecialchars($_POST['author_id']); // Update: Pakai ID
    $publisher_id = htmlspecialchars($_POST['publisher_id']);
    $category_id  = htmlspecialchars($_POST['category_id']);
    $shelf_id     = htmlspecialchars($_POST['shelf_id']);
    
    $gambar_lama  = $_POST['cover_lama'];

    // Cek apakah user upload gambar baru?
    if ($_FILES['cover']['error'] === 0) {
        $file_name = basename($_FILES["cover"]["name"]);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_name = time() . '_' . rand(100, 999) . '.' . $file_ext;
        
        move_uploaded_file($_FILES["cover"]["tmp_name"], "../uploads/" . $new_name);
        
        if ($gambar_lama != 'default.jpg' && file_exists("../uploads/" . $gambar_lama)) {
            unlink("../uploads/" . $gambar_lama);
        }
        $cover_fix = $new_name;
    } else {
        $cover_fix = $gambar_lama;
    }

    // UPDATE QUERY
    // Perhatikan: Kolom 'author' diganti 'author_id' dan ditambah 'synopsis'
    $query = "UPDATE books SET 
              title = '$judul', 
              synopsis = '$sinopsis',
              author_id = '$author_id', 
              publisher_id = '$publisher_id', 
              category_id = '$category_id',
              shelf_id = '$shelf_id',
              year = '$tahun', 
              stock = '$stok', 
              cover = '$cover_fix' 
              WHERE book_id = $id";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil diupdate!'); window.location='listbuku.php';</script>";
    } else {
        echo "<script>alert('Gagal update data: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Buku</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #efebe9; --sidebar-bg: #5d4037; --sidebar-text: #ffffff; --active-item: #3e2723; --card-bg: #ffffff; --text-color: #4e342e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        .content { margin-left: 250px; padding: 40px; width: 100%; color: var(--text-color); }
        .card { background: var(--card-bg); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        
        /* Style Input, Select, Textarea */
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: 'Poppins', sans-serif;
        }
        
        .btn-submit { background-color: var(--sidebar-bg); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 10px;}
        .preview-img { width: 100px; margin-top: 10px; border-radius: 5px; border: 1px solid #ccc; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="menu">
            <h2 style="margin-bottom:20px; text-align:center;">Admin Panel</h2>
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="listbuku.php" class="active">Kembali ke List</a>
        </div>
    </div>

    <div class="content">
        <h2>Edit Data Buku</h2>
        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="cover_lama" value="<?= $buku['cover'] ?>">
                
                <div class="form-group">
                    <label>Judul Buku</label>
                    <input type="text" name="judul" value="<?= htmlspecialchars($buku['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Sinopsis</label>
                    <textarea name="sinopsis" rows="5" required><?= htmlspecialchars($buku['synopsis']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Penulis</label>
                    <select name="author_id" required>
                        <option value="">-- Pilih Penulis --</option>
                        <?php foreach($authors as $a): ?>
                            <option value="<?= $a['author_id'] ?>" <?= ($a['author_id'] == $buku['author_id']) ? 'selected' : '' ?>>
                                <?= $a['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Penerbit</label>
                    <select name="publisher_id" required>
                        <option value="">-- Pilih Penerbit --</option>
                        <?php foreach($publishers as $p): ?>
                            <option value="<?= $p['publisher_id'] ?>" <?= ($p['publisher_id'] == $buku['publisher_id']) ? 'selected' : '' ?>>
                                <?= $p['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['category_id'] ?>" <?= ($c['category_id'] == $buku['category_id']) ? 'selected' : '' ?>>
                                <?= $c['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lokasi Rak</label>
                    <select name="shelf_id" required>
                        <option value="">-- Pilih Rak --</option>
                        <?php foreach($shelves as $s): ?>
                            <option value="<?= $s['shelf_id'] ?>" <?= ($s['shelf_id'] == $buku['shelf_id']) ? 'selected' : '' ?>>
                                <?= $s['location'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <input type="number" name="tahun" value="<?= $buku['year'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" value="<?= $buku['stock'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Ganti Cover (Biarkan kosong jika tidak ingin ganti)</label>
                    <input type="file" name="cover" accept="image/*">
                    <br>
                    <img src="../uploads/<?= $buku['cover'] ?>" class="preview-img" onerror="this.src='../uploads/default.jpg'">
                    <br><small style="color:#888;">Cover saat ini</small>
                </div>

                <button type="submit" name="update_buku" class="btn-submit">Update Data</button>
            </form>
        </div>
    </div>

</body>
</html>
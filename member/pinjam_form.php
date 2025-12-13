<?php
session_start();
include '../koneksi.php';

// 1. Cek Login & Role
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'member') {
    header("Location: ../index.php");
    exit;
}

// 2. Cek ID Buku
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Pilih buku terlebih dahulu!'); window.location='listbuku_member.php';</script>";
    exit;
}

$id_buku = $_GET['id'];
$id_user = $_SESSION['user_id'];

// 3. Ambil Data Buku (untuk ditampilkan di form & validasi stok)
$query = "SELECT * FROM books WHERE book_id = '$id_buku'";
$result = mysqli_query($conn, $query);
$buku = mysqli_fetch_assoc($result);

if (!$buku) {
    echo "<script>alert('Buku tidak ditemukan!'); window.location='listbuku_member.php';</script>";
    exit;
}

// --- LOGIC PROSES PEMINJAMAN ---
if (isset($_POST['submit_pinjam'])) {
    $qty_pinjam = $_POST['quantity'];
    $tgl_pinjam = $_POST['loan_date'];

    // Validasi Stok di Server Side (Keamanan)
    if ($qty_pinjam > $buku['stock']) {
        echo "<script>alert('Stok tidak mencukupi!');</script>";
    } elseif ($qty_pinjam < 1) {
        echo "<script>alert('Minimal meminjam 1 buku!');</script>";
    } else {
        // Hitung Tanggal Kembali (Misal: Durasi pinjam standar 7 hari dari tgl pinjam)
        $tgl_kembali_rencana = date('Y-m-d', strtotime($tgl_pinjam . ' + 7 days'));

        // Query Insert ke tabel loans
        // Status default 'dipinjam'.
        $insert = "INSERT INTO loans (user_id, book_id, quantity, loan_date, due_date, status) 
                   VALUES ('$id_user', '$id_buku', '$qty_pinjam', '$tgl_pinjam', '$tgl_kembali_rencana', 'dipinjam')";

        if (mysqli_query($conn, $insert)) {
            // Update Stok Buku (Kurangi stok)
            $new_stock = $buku['stock'] - $qty_pinjam;
            mysqli_query($conn, "UPDATE books SET stock = '$new_stock' WHERE book_id = '$id_buku'");

            // --- PERUBAHAN DI SINI: REDIRECT KE LIST BUKU ---
            echo "<script>
                    alert('Berhasil meminjam buku! Harap ambil buku pada tanggal yang dipilih.');
                    window.location='listbuku_member.php'; 
                  </script>";
            // ------------------------------------------------
        } else {
            echo "<script>alert('Gagal memproses: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// --- PERSIAPAN TANGGAL UNTUK INPUT HTML ---
$today = date('Y-m-d'); // Hari ini
$max_date = date('Y-m-d', strtotime('+14 days')); // 2 Minggu kedepan
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman Buku</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; color: var(--text-color); }

        /* SIDEBAR */
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 40px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; font-size: 14px; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }

        /* CONTENT */
        .content { margin-left: 250px; padding: 40px; width: 100%; display: flex; justify-content: center; align-items: center; min-height: 100vh; }

        /* FORM CARD */
        .form-card {
            background: white; width: 100%; max-width: 800px;
            border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: flex; overflow: hidden; border: 1px solid #d7ccc8;
        }

        .book-preview {
            width: 40%; background-color: #f5f5f5;
            padding: 30px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
            border-right: 1px solid #eee;
        }
        .book-preview img { width: 150px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); border-radius: 5px; margin-bottom: 15px; }
        .book-preview h3 { font-size: 18px; margin-bottom: 5px; color: #3e2723; }
        .book-preview p { font-size: 14px; color: #757575; }
        .stock-badge { background-color: #e8f5e9; color: #2e7d32; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; margin-top: 10px; }

        .form-area { padding: 40px; width: 60%; }
        .form-header { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 20px; color: #3e2723; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #5d4037; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #eee; border-radius: 8px; font-family: 'Poppins'; transition: 0.3s; }
        .form-control:focus { border-color: #5d4037; outline: none; }

        .btn-submit { width: 100%; background-color: #2e7d32; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 16px; margin-top: 10px; }
        .btn-submit:hover { background-color: #1b5e20; }
        
        .btn-cancel { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #757575; font-size: 14px; }
        .btn-cancel:hover { color: #3e2723; }

        .note { font-size: 12px; color: #888; margin-top: 5px; }
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
        <div class="form-card">
            
            <div class="book-preview">
                <?php $img = !empty($buku['cover']) ? $buku['cover'] : 'default.jpg'; ?>
                <img src="../uploads/<?= $img ?>" alt="Cover">
                <h3><?= $buku['title'] ?></h3>
                <p>Tahun: <?= $buku['year'] ?></p>
                <div class="stock-badge">Sisa Stok: <?= $buku['stock'] ?></div>
            </div>

            <div class="form-area">
                <h2 class="form-header">Formulir Peminjaman</h2>
                
                <form action="" method="POST">
                    
                    <div class="form-group">
                        <label>Jumlah Buku yang Dipinjam</label>
                        <input type="number" name="quantity" class="form-control" 
                               min="1" max="<?= $buku['stock'] ?>" value="1" required 
                               oninput="checkStock(this)">
                        <div class="note">* Maksimal <?= $buku['stock'] ?> buku.</div>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Mulai Pinjam</label>
                        <input type="date" name="loan_date" class="form-control" 
                               min="<?= $today ?>" max="<?= $max_date ?>" required>
                        <div class="note">* Anda dapat membooking untuk tanggal s.d. <?= date('d M Y', strtotime($max_date)) ?>.</div>
                    </div>

                    <button type="submit" name="submit_pinjam" class="btn-submit">
                        <i class="fas fa-check-circle"></i> Konfirmasi Peminjaman
                    </button>

                    <a href="listbuku_member.php" class="btn-cancel">Batal</a>
                </form>
            </div>

        </div>
    </div>

    <script>
        function checkStock(input) {
            var max = <?= $buku['stock'] ?>;
            if (input.value > max) {
                alert("Jumlah melebihi stok yang tersedia!");
                input.value = max;
            }
            if (input.value < 1) {
                input.value = 1;
            }
        }
    </script>

</body>
</html>
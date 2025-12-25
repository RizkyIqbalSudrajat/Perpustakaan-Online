<?php
session_start();
require '../koneksi.php';

// 1. Cek Login & Role
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'member') {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$fine_id = isset($_GET['id']) ? $_GET['id'] : 0;

// ==========================================
// LOGIKA PEMBAYARAN (PROSES)
// ==========================================
if (isset($_POST['bayar'])) {
    // 1. Ambil loan_id sebelum menghapus data denda
    $cariLoan = mysqli_query($conn, "SELECT loan_id FROM fines WHERE fine_id = '$fine_id'");
    $dataLoan = mysqli_fetch_assoc($cariLoan);
    
    if ($dataLoan) {
        $loan_id = $dataLoan['loan_id'];
        
        // 2. Update status peminjaman jadi 'dikembalikan' dan set tanggal kembali hari ini
        $updateLoan = "UPDATE loans SET status = 'dikembalikan', return_date = CURRENT_DATE WHERE loan_id = '$loan_id'";
        mysqli_query($conn, $updateLoan);
        
        // 3. HAPUS data denda dari tabel fines
        $hapusDenda = "DELETE FROM fines WHERE fine_id = '$fine_id'";
        
        if (mysqli_query($conn, $hapusDenda)) {
            echo "<script>
                    alert('Pembayaran berhasil! Buku telah dikembalikan dan denda dihapus.');
                    window.location='denda.php';
                  </script>";
        } else {
            echo "<script>alert('Gagal memproses pembayaran.');</script>";
        }
    } else {
        echo "<script>alert('Data denda tidak ditemukan.'); window.location='denda.php';</script>";
    }
    exit;
}

// ==========================================
// TAMPILAN HALAMAN (VIEW)
// ==========================================

// Ambil Detail Denda untuk ditampilkan di struk
$query = "SELECT f.*, b.title, b.cover 
          FROM fines f
          JOIN loans l ON f.loan_id = l.loan_id
          JOIN books b ON l.book_id = b.book_id
          WHERE f.fine_id = ? AND f.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $fine_id, $id_user);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// Jika data tidak valid, kembalikan ke list denda
if (!$data) {
    echo "<script>alert('Data tagihan tidak ditemukan!'); window.location='denda.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Denda</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #efebe9;
            --text-color: #4e342e;
            --card-bg: #ffffff;
            --primary: #5d4037;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; color: var(--text-color); }

        .container {
            display: flex;
            gap: 30px;
            max-width: 900px;
            width: 100%;
        }

        /* CARD STYLE */
        .card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            flex: 1;
        }

        h2 { font-family: 'Playfair Display', serif; margin-bottom: 20px; color: var(--primary); }
        h4 { margin-bottom: 10px; font-weight: 600; opacity: 0.8; }

        /* DETAIL BUKU */
        .book-details { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #ccc; }
        .book-cover { width: 70px; height: 100px; object-fit: cover; border-radius: 6px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .total-row { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; font-weight: 700; font-size: 18px; color: #c62828; }

        /* PAYMENT METHOD */
        .payment-options { display: grid; gap: 10px; }
        .option {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
        }
        .option:hover { border-color: var(--primary); background-color: #fcfcfc; }
        .option input { accent-color: var(--primary); }

        .btn-confirm {
            background-color: var(--primary);
            color: white;
            border: none;
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-confirm:hover { background-color: #3e2723; }
        
        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #777;
            font-size: 14px;
        }
        .btn-cancel:hover { color: #333; }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="card">
            <h2>Rincian Tagihan</h2>
            
            <div class="book-details">
                <img src="../uploads/<?= htmlspecialchars($data['cover']) ?>" class="book-cover" onerror="this.src='../uploads/default.jpg'">
                <div>
                    <div style="font-weight: 600; margin-bottom: 5px;"><?= htmlspecialchars($data['title']) ?></div>
                    <div style="font-size: 13px; color: #777;">Keterlambatan Pengembalian</div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;">ID Denda: #<?= $data['fine_id'] ?></div>
                </div>
            </div>

            <div class="info-row">
                <span>Denda Buku</span>
                <span>Rp <?= number_format($data['amount'], 0, ',', '.') ?></span>
            </div>
            <div class="info-row">
                <span>Biaya Admin</span>
                <span>Rp 0</span>
            </div>
            
            <div class="total-row">
                <span>Total Bayar</span>
                <span>Rp <?= number_format($data['amount'], 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="card">
            <h2>Metode Pembayaran</h2>
            <form action="" method="POST">
                
                <h4>Pilih Cara Bayar</h4>
                <div class="payment-options">
                    <label class="option">
                        <input type="radio" name="method" value="qris" checked>
                        <span>QRIS (Scan Barcode)</span>
                    </label>
                    <label class="option">
                        <input type="radio" name="method" value="transfer">
                        <span>Transfer Bank (BCA/Mandiri)</span>
                    </label>
                    <label class="option">
                        <input type="radio" name="method" value="ewallet">
                        <span>E-Wallet (Gopay/OVO)</span>
                    </label>
                </div>

                <button type="submit" name="bayar" class="btn-confirm" onclick="return confirm('Apakah Anda yakin ingin membayar denda ini?')">Bayar Sekarang</button>
                <a href="denda.php" class="btn-cancel">Batal & Kembali</a>
                
            </form>
        </div>

    </div>

</body>
</html>
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

// --- LOGIKA: PROSES BOOKING ---
if (isset($_POST['book_room'])) {
    $room_id = $_POST['room_id'];
    $tgl     = $_POST['booking_date'];
    $start   = $_POST['start_time'];
    $end     = $_POST['end_time'];

    // Validasi 1: Jam selesai harus lebih besar dari jam mulai
    if (strtotime($end) <= strtotime($start)) {
        echo "<script>alert('Jam selesai harus lebih akhir dari jam mulai!');</script>";
    } 
    // Validasi 2: Tanggal tidak boleh masa lalu
    elseif (strtotime($tgl) < strtotime(date('Y-m-d'))) {
        echo "<script>alert('Tanggal booking tidak valid!');</script>";
    }
    else {
        // Cek dulu apakah ruangan masih available (untuk mencegah booking ganda di detik yang sama)
        $cek_status = mysqli_query($conn, "SELECT status FROM rooms WHERE room_id='$room_id'");
        $data_room = mysqli_fetch_assoc($cek_status);

        if ($data_room['status'] == 'booked') {
            echo "<script>alert('Maaf, ruangan ini baru saja dibooking orang lain!'); window.location='booking.php';</script>";
        } else {
            // UPDATE LANGSUNG TABEL ROOMS (Sesuai Permintaan)
            // Mengubah status jadi 'booked' dan mengisi atribut waktu
            $query_update = "UPDATE rooms SET 
                             status = 'booked',
                             booked_date = '$tgl',
                             start_time = '$start',
                             end_time = '$end'
                             WHERE room_id = '$room_id'";
            
            if (mysqli_query($conn, $query_update)) {
                echo "<script>alert('Berhasil Booking! Ruangan kini berstatus Booked.'); window.location='booking.php';</script>";
            } else {
                echo "<script>alert('Gagal melakukan booking.');</script>";
            }
        }
    }
}

// 2. Ambil Data Ruangan
$rooms = mysqli_query($conn, "SELECT * FROM rooms ORDER BY room_id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ruangan - Member</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- STYLE DASHBOARD --- */
        :root {
            --bg-color: #efebe9;
            --sidebar-bg: #5d4037;
            --sidebar-text: #ffffff;
            --active-item: #3e2723;
            --card-bg: #ffffff;
            --text-color: #4e342e;
            --accent-color: #8d6e63;
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h2 { font-family: 'Playfair Display', serif; font-size: 28px; }

        /* --- STYLE KHUSUS BOOKING --- */
        
        /* Grid Layout */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        /* Card Ruangan */
        .room-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        .room-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }

        .room-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .room-name { font-size: 18px; font-weight: 700; color: #3e2723; margin-bottom: 5px; }
        .room-cap { font-size: 12px; color: #777; margin-bottom: 10px; display: flex; align-items: center; gap: 5px; }
        .room-desc { font-size: 13px; color: #555; line-height: 1.5; margin-bottom: 15px; flex-grow: 1; }

        /* Form Mini di dalam Card */
        .booking-box {
            background-color: #f8f5f4;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #efebe9;
        }
        .form-label { font-size: 11px; font-weight: 600; color: #5d4037; display: block; margin-bottom: 4px; }
        .form-control { 
            width: 100%; padding: 8px; border: 1px solid #ddd; 
            border-radius: 4px; font-size: 13px; font-family: 'Poppins'; 
            margin-bottom: 10px;
        }
        
        .btn-book {
            width: 100%; background-color: #5d4037; color: white; border: none;
            padding: 10px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;
        }
        .btn-book:hover { background-color: #3e2723; }

        /* Tampilan Jika Sudah Dibooking */
        .booked-info {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ffcdd2;
        }
        .booked-title { font-weight: 700; font-size: 14px; margin-bottom: 5px; text-transform: uppercase; }
        .booked-detail { font-size: 12px; }

    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand">Perpus Online</div>
        <div class="menu">
            <a href="dashboardmember.php">🏠 Dashboard</a>
            <a href="listbuku_member.php">📚 List Buku</a> 
            <a href="wishlist.php">❤️ Wishlist</a>
            <a href="peminjaman.php">📖 Peminjaman</a>
            <a href="booking.php" class="active">📅 Booking Tempat</a>
            <a href="denda.php">💰 Denda</a>
            
            <a href="settings.php" style="margin-top: 30px; background-color: #3e2723;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <h2>Booking Ruangan</h2>
            <div>User: <b><?= htmlspecialchars($nama_user) ?></b></div>
        </div>

        <div class="room-grid">
            <?php if(mysqli_num_rows($rooms) > 0): ?>
                <?php while($r = mysqli_fetch_assoc($rooms)): ?>
                    <div class="room-card">
                        <img src="../uploads/<?= $r['photo'] ?>" alt="Room" class="room-img" onerror="this.src='../uploads/default_room.jpg'">
                        
                        <div class="room-content">
                            <div class="room-name"><?= htmlspecialchars($r['room_name']) ?></div>
                            <div class="room-cap"><i class="fas fa-users"></i> Kapasitas: <?= $r['capacity'] ?> Orang</div>
                            <div class="room-desc"><?= htmlspecialchars($r['description']) ?></div>
                            
                            <?php if($r['status'] == 'available'): ?>
                                <div class="booking-box">
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin booking ruangan ini?');">
                                        <input type="hidden" name="room_id" value="<?= $r['room_id'] ?>">
                                        
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" name="booking_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <div style="flex:1">
                                                <label class="form-label">Mulai</label>
                                                <input type="time" name="start_time" class="form-control" required>
                                            </div>
                                            <div style="flex:1">
                                                <label class="form-label">Selesai</label>
                                                <input type="time" name="end_time" class="form-control" required>
                                            </div>
                                        </div>

                                        <button type="submit" name="book_room" class="btn-book">
                                            Booking Sekarang
                                        </button>
                                    </form>
                                </div>

                            <?php else: ?>
                                <div class="booked-info">
                                    <div class="booked-title">⛔ Sudah Dibooking</div>
                                    <div class="booked-detail">
                                        📅 <?= date('d M Y', strtotime($r['booked_date'])) ?><br>
                                        ⏰ <?= date('H:i', strtotime($r['start_time'])) ?> - <?= date('H:i', strtotime($r['end_time'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Belum ada ruangan yang ditambahkan oleh Admin.</p>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
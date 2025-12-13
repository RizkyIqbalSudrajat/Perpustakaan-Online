<?php
session_start();
include '../koneksi.php'; // Sesuaikan path koneksi Anda

// Cek Login Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

// --- LOGIKA 1: TAMBAH RUANGAN BARU ---
if (isset($_POST['add_room'])) {
    $name = htmlspecialchars($_POST['room_name']);
    $capacity = intval($_POST['capacity']);
    $desc = htmlspecialchars($_POST['description']);
    
    // Upload Foto
    $photo = 'default_room.jpg';
    if ($_FILES['photo']['error'] === 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = 'room_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/' . $photo);
    }

    $query = "INSERT INTO rooms (room_name, capacity, description, photo, status) VALUES ('$name', '$capacity', '$desc', '$photo', 'available')";
    mysqli_query($conn, $query);
    echo "<script>alert('Ruangan berhasil ditambahkan!'); window.location='kelolaruangan.php';</script>";
}

// --- LOGIKA 2: UPDATE STATUS & DATA RUANGAN ---
if (isset($_POST['update_room'])) {
    $id = $_POST['room_id'];
    $name = htmlspecialchars($_POST['room_name']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status']; // 'available' atau 'booked'

    // Logika Status Booking
    $booked_date = "NULL";
    $start_time = "NULL";
    $end_time = "NULL";

    if ($status == 'booked') {
        // Jika status booked, ambil data tanggal/jam
        $booked_date = "'" . $_POST['booked_date'] . "'";
        $start_time = "'" . $_POST['start_time'] . "'";
        $end_time = "'" . $_POST['end_time'] . "'";
    }

    // Query Update
    $query = "UPDATE rooms SET 
              room_name='$name', 
              capacity='$capacity', 
              status='$status', 
              booked_date=$booked_date, 
              start_time=$start_time, 
              end_time=$end_time 
              WHERE room_id='$id'";
    
    if(mysqli_query($conn, $query)){
        echo "<script>alert('Data ruangan berhasil diupdate!'); window.location='kelolaruangan.php';</script>";
    } else {
        echo "<script>alert('Gagal update!');</script>";
    }
}

// --- LOGIKA 3: HAPUS RUANGAN ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Hapus file gambar jika perlu (opsional)
    mysqli_query($conn, "DELETE FROM rooms WHERE room_id='$id'");
    header("Location: kelolaruangan.php");
    exit;
}

// Ambil Data Ruangan
$rooms = mysqli_query($conn, "SELECT * FROM rooms ORDER BY room_id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ruangan - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #efebe9; --sidebar-bg: #5d4037; --sidebar-text: #ffffff; --active-item: #3e2723; --card-bg: #ffffff; --text-color: #4e342e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; color: var(--text-color); }
        
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; z-index: 100; }
        .brand { font-family: 'Playfair Display', serif; font-size: 24px; margin-bottom: 40px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        
        .content { margin-left: 250px; padding: 40px; width: 100%; }
        
        /* Form & Table Styles */
        .card { background: var(--card-bg); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: 'Poppins'; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: 600; }
        .btn-primary { background-color: #5d4037; }
        .btn-danger { background-color: #c62828; padding: 5px 10px; font-size: 12px; }
        .btn-edit { background-color: #ff9800; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block;}

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background-color: #f5f5f5; color: #3e2723; }

        /* Badge Status */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .bg-available { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .bg-booked { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

        /* Modal Simple */
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 25px; width: 50%; border-radius: 10px; }
        .close { float: right; font-size: 28px; cursor: pointer; }
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
            <a href="kelolaruangan.php" class="active">Kelola Ruangan</a>
            <a href="listusers.php">List Users</a>
            <a href="detailakun.php" style="margin-top: 20px; background-color: #4e342e;">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <h2>Kelola Ruangan</h2>
        <p style="color: #777; margin-bottom: 20px;">Tambah ruangan baru atau update status booking ruangan.</p>

        <div class="card">
            <h3>+ Tambah Ruangan Baru</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div class="form-group">
                        <label>Nama Ruangan</label>
                        <input type="text" name="room_name" class="form-control" required placeholder="Cth: Ruang Diskusi A">
                    </div>
                    <div class="form-group">
                        <label>Kapasitas (Orang)</label>
                        <input type="number" name="capacity" class="form-control" required placeholder="Cth: 10">
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label>Foto Ruangan</label>
                        <input type="file" name="photo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Singkat</label>
                        <textarea name="description" class="form-control" rows="1"></textarea>
                    </div>
                    <button type="submit" name="add_room" class="btn btn-primary" style="width: 100%;">Simpan Ruangan</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Daftar Ruangan</h3>
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">Foto</th>
                        <th>Detail Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Status Booking</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; while($row = mysqli_fetch_assoc($rooms)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <img src="../uploads/<?= $row['photo'] ?>" width="80" style="border-radius: 5px;">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['room_name']) ?></strong><br>
                            <small style="color:#777"><?= htmlspecialchars($row['description']) ?></small>
                        </td>
                        <td><?= $row['capacity'] ?> Orang</td>
                        <td>
                            <?php if($row['status'] == 'available'): ?>
                                <span class="badge bg-available">✅ Kosong / Ready</span>
                            <?php else: ?>
                                <span class="badge bg-booked">⛔ Di-Booking</span>
                                <div style="font-size: 12px; margin-top: 5px; color: #c62828;">
                                    📅 <?= date('d M Y', strtotime($row['booked_date'])) ?><br>
                                    ⏰ <?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="openModal(<?= htmlspecialchars(json_encode($row)) ?>)" class="btn btn-edit">
                                ✏️ Edit / Set Status
                            </button>
                            <a href="kelolaruangan.php?delete=<?= $row['room_id'] ?>" onclick="return confirm('Hapus ruangan ini?')" class="btn btn-danger">🗑️</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Update Status & Info Ruangan</h3>
            <form method="POST">
                <input type="hidden" name="room_id" id="modal_room_id">
                
                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <div class="form-group" style="flex:1">
                        <label>Nama Ruangan</label>
                        <input type="text" name="room_name" id="modal_room_name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label>Kapasitas</label>
                        <input type="number" name="capacity" id="modal_capacity" class="form-control" required>
                    </div>
                </div>

                <div class="form-group" style="background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                    <label>Status Saat Ini:</label>
                    <select name="status" id="modal_status" class="form-control" onchange="toggleDateFields()">
                        <option value="available">✅ Kosong (Available)</option>
                        <option value="booked">⛔ Di-Booking</option>
                    </select>

                    <div id="dateFields" style="margin-top: 15px; display: none;">
                        <div class="form-group">
                            <label>Tanggal Booking</label>
                            <input type="date" name="booked_date" id="modal_booked_date" class="form-control">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div class="form-group" style="flex:1">
                                <label>Jam Mulai</label>
                                <input type="time" name="start_time" id="modal_start_time" class="form-control">
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>Jam Selesai</label>
                                <input type="time" name="end_time" id="modal_end_time" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="update_room" class="btn btn-primary" style="width: 100%;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        // Logic Modal & Toggle Tampilan Tanggal
        function openModal(data) {
            document.getElementById('editModal').style.display = 'block';
            
            // Isi data ke form modal
            document.getElementById('modal_room_id').value = data.room_id;
            document.getElementById('modal_room_name').value = data.room_name;
            document.getElementById('modal_capacity').value = data.capacity;
            document.getElementById('modal_status').value = data.status;
            
            if(data.status === 'booked') {
                document.getElementById('modal_booked_date').value = data.booked_date;
                document.getElementById('modal_start_time').value = data.start_time;
                document.getElementById('modal_end_time').value = data.end_time;
            } else {
                // Reset jika kosong
                document.getElementById('modal_booked_date').value = '';
                document.getElementById('modal_start_time').value = '';
                document.getElementById('modal_end_time').value = '';
            }
            toggleDateFields(); // Jalankan fungsi cek status
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function toggleDateFields() {
            var status = document.getElementById('modal_status').value;
            var fields = document.getElementById('dateFields');
            var inputs = fields.querySelectorAll('input');

            if (status === 'booked') {
                fields.style.display = 'block';
                // Set required jika booked
                inputs.forEach(input => input.required = true);
            } else {
                fields.style.display = 'none';
                // Hapus required jika available
                inputs.forEach(input => input.required = false);
            }
        }

        // Close modal jika klik di luar
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
        }
    </script>

</body>
</html>
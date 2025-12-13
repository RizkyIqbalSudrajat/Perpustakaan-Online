<?php
session_start();
include '../koneksi.php';

// Cek Login
if (!isset($_SESSION['login'])) {
    header("Location: ../index.php");
    exit;
}

// Pastikan ID user tersimpan di session saat login (misal: $_SESSION['user_id'] = $row['id'])
// Jika login.php Anda belum set user_id, sesuaikan dengan nama session ID Anda.
$id_user = $_SESSION['user_id']; 

// Ambil Data User Terbaru
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$user = mysqli_fetch_assoc($query);

// LOGIKA UPDATE PROFIL
if (isset($_POST['update_profil'])) {
    $nama  = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $foto_lama = $user['photo'];
    $foto_fix = $foto_lama;

    // Cek Upload Foto
    if ($_FILES['photo']['error'] === 0) {
        $file_name = $_FILES['photo']['name'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array($file_ext, $allowed)) {
            $new_name = "user_" . $id_user . "_" . time() . "." . $file_ext;
            
            if(move_uploaded_file($file_tmp, "../uploads/" . $new_name)){
                // Hapus foto lama jika bukan default
                if ($foto_lama != 'default_user.png' && file_exists("../uploads/" . $foto_lama)) {
                    unlink("../uploads/" . $foto_lama);
                }
                $foto_fix = $new_name;
            }
        } else {
            echo "<script>alert('Format file harus JPG/PNG!');</script>";
        }
    }

    // Update Database
    $update = mysqli_query($conn, "UPDATE users SET full_name = '$nama', email = '$email', photo = '$foto_fix' WHERE id = '$id_user'");

    if ($update) {
        // Update Session Nama agar Dashboard ikut berubah
        $_SESSION['name'] = $nama;
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='detailakun.php';</script>";
    } else {
        echo "<script>alert('Gagal update profil.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Akun</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Menggunakan style yang sama agar konsisten */
        :root { --bg-color: #efebe9; --sidebar-bg: #5d4037; --sidebar-text: #ffffff; --active-item: #3e2723; --card-bg: #ffffff; --text-color: #4e342e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        
        .content { margin-left: 250px; padding: 40px; width: 100%; color: var(--text-color); }
        
        /* Style Khusus Halaman Ini */
        .profile-container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto; /* Tengah halaman */
            text-align: center;
        }

        .profile-pic-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px auto;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--sidebar-bg);
        }

        .form-group { text-align: left; margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: var(--active-item); }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; }

        .btn-update {
            background-color: var(--sidebar-bg);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            margin-bottom: 15px;
            transition: 0.3s;
        }
        .btn-update:hover { background-color: var(--active-item); }

        .btn-logout {
            display: inline-block;
            background-color: white;
            color: #d32f2f;
            padding: 10px 20px;
            border: 2px solid #d32f2f;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            width: 100%;
        }
        .btn-logout:hover { background-color: #d32f2f; color: white; }

        /* Input file styling */
        input[type="file"] { font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="menu">
            <h2 style="text-align:center; color:white; margin-bottom: 30px; font-family:'Playfair Display', serif;">Admin Panel</h2>
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="detailakun.php" class="active">⚙ Settings</a>
        </div>
    </div>

    <div class="content">
        <h2 style="text-align:center; margin-bottom:30px; font-family:'Playfair Display',serif;">Pengaturan Akun</h2>
        
        <div class="profile-container">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="profile-pic-wrapper">
                    <?php 
                        $foto = !empty($user['photo']) ? $user['photo'] : 'default_user.png'; 
                        // Cek apakah file benar-benar ada di folder
                        if (!file_exists("../uploads/" . $foto)) { $foto = 'default_user.png'; }
                    ?>
                    <img src="../uploads/<?= $foto ?>" class="profile-pic">
                </div>
                
                <div class="form-group" style="text-align:center;">
                    <label>Ganti Foto Profil</label>
                    <input type="file" name="photo" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" value="<?= $user['full_name'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $user['email'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?= strtoupper($user['role']) ?>" disabled style="background:#eee; cursor:not-allowed;">
                </div>

                <button type="submit" name="update_profil" class="btn-update">Simpan Perubahan</button>
                
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">
                
                <a href="../logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar aplikasi?')">
                    Logout / Keluar
                </a>

            </form>
        </div>
    </div>

</body>
</html>
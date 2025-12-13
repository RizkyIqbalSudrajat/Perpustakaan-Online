<?php
session_start();
include '../koneksi.php';

// Cek Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Ambil data saat ini
$query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id_user'");
$data = mysqli_fetch_assoc($query);

// Logic Update
if (isset($_POST['update_profile'])) {
    $nama = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $password_baru = $_POST['password'];

    // Jika password diisi, update password juga
    if (!empty($password_baru)) {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name = '$nama', email = '$email', password = '$password_hash' WHERE id = '$id_user'";
    } else {
        // Jika password kosong, jangan ubah password
        $sql = "UPDATE users SET full_name = '$nama', email = '$email' WHERE id = '$id_user'";
    }

    if (mysqli_query($conn, $sql)) {
        // Update Session Name agar langsung berubah di header dashboard
        $_SESSION['name'] = $nama; 
        echo "<script>alert('Profil berhasil diupdate!'); window.location='detailakun.php';</script>";
    } else {
        echo "<script>alert('Gagal update!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Akun Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #efebe9; --sidebar-bg: #5d4037; --sidebar-text: #ffffff; --active-item: #3e2723; --card-bg: #ffffff; --text-color: #4e342e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background-color: var(--sidebar-bg); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; }
        .menu a { display: block; color: var(--sidebar-text); text-decoration: none; padding: 15px; margin-bottom: 10px; border-radius: 8px; transition: 0.3s; }
        .menu a:hover, .menu a.active { background-color: var(--active-item); padding-left: 20px; }
        
        .content { margin-left: 250px; padding: 40px; width: 100%; color: var(--text-color); display: flex; justify-content: center; }
        
        .card { background: white; padding: 40px; width: 100%; max-width: 600px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .btn-submit { background-color: var(--sidebar-bg); color: white; padding: 12px; border: none; border-radius: 5px; width: 100%; cursor: pointer; font-size: 16px; }
        .btn-cancel { text-align: center; display: block; margin-top: 15px; color: #888; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="menu">
            <h2 style="text-align:center; color:white; margin-bottom:30px;">Admin Panel</h2>
            <a href="dashboardadmin.php">Dashboard</a>
            <a href="detailakun.php" class="active">Kembali</a>
        </div>
    </div>

    <div class="content">
        <div class="card">
            <h2 style="margin-bottom: 20px; font-family: 'Playfair Display', serif;">Edit Profil Saya</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="full_name" value="<?= $data['full_name'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= $data['email'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Password Baru (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti password">
                </div>

                <button type="submit" name="update_profile" class="btn-submit">Simpan Perubahan</button>
                <a href="detailakun.php" class="btn-cancel">Batal</a>
            </form>
        </div>
    </div>

</body>
</html>
<?php
session_start();
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 1. Cari user berdasarkan email
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    // 2. Jika email ditemukan
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // 3. Verifikasi Password (Cek apakah hash cocok dengan input user)
        if (password_verify($password, $row['password'])) {
            
            // Set Session Login
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['full_name'];

            // 4. LOGIKA PENGALIHAN (REDIRECT) YANG BENAR
            if ($row['role'] == 'admin') {
                // Jika Admin -> Masuk ke folder admin
                header("Location: admin/dashboardadmin.php");
            } else {
                // Jika Member -> Masuk ke dashboard utama (sejajar dengan login.php)
                header("Location: member/dashboardmember.php");
            }
            exit;
        }
    }

    // Jika Email tidak ketemu ATAU Password salah
    header("Location: index.php?error=1");
    exit;
}
?>
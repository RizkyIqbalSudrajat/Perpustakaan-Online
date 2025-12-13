<?php
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 1. Validasi Format Email Dasar (cek ada @ dan .)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid!'); window.location.href='index.php';</script>";
        exit;
    }

    // 2. [BARU] Validasi Domain Email (Hanya perbolehkan domain tertentu)
    // Ambil teks setelah tanda '@'
    $email_domain = substr(strrchr($email, "@"), 1);
    
    // Daftar domain yang diperbolehkan
    $allowed_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'icloud.com', 'binus.ac.id'];

    if (!in_array($email_domain, $allowed_domains)) {
        echo "<script>alert('Maaf, hanya menerima email dari: Gmail, Yahoo, Outlook, iCloud, atau Binus.'); window.location.href='index.php';</script>";
        exit;
    }

    // 3. Cek apakah email sudah terdaftar
    $cek_email = mysqli_query($conn, "SELECT email FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Email sudah digunakan! Silakan login.'); window.location.href='index.php';</script>";
        exit;
    }

    // 4. Enkripsi Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insert ke Database
    // Catatan: Kolom 'photo' tidak perlu ditulis disini karena di database sudah diset DEFAULT 'default_user.png'
    // Kolom 'created_at' juga otomatis terisi oleh database.
    $query = "INSERT INTO users (full_name, email, password, role) VALUES ('$fullname', '$email', '$hashed_password', 'member')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Pendaftaran Berhasil! Silakan Login.'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $query . "<br>" . mysqli_error($conn);
    }
}
?>
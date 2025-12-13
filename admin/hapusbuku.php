<?php
session_start();
include '../koneksi.php';

// Cek Admin
if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

$id = $_GET['id'];

// 1. Ambil data gambar lama dulu sebelum dihapus datanya
$result = mysqli_query($conn, "SELECT cover FROM books WHERE book_id = $id");
$row = mysqli_fetch_assoc($result);
$gambar_lama = $row['cover'];

// 2. Hapus Buku dari Database
$query = "DELETE FROM books WHERE book_id = $id";

if (mysqli_query($conn, $query)) {
    // 3. Hapus File Gambar Fisik (Jika bukan gambar default)
    if ($gambar_lama != 'default.jpg') {
        $target_file = "../uploads/" . $gambar_lama;
        if (file_exists($target_file)) {
            unlink($target_file); // unlink = delete file
        }
    }
    
    echo "<script>alert('Buku berhasil dihapus!'); window.location='listbuku.php';</script>";
} else {
    echo "<script>alert('Gagal menghapus buku'); window.location='listbuku.php';</script>";
}
?>
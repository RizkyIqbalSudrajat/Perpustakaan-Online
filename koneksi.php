<?php
$host = "localhost";
$user = "root";     // Sesuaikan dengan user database Anda
$pass = "";         // Sesuaikan dengan password database Anda
$db   = "Perpustakaan";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
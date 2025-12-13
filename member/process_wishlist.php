<?php
session_start();
include '../koneksi.php';

header('Content-Type: application/json');

// Cek Login
if (!isset($_SESSION['login'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silahkan login']);
    exit;
}

// --- PERBAIKAN UTAMA: Gunakan 'user_id' sesuai login.php ---
$user_id = $_SESSION['user_id']; 
// -----------------------------------------------------------

$book_id = isset($_POST['book_id']) ? $_POST['book_id'] : '';

if (empty($book_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Buku error']);
    exit;
}

// Cek data (Pakai user_id)
$check = mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = '$user_id' AND book_id = '$book_id'");

if (mysqli_num_rows($check) > 0) {
    // HAPUS
    mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = '$user_id' AND book_id = '$book_id'");
    echo json_encode(['status' => 'removed']);
} else {
    // TAMBAH
    mysqli_query($conn, "INSERT INTO wishlist (user_id, book_id) VALUES ('$user_id', '$book_id')");
    echo json_encode(['status' => 'added']);
}
?>
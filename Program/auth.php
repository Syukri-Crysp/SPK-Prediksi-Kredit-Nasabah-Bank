<?php
// auth.php
// File helper untuk mengamankan halaman dari akses ilegal tanpa login

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah session user telah terdaftar
if (!isset($_SESSION['user'])) {
    // Redirect ke halaman login jika belum terautentikasi
    header("Location: login.php");
    exit;
}
?>

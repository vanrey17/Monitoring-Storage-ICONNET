<?php
session_start();
require '../config/database.php';

// Pastikan hanya user yang bisa mengeksekusi file ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    exit("Akses ditolak");
}

$aksi = $_POST['aksi'] ?? '';

// AKSI: AJUKAN BARANG
if ($aksi == 'ajukan_barang') {
    $user_id = $_SESSION['user_id']; // Diambil dari session login
    $item_id = $_POST['item_id'];
    $jumlah = $_POST['jumlah'];
    
    // Set zona waktu agar tanggal realtime sesuai Waktu Indonesia Barat (Opsional)
    date_default_timezone_set('Asia/Jakarta');
    $tanggal = date('Y-m-d H:i:s'); 

    // VALIDASI KEAMANAN BACKEND: Cek ulang stok memastikan user tidak curang bypass HTML
    $cek_stok = $conn->query("SELECT stok FROM items WHERE id = $item_id")->fetch_assoc();
    
    if ($jumlah > $cek_stok['stok']) {
        $_SESSION['pesan'] = "<span style='color:red;'>Gagal: Jumlah pengajuan melebihi stok yang tersedia!</span>";
    } else {
        // Jika stok aman, masukkan ke tabel requests
        $sql = "INSERT INTO requests (user_id, item_id, jumlah, status, tanggal) 
                VALUES ('$user_id', '$item_id', '$jumlah', 'pending', '$tanggal')";
        
        if ($conn->query($sql)) {
            $_SESSION['pesan'] = "<span style='color:green;'>Berhasil: Pengajuan telah dikirim. Menunggu persetujuan Admin.</span>";
        } else {
            $_SESSION['pesan'] = "<span style='color:red;'>Terjadi kesalahan saat mengajukan barang.</span>";
        }
    }

    // Kembalikan user ke halaman dashboard/katalog
    header("Location: dashboard.php");
    exit();
}
?>
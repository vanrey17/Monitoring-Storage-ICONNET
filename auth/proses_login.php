<?php
session_start();
require '../config/database.php'; // Panggil koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password']; 

    // Query ke tabel users
    // Catatan: Ini cara paling basic. Nanti untuk keamanan lebih baik (mencegah SQL Injection), bisa di-upgrade pakai Prepared Statements.
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    // Jika data ditemukan (username & password cocok)
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Simpan data ke Session agar sistem ingat siapa yang sedang login
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Cek Role dan Arahkan ke folder yang tepat
        if ($row['role'] == 'admin') {
            header("Location: ../admin/dashboard_admin.php"); 
        } else if ($row['role'] == 'user') {
            header("Location: ../user/dashboard_user.php");
        }
        exit();
        
    } else {
        // Jika gagal, simpan pesan error ke session dan kembalikan ke form login
        $_SESSION['error'] = "Username atau Password salah!";
        header("Location: login.php");
        exit();
    }
}
?>
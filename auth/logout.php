<?php
// 1. Mulai session untuk mengenali session yang sedang berjalan
session_start();

// 2. Kosongkan semua variabel session yang sudah diset saat login
session_unset();

// 3. Hancurkan session sepenuhnya dari server
session_destroy();

// 4. Arahkan pengguna kembali ke halaman login
header("Location: login.php");

// 5. Hentikan eksekusi script lebih lanjut
exit();
?>
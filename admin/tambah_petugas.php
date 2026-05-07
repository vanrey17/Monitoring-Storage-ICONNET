<?php
session_start();
require '../config/database.php';

// Proteksi halaman, pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// LOGIKA PHP UNTUK MENDAFTARKAN AKUN BARU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_akun'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; 
    $role = $_POST['role'];

    // 1. Cek apakah username sudah ada di database
    $cek_username = $conn->query("SELECT id FROM users WHERE username = '$username'");
    
    if ($cek_username->num_rows > 0) {
        $pesan_error = "Username '<b>$username</b>' sudah digunakan! Silakan gunakan nama lain.";
    } else {
        // 2. Jika username belum ada, masukkan ke database
        $sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Akun baru dengan username '<b>$username</b>' berhasil didaftarkan sebagai " . strtoupper($role) . ".";
        } else {
            $pesan_error = "Terjadi kesalahan sistem: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Petugas - PLN Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS GLOBAL & LAYOUT SIDEBAR */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; background-color: #f4f7f6; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #0f2c59; color: #fff; display: flex; flex-direction: column; height: 100vh; }
        .sidebar-header { padding: 20px; text-align: center; background-color: #0a1f3f; }
        .sidebar-header h3 { margin: 0; font-size: 18px; color: #00bcd4; }
        .sidebar-header p { margin: 5px 0 0; font-size: 12px; color: #aaa; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex: 1; }
        .sidebar-menu li.menu-title { padding: 15px 20px 5px; font-size: 11px; color: #6c757d; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #d1d5db; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .sidebar-menu li a:hover { background-color: #1a3c70; color: #fff; border-left: 4px solid #00bcd4; }
        .sidebar-menu li a i { width: 25px; text-align: center; margin-right: 10px; }
        .sidebar-menu li a.text-danger { color: #ff6b6b; }
        
        /* CSS KONTEN UTAMA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }
        
        /* LAYOUT DUA KOLOM MODERN */
        .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }
        
        /* KARTU FORM (KIRI) */
        .card { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .card-header { display: flex; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        .card-header i { font-size: 24px; color: #00bcd4; margin-right: 15px; background: rgba(0, 188, 212, 0.1); padding: 15px; border-radius: 50%; }
        .card-header h3 { margin: 0; color: #333; font-size: 20px; }
        .card-header p { margin: 5px 0 0; color: #888; font-size: 13px; }

        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Styling Icon di dalam Input */
        .form-group .input-icon { position: absolute; left: 15px; top: 40px; color: #00bcd4; z-index: 2; }
        .form-control { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; background-color: #fcfcfc; transition: all 0.3s; position: relative; }
        .form-control:focus { border-color: #00bcd4; background-color: #fff; outline: none; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1); }
        
        /* Icon Toggle Password (Mata) */
        .toggle-password { position: absolute; right: 15px; top: 40px; color: #888; cursor: pointer; z-index: 2; }
        .toggle-password:hover { color: #333; }

        /* Tombol Submit Gradien */
        .btn-submit { background: linear-gradient(135deg, #00bcd4 0%, #0f2c59 100%); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 15px; font-weight: bold; width: 100%; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3); }

        /* KARTU PANDUAN (KANAN) */
        .info-card { background: linear-gradient(135deg, #f8fcff 0%, #e0f2fe 100%); border-radius: 10px; padding: 25px; border-left: 4px solid #00bcd4; }
        .info-card h4 { margin-top: 0; color: #0f2c59; display: flex; align-items: center; gap: 10px; font-size: 16px; }
        .info-card ul { padding-left: 20px; color: #555; font-size: 13px; line-height: 1.8; margin-bottom: 0; }
        .info-card ul li { margin-bottom: 10px; }

        .alert-success { background-color: #e6f4ea; color: #1e8e3e; padding: 15px; border-radius: 8px; border-left: 5px solid #1e8e3e; margin-bottom: 25px; font-weight: 500; }
        .alert-error { background-color: #fce8e6; color: #d93025; padding: 15px; border-radius: 8px; border-left: 5px solid #d93025; margin-bottom: 25px; font-weight: 500; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Registrasi Akun Baru</h2>
            <div>
                <span>Halo, Admin <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            <?php if(isset($pesan_sukses)) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-exclamation-triangle'></i> $pesan_error</div>"; ?>

            <div class="grid-layout">
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-plus"></i>
                        <div>
                            <h3>Formulir Tambah Petugas</h3>
                            <p>Berikan hak akses kepada tim operasional atau admin baru.</p>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Username</label>
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="username" class="form-control" placeholder="Buat username (tanpa spasi)" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Kata Sandi (Password)</label>
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="passInput" name="password" class="form-control" placeholder="Buat kata sandi minimal 6 karakter" required minlength="6">
                            <i class="fas fa-eye-slash toggle-password" id="toggleIcon" onclick="togglePassword()" title="Tampilkan Password"></i>
                        </div>

                        <div class="form-group">
                            <label>Hak Akses (Role)</label>
                            <i class="fas fa-user-shield input-icon"></i>
                            <select name="role" class="form-control" required>
                                <option value="">-- Tentukan Hak Akses --</option>
                                <option value="user">User (Teknisi Lapangan / Petugas)</option>
                                <option value="admin">Admin (Administrator Gudang)</option>
                            </select>
                        </div>

                        <button type="submit" name="tambah_akun" class="btn-submit">
                            <i class="fas fa-save"></i> Buat Akun Sekarang
                        </button>
                    </form>
                </div>

                <div class="info-card">
                    <h4><i class="fas fa-info-circle"></i> Info Hak Akses</h4>
                    <ul>
                        <li><b>User (Teknisi):</b> Hanya dapat melihat katalog barang, mengajukan permintaan material, dan melakukan retur barang. Tidak bisa mengakses halaman persetujuan.</li>
                        <li><b>Admin (Gudang):</b> Memiliki akses penuh untuk menambah stok, menyetujui barang keluar (Approval), dan memvalidasi barang retur.</li>
                        <li><b>Keamanan Akun:</b> Pastikan username bersifat unik. Hindari menggunakan password yang mudah ditebak seperti "123456" untuk keamanan data inventaris.</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var x = document.getElementById("passInput");
            var icon = document.getElementById("toggleIcon");
            if (x.type === "password") {
                x.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                x.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        }
    </script>

</body>
</html>
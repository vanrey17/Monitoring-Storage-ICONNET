<?php
session_start();

// Jika sudah ada session (sudah login), arahkan ke dashboard sesuai rolenya
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard_admin.php");
    } else {
        header("Location: ../user/dashboard_user.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PLN Icon Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reset Dasar */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            /* Latar belakang gradien biru khas korporat */
            background: linear-gradient(135deg, #0f2c59 0%, #00bcd4 100%);
            position: relative;
            overflow: hidden;
        }

        /* Efek Ornamen Background Melayang */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
        body::before {
            width: 400px; height: 400px;
            top: -100px; left: -100px;
        }
        body::after {
            width: 300px; height: 300px;
            bottom: -50px; right: -50px;
        }

        /* Kotak Login Modern */
        .login-container {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 40px 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 380px;
            text-align: center;
            position: relative;
            z-index: 10;
        }

        /* Bagian Logo & Judul */
        .logo-section {
            margin-bottom: 20px;
        }
        .pln-logo {
            max-width: 85px;
            height: auto;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f2c59;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 13px;
            color: #777;
            margin-bottom: 30px;
        }

        /* Notifikasi Error */
        .alert-error {
            color: #d93025; 
            background: #fce8e6; 
            padding: 12px 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 13px; 
            border-left: 4px solid #d93025;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
        }

        /* Form Input */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #00bcd4; /* Warna icon cyan */
            font-size: 16px;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px; /* Ruang ekstra di kiri untuk icon */
            border: 1.5px solid #e0e6ed;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #00bcd4;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.1);
        }

        /* Toggle Mata Password */
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s ease;
            z-index: 2;
        }

        .toggle-password:hover {
            color: #00bcd4;
        }

        /* Tombol Login Utama */
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #00bcd4 0%, #0a8e9e 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.2);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 188, 212, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }

        .footer-text {
            margin-top: 25px;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-section">
            <img src="../assets/img/logo-pln-plus.png" alt="Logo PLN Icon" class="pln-logo" onerror="this.style.display='none'">
        </div>

        <h2>Sistem Inventaris</h2>
        <p class="subtitle">Masuk untuk mengelola gudang & material</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error">
                <i class='fas fa-exclamation-circle' style="font-size: 16px;"></i> 
                <span><?php echo $_SESSION['error']; ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="form-control" placeholder="Nama Pengguna" required autocomplete="off">
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="passwordField" class="form-control" placeholder="Kata Sandi" required>
                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword()" title="Tampilkan Password"></i>
            </div>
            
            <button type="submit" class="btn-primary">Masuk Sekarang</button>
        </form>

        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> PLN Icon Plus
        </div>
    </div>

    <script>
        // Logika untuk menampilkan dan menyembunyikan password
        function togglePassword() {
            var inputType = document.getElementById("passwordField");
            var icon = document.querySelector(".toggle-password");
            
            if (inputType.type === "password") {
                inputType.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
                icon.style.color = "#00bcd4"; // Sorot ikon saat password terlihat
            } else {
                inputType.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
                icon.style.color = "#94a3b8"; // Kembalikan ke warna abu-abu
            }
        }
    </script>
</body>
</html>
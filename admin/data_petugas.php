<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// LOGIKA MENGHAPUS AKUN
if (isset($_GET['hapus'])) {
    $id_hapus = $conn->real_escape_string($_GET['hapus']);
    
    // Jangan biarkan admin menghapus akunnya sendiri yang sedang login
    if ($id_hapus == $_SESSION['user_id']) {
        $pesan_error = "Anda tidak dapat menghapus akun Anda sendiri saat sedang login!";
    } else {
        if ($conn->query("DELETE FROM users WHERE id = '$id_hapus'")) {
            $pesan_sukses = "Akun berhasil dihapus secara permanen.";
        } else {
            $pesan_error = "Gagal menghapus akun: " . $conn->error;
        }
    }
}

// Ambil semua data user
$result = $conn->query("SELECT * FROM users ORDER BY role ASC, username ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Akun Petugas - PLN Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS LAYOUT KONSISTEN */
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
        
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }

        /* CSS TABEL MODERN */
        .table-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-top: 4px solid #0f2c59; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        table th, table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        table tbody tr:hover { background-color: #f8fcff; }

        /* Badge Role */
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-admin { background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .badge-user { background-color: #e6f4ea; color: #1e8e3e; border: 1px solid #c3e6cb; }

        /* Tombol Aksi */
        .btn-action { padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-block; margin-right: 5px; }
        .btn-edit { background-color: #fff3cd; color: #d39e00; border: 1px solid #ffeeba; }
        .btn-edit:hover { background-color: #ffeeba; }
        .btn-delete { background-color: #fce8e6; color: #d93025; border: 1px solid #f5c6cb; }
        .btn-delete:hover { background-color: #fad2cf; }
        
        .btn-add-new { background: #00bcd4; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: bold; transition: 0.3s; float: right; margin-bottom: 15px; }
        .btn-add-new:hover { background: #0097a7; box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3); }

        .alert-success { background-color: #e6f4ea; color: #1e8e3e; padding: 15px; border-radius: 8px; border-left: 5px solid #1e8e3e; margin-bottom: 25px; }
        .alert-error { background-color: #fce8e6; color: #d93025; padding: 15px; border-radius: 8px; border-left: 5px solid #d93025; margin-bottom: 25px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Kelola Akun Petugas</h2>
            <div><span>Halo, Admin <b><?php echo $_SESSION['username']; ?></b></span></div>
        </div>

        <div class="content-area">
            <?php if(isset($pesan_sukses)) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-exclamation-triangle'></i> $pesan_error</div>"; ?>

            <div class="table-card">
                <div>
                    <h3 style="margin: 0 0 5px 0; color: #333;">Daftar Akun Sistem</h3>
                    <p style="margin: 0 0 20px 0; color: #888; font-size: 13px; display: inline-block;">Kelola hak akses pengguna aplikasi inventory.</p>
                    <a href="tambah_petugas.php" class="btn-add-new"><i class="fas fa-user-plus"></i> Tambah Akun Baru</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Hak Akses (Role)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result->num_rows > 0) {
                            $no = 1;
                            while($row = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><b><?php echo $row['username']; ?></b></td>
                            <td>
                                <?php if($row['role'] == 'admin'): ?>
                                    <span class="badge badge-admin"><i class="fas fa-user-shield"></i> Admin Gudang</span>
                                <?php else: ?>
                                    <span class="badge badge-user"><i class="fas fa-tools"></i> Teknisi / User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_petugas.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <?php if ($row['id'] != $_SESSION['user_id']): // Sembunyikan tombol hapus untuk diri sendiri ?>
                                    <a href="?hapus=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus akun <?php echo $row['username']; ?> secara permanen?')"><i class="fas fa-trash"></i> Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
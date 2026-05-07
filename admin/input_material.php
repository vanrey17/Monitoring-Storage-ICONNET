<?php
session_start();
require '../config/database.php'; // Panggil koneksi DB

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// LOGIKA PHP UNTUK MENYIMPAN DATA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_material'])) {
    // Gunakan real_escape_string untuk mencegah error jika ada tanda petik pada nama barang/merk
    $nama = $conn->real_escape_string($_POST['nama_barang']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $merk = $conn->real_escape_string($_POST['merk']);
    $stok = $conn->real_escape_string($_POST['stok']);

    // Query INSERT sekarang memasukkan data merk juga
    $sql = "INSERT INTO items (nama_barang, kategori, merk, stok) VALUES ('$nama', '$kategori', '$merk', '$stok')";
    if ($conn->query($sql)) {
        $pesan_sukses = "Berhasil! Material <b>'$nama'</b> (Merk: $merk) telah ditambahkan ke gudang.";
    } else {
        $pesan_error = "Gagal menyimpan data: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Material - PLN Inventory</title>
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

        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-group { margin-bottom: 20px; position: relative; flex: 1; min-width: 200px; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Styling Icon di dalam Input */
        .form-group .input-icon { position: absolute; left: 15px; top: 40px; color: #00bcd4; z-index: 2; }
        .form-control { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; background-color: #fcfcfc; transition: all 0.3s; position: relative; }
        .form-control:focus { border-color: #00bcd4; background-color: #fff; outline: none; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1); }

        /* Tombol Submit Gradien */
        .btn-submit { background: linear-gradient(135deg, #00bcd4 0%, #0f2c59 100%); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 15px; font-weight: bold; width: 100%; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 10px;}
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3); }

        /* KARTU PANDUAN (KANAN) */
        .info-card { background: linear-gradient(135deg, #f8fcff 0%, #e0f2fe 100%); border-radius: 10px; padding: 25px; border-left: 4px solid #00bcd4; }
        .info-card h4 { margin-top: 0; color: #0f2c59; display: flex; align-items: center; gap: 10px; font-size: 16px; }
        .info-card ul { padding-left: 20px; color: #555; font-size: 13px; line-height: 1.8; margin-bottom: 0; }
        .info-card ul li { margin-bottom: 10px; }

        .alert-success { background-color: #e6f4ea; color: #1e8e3e; padding: 15px; border-radius: 8px; border-left: 5px solid #1e8e3e; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-error { background-color: #fce8e6; color: #d93025; padding: 15px; border-radius: 8px; border-left: 5px solid #d93025; margin-bottom: 25px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Input Material Baru</h2>
            <div>
                <span>Halo, Admin <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            <?php if(isset($pesan_sukses)) echo "<div class='alert-success'><i class='fas fa-check-circle' style='font-size:18px;'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-exclamation-triangle' style='font-size:18px;'></i> $pesan_error</div>"; ?>

            <div class="grid-layout">
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-plus-square"></i>
                        <div>
                            <h3>Form Tambah Material</h3>
                            <p>Masukkan data material atau perangkat baru ke dalam database gudang.</p>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <div class="form-group" style="width: 100%;">
                            <label>Nama Material / Perangkat</label>
                            <i class="fas fa-tag input-icon"></i>
                            <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Kabel Fiber Optik Drop Core" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Kategori</label>
                                <i class="fas fa-layer-group input-icon"></i>
                                <select name="kategori" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Kabel & Jaringan">Kabel & Jaringan</option>
                                    <option value="Perangkat Aktif (Router/Switch)">Perangkat Aktif (Router/Switch)</option>
                                    <option value="Peralatan Kerja (Tools)">Peralatan Kerja (Tools)</option>
                                    <option value="Aksesoris & Lainnya">Aksesoris & Lainnya</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Merk / Brand</label>
                                <i class="fas fa-copyright input-icon"></i>
                                <input type="text" name="merk" class="form-control" placeholder="Contoh: ZTE, Huawei, Paz" required>
                            </div>
                        </div>

                        <div class="form-group" style="width: 100%;">
                            <label>Jumlah Stok Awal</label>
                            <i class="fas fa-boxes input-icon"></i>
                            <input type="number" name="stok" class="form-control" min="0" placeholder="Masukkan jumlah unit fisik" required>
                        </div>

                        <button type="submit" name="simpan_material" class="btn-submit">
                            <i class="fas fa-save"></i> Simpan ke Database
                        </button>
                    </form>
                </div>

                <div class="info-card">
                    <h4><i class="fas fa-info-circle"></i> Info Input Material</h4>
                    <ul>
                        <li><b>Kabel & Jaringan:</b> Digunakan untuk barang pasif seperti Drop Core, Patch Cord, Pigtail, dan ODP.</li>
                        <li><b>Perangkat Aktif:</b> Peralatan yang membutuhkan listrik seperti ONT/Modem, Router, Switch, dan OLT.</li>
                        <li><b>Merk Barang:</b> Jika barang bersifat generik atau tidak memiliki merk khusus (seperti Paku atau S-Clamp), bisa diisi dengan "Standard PLN" atau strip "-".</li>
                        <li><b>Penamaan:</b> Pastikan menggunakan spesifikasi lengkap (Contoh: "Router ZTE F609" bukan hanya "Router").</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
<?php
session_start();
require '../config/database.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 1. CEK ID BARANG DI URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: data_material.php");
    exit();
}

$id_barang = $conn->real_escape_string($_GET['id']);

// 2. LOGIKA UPDATE DATA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_material'])) {
    $nama = $conn->real_escape_string($_POST['nama_barang']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $merk = $conn->real_escape_string($_POST['merk']);
    $stok = $conn->real_escape_string($_POST['stok']);

    // Logika Upload Foto Baru (Jika Ada)
    $foto_update_sql = ""; // Kosongkan secara default
    
    if (isset($_FILES['foto']['name']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = '../uploads/items/';
        
        // Buat folder jika belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_name = "ITEM_" . time() . "_" . rand(100, 999) . "." . $ext;
        $tmp_name = $_FILES['foto']['tmp_name'];
        
        if (move_uploaded_file($tmp_name, $upload_dir . $foto_name)) {
            // Jika berhasil upload, tambahkan query untuk update kolom foto
            $foto_update_sql = ", foto = '$foto_name'";
        }
    }

    // Gabungkan query dengan atau tanpa foto
    $sql_update = "UPDATE items SET nama_barang = '$nama', kategori = '$kategori', merk = '$merk', stok = '$stok' $foto_update_sql WHERE id = '$id_barang'";
    
    if ($conn->query($sql_update)) {
        $pesan_sukses = "Data material <b>'$nama'</b> berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui data: " . $conn->error;
    }
}

// 3. AMBIL DATA BARANG SAAT INI UNTUK DITAMPILKAN DI FORM
$query = $conn->query("SELECT * FROM items WHERE id = '$id_barang'");
if ($query->num_rows == 0) {
    header("Location: data_material.php");
    exit();
}
$item = $query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material - PLN Inventory</title>
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
        
        /* CSS KONTEN UTAMA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }
        
        /* LAYOUT DUA KOLOM MODERN */
        .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }
        
        /* KARTU FORM (KIRI) */
        .card { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .card-header { display: flex; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; justify-content: space-between; }
        .card-header-left { display: flex; align-items: center; }
        .card-header i.main-icon { font-size: 24px; color: #ff9800; margin-right: 15px; background: rgba(255, 152, 0, 0.1); padding: 15px; border-radius: 50%; }
        .card-header h3 { margin: 0; color: #333; font-size: 20px; }
        .card-header p { margin: 5px 0 0; color: #888; font-size: 13px; }

        .btn-back { background-color: #f1f5f9; color: #475569; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-back:hover { background-color: #e2e8f0; color: #1e293b; }

        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-group { margin-bottom: 20px; position: relative; flex: 1; min-width: 200px; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-group .input-icon { position: absolute; left: 15px; top: 40px; color: #00bcd4; z-index: 2; }
        .form-control { width: 100%; padding: 12px 15px 12px 45px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; background-color: #fcfcfc; transition: all 0.3s; position: relative; }
        .form-control:focus { border-color: #00bcd4; background-color: #fff; outline: none; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1); }
        .form-control[type="file"] { padding: 9px 15px 9px 45px; }

        .btn-submit { background: linear-gradient(135deg, #ff9800 0%, #e65100 100%); color: white; border: none; padding: 15px; border-radius: 8px; font-size: 15px; font-weight: bold; width: 100%; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3); }

        /* PREVIEW FOTO KEKINIAN */
        .photo-preview-container { margin-top: 15px; display: flex; align-items: center; gap: 15px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px dashed #ddd; }
        .photo-preview { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .photo-preview-text { font-size: 12px; color: #666; line-height: 1.5; }

        /* KARTU PANDUAN (KANAN) */
        .info-card { background: linear-gradient(135deg, #f8fcff 0%, #e0f2fe 100%); border-radius: 10px; padding: 25px; border-left: 4px solid #ff9800; }
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
            <h2 style="margin: 0; font-size: 20px;">Edit Material Gudang</h2>
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
                        <div class="card-header-left">
                            <i class="fas fa-edit main-icon"></i>
                            <div>
                                <h3>Form Edit Material</h3>
                                <p>Ubah detail material atau sesuaikan stok gudang secara manual.</p>
                            </div>
                        </div>
                        <a href="data_material.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Nama Material / Perangkat</label>
                            <i class="fas fa-tag input-icon"></i>
                            <input type="text" name="nama_barang" class="form-control" value="<?php echo $item['nama_barang']; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Kategori</label>
                                <i class="fas fa-layer-group input-icon"></i>
                                <select name="kategori" class="form-control" required>
                                    <option value="Kabel & Jaringan" <?php if($item['kategori'] == 'Kabel & Jaringan') echo 'selected'; ?>>Kabel & Jaringan</option>
                                    <option value="Perangkat Aktif (Router/Switch)" <?php if($item['kategori'] == 'Perangkat Aktif (Router/Switch)') echo 'selected'; ?>>Perangkat Aktif (Router/Switch)</option>
                                    <option value="Peralatan Kerja (Tools)" <?php if($item['kategori'] == 'Peralatan Kerja (Tools)') echo 'selected'; ?>>Peralatan Kerja (Tools)</option>
                                    <option value="Aksesoris & Lainnya" <?php if($item['kategori'] == 'Aksesoris & Lainnya') echo 'selected'; ?>>Aksesoris & Lainnya</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Merk / Brand</label>
                                <i class="fas fa-copyright input-icon"></i>
                                <input type="text" name="merk" class="form-control" value="<?php echo isset($item['merk']) ? $item['merk'] : '-'; ?>" placeholder="Contoh: ZTE, Huawei, Krisbow" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Foto Material (Opsional - Kosongkan jika tidak diubah)</label>
                            <i class="fas fa-image input-icon"></i>
                            <input type="file" name="foto" id="inputFoto" class="form-control" accept="image/*" onchange="previewImage(event)">
                            
                            <div class="photo-preview-container">
                                <?php if (!empty($item['foto']) && file_exists('../uploads/items/' . $item['foto'])): ?>
                                    <img id="imgPreview" src="../uploads/items/<?php echo $item['foto']; ?>" class="photo-preview" alt="Foto Material">
                                    <div class="photo-preview-text">
                                        <b>Foto Saat Ini</b><br>
                                        Pilih file baru di atas jika ingin mengganti gambar ini.
                                    </div>
                                <?php else: ?>
                                    <img id="imgPreview" src="https://via.placeholder.com/80x80?text=No+Image" class="photo-preview" alt="Placeholder">
                                    <div class="photo-preview-text">
                                        <b>Belum ada foto</b><br>
                                        Silakan unggah foto agar teknisi mudah mengenali barang.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Penyesuaian Stok Gudang Saat Ini</label>
                            <i class="fas fa-cubes input-icon"></i>
                            <input type="number" name="stok" class="form-control" min="0" value="<?php echo $item['stok']; ?>" required style="font-weight: bold; color: #d35400;">
                        </div>

                        <button type="submit" name="update_material" class="btn-submit">
                            <i class="fas fa-save"></i> Simpan Perubahan Data
                        </button>
                    </form>
                </div>

                <div class="info-card">
                    <h4><i class="fas fa-exclamation-circle"></i> Info Update Data</h4>
                    <ul>
                        <li><b>Perubahan Nama & Foto:</b> Mengubah informasi di sini akan langsung terlihat oleh teknisi di halaman Katalog User.</li>
                        <li><b>Abaikan Foto:</b> Jika Anda tidak memilih *file* foto baru, maka gambar lama tidak akan dihapus atau diganti.</li>
                        <li><b>Koreksi Stok:</b> Lakukan perubahan angka pada kolom stok <b>HANYA</b> jika ada kesalahan input di awal atau setelah melakukan opname (audit fisik) gudang rutin.</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('imgPreview');
                output.src = reader.result;
            };
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>

</body>
</html>
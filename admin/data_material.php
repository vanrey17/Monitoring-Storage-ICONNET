<?php
session_start();
require '../config/database.php';

// Proteksi halaman admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// LOGIKA MENGHAPUS DATA
if (isset($_GET['hapus'])) {
    $id = $conn->real_escape_string($_GET['hapus']);
    
    // Ambil nama barang dan nama file foto untuk dihapus (Opsional: menghapus file foto)
    $cek_data = $conn->query("SELECT nama_barang, foto FROM items WHERE id = '$id'")->fetch_assoc();
    $nama_hapus = $cek_data ? $cek_data['nama_barang'] : 'Barang';
    $foto_hapus = $cek_data ? $cek_data['foto'] : null;

    if ($conn->query("DELETE FROM items WHERE id = '$id'")) {
        // Hapus file foto fisik jika ada agar tidak memenuhi memori server
        if ($foto_hapus && file_exists('../uploads/items/' . $foto_hapus)) {
            unlink('../uploads/items/' . $foto_hapus);
        }
        $pesan_sukses = "Data material <b>$nama_hapus</b> berhasil dihapus secara permanen.";
    } else {
        $pesan_error = "Gagal menghapus data karena sedang terhubung dengan riwayat transaksi.";
    }
}

// AMBIL SEMUA DATA BARANG
$sql = "SELECT * FROM items ORDER BY nama_barang ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Material - PLN Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS LAYOUT SIDEBAR */
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
        
        /* CSS KONTEN */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }

        /* ALERTS */
        .alert-success { background-color: #e6f4ea; color: #1e8e3e; padding: 15px; border-radius: 8px; border-left: 5px solid #1e8e3e; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background-color: #fce8e6; color: #d93025; padding: 15px; border-radius: 8px; border-left: 5px solid #d93025; margin-bottom: 20px; font-size: 14px; }

        /* HEADER & SEARCH BAR */
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; flex-wrap: wrap; }
        .filter-section { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); flex: 1; min-width: 300px; }
        
        .search-box { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; background: url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/svgs/solid/search.svg') no-repeat 15px center; background-size: 15px; transition: 0.3s; margin-bottom: 15px; }
        .search-box:focus { border-color: #00bcd4; outline: none; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1); }
        
        .quick-tags { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .quick-tags span { font-size: 13px; color: #666; font-weight: bold; margin-right: 5px; }
        .tag-btn { background: #f0f4f8; color: #333; border: 1px solid #dcdfe3; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .tag-btn:hover { background: #00bcd4; color: white; border-color: #00bcd4; }
        
        .btn-add-new { background: linear-gradient(135deg, #00bcd4 0%, #0a8e9e 100%); color: white; text-decoration: none; padding: 15px 25px; border-radius: 8px; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(0,188,212,0.2); transition: 0.3s; height: 100%; }
        .btn-add-new:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,188,212,0.3); }

        /* CARD KATALOG STYLE */
        .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .item-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); border-left: 5px solid #00bcd4; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.3s, box-shadow 0.3s; }
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.08); }
        
        .item-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .item-card h3 { margin: 0; color: #333; font-size: 17px; line-height: 1.3; padding-right: 10px; }
        
        /* Ikon & Foto */
        .item-icon { background: #e0f7fa; color: #00bcd4; width: 45px; height: 45px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .item-image { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; flex-shrink: 0; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        .item-details p { margin: 5px 0; font-size: 13px; color: #666; display: flex; align-items: center; gap: 5px; }
        .item-details span { font-weight: bold; color: #333; }
        
        .stock-badge { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; margin-top: 15px; }
        .stock-ready { background-color: #e6f4ea; color: #1e8e3e; }
        .stock-warning { background-color: #fff3cd; color: #856404; }
        .stock-empty { background-color: #fce8e6; color: #d93025; }

        /* TOMBOL AKSI ADMIN (EDIT & HAPUS) */
        .admin-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn-edit-card { flex: 1; background: #f8f9fa; color: #0f2c59; border: 1px solid #ddd; padding: 10px; border-radius: 6px; text-align: center; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-edit-card:hover { background: #e2e8f0; border-color: #cbd5e1; }
        
        .btn-delete-card { flex: 1; background: #fce8e6; color: #d93025; border: 1px solid #fad2cf; padding: 10px; border-radius: 6px; text-align: center; text-decoration: none; font-size: 13px; font-weight: bold; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-delete-card:hover { background: #fad2cf; }

        /* Pesan Kosong saat pencarian tidak ditemukan */
        .no-result { display: none; width: 100%; text-align: center; padding: 40px; color: #888; font-size: 15px; background: #fff; border-radius: 10px; grid-column: 1 / -1; box-shadow: 0 2px 8px rgba(0,0,0,0.05);}
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Data Katalog Material</h2>
            <div>
                <span>Halo, Admin <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            <?php if (isset($pesan_sukses)) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $pesan_sukses</div>"; ?>
            <?php if (isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-exclamation-triangle'></i> $pesan_error</div>"; ?>

            <div class="header-actions">
                <div class="filter-section">
                    <input type="text" id="searchInput" class="search-box" placeholder="Cari nama atau merk barang, misal: Drop Core, ZTE..." onkeyup="filterCards()">
                    
                    <div class="quick-tags">
                        <span><i class="fas fa-layer-group"></i> Filter Kategori:</span>
                        <button class="tag-btn" onclick="quickSearch('Kabel & Jaringan')">Kabel & Jaringan</button>
                        <button class="tag-btn" onclick="quickSearch('Perangkat Aktif')">Perangkat Aktif</button>
                        <button class="tag-btn" onclick="quickSearch('Peralatan')">Peralatan Kerja</button>
                        <button class="tag-btn" onclick="quickSearch('Aksesoris')">Aksesoris</button>
                        <button class="tag-btn" style="background: #ffebee; color: #c62828; border-color: #ffcdd2;" onclick="quickSearch('')"><i class="fas fa-times"></i> Reset</button>
                    </div>
                </div>
                
                <a href="input_material.php" class="btn-add-new">
                    <i class="fas fa-plus-circle" style="font-size: 18px;"></i> Tambah Material
                </a>
            </div>

            <div class="grid-container" id="katalogGrid">
                <div class="no-result" id="noResultMsg">
                    <i class="fas fa-box-open" style="font-size: 40px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                    Barang yang Anda cari tidak ditemukan dalam database.
                </div>

                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $merk = isset($row['merk']) ? $row['merk'] : '-';

                        // Tentukan Icon Fallback berdasarkan nama barang
                        $icon = "fa-box";
                        $nama_lower = strtolower($row['nama_barang']);
                        if (strpos($nama_lower, 'kabel') !== false || strpos($nama_lower, 'drop core') !== false || strpos($nama_lower, 'patchcord') !== false) $icon = "fa-network-wired";
                        if (strpos($nama_lower, 'ont') !== false || strpos($nama_lower, 'router') !== false || strpos($nama_lower, 'adaptor') !== false) $icon = "fa-wifi";
                        if (strpos($nama_lower, 'clamp') !== false || strpos($nama_lower, 'paku') !== false || strpos($nama_lower, 'sclamp') !== false || strpos($nama_lower, 'hook') !== false) $icon = "fa-tools";
                        if (strpos($nama_lower, 'splitter') !== false || strpos($nama_lower, 'konektor') !== false) $icon = "fa-plug";

                        $searchData = strtolower($row['nama_barang'] . " " . $row['kategori'] . " " . $merk);

                        echo "<div class='item-card' data-search='" . $searchData . "'>";
                        
                        echo "<div>"; // Wrapper atas
                        echo "<div class='item-header'>";
                        echo "<h3>" . $row['nama_barang'] . "</h3>";
                        
                        // LOGIKA PENAMPILAN FOTO / ICON
                        if (!empty($row['foto']) && file_exists('../uploads/items/' . $row['foto'])) {
                            // Tampilkan Foto Asli jika sudah di-upload
                            echo "<img src='../uploads/items/" . $row['foto'] . "' class='item-image' alt='Foto'>";
                        } else {
                            // Tampilkan Icon jika belum ada foto
                            echo "<div class='item-icon'><i class='fas " . $icon . "'></i></div>";
                        }
                        echo "</div>";
                        
                        echo "<div class='item-details'>";
                        echo "<p><i class='fas fa-tag' style='color:#ccc; width:15px;'></i> Kategori: <span>" . $row['kategori'] . "</span></p>";
                        echo "<p><i class='fas fa-copyright' style='color:#ccc; width:15px;'></i> Merk: <span>" . $merk . "</span></p>";
                        echo "</div>";
                        echo "</div>"; // End Wrapper atas

                        echo "<div>"; // Wrapper bawah (Stok & Tombol)
                        
                        // Badge Stok
                        if ($row['stok'] > 5) {
                            echo "<div class='stock-badge stock-ready'><i class='fas fa-check-circle'></i> Stok Aman: " . $row['stok'] . " Unit</div>";
                        } elseif ($row['stok'] > 0 && $row['stok'] <= 5) {
                            echo "<div class='stock-badge stock-warning'><i class='fas fa-exclamation-triangle'></i> Menipis: " . $row['stok'] . " Unit</div>";
                        } else {
                            echo "<div class='stock-badge stock-empty'><i class='fas fa-times-circle'></i> Stok Habis (0)</div>";
                        }

                        // Tombol Aksi
                        echo "<div class='admin-actions'>";
                        echo "<a href='edit_material.php?id=" . $row['id'] . "' class='btn-edit-card'><i class='fas fa-edit'></i> Edit</a>";
                        echo "<a href='?hapus=" . $row['id'] . "' class='btn-delete-card' onclick=\"return confirm('Apakah Anda yakin ingin menghapus " . $row['nama_barang'] . " secara permanen?');\"><i class='fas fa-trash'></i> Hapus</a>";
                        echo "</div>";

                        echo "</div>"; // End Wrapper bawah
                        
                        echo "</div>";
                    }
                } else {
                    echo "<p style='grid-column: 1 / -1; text-align: center; color: #888;'>Belum ada material yang terdaftar di database.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        function filterCards() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let cards = document.querySelectorAll('.item-card');
            let hasVisibleCards = false;

            cards.forEach(card => {
                let searchData = card.getAttribute('data-search');
                if (searchData.includes(input)) {
                    card.style.display = 'flex';
                    hasVisibleCards = true;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('noResultMsg').style.display = hasVisibleCards ? 'none' : 'block';
        }

        function quickSearch(keyword) {
            document.getElementById('searchInput').value = keyword;
            filterCards();
        }
    </script>

</body>
</html> 
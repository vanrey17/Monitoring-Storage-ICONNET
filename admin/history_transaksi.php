<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 1. TANGKAP NILAI FILTER DARI URL
$filter_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : '';
$filter_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : '';
$filter_petugas = isset($_GET['petugas']) ? $_GET['petugas'] : '';
$filter_material = isset($_GET['material']) ? $_GET['material'] : '';
$filter_tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';

// 2. BANGUN QUERY DINAMIS BERDASARKAN FILTER
$where_req = "req.status != 'pending'";
$where_ret = "ret.status != 'pending'";

if ($filter_mulai != '' && $filter_akhir != '') {
    $mulai = $filter_mulai . " 00:00:00";
    $akhir = $filter_akhir . " 23:59:59";
    $where_req .= " AND req.tanggal BETWEEN '$mulai' AND '$akhir'";
    $where_ret .= " AND ret.tanggal BETWEEN '$mulai' AND '$akhir'";
}

if ($filter_petugas != '') {
    $where_req .= " AND u.id = '$filter_petugas'";
    $where_ret .= " AND u.id = '$filter_petugas'";
}

if ($filter_material != '') {
    $where_req .= " AND i.id = '$filter_material'";
    $where_ret .= " AND i.id = '$filter_material'";
}

// 3. JALANKAN QUERY UTAMA DENGAN UNION ALL (Tambahan field mitra, no_telpon, tanggal_disetujui)
if ($filter_tipe == 'pengambilan') {
    $sql = "SELECT 'Pengambilan' as tipe, req.id, req.jumlah, req.status, req.tanggal as tgl_pengajuan, req.tanggal_disetujui as tgl_disetujui, u.username, u.mitra, u.no_telpon, i.nama_barang, NULL as foto 
            FROM requests req 
            JOIN users u ON req.user_id = u.id 
            JOIN items i ON req.item_id = i.id 
            WHERE $where_req 
            ORDER BY req.tanggal DESC";
} elseif ($filter_tipe == 'pengembalian') {
    $sql = "SELECT 'Pengembalian' as tipe, ret.id, ret.jumlah, ret.status, ret.tanggal as tgl_pengajuan, ret.tanggal_disetujui as tgl_disetujui, u.username, u.mitra, u.no_telpon, i.nama_barang, ret.foto 
            FROM returns ret 
            JOIN users u ON ret.user_id = u.id 
            JOIN items i ON ret.item_id = i.id 
            WHERE $where_ret 
            ORDER BY ret.tanggal DESC";
} else {
    $sql = "SELECT 'Pengambilan' as tipe, req.id, req.jumlah, req.status, req.tanggal as tgl_pengajuan, req.tanggal_disetujui as tgl_disetujui, u.username, u.mitra, u.no_telpon, i.nama_barang, NULL as foto 
            FROM requests req 
            JOIN users u ON req.user_id = u.id 
            JOIN items i ON req.item_id = i.id 
            WHERE $where_req
            UNION ALL
            SELECT 'Pengembalian' as tipe, ret.id, ret.jumlah, ret.status, ret.tanggal as tgl_pengajuan, ret.tanggal_disetujui as tgl_disetujui, u.username, u.mitra, u.no_telpon, i.nama_barang, ret.foto 
            FROM returns ret 
            JOIN users u ON ret.user_id = u.id 
            JOIN items i ON ret.item_id = i.id 
            WHERE $where_ret
            ORDER BY tgl_pengajuan DESC";
}

$result = $conn->query($sql);

$users = $conn->query("SELECT id, username FROM users WHERE role='user' ORDER BY username ASC");
$items = $conn->query("SELECT id, nama_barang FROM items ORDER BY nama_barang ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>History Transaksi Lengkap - PLN Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS GLOBAL & LAYOUT SIDEBAR */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; background-color: #f4f7f6; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #0f2c59; color: #fff; display: flex; flex-direction: column; height: 100vh; flex-shrink: 0; }
        .sidebar-header { padding: 20px; text-align: center; background-color: #0a1f3f; }
        .sidebar-header h3 { margin: 0; font-size: 18px; color: #00bcd4; }
        .sidebar-header p { margin: 5px 0 0; font-size: 12px; color: #aaa; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex: 1; }
        .sidebar-menu li.menu-title { padding: 15px 20px 5px; font-size: 11px; color: #6c757d; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #d1d5db; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .sidebar-menu li a:hover { background-color: #1a3c70; color: #fff; border-left: 4px solid #00bcd4; }
        .sidebar-menu li a i { width: 25px; text-align: center; margin-right: 10px; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; min-width: 0; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }
        
        /* CSS FILTER CARD */
        .filter-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-top: 4px solid #0f2c59; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group input, .filter-group select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; outline: none; background-color: #fcfcfc; width: 100%; box-sizing: border-box;}
        .filter-group input:focus, .filter-group select:focus { border-color: #00bcd4; background-color: #fff; }
        
        .filter-actions { display: flex; gap: 10px; }
        .btn-filter { background-color: #00bcd4; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 13px; height: 38px; display: flex; align-items: center; justify-content: center; gap: 5px; transition: 0.3s; flex: 1; }
        .btn-filter:hover { background-color: #0097a7; }
        .btn-reset { background-color: #fce8e6; color: #d93025; border: 1px solid #fad2cf; text-decoration: none; padding: 10px 15px; border-radius: 5px; font-weight: bold; font-size: 13px; height: 16px; line-height: 16px; display: flex; align-items: center; justify-content: center; gap: 5px; transition: 0.3s; flex: 1; text-align: center; }
        .btn-reset:hover { background-color: #fad2cf; }

        /* CSS TABEL MODERN & RESPONSIVE SCROLL */
        .table-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; min-width: 1400px; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        table th, table td { padding: 15px 12px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; vertical-align: middle;}
        table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; white-space: nowrap; }
        table tr:last-child td { border-bottom: none; }
        table tbody tr:hover { background-color: #f8fcff; }

        /* Typography Helpers */
        .tgl-text { color: #666; font-size: 12px; white-space: nowrap; }
        .mitra-text { background: #e2e8f0; color: #334155; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .telp-text { color: #0284c7; font-weight: 500; font-size: 12px; }

        /* Tipe Transaksi Badges */
        .tipe-out { color: #ff9800; font-weight: bold; display: flex; align-items: center; gap: 5px; white-space: nowrap;} 
        .tipe-in { color: #00bcd4; font-weight: bold; display: flex; align-items: center; gap: 5px; white-space: nowrap;} 

        .badge { padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; text-align: center; color: white; white-space: nowrap;}
        .badge-approved { background-color: #28a745; }
        .badge-rejected { background-color: #dc3545; }

        /* Tombol Evidence (Pengganti Foto Langsung) */
        .btn-evidence { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid #00bcd4; color: #00bcd4; background-color: transparent; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: all 0.3s ease; }
        .btn-evidence:hover { background-color: #00bcd4; color: white; box-shadow: 0 2px 8px rgba(0,188,212,0.3); }

        /* CSS UNTUK POP-UP MODAL LIGHTBOX FOTO */
        .image-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); backdrop-filter: blur(5px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .image-modal.show { display: flex; opacity: 1; }
        .modal-content { max-width: 85%; max-height: 85vh; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); transform: scale(0.8); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .image-modal.show .modal-content { transform: scale(1); }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #fff; font-size: 40px; font-weight: bold; cursor: pointer; transition: color 0.2s; text-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .close-modal:hover { color: #ff6b6b; text-decoration: none; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">History Transaksi Terperinci</h2>
            <div>
                <span>Halo, Admin <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            
            <form action="" method="GET" class="filter-card">
                <div class="filter-group">
                    <label><i class="far fa-calendar-alt"></i> Tgl Mulai</label>
                    <input type="date" name="mulai" value="<?php echo $filter_mulai; ?>">
                </div>
                
                <div class="filter-group">
                    <label><i class="far fa-calendar-check"></i> Tgl Akhir</label>
                    <input type="date" name="akhir" value="<?php echo $filter_akhir; ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-exchange-alt"></i> Tipe Transaksi</label>
                    <select name="tipe">
                        <option value="">Semua Transaksi</option>
                        <option value="pengambilan" <?php if($filter_tipe == 'pengambilan') echo 'selected'; ?>>Pengambilan (Barang Keluar)</option>
                        <option value="pengembalian" <?php if($filter_tipe == 'pengembalian') echo 'selected'; ?>>Pengembalian (Barang Masuk)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-user-tag"></i> Nama Petugas</label>
                    <select name="petugas">
                        <option value="">Semua Petugas</option>
                        <?php while($u = $users->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>" <?php if($filter_petugas == $u['id']) echo 'selected'; ?>>
                                <?php echo $u['username']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-box"></i> Material</label>
                    <select name="material">
                        <option value="">Semua Material</option>
                        <?php while($i = $items->fetch_assoc()): ?>
                            <option value="<?php echo $i['id']; ?>" <?php if($filter_material == $i['id']) echo 'selected'; ?>>
                                <?php echo $i['nama_barang']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group filter-actions">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                </div>
            </form>

            <div class="table-card">
                <p style="color: #666; font-size: 14px; margin-top: 0; margin-bottom: 20px;">
                    Catatan gabungan seluruh pengambilan dan pengembalian (retur) material yang telah diproses secara mendetail. Total data: <b><?php echo $result->num_rows; ?> transaksi</b>.
                </p>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 3%;">No</th>
                                <th>Tipe Transaksi</th>
                                <th>Tanggal Pengajuan</th>
                                <th>Tanggal Disetujui</th>
                                <th>Nama Petugas</th>
                                <th>Mitra</th>
                                <th>No. Telp</th>
                                <th>Nama Material</th>
                                <th style="text-align: center;">Evidence</th>
                                <th style="text-align: center;">Qty</th>
                                <th>Status Akhir</th>
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
                                <td>
                                    <?php if($row['tipe'] == 'Pengambilan'): ?>
                                        <span class="tipe-out"><i class="fas fa-upload"></i> Pengambilan</span>
                                    <?php else: ?>
                                        <span class="tipe-in"><i class="fas fa-download"></i> Pengembalian</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="tgl-text"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($row['tgl_pengajuan'])); ?></td>
                                
                                <td class="tgl-text">
                                    <?php if($row['tgl_disetujui']): ?>
                                        <i class="fas fa-check-double" style="color:#28a745;"></i> <?php echo date('d/m/Y H:i', strtotime($row['tgl_disetujui'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                
                                <td><b><?php echo strtoupper($row['username']); ?></b></td>
                                
                                <td><span class="mitra-text"><?php echo !empty($row['mitra']) ? $row['mitra'] : '-'; ?></span></td>
                                
                                <td class="telp-text"><?php echo !empty($row['no_telpon']) ? $row['no_telpon'] : '-'; ?></td>
                                
                                <td style="max-width: 200px; white-space: normal;"><?php echo $row['nama_barang']; ?></td>
                                
                                <td style="text-align: center;">
                                    <?php if($row['tipe'] == 'Pengembalian'): ?>
                                        <?php if(!empty($row['foto']) && file_exists('../uploads/retur/' . $row['foto'])): ?>
                                            <button type="button" class="btn-evidence" onclick="openModal('../uploads/retur/<?php echo $row['foto']; ?>')" title="Lihat Foto Bukti">
                                                <i class="fas fa-camera"></i> Bukti
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: #aaa;"><i class="fas fa-image-slash"></i> Hilang</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size: 14px; color: #ccc;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align: center;"><b><?php echo $row['jumlah']; ?></b></td>
                                
                                <td>
                                    <?php if($row['status'] == 'approved'): ?>
                                        <span class="badge badge-approved"><i class="fas fa-check-circle"></i> Disetujui</span>
                                    <?php elseif($row['status'] == 'rejected'): ?>
                                        <span class="badge badge-rejected"><i class="fas fa-times-circle"></i> Ditolak</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo "<tr><td colspan='11' align='center' style='padding: 40px; color:#999;'><i class='fas fa-search' style='font-size:30px; display:block; margin-bottom:10px; color:#ddd;'></i> Data tidak ditemukan.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div> </div>
        </div>
    </div>

    <div id="imageModal" class="image-modal" onclick="closeModal()">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="expandedImg">
    </div>

    <script>
        function openModal(imgSrc) {
            var modal = document.getElementById("imageModal");
            var modalImg = document.getElementById("expandedImg");
            
            modalImg.src = imgSrc;
            modal.classList.add("show");
            document.body.style.overflow = "hidden";
        }

        function closeModal() {
            var modal = document.getElementById("imageModal");
            modal.classList.remove("show");
            document.body.style.overflow = "auto";
        }
    </script>

</body>
</html>
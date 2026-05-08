<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ==============================================================================
// 1. QUERY UNTUK METRIK DASHBOARD UTAMA
// ==============================================================================
$total_stok_fisik = $conn->query("SELECT SUM(stok) as total FROM items")->fetch_assoc()['total'] ?? 0;
$total_barang_keluar = $conn->query("SELECT SUM(jumlah) as total FROM requests WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
$total_barang_masuk = $conn->query("SELECT SUM(jumlah) as total FROM returns WHERE status = 'approved' AND kondisi = 'Baik'")->fetch_assoc()['total'] ?? 0;

$pending_out = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status = 'pending'")->fetch_assoc()['total'];
$pending_in = $conn->query("SELECT COUNT(*) as total FROM returns WHERE status = 'pending'")->fetch_assoc()['total'];

$petugas_pengambil = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM requests")->fetch_assoc()['total'];
$petugas_pengembali = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM returns")->fetch_assoc()['total'];

// ==============================================================================
// 2. QUERY UNTUK ISI TABEL DI DALAM MODAL POP-UP
// ==============================================================================
// List Material Ready (Stok > 0)
$list_materials = $conn->query("SELECT nama_barang, kategori, merk, stok FROM items WHERE stok > 0 ORDER BY nama_barang ASC");

// List Barang Keluar Terakhir
$list_out = $conn->query("SELECT req.tanggal, u.username, i.nama_barang, req.jumlah FROM requests req JOIN users u ON req.user_id = u.id JOIN items i ON req.item_id = i.id WHERE req.status = 'approved' ORDER BY req.tanggal DESC LIMIT 50");

// List Barang Masuk Terakhir
$list_in = $conn->query("SELECT ret.tanggal, u.username, i.nama_barang, ret.jumlah, ret.kondisi FROM returns ret JOIN users u ON ret.user_id = u.id JOIN items i ON ret.item_id = i.id WHERE ret.status = 'approved' AND ret.kondisi = 'Baik' ORDER BY ret.tanggal DESC LIMIT 50");

// List Pending
$list_pending_req = $conn->query("SELECT req.tanggal, u.username, i.nama_barang, req.jumlah FROM requests req JOIN users u ON req.user_id = u.id JOIN items i ON req.item_id = i.id WHERE req.status = 'pending' ORDER BY req.tanggal ASC");
$list_pending_ret = $conn->query("SELECT ret.tanggal, u.username, i.nama_barang, ret.jumlah FROM returns ret JOIN users u ON ret.user_id = u.id JOIN items i ON ret.item_id = i.id WHERE ret.status = 'pending' ORDER BY ret.tanggal ASC");

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLN Icon Inventory</title>
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

        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }
        
        /* SECTION TITLES */
        .section-title { margin: 0 0 15px 0; font-size: 18px; color: #333; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .section-title i { color: #00bcd4; }

        /* CARD DASHBOARD (DIBAGI 3 DAN 4) */
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 35px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        
        .card { 
            background: #fff; padding: 25px 20px; border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.04); display: flex; 
            align-items: center; justify-content: space-between; 
            border-left: 5px solid #00bcd4; transition: all 0.3s ease;
            cursor: pointer; position: relative; overflow: hidden;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.08); }
        .card .info h4 { margin: 0; font-size: 13px; color: #777; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .card .info h2 { margin: 10px 0 0; font-size: 32px; color: #1a202c; }
        .card .icon i { font-size: 40px; color: rgba(0, 188, 212, 0.15); transition: 0.3s; }
        .card:hover .icon i { color: rgba(0, 188, 212, 0.4); transform: scale(1.1); }

        /* Warna Garis Kiri Card */
        .c-cyan { border-left-color: #00bcd4; }
        .c-orange { border-left-color: #fd7e14; }
        .c-teal { border-left-color: #20c997; }
        .c-yellow { border-left-color: #ffc107; }
        .c-gray { border-left-color: #6c757d; }
        .c-purple { border-left-color: #6f42c1; }
        .c-green { border-left-color: #28a745; }

        /* ===================================================================
           CSS UNTUK MODAL POP-UP & TABEL
           =================================================================== */
        .detail-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .detail-modal.show { display: flex; opacity: 1; }
        .detail-content { background: #fff; width: 100%; max-width: 750px; border-radius: 12px; box-shadow: 0 15px 30px rgba(0,0,0,0.2); overflow: hidden; transform: translateY(30px); transition: transform 0.3s ease; position: relative; }
        .detail-modal.show .detail-content { transform: translateY(0); }
        
        .detail-header { padding: 25px; color: white; text-align: center; position: relative; }
        .close-btn { position: absolute; top: 15px; right: 20px; color: white; font-size: 24px; cursor: pointer; opacity: 0.8; transition: 0.2s; }
        .close-btn:hover { opacity: 1; transform: scale(1.1); }
        .detail-header h2 { margin: 0; font-size: 26px; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px;}
        
        .detail-body { padding: 20px; }
        
        /* Tabel di dalam Modal */
        .modal-list-container { max-height: 380px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
        .modal-table { width: 100%; border-collapse: collapse; text-align: left; }
        .modal-table th, .modal-table td { padding: 12px 15px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
        .modal-table th { background: #f8fafc; position: sticky; top: 0; z-index: 1; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1); color: #475569; font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .modal-table tbody tr:hover { background-color: #f1f5f9; }
        .modal-desc-text { text-align: center; color: #555; font-size: 15px; line-height: 1.6; margin: 20px; }

        .detail-footer { padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: center; gap: 15px; }
        .btn-link-modal { background: #00bcd4; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 13px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-link-modal:hover { background: #0097a7; box-shadow: 0 4px 10px rgba(0,188,212,0.3); }
        .btn-oke { background: #e2e8f0; color: #475569; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.3s; }
        .btn-oke:hover { background: #cbd5e1; color: #1e293b; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div><h2 style="margin: 0; font-size: 20px;">Dashboard Indikator</h2></div>
            <div>
                <span>Halo, Admin <b><?php echo $_SESSION['username']; ?></b></span>
                <img src="https://ui-avatars.com/api/?name=Admin+PLN&background=00bcd4&color=fff" alt="Avatar" style="width: 35px; border-radius: 50%; vertical-align: middle; margin-left: 10px;">
            </div>
        </div>

        <div class="content-area">
            
            <h3 class="section-title"><i class="fas fa-boxes"></i> Pergerakan Material Gudang</h3>
            <div class="dashboard-cards grid-3">
                <div class="card c-cyan" onclick="openDetail('#00bcd4', 'fas fa-cubes', 'Total Fisik Unit Tersedia', 'desc-fisik', 'data_material.php')">
                    <div class="info">
                        <h4>Total Stok Ready</h4>
                        <h2><?php echo number_format($total_stok_fisik); ?> <span style="font-size:14px; color:#aaa;">Unit</span></h2>
                    </div>
                    <div class="icon"><i class="fas fa-cubes"></i></div>
                </div>

                <div class="card c-orange" onclick="openDetail('#fd7e14', 'fas fa-arrow-circle-up', 'List Barang Keluar', 'desc-out', 'data_pengambilan.php?tipe=pengambilan')">
                    <div class="info">
                        <h4>Material Keluar</h4>
                        <h2><?php echo number_format($total_barang_keluar); ?> <span style="font-size:14px; color:#aaa;">Unit</span></h2>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
                </div>

                <div class="card c-teal" onclick="openDetail('#20c997', 'fas fa-arrow-circle-down', 'List Barang Masuk', 'desc-in', 'data_pengembalian.php?tipe=pengembalian')">
                    <div class="info">
                        <h4>Material Masuk (Retur)</h4>
                        <h2><?php echo number_format($total_barang_masuk); ?> <span style="font-size:14px; color:#aaa;">Unit</span></h2>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
                </div>
            </div>

            <h3 class="section-title"><i class="fas fa-tasks"></i> Antrean & Aktivitas Petugas</h3>
            <div class="dashboard-cards grid-4">
                <div class="card c-yellow" onclick="openDetail('#ffc107', 'fas fa-hourglass-half', 'Antrean Pengambilan (<?php echo $pending_out; ?>)', 'desc-pending-req', 'persetujuan_pengambilan.php')">
                    <div class="info">
                        <h4>Pending Pengambilan</h4>
                        <h2><?php echo number_format($pending_out); ?></h2>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>

                <div class="card c-gray" onclick="openDetail('#6c757d', 'fas fa-clipboard-list', 'Pending Retur (<?php echo $pending_in; ?>)', 'desc-pending-ret', 'data_pengembalian.php')">
                    <div class="info">
                        <h4>Pending Retur</h4>
                        <h2><?php echo number_format($pending_in); ?></h2>
                    </div>
                    <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                </div>

                <div class="card c-purple" onclick="openDetail('#6f42c1', 'fas fa-user-tag', 'Petugas Pengambil', 'desc-user-req', 'data_petugas.php')">
                    <div class="info">
                        <h4>Petugas Request</h4>
                        <h2><?php echo number_format($petugas_pengambil); ?> <span style="font-size:12px; color:#aaa;">Orang</span></h2>
                    </div>
                    <div class="icon"><i class="fas fa-user-tag"></i></div>
                </div>

                <div class="card c-green" onclick="openDetail('#28a745', 'fas fa-user-check', 'Petugas Retur', 'desc-user-ret', 'data_petugas.php')">
                    <div class="info">
                        <h4>Petugas Retur</h4>
                        <h2><?php echo number_format($petugas_pengembali); ?> <span style="font-size:12px; color:#aaa;">Orang</span></h2>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>

        </div> 
    </div>

    <div style="display: none;">
        
        <div id="desc-fisik">
            <div class="modal-list-container">
                <table class="modal-table">
                    <thead><tr><th style="width: 5%;">No</th><th>Nama Material</th><th>Kategori</th><th>Merk</th><th style="text-align: center;">Ready Stok</th></tr></thead>
                    <tbody>
                        <?php 
                        if($list_materials->num_rows > 0){
                            $nm = 1;
                            while($m = $list_materials->fetch_assoc()){
                                echo "<tr>";
                                echo "<td>{$nm}</td>";
                                echo "<td><b>{$m['nama_barang']}</b></td>";
                                echo "<td>{$m['kategori']}</td>";
                                echo "<td>" . ($m['merk'] ? $m['merk'] : '-') . "</td>";
                                echo "<td style='text-align: center; font-weight: bold; color: #00bcd4;'>{$m['stok']} Unit</td>";
                                echo "</tr>";
                                $nm++;
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>Gudang kosong.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="desc-out">
            <div class="modal-list-container">
                <table class="modal-table">
                    <thead><tr><th>Tanggal</th><th>Teknisi</th><th>Material</th><th style="text-align: center;">Jumlah Keluar</th></tr></thead>
                    <tbody>
                        <?php 
                        if($list_out->num_rows > 0){
                            while($o = $list_out->fetch_assoc()){
                                echo "<tr>";
                                echo "<td style='color:#666; font-size:12px;'>" . date('d/m/y H:i', strtotime($o['tanggal'])) . "</td>";
                                echo "<td><b>{$o['username']}</b></td>";
                                echo "<td>{$o['nama_barang']}</td>";
                                echo "<td style='text-align: center; font-weight: bold; color: #fd7e14;'>- {$o['jumlah']}</td>";
                                echo "</tr>";
                            }
                        } else { echo "<tr><td colspan='4' style='text-align:center;'>Belum ada barang keluar.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="desc-in">
            <div class="modal-list-container">
                <table class="modal-table">
                    <thead><tr><th>Tanggal</th><th>Teknisi</th><th>Material</th><th style="text-align: center;">Jumlah Masuk</th></tr></thead>
                    <tbody>
                        <?php 
                        if($list_in->num_rows > 0){
                            while($i = $list_in->fetch_assoc()){
                                echo "<tr>";
                                echo "<td style='color:#666; font-size:12px;'>" . date('d/m/y H:i', strtotime($i['tanggal'])) . "</td>";
                                echo "<td><b>{$i['username']}</b></td>";
                                echo "<td>{$i['nama_barang']}</td>";
                                echo "<td style='text-align: center; font-weight: bold; color: #20c997;'>+ {$i['jumlah']}</td>";
                                echo "</tr>";
                            }
                        } else { echo "<tr><td colspan='4' style='text-align:center;'>Belum ada barang masuk/retur.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="desc-pending-req">
            <div class="modal-list-container">
                <table class="modal-table">
                    <thead><tr><th>Tanggal Ajuan</th><th>Teknisi</th><th>Material Diminta</th><th style="text-align: center;">Qty</th></tr></thead>
                    <tbody>
                        <?php 
                        if($list_pending_req->num_rows > 0){
                            while($p = $list_pending_req->fetch_assoc()){
                                echo "<tr>";
                                echo "<td style='color:#666; font-size:12px;'>" . date('d/m/y H:i', strtotime($p['tanggal'])) . "</td>";
                                echo "<td><b>{$p['username']}</b></td>";
                                echo "<td>{$p['nama_barang']}</td>";
                                echo "<td style='text-align: center; font-weight: bold; color: #ff9800;'>{$p['jumlah']}</td>";
                                echo "</tr>";
                            }
                        } else { echo "<tr><td colspan='4' style='text-align:center;'>Tidak ada antrean pengambilan.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="desc-pending-ret">
            <div class="modal-list-container">
                <table class="modal-table">
                    <thead><tr><th>Tanggal Retur</th><th>Teknisi</th><th>Material Dikembalikan</th><th style="text-align: center;">Qty</th></tr></thead>
                    <tbody>
                        <?php 
                        if($list_pending_ret->num_rows > 0){
                            while($pr = $list_pending_ret->fetch_assoc()){
                                echo "<tr>";
                                echo "<td style='color:#666; font-size:12px;'>" . date('d/m/y H:i', strtotime($pr['tanggal'])) . "</td>";
                                echo "<td><b>{$pr['username']}</b></td>";
                                echo "<td>{$pr['nama_barang']}</td>";
                                echo "<td style='text-align: center; font-weight: bold; color: #6c757d;'>{$pr['jumlah']}</td>";
                                echo "</tr>";
                            }
                        } else { echo "<tr><td colspan='4' style='text-align:center;'>Tidak ada antrean retur.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="desc-user-req"><p class="modal-desc-text">Ada sebanyak <b><?php echo $petugas_pengambil; ?> teknisi</b> berbeda yang pernah mengambil material dari gudang.</p></div>
        <div id="desc-user-ret"><p class="modal-desc-text">Tercatat <b><?php echo $petugas_pengembali; ?> teknisi</b> memiliki catatan disiplin tinggi dengan mematuhi prosedur mengembalikan (retur) barang sisa instalasi kembali ke gudang.</p></div>
    </div>
    <div id="detailModal" class="detail-modal" onclick="closeDetail(event)">
        <div class="detail-content" id="modalContentBox" onclick="event.stopPropagation()">
            <div class="detail-header" id="modalHeader">
                <span class="close-btn" onclick="closeDetail(event)">&times;</span>
                <h2 id="modalTitle"><i id="modalIcon" class="fas fa-info-circle"></i> Judul</h2>
            </div>
            
            <div class="detail-body" id="modalBodyInject"></div>
            
            <div class="detail-footer">
                <a href="#" id="modalLink" class="btn-link-modal">
                    <i class="fas fa-external-link-alt"></i> Buka Halaman Data Lengkap
                </a>
                <button type="button" class="btn-oke" onclick="closeDetail(event)">Tutup Modal</button>
            </div>
        </div>
    </div>

    <script>
        function openDetail(color, iconClass, title, sourceDataId, linkUrl) {
            var modal = document.getElementById("detailModal");
            var header = document.getElementById("modalHeader");
            var icon = document.getElementById("modalIcon");
            var titleEl = document.getElementById("modalTitle");
            var bodyInject = document.getElementById("modalBodyInject");
            var linkEl = document.getElementById("modalLink");

            header.style.backgroundColor = color;
            titleEl.innerHTML = `<i class="${iconClass}" style="margin-right:10px;"></i> ${title}`;
            
            // SUNTIK HTML LIST DATA KE MODAL
            bodyInject.innerHTML = document.getElementById(sourceDataId).innerHTML;
            
            linkEl.href = linkUrl;
            linkEl.style.backgroundColor = color;

            modal.classList.add("show");
            document.body.style.overflow = "hidden"; 
        }

        function closeDetail(event) {
            var modal = document.getElementById("detailModal");
            modal.classList.remove("show");
            document.body.style.overflow = "auto"; 
        }
    </script>

</body>
</html>
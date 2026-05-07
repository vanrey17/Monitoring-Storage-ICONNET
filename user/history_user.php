<?php
session_start();
require '../config/database.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. TANGKAP NILAI FILTER DARI URL
$filter_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : '';
$filter_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// 2. BANGUN KONDISI WHERE (Pastikan hanya data milik user yang login)
$where_clause = "requests.user_id = '$user_id'";

if ($filter_mulai != '' && $filter_akhir != '') {
    $mulai = $filter_mulai . " 00:00:00";
    $akhir = $filter_akhir . " 23:59:59";
    $where_clause .= " AND requests.tanggal BETWEEN '$mulai' AND '$akhir'";
}

if ($filter_status != '') {
    $where_clause .= " AND requests.status = '$filter_status'";
}

if ($filter_cari != '') {
    $where_clause .= " AND items.nama_barang LIKE '%$filter_cari%'";
}

// 3. JALANKAN QUERY UTAMA
$sql = "SELECT requests.*, items.nama_barang 
        FROM requests 
        JOIN items ON requests.item_id = items.id 
        WHERE $where_clause 
        ORDER BY requests.tanggal DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pengajuan - Teknisi PLN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* CSS LAYOUT KONSISTEN DENGAN SIDEBAR */
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

        /* BANNER NOTE & WELCOME */
        .welcome-banner { background: linear-gradient(135deg, #0f2c59 0%, #00bcd4 100%); border-radius: 12px; padding: 25px 30px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 5px 15px rgba(0, 188, 212, 0.2); margin-bottom: 25px; position: relative; overflow: hidden; }
        .welcome-banner .text-content { position: relative; z-index: 2; }
        .welcome-banner h2 { margin: 0 0 10px 0; font-size: 24px; }
        .welcome-banner p { margin: 0; font-size: 14px; opacity: 0.9; line-height: 1.6; max-width: 800px; }
        .welcome-banner i.bg-icon { position: absolute; right: -10px; top: -10px; font-size: 130px; opacity: 0.1; transform: rotate(-10deg); z-index: 1; }
        
        /* CSS FILTER CARD */
        .filter-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group input, .filter-group select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; background-color: #fcfcfc; width: 100%; box-sizing: border-box; transition: 0.3s; }
        .filter-group input:focus, .filter-group select:focus { border-color: #00bcd4; background-color: #fff; box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.1); }
        
        .filter-actions { display: flex; gap: 10px; }
        .btn-filter { background: linear-gradient(135deg, #00bcd4 0%, #0a8e9e 100%); color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 13px; height: 40px; display: flex; align-items: center; justify-content: center; gap: 5px; transition: 0.3s; flex: 1; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3); }
        .btn-reset { background-color: #fce8e6; color: #d93025; border: 1px solid #fad2cf; text-decoration: none; padding: 10px 15px; border-radius: 8px; font-weight: bold; font-size: 13px; height: 18px; line-height: 18px; display: flex; align-items: center; justify-content: center; gap: 5px; transition: 0.3s; flex: 1; text-align: center; }
        .btn-reset:hover { background-color: #fad2cf; transform: translateY(-2px); }

        /* CSS KHUSUS TABEL RIWAYAT */
        .table-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { display: flex; align-items: center; margin-bottom: 20px; }
        .card-header i { font-size: 24px; color: #00bcd4; margin-right: 15px; background: rgba(0, 188, 212, 0.1); padding: 12px; border-radius: 8px; }
        .card-header h3 { margin: 0; color: #333; font-size: 18px; }
        .card-header p { margin: 5px 0 0; color: #888; font-size: 13px; }

        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        table th, table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; font-size: 14px; vertical-align: middle;}
        table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        table tr:last-child td { border-bottom: none; }
        table tbody tr:hover { background-color: #f8fcff; }
        
        /* Badge Status */
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .badge-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-approved { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-rejected { background-color: #fce8e6; color: #d93025; border: 1px solid #f5c6cb; }

        /* Tombol Cetak Surat */
        .btn-cetak { background-color: #0f2c59; color: white; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; transition: 0.3s; }
        .btn-cetak:hover { background-color: #00bcd4; box-shadow: 0 2px 8px rgba(0, 188, 212, 0.3); }
        .btn-disabled { background-color: #f0f0f0; color: #aaa; padding: 6px 12px; border-radius: 6px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; cursor: not-allowed; }
    </style>
</head>
<body>

    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Riwayat Transaksi</h2>
            <div>
                <span>Halo, Teknisi <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            
            <div class="welcome-banner">
                <div class="text-content">
                    <h2>Riwayat Pengajuan Material</h2>
                    <p>Pantau seluruh status permintaan material yang telah Anda ajukan. Jika status disetujui, Anda dapat mengunduh dan mencetak Surat Jalan sebagai bukti fisik pengambilan barang di gudang.</p>
                </div>
                <i class="fas fa-history bg-icon"></i>
            </div>

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
                    <label><i class="fas fa-info-circle"></i> Status Pengajuan</label>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php if($filter_status == 'pending') echo 'selected'; ?>>Menunggu Persetujuan</option>
                        <option value="approved" <?php if($filter_status == 'approved') echo 'selected'; ?>>Disetujui</option>
                        <option value="rejected" <?php if($filter_status == 'rejected') echo 'selected'; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Cari Material</label>
                    <input type="text" name="cari" value="<?php echo $filter_cari; ?>" placeholder="Ketik nama material...">
                </div>
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                    <a href="history_user.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>

            <div class="table-card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i>
                    <div>
                        <h3>Status & Log Pengambilan</h3>
                        <p>Total data ditemukan: <b><?php echo $result->num_rows; ?> transaksi</b>.</p>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Nama Material</th>
                            <th>Jumlah</th>
                            <th>Keterangan Anda</th>
                            <th>Status Admin</th>
                            <th style="text-align: center;">Surat Jalan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td style="color: #666; font-size: 13px;">
                                        <i class="far fa-clock" style="margin-right: 5px; color: #aaa;"></i> 
                                        <?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?>
                                    </td>
                                    <td><b><?php echo $row['nama_barang']; ?></b></td>
                                    <td><?php echo $row['jumlah']; ?> unit</td>
                                    <td style="font-size: 12px; color: #777; max-width: 200px; line-height: 1.5;">
                                        "<?php echo $row['keterangan']; ?>"
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                            <?php 
                                            if($row['status'] == 'approved') echo "<i class='fas fa-check-circle'></i> Disetujui";
                                            elseif($row['status'] == 'rejected') echo "<i class='fas fa-times-circle'></i> Ditolak";
                                            else echo "<i class='fas fa-hourglass-half'></i> Menunggu";
                                            ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if($row['status'] == 'approved'): ?>
                                            <a href="cetak_surat.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-cetak">
                                                <i class="fas fa-print"></i> Cetak / PDF
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-disabled"><i class="fas fa-ban"></i> Belum Siap</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 40px 20px; color: #888;">
                                    <i class="fas fa-search" style="font-size: 40px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                                    Tidak ada riwayat transaksi yang cocok.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
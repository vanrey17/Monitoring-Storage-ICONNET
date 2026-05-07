<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 1. LOGIKA PROSES APPROVE / REJECT (Single & Massal)
if (isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];

    // JIKA ADMIN KLIK "APPROVE SEMUA"
    if ($aksi == 'approve_all') {
        $pending_query = $conn->query("SELECT * FROM requests WHERE status = 'pending'");
        $count = 0;
        
        if ($pending_query->num_rows > 0) {
            while ($req = $pending_query->fetch_assoc()) {
                $id_req = $req['id'];
                $id_barang = $req['item_id'];
                $jumlah = $req['jumlah'];

                // Kurangi stok barang di gudang
                $conn->query("UPDATE items SET stok = stok - $jumlah WHERE id = $id_barang");
                
                // Ubah status request menjadi approved
                $conn->query("UPDATE requests SET status = 'approved' WHERE id = $id_req");
                $count++;
            }
            $pesan_sukses = "<b>Luar biasa!</b> $count pengajuan pengambilan berhasil DISETUJUI SECARA MASSAL. Stok material telah dikurangi.";
        } else {
            $pesan_error = "Tidak ada pengajuan yang menunggu persetujuan.";
        }
    } 
    // JIKA ADMIN KLIK APPROVE / REJECT SATU PER SATU
    elseif (isset($_GET['id'])) {
        $id_req = $_GET['id'];
        
        $cek = $conn->query("SELECT * FROM requests WHERE id = $id_req")->fetch_assoc();
        $id_barang = $cek['item_id'];
        $jumlah = $cek['jumlah'];

        if ($aksi == 'approve') {
            $conn->query("UPDATE items SET stok = stok - $jumlah WHERE id = $id_barang");
            $conn->query("UPDATE requests SET status = 'approved' WHERE id = $id_req");
            $pesan_sukses = "Permintaan berhasil <b>DISETUJUI</b>. Stok material telah dikurangi.";
        } elseif ($aksi == 'reject') {
            $conn->query("UPDATE requests SET status = 'rejected' WHERE id = $id_req");
            $pesan_error = "Permintaan <b>DITOLAK</b>. Stok material di gudang tetap aman.";
        }
    }
}

// 2. TANGKAP NILAI FILTER DARI URL
$filter_mulai = isset($_GET['mulai']) ? $_GET['mulai'] : '';
$filter_akhir = isset($_GET['akhir']) ? $_GET['akhir'] : '';
$filter_petugas = isset($_GET['petugas']) ? $_GET['petugas'] : '';
$filter_material = isset($_GET['material']) ? $_GET['material'] : '';

// 3. BANGUN QUERY DINAMIS (Khusus untuk status 'pending')
$where_clause = "requests.status = 'pending'";

if ($filter_mulai != '' && $filter_akhir != '') {
    $mulai = $filter_mulai . " 00:00:00";
    $akhir = $filter_akhir . " 23:59:59";
    $where_clause .= " AND requests.tanggal BETWEEN '$mulai' AND '$akhir'";
}
if ($filter_petugas != '') {
    $where_clause .= " AND users.id = '$filter_petugas'";
}
if ($filter_material != '') {
    $where_clause .= " AND items.id = '$filter_material'";
}

// 4. JALANKAN QUERY UTAMA
$sql = "SELECT requests.*, users.username, items.nama_barang 
        FROM requests 
        JOIN users ON requests.user_id = users.id 
        JOIN items ON requests.item_id = items.id 
        WHERE $where_clause
        ORDER BY requests.id DESC";
$result = $conn->query($sql);

// 5. QUERY UNTUK DROPDOWN FILTER & COUNTER
$users_query = $conn->query("SELECT id, username FROM users WHERE role='user' ORDER BY username ASC");
$items_query = $conn->query("SELECT id, nama_barang FROM items ORDER BY nama_barang ASC");

// Hitung total pengajuan pending untuk tombol massal
$total_pending = $conn->query("SELECT COUNT(*) as total FROM requests WHERE status='pending'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Persetujuan Pengambilan - PLN Inventory</title>
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
        
        /* CSS KONTEN */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { background-color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .content-area { padding: 30px; }

        /* CSS FILTER CARD */
        .filter-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; border-top: 4px solid #00bcd4; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; font-weight: bold; color: #555; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group input, .filter-group select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; outline: none; background-color: #fcfcfc; }
        .filter-group input:focus, .filter-group select:focus { border-color: #00bcd4; background-color: #fff; }
        
        .btn-filter { background-color: #00bcd4; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 13px; height: 38px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-filter:hover { background-color: #0097a7; }
        .btn-reset { background-color: #fce8e6; color: #d93025; border: 1px solid #fad2cf; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 13px; height: 16px; line-height: 16px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-reset:hover { background-color: #fad2cf; }
        
        /* CSS KHUSUS TABEL & CARD */
        .table-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }

        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        table th, table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        table tr:last-child td { border-bottom: none; }
        table tbody tr:hover { background-color: #f8fcff; }
        
        /* Badge Status */
        .badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-block; text-align: center; }
        .badge-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        
        /* Action Buttons Modern */
        .btn-action { padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold; transition: 0.3s; display: inline-block; margin-right: 5px; border: none; cursor: pointer; }
        .btn-approve { background-color: #e6f4ea; color: #1e8e3e; }
        .btn-approve:hover { background-color: #ceead6; }
        .btn-approve-mass { background: linear-gradient(135deg, #1e8e3e 0%, #145c27 100%); color: white; padding: 12px 20px; font-size: 13px; }
        .btn-approve-mass:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(30, 142, 62, 0.3); }
        .btn-reject { background-color: #fce8e6; color: #d93025; }
        .btn-reject:hover { background-color: #fad2cf; }

        .alert-success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Menunggu Persetujuan</h2>
        </div>

        <div class="content-area">
            <?php if(isset($pesan_sukses)) echo "<div class='alert-success'><i class='fas fa-check-circle'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-times-circle'></i> $pesan_error</div>"; ?>

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
                    <label><i class="fas fa-user-tag"></i> Nama Petugas</label>
                    <select name="petugas">
                        <option value="">-- Semua Petugas --</option>
                        <?php while($u = $users_query->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>" <?php if($filter_petugas == $u['id']) echo 'selected'; ?>>
                                <?php echo $u['username']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-box"></i> Material</label>
                    <select name="material">
                        <option value="">-- Semua Material --</option>
                        <?php while($i = $items_query->fetch_assoc()): ?>
                            <option value="<?php echo $i['id']; ?>" <?php if($filter_material == $i['id']) echo 'selected'; ?>>
                                <?php echo $i['nama_barang']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                    <a href="data_pengambilan.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>

            <div class="table-card">
                <div class="table-header-flex">
                    <p style="color: #666; margin: 0; max-width: 65%;">
                        Daftar di bawah ini adalah pengajuan pengambilan material dari petugas yang memerlukan validasi.
                    </p>

                    <?php if($total_pending > 0): ?>
                        <a href="?aksi=approve_all" class="btn-action btn-approve-mass" onclick="return confirm('PERINGATAN: Anda akan menyetujui seluruh (<?php echo $total_pending; ?>) antrean pengajuan yang berstatus pending sekaligus. Stok gudang akan otomatis terpotong. Lanjutkan?')">
                            <i class="fas fa-check-double"></i> Approve Semua Pending (<?php echo $total_pending; ?>)
                        </a>
                    <?php endif; ?>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Waktu Pengajuan</th>
                            <th>Nama Petugas</th>
                            <th>Material</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Aksi Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td style="color: #666;"><i class="far fa-clock"></i> <?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?></td>
                            <td><b><?php echo $row['username']; ?></b></td>
                            <td>
                                <?php echo $row['nama_barang']; ?>
                                <div style="font-size: 12px; color: #777; margin-top: 5px;">
                                    Catatan: "<?php echo isset($row['keterangan']) ? $row['keterangan'] : '-'; ?>"
                                </div>
                            </td>
                            <td><b><?php echo $row['jumlah']; ?></b> unit</td>
                            <td><span class="badge badge-pending">Menunggu</span></td>
                            <td>
                                <a href="?aksi=approve&id=<?php echo $row['id']; ?>" class="btn-action btn-approve" onclick="return confirm('Setujui pengambilan material ini?')"><i class="fas fa-check"></i> Approve</a>
                                <a href="?aksi=reject&id=<?php echo $row['id']; ?>" class="btn-action btn-reject" onclick="return confirm('Tolak pengambilan material ini?')"><i class="fas fa-times"></i> Reject</a>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        } else {
                            echo "<tr><td colspan='6' align='center' style='padding: 30px; color:#999;'><i class='fas fa-check-circle' style='font-size:30px; display:block; margin-bottom:10px; color:#ddd;'></i> Semua pengajuan sudah divalidasi atau tidak ada data yang cocok dengan filter.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
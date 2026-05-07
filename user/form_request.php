<?php
session_start();
require '../config/database.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id_get = isset($_GET['id']) ? $_GET['id'] : '';

// Ambil semua daftar barang yang stoknya lebih dari 0
$items_query = $conn->query("SELECT * FROM items WHERE stok > 0 ORDER BY nama_barang ASC");

// --- LOGIKA SIMPAN PENGAJUAN MULTIPLE (MASSAL) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pengajuan'])) {
    // Tangkap data array JSON dari input hidden (dikirim via JavaScript)
    $cart_data = json_decode($_POST['cart_data'], true);
    
    if (empty($cart_data)) {
        $pesan_error = "Daftar permintaan masih kosong! Tambahkan minimal 1 barang.";
    } else {
        $berhasil = 0;
        $gagal = 0;

        // Looping setiap barang yang ada di keranjang
        foreach ($cart_data as $req) {
            $item_id_post = $conn->real_escape_string($req['item_id']);
            $jumlah_diminta = $conn->real_escape_string($req['jumlah']);
            $keterangan = $conn->real_escape_string($req['keterangan']);

            // Validasi ulang stok di database untuk keamanan (mencegah inspect element)
            $cek_stok = $conn->query("SELECT stok FROM items WHERE id = '$item_id_post'")->fetch_assoc();
            
            if ($jumlah_diminta <= $cek_stok['stok']) {
                $sql_insert = "INSERT INTO requests (user_id, item_id, jumlah, keterangan, status, tanggal) 
                               VALUES ('$user_id', '$item_id_post', '$jumlah_diminta', '$keterangan', 'pending', NOW())";
                if ($conn->query($sql_insert)) {
                    $berhasil++;
                } else {
                    $gagal++;
                }
            } else {
                $gagal++; // Gagal karena stok tidak cukup saat disubmit
            }
        }

        // Redirect jika ada yang berhasil disimpan
        if ($berhasil > 0) {
            $_SESSION['pesan'] = "<b>$berhasil jenis material</b> berhasil diajukan dan menunggu persetujuan Admin." . ($gagal > 0 ? " ($gagal gagal karena kehabisan stok)." : "");
            header("Location: history_user.php"); // Lempar ke halaman riwayat
            exit();
        } else {
            $pesan_error = "Gagal memproses pengajuan. Pastikan stok barang mencukupi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Material - Teknisi</title>
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
        
        /* CSS FORM MULTI ADD */
        .card { background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; border-top: 4px solid #00bcd4; }
        .card-header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        .card-header i { font-size: 20px; color: #00bcd4; margin-right: 15px; background: rgba(0, 188, 212, 0.1); padding: 12px; border-radius: 50%; }
        .card-header h3 { margin: 0; color: #333; font-size: 18px; }

        .form-row { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #fcfcfc; transition: 0.3s; }
        .form-control:focus { border-color: #00bcd4; outline: none; background-color: #fff; }
        .form-control[readonly] { background-color: #f0f4f8; color: #1e8e3e; font-weight: bold; cursor: not-allowed; border-color: #ceead6; }
        
        .btn-add { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.3s; height: 39px; white-space: nowrap; margin-top: 25px; }
        .btn-add:hover { background: #bae6fd; }

        /* TABEL KERANJANG BAWAH */
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
        table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 11px; }
        
        .btn-remove { background: #fce8e6; color: #d93025; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.3s; }
        .btn-remove:hover { background: #fad2cf; }

        .btn-submit-all { background: linear-gradient(135deg, #1e8e3e 0%, #145c27 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.3s; display: block; width: 100%; text-align: center; margin-top: 20px; }
        .btn-submit-all:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(30, 142, 62, 0.3); }
        .btn-submit-all:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }

        .alert-error { background-color: #fce8e6; color: #d93025; padding: 15px; border-radius: 8px; border-left: 5px solid #d93025; margin-bottom: 25px; }
        .empty-cart { text-align: center; padding: 30px; color: #888; font-style: italic; font-size: 13px; }
    </style>
</head>
<body>

    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Form Pengambilan Material</h2>
            <div>
                <span>Halo, Teknisi <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            <?php if(isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-exclamation-triangle'></i> $pesan_error</div>"; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cart-plus"></i>
                    <div>
                        <h3>Pilih Material</h3>
                        <p style="margin:0; font-size:12px; color:#777;">Anda dapat menambahkan lebih dari satu jenis material ke dalam daftar sebelum diajukan.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Pilih Material yang Dibutuhkan</label>
                        <select id="itemSelect" class="form-control" onchange="updateStok()">
                            <option value="">-- Klik untuk mencari material --</option>
                            <?php 
                            if($items_query->num_rows > 0) {
                                while($row = $items_query->fetch_assoc()) {
                                    $selected = ($item_id_get == $row['id']) ? 'selected' : '';
                                    echo "<option value='".$row['id']."' data-stok='".$row['stok']."' data-name='".$row['nama_barang']."' $selected>";
                                    echo $row['nama_barang'] . " (" . $row['kategori'] . ")";
                                    echo "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label>Sisa Stok</label>
                        <input type="text" id="stokTersedia" class="form-control" value="0 Unit" readonly>
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label>Jumlah</label>
                        <input type="number" id="jumlahDiminta" class="form-control" min="1" placeholder="Qty">
                    </div>
                </div>

                <div class="form-row" style="align-items: center; margin-bottom: 0;">
                    <div class="form-group" style="flex: 3;">
                        <label>Tujuan / Keterangan Pekerjaan</label>
                        <input type="text" id="keteranganKerja" class="form-control" placeholder="Contoh: Pasang baru tiket #12345 a.n Budi di Jl. Sudirman">
                    </div>
                    <button type="button" class="btn-add" onclick="tambahKeDaftar()">
                        <i class="fas fa-plus"></i> Tambah ke List
                    </button>
                </div>
            </div>

            <div class="card" style="border-top-color: #1e8e3e;">
                <div class="card-header">
                    <i class="fas fa-clipboard-list" style="color: #1e8e3e; background: rgba(30,142,62,0.1);"></i>
                    <div>
                        <h3>Daftar Material yang Akan Diajukan</h3>
                        <p style="margin:0; font-size:12px; color:#777;">Periksa kembali daftar ini. Klik 'Kirim Semua Pengajuan' jika sudah selesai memilih.</p>
                    </div>
                </div>

                <table id="cartTable">
                    <thead>
                        <tr>
                            <th>Nama Material</th>
                            <th style="width: 80px; text-align: center;">Jumlah</th>
                            <th>Keterangan Pekerjaan</th>
                            <th style="width: 80px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr><td colspan="4" class="empty-cart">Belum ada material yang ditambahkan ke keranjang.</td></tr>
                    </tbody>
                </table>

                <form action="" method="POST" id="formSubmitAll">
                    <input type="hidden" name="cart_data" id="cartDataInput">
                    <button type="submit" name="submit_pengajuan" id="btnSubmitAll" class="btn-submit-all" disabled>
                        <i class="fas fa-paper-plane"></i> Kirim Semua Pengajuan
                    </button>
                </form>
            </div>

        </div>
    </div>

    <script>
        // Array untuk menyimpan data sementara (keranjang)
        let cart = [];

        // Fungsi membaca stok dari dropdown yang dipilih
        function updateStok() {
            let select = document.getElementById("itemSelect");
            let selectedOption = select.options[select.selectedIndex];
            
            if (select.value !== "") {
                let stok = selectedOption.getAttribute("data-stok");
                document.getElementById("stokTersedia").value = stok + " Unit";
                document.getElementById("jumlahDiminta").max = stok;
            } else {
                document.getElementById("stokTersedia").value = "0 Unit";
                document.getElementById("jumlahDiminta").max = 0;
            }
        }

        // Panggil saat halaman pertama dibuka (penting jika dibuka dari Dashboard)
        window.onload = updateStok;

        // Fungsi memasukkan barang dari form ke array JS
        function tambahKeDaftar() {
            let select = document.getElementById("itemSelect");
            let id_barang = select.value;
            
            if (id_barang === "") {
                alert("Silakan pilih material terlebih dahulu!");
                return;
            }

            let nama_barang = select.options[select.selectedIndex].getAttribute("data-name");
            let max_stok = parseInt(select.options[select.selectedIndex].getAttribute("data-stok"));
            let jumlah = parseInt(document.getElementById("jumlahDiminta").value);
            let keterangan = document.getElementById("keteranganKerja").value;

            // Validasi Input
            if (isNaN(jumlah) || jumlah <= 0) {
                alert("Masukkan jumlah permintaan yang valid!");
                return;
            }
            if (jumlah > max_stok) {
                alert("Jumlah yang diminta melebihi batas stok (" + max_stok + ")!");
                return;
            }
            if (keterangan.trim() === "") {
                alert("Keterangan pekerjaan tidak boleh kosong!");
                return;
            }

            // Cek apakah barang sudah di-add sebelumnya (mencegah double input)
            let indexAda = cart.findIndex(item => item.item_id === id_barang);
            if(indexAda !== -1) {
                alert("Barang ini sudah ada di keranjang! Hapus yang lama jika ingin mengubah jumlahnya.");
                return;
            }

            // Masukkan data ke array cart
            cart.push({
                item_id: id_barang,
                nama_barang: nama_barang,
                jumlah: jumlah,
                keterangan: keterangan
            });

            // Bersihkan form input untuk barang selanjutnya
            document.getElementById("jumlahDiminta").value = "";
            document.getElementById("keteranganKerja").value = "";
            select.value = "";
            updateStok();

            // Refresh tampilan tabel
            renderTabel();
        }

        // Fungsi menghapus barang dari keranjang
        function hapusDariDaftar(index) {
            cart.splice(index, 1);
            renderTabel();
        }

        // Fungsi menampilkan array cart ke HTML & menyiapkan data JSON
        function renderTabel() {
            let tbody = document.getElementById("cartBody");
            let btnSubmit = document.getElementById("btnSubmitAll");
            let dataInput = document.getElementById("cartDataInput");

            tbody.innerHTML = "";

            if (cart.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-cart">Belum ada material yang ditambahkan ke keranjang.</td></tr>';
                btnSubmit.disabled = true;
                dataInput.value = "";
                return;
            }

            btnSubmit.disabled = false;
            
            // CONVERT ARRAY KE JSON UNTUK DIKIRIM KE PHP POST
            dataInput.value = JSON.stringify(cart);

            cart.forEach((item, index) => {
                let tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><b>${item.nama_barang}</b></td>
                    <td style="text-align: center;">${item.jumlah} Unit</td>
                    <td>${item.keterangan}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-remove" onclick="hapusDariDaftar(${index})"><i class="fas fa-trash"></i> Hapus</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    </script>

</body>
</html>
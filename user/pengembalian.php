<?php
session_start();
require '../config/database.php';

// Proteksi Halaman
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data barang untuk pilihan dropdown (Semua barang bisa diretur)
$items_query = $conn->query("SELECT id, nama_barang, kategori FROM items ORDER BY nama_barang ASC");

// --- LOGIKA SIMPAN RETUR MULTIPLE BESERTA FOTO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_retur'])) {
    
    // Pastikan folder uploads tersedia
    $upload_dir = '../uploads/retur/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $berhasil = 0;
    $gagal = 0;

    // Cek apakah ada data yang dikirim (berupa array)
    if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
        $count_items = count($_POST['item_id']);

        for ($i = 0; $i < $count_items; $i++) {
            $item_id_post = $conn->real_escape_string($_POST['item_id'][$i]);
            $jumlah = $conn->real_escape_string($_POST['jumlah'][$i]);
            $kondisi = $conn->real_escape_string($_POST['kondisi'][$i]);
            $keterangan = $conn->real_escape_string($_POST['keterangan'][$i]);
            
            // Proses Upload Foto
            $foto_name = '';
            if (isset($_FILES['foto']['name'][$i]) && $_FILES['foto']['error'][$i] == 0) {
                // Ambil ekstensi asli
                $ext = pathinfo($_FILES['foto']['name'][$i], PATHINFO_EXTENSION);
                // Buat nama file unik untuk mencegah bentrok nama file
                $foto_name = "RETUR_" . time() . "_" . rand(100, 999) . "." . $ext;
                $tmp_name = $_FILES['foto']['tmp_name'][$i];
                
                // Pindahkan file dari memori sementara ke folder tujuan
                move_uploaded_file($tmp_name, $upload_dir . $foto_name);
            }

            // Insert ke database returns (Stok belum bertambah, tunggu admin approve)
            $sql_insert = "INSERT INTO returns (user_id, item_id, jumlah, kondisi, keterangan, foto, status, tanggal) 
                           VALUES ('$user_id', '$item_id_post', '$jumlah', '$kondisi', '$keterangan', '$foto_name', 'pending', NOW())";
            
            if ($conn->query($sql_insert)) {
                $berhasil++;
            } else {
                $gagal++;
            }
        }

        if ($berhasil > 0) {
            $_SESSION['pesan'] = "<b>$berhasil jenis material</b> beserta foto buktinya berhasil dikirim untuk proses retur.";
            // Arahkan teknisi kembali ke dashboard setelah sukses
            header("Location: dashboard_user.php"); 
            exit();
        } else {
            $pesan_error = "Gagal memproses data retur. Silakan coba lagi atau hubungi Admin.";
        }
    } else {
        $pesan_error = "Daftar retur kosong! Silakan tambahkan barang ke dalam list terlebih dahulu.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengembalian (Retur) - Teknisi</title>
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
        
        /* CSS FORM MULTI ADD TEMA RETUR (ORANYE) */
        .card { background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; border-top: 4px solid #f39c12; }
        .card-header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        .card-header i { font-size: 20px; color: #f39c12; margin-right: 15px; background: rgba(243, 156, 18, 0.1); padding: 12px; border-radius: 50%; }
        .card-header h3 { margin: 0; color: #333; font-size: 18px; }

        .form-row { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; position: relative; }
        .form-group label { display: block; font-weight: 600; color: #444; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #fcfcfc; transition: 0.3s; }
        .form-control:focus { border-color: #f39c12; outline: none; background-color: #fff; }
        
        .btn-add { background: #fff3cd; color: #d39e00; border: 1px solid #ffeeba; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.3s; height: 39px; white-space: nowrap; margin-top: 25px; }
        .btn-add:hover { background: #ffeeba; }

        /* TABEL KERANJANG BAWAH */
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        table th, table td { padding: 12px 15px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; vertical-align: middle; }
        table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 11px; }
        
        .btn-remove { background: #fce8e6; color: #d93025; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.3s; }
        .btn-remove:hover { background: #fad2cf; }

        .btn-submit-all { background: linear-gradient(135deg, #f39c12 0%, #d35400 100%); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.3s; display: block; width: 100%; text-align: center; margin-top: 20px; }
        .btn-submit-all:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3); }
        .btn-submit-all:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }

        .alert-error { background-color: #fce8e6; color: #d93025; padding: 15px; border-radius: 8px; border-left: 5px solid #d93025; margin-bottom: 25px; }
        .empty-cart { text-align: center; padding: 30px; color: #888; font-style: italic; font-size: 13px; }
        
        /* Preview Foto Mini */
        .photo-preview { width: 45px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <?php include 'sidebar_user.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <h2 style="margin: 0; font-size: 20px;">Form Pengembalian (Retur)</h2>
            <div>
                <span>Halo, Teknisi <b><?php echo $_SESSION['username']; ?></b></span>
            </div>
        </div>

        <div class="content-area">
            <?php if(isset($pesan_error)) echo "<div class='alert-error'><i class='fas fa-exclamation-triangle'></i> $pesan_error</div>"; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="mainReturnForm">
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-undo-alt"></i>
                        <div>
                            <h3>Pilih Material Retur</h3>
                            <p style="margin:0; font-size:12px; color:#777;">Kembalikan sisa material atau laporkan perangkat rusak beserta foto buktinya.</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Pilih Material yang Dikembalikan</label>
                            <select id="itemSelect" class="form-control">
                                <option value="">-- Cari Barang --</option>
                                <?php 
                                if($items_query->num_rows > 0) {
                                    while($row = $items_query->fetch_assoc()) {
                                        echo "<option value='".$row['id']."' data-name='".$row['nama_barang']."'>";
                                        echo $row['nama_barang'] . " (" . $row['kategori'] . ")";
                                        echo "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 0.5;">
                            <label>Jumlah</label>
                            <input type="number" id="jumlahRetur" class="form-control" min="1" placeholder="Qty">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Kondisi Fisik</label>
                            <select id="kondisiBarang" class="form-control">
                                <option value="Baik">Baik (Sisa Material)</option>
                                <option value="Rusak">Rusak / Cacat</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row" style="align-items: flex-end; margin-bottom: 0;">
                        <div class="form-group" style="flex: 2;">
                            <label>Alasan / Keterangan Tambahan</label>
                            <input type="text" id="keteranganRetur" class="form-control" placeholder="Contoh: Sisa kabel tarikan, atau ONT mati total">
                        </div>
                        
                        <div class="form-group" style="flex: 1; position: relative;" id="fileInputContainer">
                            <label>Upload Foto Bukti Fisik</label>
                            <input type="file" id="fotoBarang" class="form-control" accept="image/*" style="padding: 7px 15px;">
                        </div>

                        <button type="button" class="btn-add" onclick="tambahKeDaftar()">
                            <i class="fas fa-plus"></i> Tambah ke List
                        </button>
                    </div>
                </div>

                <div class="card" style="border-top-color: #d35400;">
                    <div class="card-header">
                        <i class="fas fa-list-check" style="color: #d35400; background: rgba(211, 84, 0, 0.1);"></i>
                        <div>
                            <h3>Daftar Material Retur Anda</h3>
                            <p style="margin:0; font-size:12px; color:#777;">Periksa kesesuaian barang, jumlah, kondisi, dan foto bukti sebelum mengirim.</p>
                        </div>
                    </div>

                    <table id="cartTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Foto</th>
                                <th>Nama Material</th>
                                <th>Kondisi</th>
                                <th style="width: 60px; text-align: center;">Jml</th>
                                <th>Keterangan</th>
                                <th style="width: 80px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="cartBody">
                            <tr id="emptyRow"><td colspan="6" class="empty-cart">Belum ada material retur yang ditambahkan.</td></tr>
                        </tbody>
                    </table>

                    <button type="submit" name="submit_retur" id="btnSubmitAll" class="btn-submit-all" disabled>
                        <i class="fas fa-upload"></i> Kirim Form Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let rowCount = 0; // Digunakan sebagai penanda ID unik untuk tiap baris data

        function tambahKeDaftar() {
            let select = document.getElementById("itemSelect");
            let id_barang = select.value;
            
            if (id_barang === "") {
                alert("Silakan pilih material yang ingin dikembalikan!");
                return;
            }

            let nama_barang = select.options[select.selectedIndex].getAttribute("data-name");
            let jumlah = parseInt(document.getElementById("jumlahRetur").value);
            let kondisi = document.getElementById("kondisiBarang").value;
            let keterangan = document.getElementById("keteranganRetur").value;
            
            // Ambil Elemen Input File Asli yang baru saja diisi user
            let fileInputAsli = document.getElementById("fotoBarang");

            // VALIDASI INPUT PENTING
            if (isNaN(jumlah) || jumlah <= 0) {
                alert("Masukkan jumlah retur yang valid!");
                return;
            }
            if (keterangan.trim() === "") {
                alert("Alasan / keterangan retur wajib diisi!");
                return;
            }
            if (fileInputAsli.files.length === 0) {
                alert("Wajib melampirkan Foto Bukti fisik barang!");
                return;
            }

            // Hapus baris peringatan "Kosong" jika ada
            let emptyRow = document.getElementById("emptyRow");
            if(emptyRow) emptyRow.remove();

            let tbody = document.getElementById("cartBody");
            let tr = document.createElement("tr");
            tr.id = "row_" + rowCount;

            // Ambil URL sementara dari memori browser untuk menampilkan preview foto
            let fileURL = URL.createObjectURL(fileInputAsli.files[0]);

            // Buat HTML Tampilan Tabel (UI)
            let tampilanTabel = `
                <td><img src="${fileURL}" class="photo-preview" alt="Preview Foto"></td>
                <td><b>${nama_barang}</b></td>
                <td><span style="color: ${kondisi === 'Baik' ? '#1e8e3e' : '#d93025'}; font-weight:bold;"><i class="fas fa-circle" style="font-size:8px;"></i> ${kondisi}</span></td>
                <td style="text-align: center;">${jumlah}</td>
                <td>${keterangan}</td>
                <td style="text-align: center;">
                    <button type="button" class="btn-remove" onclick="hapusDariDaftar(${rowCount})"><i class="fas fa-trash"></i> Batal</button>
                </td>
            `;

            // Buat Container tersembunyi untuk menyimpan input data TEXT asli (array)
            let hiddenData = document.createElement("div");
            hiddenData.style.display = "none";
            hiddenData.innerHTML = `
                <input type='hidden' name='item_id[]' value='${id_barang}'>
                <input type='hidden' name='jumlah[]' value='${jumlah}'>
                <input type='hidden' name='kondisi[]' value='${kondisi}'>
                <input type='hidden' name='keterangan[]' value='${keterangan}'>
            `;

            // PINDAHKAN FILE: Kita tidak bisa meng-copy file lewat JS, jadi kita pindahkan kotak input-nya
            fileInputAsli.id = ""; // Hapus ID agar tidak duplikat dengan form baru nanti
            fileInputAsli.name = "foto[]"; // Ubah name agar menjadi array saat diterima PHP POST
            hiddenData.appendChild(fileInputAsli); // Memindahkan fisik kotak file ke tabel tersembunyi

            // Masukkan elemen tampilan & elemen rahasia ke dalam baris tabel (TR)
            tr.innerHTML = tampilanTabel;
            tr.appendChild(hiddenData);
            tbody.appendChild(tr);

            // BUAT KOTAK UPLOAD FILE BARU yang bersih di form bagian atas
            let containerFoto = document.getElementById("fileInputContainer");
            let newFileInput = document.createElement("input");
            newFileInput.type = "file";
            newFileInput.id = "fotoBarang";
            newFileInput.className = "form-control";
            newFileInput.accept = "image/*";
            newFileInput.style.padding = "7px 15px";
            containerFoto.appendChild(newFileInput);

            // Reset form input teks lainnya untuk barang selanjutnya
            document.getElementById("jumlahRetur").value = "";
            document.getElementById("keteranganRetur").value = "";
            select.value = "";
            document.getElementById("kondisiBarang").value = "Baik";

            rowCount++;
            cekTombolSubmit();
        }

        // Fungsi menghapus baris data (dan foto yang tersimpan bersamanya)
        function hapusDariDaftar(idRow) {
            let row = document.getElementById("row_" + idRow);
            if(row) row.remove(); // Otomatis menghapus input hidden & input file yang ada di dalamnya
            cekTombolSubmit();
        }

        // Fungsi untuk mengecek apakah tombol Submit boleh diklik atau harus didisable
        function cekTombolSubmit() {
            let tbody = document.getElementById("cartBody");
            let btnSubmit = document.getElementById("btnSubmitAll");
            
            // Jika isi tabel cuma header kosong, matikan tombol
            if (tbody.getElementsByTagName("tr").length === 0) {
                tbody.innerHTML = '<tr id="emptyRow"><td colspan="6" class="empty-cart">Belum ada material retur yang ditambahkan.</td></tr>';
                btnSubmit.disabled = true;
            } else {
                btnSubmit.disabled = false;
            }
        }
    </script>

</body>
</html>
<?php
session_start();
require '../config/database.php';

// Pastikan user sudah login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

// Pastikan ada ID yang mau dicetak
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID Pengajuan tidak valid.");
}

$id_request = $conn->real_escape_string($_GET['id']);
$user_id = $_SESSION['user_id'];

// Ambil data pengajuan lengkap dengan nama barang dan nama petugas
$sql = "SELECT req.*, i.nama_barang, i.kategori, i.merk, u.username 
        FROM requests req 
        JOIN items i ON req.item_id = i.id 
        JOIN users u ON req.user_id = u.id 
        WHERE req.id = '$id_request' AND req.user_id = '$user_id' AND req.status = 'approved'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Error: Surat tidak ditemukan atau belum disetujui Admin.");
}

$data = $result->fetch_assoc();

// Buat nomor surat unik berdasarkan tanggal dan ID
$nomor_surat = "PLN-ICON/" . date('Y/m', strtotime($data['tanggal'])) . "/REQ-" . str_pad($data['id'], 4, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - <?php echo $nomor_surat; ?></title>
    <style>
        /* Desain khusus untuk format cetak A4/PDF */
        body { font-family: 'Times New Roman', Times, serif; background-color: #f0f0f0; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .kertas-a4 { background-color: white; width: 210mm; min-height: 297mm; padding: 20mm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        /* Kop Surat */
        .kop-surat { display: flex; align-items: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat img { width: 80px; height: auto; margin-right: 20px; }
        .kop-teks h2, .kop-teks h4, .kop-teks p { margin: 0; }
        .kop-teks h2 { font-size: 22px; color: #00bcd4; }
        .kop-teks p { font-size: 12px; color: #555; }
        
        /* Isi Surat */
        .judul-surat { text-align: center; font-size: 18px; text-decoration: underline; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;}
        .nomor-surat { text-align: center; font-size: 12px; margin-bottom: 30px; }
        
        .paragraf { font-size: 14px; line-height: 1.6; margin-bottom: 15px; text-align: justify; }
        
        /* Tabel Rincian Barang */
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; margin-top: 15px;}
        th, td { border: 1px solid #000; padding: 10px; font-size: 14px; }
        th { background-color: #f0f0f0; text-align: left; }
        
        /* Tanda Tangan */
        .ttd-container { display: flex; justify-content: space-between; margin-top: 50px; }
        .ttd-box { text-align: center; width: 250px; font-size: 14px; }
        .ttd-space { height: 100px; }
        .ttd-name { font-weight: bold; text-decoration: underline; }

        /* Sembunyikan elemen UI bawaan saat dicetak */
        @media print {
            body { background-color: white; padding: 0; }
            .kertas-a4 { box-shadow: none; width: auto; min-height: auto; margin: 0; padding: 0; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body>

    <div class="kertas-a4">
        <div class="kop-surat">
            <img src="..\assets\img\logo-pln-plus.png" alt="Logo PLN" style="width: 100px; margin-bottom: 10px;">
            <div class="kop-teks">
                <h2>PT PLN ICON PLUS</h2>
                <h4>DEPARTEMEN INVENTARIS DAN PERGUDANGAN</h4>
                <p>Jl. Contoh Alamat Kantor Operasional No. 123, Kota Anda, Kode Pos 12345</p>
                <p>Email: gudang@iconpln.co.id | Telp: (021) 12345678</p>
            </div>
        </div>

        <div class="judul-surat">SURAT JALAN PENGAMBILAN MATERIAL</div>
        <div class="nomor-surat">Nomor: <?php echo $nomor_surat; ?></div>

        <div class="paragraf">
            Yang bertanda tangan di bawah ini, Admin Gudang PT PLN Icon Plus, menerangkan bahwa telah menyetujui dan menyerahkan material/perangkat kerja operasional kepada petugas di bawah ini:
        </div>

        <table style="width: 70%; border: none; margin-bottom: 20px;">
            <tr>
                <td style="border: none; width: 30%; padding: 5px;">Nama Teknisi</td>
                <td style="border: none; width: 5%; padding: 5px;">:</td>
                <td style="border: none; padding: 5px;"><b><?php echo strtoupper($data['username']); ?></b></td>
            </tr>
            <tr>
                <td style="border: none; padding: 5px;">Waktu Persetujuan</td>
                <td style="border: none; padding: 5px;">:</td>
                <td style="border: none; padding: 5px;"><?php echo date('d F Y, H:i', strtotime($data['tanggal'])); ?> WIB</td>
            </tr>
            <tr>
                <td style="border: none; padding: 5px;">Keterangan Pekerjaan</td>
                <td style="border: none; padding: 5px;">:</td>
                <td style="border: none; padding: 5px;"><?php echo $data['keterangan']; ?></td>
            </tr>
        </table>

        <div class="paragraf">
            Adapun rincian material yang telah diserahkan dari gudang adalah sebagai berikut:
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th>Nama Material / Perangkat</th>
                    <th>Kategori</th>
                    <th>Merk</th>
                    <th style="width: 15%; text-align: center;">Jumlah (Qty)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td><b><?php echo $data['nama_barang']; ?></b></td>
                    <td><?php echo $data['kategori']; ?></td>
                    <td><?php echo isset($data['merk']) ? $data['merk'] : '-'; ?></td>
                    <td style="text-align: center;"><b><?php echo $data['jumlah']; ?> Unit</b></td>
                </tr>
            </tbody>
        </table>

        <div class="paragraf">
            Demikian surat jalan ini diterbitkan sebagai bukti serah terima barang yang sah dari gudang untuk dipergunakan semestinya di lapangan.
        </div>

        <div class="ttd-container">
            <div class="ttd-box">
                <p>Penerima (Teknisi),</p>
                <div class="ttd-space"></div>
                <p class="ttd-name"><?php echo strtoupper($data['username']); ?></p>
            </div>
            
            <div class="ttd-box">
                <p>Mengetahui & Menyetujui,</p>
                <p style="margin: 0;">Admin Gudang Operasional</p>
                <div class="ttd-space"></div>
                <p class="ttd-name">___________________________</p>
            </div>
        </div>
    </div>

    <script>
        // Membuka dialog print/save as PDF secara otomatis saat halaman dimuat
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
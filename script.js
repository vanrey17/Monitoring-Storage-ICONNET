/* file: script.js */

document.addEventListener("DOMContentLoaded", function() {

    // 1. FITUR AUTO-HIDE ALERT
    // Mencari semua elemen dengan class 'alert'
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        // Setelah 3.5 detik (3500 ms), alert akan menghilang perlahan
        setTimeout(function() {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            
            // Hapus elemen dari DOM setelah animasi selesai
            setTimeout(function() {
                alert.style.display = "none";
            }, 500);
        }, 3500);
    });

});

// 2. FITUR PENCARIAN BARANG (LIVE SEARCH)
function cariBarang() {
    // Ambil nilai teks dari input pencarian
    let input = document.getElementById("searchInput").value.toLowerCase();
    
    // Logika untuk halaman Admin (Pencarian di Tabel)
    let tabel = document.querySelector(".data-table");
    if (tabel) {
        let rows = tabel.getElementsByTagName("tr");
        // Mulai dari i=1 karena i=0 adalah header tabel (th)
        for (let i = 1; i < rows.length; i++) {
            // Ambil kolom nama barang (biasanya di kolom ke-2 / index 1)
            let namaBarang = rows[i].getElementsByTagName("td")[1]; 
            if (namaBarang) {
                let teksBarang = namaBarang.textContent || namaBarang.innerText;
                // Sembunyikan atau tampilkan baris sesuai hasil pencarian
                if (teksBarang.toLowerCase().indexOf(input) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    }

    // Logika untuk halaman User (Pencarian di Card/Katalog)
    let cards = document.querySelectorAll(".card");
    if (cards.length > 0) {
        cards.forEach(function(card) {
            // Ambil elemen judul (h3) di dalam card
            let judul = card.querySelector("h3");
            if (judul) {
                let teksJudul = judul.textContent || judul.innerText;
                if (teksJudul.toLowerCase().indexOf(input) > -1) {
                    card.style.display = "block"; // Munculkan card
                } else {
                    card.style.display = "none";  // Sembunyikan card
                }
            }
        });
    }
}
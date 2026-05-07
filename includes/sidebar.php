<aside class="sidebar">
            <div class="sidebar-brand">
                <h2>PLN Inventory</h2>
            </div>
            
            <ul class="sidebar-menu">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') : ?>
                    <li class="menu-header">Menu Admin</li>
                    <li><a href="../admin/dashboard.php">Dashboard Admin</a></li>
                    <li><a href="../admin/kelola_barang.php">Data Barang & Stok</a></li>
                    <li><a href="../admin/persetujuan.php">Persetujuan Pesanan</a></li>
                
                <?php else : ?>
                    <li class="menu-header">Menu User</li>
                    <li><a href="../user/dashboard.php">Monitoring Barang</a></li>
                    <li><a href="../user/form_request.php">Ambil Barang</a></li>
                    <li><a href="../user/riwayat_request.php">Riwayat Saya</a></li>
                <?php endif; ?>
            </ul>
        </aside>
<div class="main-content">
            
            <nav class="navbar">
                <div class="navbar-left">
                    <span class="menu-toggle">&#9776;</span> </div>
                <div class="navbar-right">
                    <span class="user-info">
                        Halo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong> 
                        (<?= ucfirst($_SESSION['role'] ?? ''); ?>)
                    </span>
                    <a href="../auth/logout.php" class="btn-logout">Logout</a>
                </div>
            </nav>
            
            <div class="content-body">
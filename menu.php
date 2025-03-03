        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-chart-area"></i>
                </div>
                <div class="sidebar-brand-text mx-3">LLBDASH</div>
            </a>
            <a class="sidebar-heading d-flex align-items-center justify-content-center" href="https://github.com/StartBootstrap/startbootstrap-sb-admin-2">
                <span>by SB Admin <sup>2</sup></span>
            </a>



            <?php
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            ?>

            <nav>
                <ul>
                    <li><a href="index.php">📊 Tableau de bord</a></li>
                    <?php if ($_SESSION['role_id'] == 1) : ?>
                        <li><a href="gest-publications.php">✍️ Gestion des publications</a></li>
                    <?php endif; ?>
                </ul>
            </nav>


            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->
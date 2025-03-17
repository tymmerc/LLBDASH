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



          

            <nav>
        <ul>
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa_fw fa _tachometer-alt"></i>
                    📊 Tableau de bord</a></li>
            
            <?php 
            if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) : ?>
                <li class="nav-item active"><a class="nav-link"  href="gest-publications.php">✍️ Gestion des publications</a></li>
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
        <!-- NOTE POUR PLUS TARD : IL FAUT FAIRE UNE PAGE DE PROPOSITION D'ARTICLES POUR LE LECTEUR -->
         
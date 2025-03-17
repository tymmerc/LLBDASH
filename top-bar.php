<?php
//include_once pour eviter les inclusions multiples
include_once "db/functions.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//avatar defini en fonction du role de l'user actuel
//garantit que avatar est MaJ pour chaque utilisateur
if (isset($_SESSION['role_id'])) {  //verifie si role_id est défini dans la session 
    switch ($_SESSION['role_id']) {
        case 1:  //same as if but more organised 
            $_SESSION['avatar'] = "img/admin.jpg";
            break;
        case 2:
            $_SESSION['avatar'] = "img/lect.png";
            break;
        default:
            $_SESSION['avatar'] = "img/default.png";
            break;
    }
} else {
    //default avatar
    $_SESSION['avatar'] = "img/default.png";
}
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?>
                </span>
                <img class="img-profile rounded-circle"
                    src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Avatar">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <a class="dropdown-item" href="#">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Settings
                </a>
                <a class="dropdown-item" href="#">
                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                    Activity Log
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
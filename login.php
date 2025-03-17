<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SB Admin 2 - Login</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

<?php
session_start();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=dbsitellb", "root", "rootMysql", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

//fonction pour enregistrer les connexions
function logLogin($pdo, $username, $userId, $status) {
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, username, ip_address, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $username, $_SERVER['REMOTE_ADDR'], $status]);
}

//vérifier si le formulaire a été soumis
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    //vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['passwd'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];



        logLogin($pdo, $username, $user['user_id'], 'success');
        
        $redirect = ($user['role_id'] == 1) ? "gest-publications.php" : "index.php";
        header("Location: $redirect");
        exit;
    } else {
        logLogin($pdo, $username, null, 'failure');
        $error = "Identifiant ou mot de passe incorrect.";
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <form method="post">
                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-user" name="username" placeholder="Enter Username..." required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control form-control-user" name="password" placeholder="Password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
                                </form>

                                <hr>
                                <div class="text-center">
                                    <a class="small" href="forgot-password.php">Forgot Password?</a>
                                </div>
                                <div class="text-center">
                                    <a class="small" href="register.php">Create an Account!</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

</body>
</html>

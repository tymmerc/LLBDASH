<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
try {
    $pdo = new PDO("mysql:host=localhost;dbname=dbsitellb", "root", "rootMysql", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['email'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->fetch()) {
        $error = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
    } else {
        // Insérer le nouvel utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (username, email, passwd, role_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword, 2])) {  // 2 = Lecteur par défaut
            $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Une erreur est survenue lors de l'inscription.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-register-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">Créer un compte</h1>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                                <?php endif; ?>

                                <form method="post">
                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-user" name="username" placeholder="Nom d'utilisateur" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="email" class="form-control form-control-user" name="email" placeholder="Adresse email" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control form-control-user" name="password" placeholder="Mot de passe" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">S'inscrire</button>
                                </form>

                                <hr>
                                <div class="text-center">
                                    <a class="small" href="login.php">Déjà un compte ? Connectez-vous !</a>
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
</body>
</html>
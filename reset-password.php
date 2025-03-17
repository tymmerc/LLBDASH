<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if Dotenv is being used and handle it properly
if (class_exists('Dotenv\Dotenv')) {
    // Only try to load .env if the file exists
    $envPath = __DIR__; // Current directory
    $envFile = $envPath . '/.env';
    
    if (file_exists($envFile)) {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath);
        $dotenv->load();
    }
    // If .env doesn't exist, just continue without it
}

try {
    // Use environment variables if available, otherwise use defaults
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'dbsitellb';
    $user = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? 'rootMysql';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$error = "";
$success = "";
$debug = "";
$validToken = false;
$user = null;

// Vérifier la structure de la table users pour le débogage
try {
    $tableInfo = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    $debug .= "Structure de la table users:<br>";
    foreach ($tableInfo as $column) {
        $debug .= "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
} catch (PDOException $e) {
    $debug .= "Erreur lors de la récupération de la structure de la table: " . $e->getMessage() . "<br>";
}

// Vérifier directement les tokens dans la base de données
try {
    $tokens = $pdo->query("SELECT user_id, email, reset_token, reset_expires FROM users WHERE reset_token IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $debug .= "Tokens actifs dans la base de données:<br>";
    foreach ($tokens as $t) {
        $debug .= "- ID: " . $t['user_id'] . ", Email: " . $t['email'] . ", Token: " . substr($t['reset_token'], 0, 10) . "..., Expire: " . $t['reset_expires'] . "<br>";
    }
} catch (PDOException $e) {
    $debug .= "Erreur lors de la récupération des tokens: " . $e->getMessage() . "<br>";
}

// Stocker le token original pour comparaison
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $debug .= "Token reçu (GET): " . $token . "<br>";
    
    // Ajouter du débogage pour voir ce qui se passe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Lien invalide ou expiré.";
        $debug .= "Token non trouvé dans la base de données.<br>";
    } else {
        // Vérifier si le token a expiré
        $now = new DateTime();
        $expires = new DateTime($user['reset_expires']);
        
        if ($now > $expires) {
            $error = "Ce lien de réinitialisation a expiré.";
            $debug .= "Token expiré. Expiration: " . $user['reset_expires'] . ", Maintenant: " . $now->format('Y-m-d H:i:s') . "<br>";
        } else {
            $validToken = true;
            $debug .= "Token valide trouvé pour l'utilisateur: " . $user['email'] . "<br>";
            $debug .= "ID utilisateur associé au token: " . $user['user_id'] . "<br>";
            
            // Stocker les informations de l'utilisateur dans la session
            $_SESSION['reset_user_id'] = $user['user_id'];
            $_SESSION['reset_user_email'] = $user['email'];
            $_SESSION['reset_token'] = $token;
        }
    }
} else {
    $error = "Aucun token fourni.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'], $_POST['confirm_password'])) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Récupérer les informations de l'utilisateur depuis la session
    $userId = $_SESSION['reset_user_id'] ?? 0;
    $userEmail = $_SESSION['reset_user_email'] ?? '';
    $token = $_SESSION['reset_token'] ?? '';
    
    $debug .= "Formulaire soumis - ID utilisateur: " . $userId . ", Email: " . $userEmail . "<br>";
    
    if (empty($userId) || empty($userEmail)) {
        $error = "Session expirée ou invalide. Veuillez réessayer.";
        $debug .= "Informations utilisateur manquantes en session.<br>";
    } else if ($newPassword !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
        $debug .= "Les mots de passe ne correspondent pas.<br>";
    } else {
        // Hasher le mot de passe
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // APPROCHE DIRECTE: Mettre à jour le mot de passe sans vérifier le token
        try {
            // Méthode 1: Mise à jour par ID utilisateur
            $stmt = $pdo->prepare("UPDATE users SET passwd = ? WHERE user_id = ?");
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
                $debug .= "Mot de passe mis à jour avec succès (méthode 1). Lignes affectées: " . $stmt->rowCount() . "<br>";
                
                // Effacer le token après la mise à jour réussie
                $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                // Nettoyer les variables de session
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_user_email']);
                
                // Redirection vers la page de connexion après 3 secondes
                header("refresh:3;url=login.php");
            } else {
                $debug .= "Échec de la méthode 1. Tentative avec la méthode 2...<br>";
                
                // Méthode 2: Mise à jour par email
                $stmt = $pdo->prepare("UPDATE users SET passwd = ? WHERE email = ?");
                $result = $stmt->execute([$hashedPassword, $userEmail]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $success = "Votre mot de passe a été réinitialisé avec succès (méthode 2). Vous pouvez maintenant vous connecter.";
                    $debug .= "Mot de passe mis à jour avec succès (méthode 2). Lignes affectées: " . $stmt->rowCount() . "<br>";
                    
                    // Effacer le token après la mise à jour réussie
                    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE email = ?");
                    $stmt->execute([$userEmail]);
                    
                    // Nettoyer les variables de session
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_user_email']);
                    
                    // Redirection vers la page de connexion après 3 secondes
                    header("refresh:3;url=login.php");
                } else {
                    $debug .= "Échec de la méthode 2. Tentative avec la méthode 3...<br>";
                    
                    // Méthode 3: Mise à jour directe par token
                    if (!empty($token)) {
                        $stmt = $pdo->prepare("UPDATE users SET passwd = ? WHERE reset_token = ?");
                        $result = $stmt->execute([$hashedPassword, $token]);
                        
                        if ($result && $stmt->rowCount() > 0) {
                            $success = "Votre mot de passe a été réinitialisé avec succès (méthode 3). Vous pouvez maintenant vous connecter.";
                            $debug .= "Mot de passe mis à jour avec succès (méthode 3). Lignes affectées: " . $stmt->rowCount() . "<br>";
                            
                            // Effacer le token après la mise à jour réussie
                            $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
                            $stmt->execute([$token]);
                            
                            // Nettoyer les variables de session
                            unset($_SESSION['reset_token']);
                            unset($_SESSION['reset_user_id']);
                            unset($_SESSION['reset_user_email']);
                            
                            // Redirection vers la page de connexion après 3 secondes
                            header("refresh:3;url=login.php");
                        } else {
                            $error = "Toutes les méthodes de mise à jour ont échoué. Veuillez réessayer.";
                            $debug .= "Échec de toutes les méthodes de mise à jour.<br>";
                        }
                    } else {
                        $error = "Token manquant pour la méthode 3.";
                        $debug .= "Token manquant pour la méthode 3.<br>";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour du mot de passe.";
            $debug .= "Exception PDO: " . $e->getMessage() . "<br>";
            
            // Méthode d'urgence: Exécuter une requête SQL directe
            try {
                $debug .= "Tentative avec une requête SQL directe...<br>";
                $sql = "UPDATE users SET passwd = '" . $pdo->quote($hashedPassword) . "' WHERE user_id = " . intval($userId);
                $debug .= "SQL: " . $sql . "<br>";
                $result = $pdo->exec($sql);
                
                if ($result > 0) {
                    $success = "Votre mot de passe a été réinitialisé avec succès (méthode d'urgence). Vous pouvez maintenant vous connecter.";
                    $debug .= "Mot de passe mis à jour avec succès (méthode d'urgence). Lignes affectées: " . $result . "<br>";
                    
                    // Redirection vers la page de connexion après 3 secondes
                    header("refresh:3;url=login.php");
                }
            } catch (PDOException $e2) {
                $debug .= "Exception PDO (méthode d'urgence): " . $e2->getMessage() . "<br>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe</title>
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-2">Réinitialisation du mot de passe</h1>
                                    <p class="mb-4">Entrez un nouveau mot de passe.</p>
                                </div>

                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <?= htmlspecialchars($success) ?>
                                        <p class="mt-2">Vous allez être redirigé vers la page de connexion dans 3 secondes...</p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($error && !$validToken): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>
                                
                                <?php if ($error && $validToken): ?>
                                    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <?php if (!$success && $validToken): ?>
                                <form method="post" id="resetForm">
                                    <div class="form-group">
                                        <input type="password" class="form-control form-control-user" name="password" placeholder="Nouveau mot de passe" required minlength="6">
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control form-control-user" name="confirm_password" placeholder="Confirmer le mot de passe" required minlength="6">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Modifier</button>
                                </form>
                                
                                <script>
                                document.getElementById('resetForm').addEventListener('submit', function(e) {
                                    var password = document.querySelector('input[name="password"]').value;
                                    var confirmPassword = document.querySelector('input[name="confirm_password"]').value;
                                    
                                    if (password !== confirmPassword) {
                                        e.preventDefault();
                                        alert('Les mots de passe ne correspondent pas.');
                                    }
                                });
                                </script>
                                <?php endif; ?>

                                <hr>
                                <div class="text-center">
                                    <a class="small" href="login.php">Retour à la connexion</a>
                                </div>
                                
                                <!-- Afficher les logs de débogage en mode développement -->
                                <?php if (!empty($debug)): ?>
                                    <div class="alert alert-info mt-4">
                                        <h5>Informations de débogage:</h5>
                                        <div style="max-height: 200px; overflow-y: auto; font-size: 0.8em;">
                                            <?= $debug ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


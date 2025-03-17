<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Dotenv\Dotenv;

// Charger les variables d'environnement depuis le fichier .env
try {
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
} catch (Exception $e) {
    // Continuer sans .env
}

// Définir manuellement les variables d'environnement si elles ne sont pas chargées
if (!isset($_ENV['GMAIL_USERNAME'])) {
    $_ENV['GMAIL_USERNAME'] = "merciertymeo@gmail.com";
}
if (!isset($_ENV['GMAIL_PASSWORD'])) {
    $_ENV['GMAIL_PASSWORD'] = "votre_mot_de_passe_app"; // Ne pas inclure le mot de passe réel ici
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
$debugLog = "";
$resetLink = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Générer un token plus court mais toujours sécurisé
        $token = bin2hex(random_bytes(32));
        
        // S'assurer que le token est correctement stocké dans la base de données
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $expiryTime = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $stmt->execute([$token, $expiryTime, $email]);
        
        // Vérifier que la mise à jour a bien fonctionné
        if ($stmt->rowCount() > 0) {
            // Construire le lien avec le chemin correct
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseDir = dirname($_SERVER['PHP_SELF']);
            
            // Ajuster le chemin si nécessaire
            if (strpos($baseDir, 'LLBDASH') === false) {
                $baseDir = '/LLBDASH' . $baseDir;
            }
            
            $resetLink = "$protocol://$host$baseDir/reset-password.php?token=$token";
            
            // Amélioration du message HTML avec un style plus clair
            $subject = "Réinitialisation de votre mot de passe";
            $htmlMessage = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #4e73df; color: white; 
                           text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { margin-top: 30px; font-size: 0.9em; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Réinitialisation de votre mot de passe</h2>
                    <p>Bonjour,</p>
                    <p>Vous avez demandé une réinitialisation de mot de passe.</p>
                    <p><a href='$resetLink' class='btn'>Cliquez ici pour réinitialiser votre mot de passe</a></p>
                    <p>Si le bouton ne fonctionne pas, vous pouvez copier et coller ce lien dans votre navigateur :</p>
                    <p><a href='$resetLink'>$resetLink</a></p>
                    <p>Ce lien expirera dans 1 heure.</p>
                    <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
                    <div class='footer'>
                        <p>Ceci est un message automatique, merci de ne pas y répondre.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            // Version texte simple pour les clients qui ne supportent pas HTML
            $textMessage = "Bonjour,\n\n" .
                          "Vous avez demandé une réinitialisation de mot de passe.\n\n" .
                          "Utilisez ce lien pour réinitialiser votre mot de passe : $resetLink\n\n" .
                          "Ce lien expirera dans 1 heure.\n\n" .
                          "Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.";

            $mail = new PHPMailer(true);
            try {
                // Configuration du débogage
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) use (&$debugLog) {
                    $debugLog .= "Debug ($level): $str<br>";
                };
                
                // Configuration du serveur
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['GMAIL_USERNAME']; 
                $mail->Password = $_ENV['GMAIL_PASSWORD']; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Destinataires
                $mail->setFrom($_ENV['GMAIL_USERNAME'], 'Support LLB');
                $mail->addAddress($email);
                
                // Contenu
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = $subject;
                $mail->Body    = $htmlMessage;
                $mail->AltBody = $textMessage;

                $mail->send();
                $success = "Un email de réinitialisation a été envoyé à $email.";
                $success .= "<br><br>Lien de test: <a href='$resetLink' target='_blank'>$resetLink</a>";
                
                // Pas de redirection automatique - l'utilisateur doit cliquer sur le lien dans l'email
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo;
            }
        } else {
            $error = "Erreur lors de la mise à jour du token. Veuillez réessayer.";
        }
    } else {
        // Même message que pour un succès pour éviter de révéler l'existence de l'email
        $success = "Si cette adresse est correcte, un email a été envoyé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Oubli de mot de passe</title>
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
                                    <h1 class="h4 text-gray-900 mb-2">Mot de passe oublié ?</h1>
                                    <p class="mb-4">Entrez votre adresse email et nous vous enverrons un lien de réinitialisation.</p>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= $error ?></div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <?= $success ?>
                                        <p class="mt-3">Veuillez vérifier votre boîte de réception et cliquer sur le lien dans l'email.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Afficher les logs de débogage en mode développement -->
                                <?php if (!empty($debugLog)): ?>
                                    <div class="alert alert-info">
                                        <h5>Logs de débogage:</h5>
                                        <div style="max-height: 200px; overflow-y: auto; font-size: 0.8em;">
                                            <?= $debugLog ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form method="post">
                                    <div class="form-group">
                                        <input type="email" class="form-control form-control-user" name="email" placeholder="Adresse email" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-user btn-block">Envoyer</button>
                                </form>

                                <hr>
                                <div class="text-center">
                                    <a class="small" href="register.php">Créer un compte</a>
                                </div>
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
</body>
</html>


<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$to = "tym.mercier@gmail.com"; // Remplace par ton adresse email
$subject = "Test d'envoi d'email";
$message = "Ceci est un test pour vérifier si PHP peut envoyer des e-mails.";
$headers = "From: noreply@tonsite.com";

if(mail($to, $subject, $message, $headers)) {
    echo "Email envoyé avec succès.";
} else {
    echo "Erreur lors de l'envoi de l'email.";
}
?>

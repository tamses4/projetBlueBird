<?php
// functions/send_email.php

// ON CHARGE TOUT PHPMailer AUTOMATIQUEMENT GRÂCE À COMPOSER
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

function envoyerEmailConfirmation($to, $nom_client, $lien_confirmation) {
    $mail = new PHPMailer(true);

   try {
        // === CONFIGURATION GMAIL ===
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ondoatamar@gmail.com';           // ← TON EMAIL
        $mail->Password   = 'fxvncleurevgubdj';           // ← MOT DE PASSE D'APPLICATION (voir ci-dessous)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('tonemail@gmail.com', 'BusExpress Cameroun');
        $mail->addAddress($to, $nom_client);
        $mail->isHTML(true);
        $mail->Subject = 'Confirmez votre réservation BusExpress';

        $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:40px auto;padding:30px;background:#fff;border:1px solid #eee;border-radius:15px;text-align:center;'>
            <h2 style='color:#9c27b0;'>BusExpress</h2>
            <h3>Bonjour $nom_client,</h3>
            <p>Merci pour votre réservation !</p>
            <p>Cliquez sur le bouton pour confirmer et payer :</p>
            <br>
            <a href='$lien_confirmation' style='background:#9c27b0;color:white;padding:18px 45px;text-decoration:none;border-radius:50px;font-size:18px;font-weight:bold;'>
                Confirmer et Payer
            </a>
            <br><br>
            <small style='color:#777;'>Lien valable 1 heure seulement</small>
        </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Échec envoi email : " . $mail->ErrorInfo);
        return false;
    }
}
?>
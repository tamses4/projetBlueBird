<?php
session_start();
require_once '../../config/db.php';
require_once '../../functions/update_voyage_status.php';
require_once '../../functions/send_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<div class='error-alert'>Accès interdit.</div>");
}

// Récupération sécurisée des données
$id_voyage = $_POST['id_voyage'] ?? null;
$id_siege  = $_POST['id_siege'] ?? null;
$client_connecte = isset($_POST['client_connecte']) && $_POST['client_connecte'] == '1';
$id_client = $client_connecte ? ($_POST['id_client'] ?? null) : null;

$nom_client = trim($_POST['nom_client'] ?? '');
$telephone  = trim($_POST['telephone'] ?? '');
$email      = trim($_POST['email_client'] ?? '');

// Validation
if (!$id_voyage || !$id_siege || empty($nom_client) || empty($telephone) || empty($email)) {
    echo "<div class='error-alert'>Tous les champs sont obligatoires.</div>";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<div class='error-alert'>Email invalide.</div>";
    exit;
}

try {
    $pdo->beginTransaction();

    // === SI CLIENT DÉJÀ CONNECTÉ → ON LE RÉCUPÈRE, SINON ON LE CRÉE ===
    if ($client_connecte && $id_client) {
        // Vérifions qu’il existe vraiment
        $stmt = $pdo->prepare("SELECT id_client FROM Client WHERE id_client = ?");
        $stmt->execute([$id_client]);
        if (!$stmt->fetch()) {
            throw new Exception("Client introuvable.");
        }
    } else {
        // Vérifier que l'email n'existe pas déjà
        $stmt = $pdo->prepare("SELECT id_client FROM Client WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<div class='error-alert'>Cet email est déjà utilisé. <a href='login.php' style='color:#9c27b0; font-weight:bold;'>Connectez-vous</a></div>";
            $pdo->rollBack();
            exit;
        }

        // Créer le nouveau client
        $stmt = $pdo->prepare("INSERT INTO Client (nom_client, telephone, email) VALUES (?, ?, ?)");
        $stmt->execute([$nom_client, $telephone, $email]);
        $id_client = $pdo->lastInsertId();
    }

    // === CRÉER LA RÉSERVATION ===
    $stmt = $pdo->prepare("
        INSERT INTO Reservation (id_client, id_voyage, date_reservation, statut_reservation) 
        VALUES (?, ?, NOW(), 'en_attente')
    ");
    $stmt->execute([$id_client, $id_voyage]);
    $id_reservation = $pdo->lastInsertId();

    // === RÉSERVER LE SIÈGE ===
    $stmt = $pdo->prepare("INSERT INTO Billet (id_reservation, id_siege, montant) VALUES (?, ?, 0)");
    $stmt->execute([$id_reservation, $id_siege]);

    // === GÉNÉRER UN TOKEN UNIQUE POUR LE PAIEMENT ===
    $token = bin2hex(random_bytes(32));
    $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $pdo->prepare("INSERT INTO Paiement (id_reservation, token, expire_le, statut) VALUES (?, ?, ?, 'en_attente')");
    $stmt->execute([$id_reservation, $token, $expire]);

    $pdo->commit();

    // === LIEN DE PAIEMENT ===
    $lien = "https://louie-uncursing-yolanda.ngrok-free.dev/pages/client/confirmer_paiement.php?token=$token";

    // === ENVOI DE L'EMAIL ===
    if (envoyerEmailConfirmation($email, $nom_client, $lien)) {
        $message = "
        <div style='text-align:center; padding:40px; background:white; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.15); max-width:600px; margin:50px auto;'>
            <h2 style='color:#9c27b0; margin-bottom:20px;'>Félicitations $nom_client !</h2>
            <p style='font-size:18px; color:#333; line-height:1.6;'>
                Votre réservation est bien enregistrée !<br><br>
                Un email de confirmation a été envoyé à :<br>
                <strong style='color:#9c27b0; font-size:20px;'>$email</strong>
            </p>
            <div style='margin:30px 0;'>
                <a href='$lien' target='_blank' style='
                    background:#9c27b0; color:white; padding:18px 50px; border-radius:50px; 
                    text-decoration:none; font-weight:bold; font-size:20px; display:inline-block;
                    box-shadow:0 10px 30px rgba(156,39,176,0.4);'>
                    Finaliser mon paiement
                </a>
            </div>
            <p style='color:#777; font-size:14px;'>
                Lien valable 1 heure • Siège réservé : <strong>$id_siege</strong>
            </p>
        </div>";
    } else {
        $message = "
        <div style='text-align:center; padding:40px; background:white; border-radius:20px; box-shadow:0 15px 40px rgba(0,0,0,0.15); max-width:600px; margin:50px auto;'>
            <h2 style='color:#9c27b0;'>Réservation enregistrée</h2>
            <p style='color:#e74c3c; font-weight:bold;'>
                L'email n'a pas pu être envoyé.
            </p>
            <p>Cliquez ici pour payer :</p>
            <a href='$lien' target='_blank' style='
                background:#9c27b0; color:white; padding:16px 40px; border-radius:50px; 
                text-decoration:none; font-weight:bold; font-size:18px; display:inline-block; margin:20px 0;'>
                Finaliser le paiement maintenant
            </a>
        </div>";
    }

    echo $message;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='error-alert' style='padding:30px; font-size:18px;'>
            Erreur technique : " . htmlspecialchars($e->getMessage()) . "
          </div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - BlueBirdExpress</title>
    <style>
        body { 
            background: linear-gradient(135deg, #9c27b0, #e91e63); 
            font-family: 'Segoe UI', sans-serif; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            margin: 0;
        }
        .error-alert {
            background: #ffebee; color: #c62828; padding: 20px; border-radius: 15px; 
            border: 2px solid #ffcdd2; font-weight: bold; text-align: center;
            max-width: 600px; margin: 50px auto;
        }
    </style>
</head>
<body>
    <!-- Le message est déjà affiché plus haut -->
</body>
</html>
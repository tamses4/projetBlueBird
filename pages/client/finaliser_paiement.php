<?php
require_once dirname(__DIR__, 2) . '/vendor/tcpdf/tcpdf.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

include '../../config/db.php';

$token = $_POST['token'] ?? $_GET['token'] ?? '';
if (empty($token)) {
    die("<div class='error-page'>Token manquant ou invalide.</div>");
}

// Récupération sécurisée des données
$stmt = $pdo->prepare("
    SELECT 
        p.id_paiement, p.id_reservation,
        c.nom_client, c.email, c.telephone,
        t.prix_base, t.ville_depart, t.ville_arrivee,
        s.numero_siege,
        b.immatriculation, a.nom_agence
    FROM paiement p
    JOIN reservation r ON p.id_reservation = r.id_reservation
    JOIN client c ON r.id_client = c.id_client
    JOIN voyage v ON r.id_voyage = v.id_voyage
    JOIN trajet t ON v.id_trajet = t.id_trajet
    JOIN billet bi ON r.id_reservation = bi.id_reservation
    JOIN siege s ON bi.id_siege = s.id_siege
    JOIN bus b ON v.id_bus = b.id_bus
    JOIN agence a ON v.id_agence = a.id_agence
    WHERE p.token = ? AND p.statut = 'en_attente' AND p.expire_le > NOW()
");
$stmt->execute([$token]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("<div class='error-page'>
            <h2>Lien expiré ou paiement déjà effectué</h2>
            <p>Contactez le support si besoin.</p>
         </div>");
}

try {
    $pdo->beginTransaction();

    // Marquer comme payé
    $pdo->prepare("UPDATE Paiement SET statut = 'paye', date_paiement = NOW() WHERE id_paiement = ?")
        ->execute([$data['id_paiement']]);
    $pdo->prepare("UPDATE Reservation SET statut_reservation = 'confirmée' WHERE id_reservation = ?")
        ->execute([$data['id_reservation']]);
    $pdo->prepare("UPDATE Billet SET montant = ? WHERE id_reservation = ?")
        ->execute([$data['prix_base'], $data['id_reservation']]);

    $pdo->commit();

    // === GÉNÉRER UN BEAU PDF PROFESSIONNEL ===
    $code_billet = "BEX" . str_pad($data['id_reservation'], 6, '0', STR_PAD_LEFT);

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('BlueBirdExpress Cameroun');
    $pdf->SetAuthor('BlueBirdExpress');
    $pdf->SetTitle('Billet - ' . $code_billet);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // En-tête violet magnifique
    $pdf->SetFillColor(156, 39, 176);
    $pdf->Rect(0, 0, 210, 40, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 28);
    $pdf->SetY(10);
    $pdf->Cell(0, 15, 'BILLET DE VOYAGE', 0, 1, 'C');

    $pdf->SetTextColor(156, 39, 176);
    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->Cell(0, 15, $code_billet, 0, 1, 'C');

    $pdf->Ln(10);

    // Contenu principal
    $pdf->SetFont('helvetica', '', 14);
    $pdf->SetTextColor(50, 50, 50);

    $html = '
    <table border="0" cellpadding="8">
        <tr><td width="35%"><strong>Passager :</strong></td><td width="65%"><h2>' . htmlspecialchars($data['nom_client']) . '</h2></td></tr>
        <tr><td><strong>Téléphone :</strong></td><td>' . htmlspecialchars($data['telephone']) . '</td></tr>
        <tr><td><strong>Email :</strong></td><td>' . htmlspecialchars($data['email']) . '</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><td><strong>Trajet :</strong></td><td><h3>' . htmlspecialchars($data['ville_depart']) . ' → ' . htmlspecialchars($data['ville_arrivee']) . '</h3></td></tr>
        <tr><td><strong>Date & Heure :</strong></td><td>' . date('d/m/Y à H:i', strtotime($data['date_depart'])) . '</td></tr>
        <tr><td><strong>Agence :</strong></td><td>' . htmlspecialchars($data['nom_agence']) . '</td></tr>
        <tr><td><strong>Bus :</strong></td><td>' . htmlspecialchars($data['immatriculation']) . '</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><td><strong>Siège :</strong></td><td><span style="font-size:32px; color:#27ae60; font-weight:bold;">' . $data['numero_siege'] . '</span></td></tr>
        <tr><td><strong>Prix payé :</strong></td><td><strong style="font-size:24px; color:#9c27b0;">' . number_format($data['prix_base'], 0, ',', ' ') . ' FCFA</strong></td></tr>
    </table>

    <br><br>
    <div style="text-align:center; padding:15px; background:#f8f9fa; border-radius:10px; font-size:12px;">
        <p><strong>BusExpress Cameroun</strong> • Bon voyage !</p>
        <p>Présentez ce billet au chauffeur • Valable uniquement pour ce trajet</p>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // QR Code (optionnel mais PRO)
    $pdf->Ln(10);
    $pdf->writeHTML("<div style='text-align:center;'><img src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("Billet: $code_billet | Passager: " . $data['nom_client']) . "'></div>", true, false, true, false, '');

    // Sauvegarde
    $pdf_folder = dirname(__DIR__, 3) . '/billets';
    if (!is_dir($pdf_folder)) mkdir($pdf_folder, 0777, true);
    $pdf_path = $pdf_folder . "/billet_{$data['id_reservation']}.pdf";
    $pdf->Output($pdf_path, 'F');

    // === ENVOI EMAIL PROFESSIONNEL ===
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'ondoatamar@gmail.com';           // Ton email
    $mail->Password = 'fxvncleurevgubdj';               // Mot de passe d'application
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('ondoatamar@gmail.com', 'BusExpress Cameroun');
    $mail->addAddress($data['email']);
    $mail->Subject = "Votre billet BusExpress - $code_billet";
    $mail->isHTML(true);

    $mail->Body = "
    <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; background:#f8f9fa; padding:30px; border-radius:15px;'>
        <h1 style='color:#9c27b0; text-align:center;'>Félicitations " . htmlspecialchars($data['nom_client']) . " !</h1>
        <p style='font-size:18px; text-align:center;'>Votre paiement est validé avec succès !</p>
        <div style='background:white; padding:30px; border-radius:15px; text-align:center; margin:30px 0;'>
            <h2 style='color:#27ae60;'>Billet confirmé</h2>
            <p><strong>Code billet :</strong> <span style='font-size:24px; color:#9c27b0;'>$code_billet</span></p>
            <p>Trajet : <strong>" . htmlspecialchars($data['ville_depart']) . " → " . htmlspecialchars($data['ville_arrivee']) . "</strong></p>
            <p>Siège : <strong style='font-size:28px; color:#27ae60;'>" . $data['numero_siege'] . "</strong></p>
        </div>
        <p style='text-align:center;'>
            <a href='http://" . $_SERVER['HTTP_HOST'] . "/billets/billet_{$data['id_reservation']}.pdf' 
               style='background:#9c27b0; color:white; padding:18px 50px; text-decoration:none; border-radius:50px; font-size:20px; font-weight:bold;'>
                Télécharger mon billet
            </a>
        </p>
        <p style='text-align:center; color:#777; margin-top:30px;'>
            Bon voyage avec <strong>BusExpress Cameroun</strong>
        </p>
    </div>";

    $mail->addAttachment($pdf_path, "Billet_BusExpress_$code_billet.pdf");
    $mail->send();

    // === PAGE DE SUCCÈS MAGNIFIQUE ===
    echo "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Billet Confirmé - BusExpress</title>
        <style>
            body { background: linear-gradient(135deg, #9c27b0, #e91e63); font-family: 'Segoe UI', sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; margin:0; }
            .success-card {
                background:white; max-width:650px; margin:20px; border-radius:25px; overflow:hidden;
                box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; padding:50px 30px;
            }
            .success-card h1 { color:#27ae60; font-size:42px; margin:0; }
            .success-card h2 { color:#9c27b0; font-size:36px; }
            .btn-download {
                background:#9c27b0; color:white; padding:20px 60px; border-radius:50px;
                text-decoration:none; font-size:22px; font-weight:bold; display:inline-block; margin:30px 0;
                box-shadow:0 15px 40px rgba(156,39,176,0.5);
            }
            .btn-download:hover { transform:translateY(-5px); box-shadow:0 20px 50px rgba(156,39,176,0.6); }
            .check { font-size:120px; color:#27ae60; }
        </style>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap' rel='stylesheet'>
    </head>
    <body>
        <div class='success-card'>
            <div class='check'>Checkmark</div>
            <h1>Paiement Validé !</h1>
            <h2>$code_billet</h2>
            <p style='font-size:20px; color:#333; margin:30px 0;'>
                Félicitations <strong>" . htmlspecialchars($data['nom_client']) . "</strong> !<br>
                Votre billet a été envoyé à <strong>" . htmlspecialchars($data['email']) . "</strong>
            </p>
            <a href='/billets/billet_{$data['id_reservation']}.pdf' target='_blank' class='btn-download'>
                Télécharger mon billet
            </a>
            <p style='color:#777; margin-top:40px;'>
                Bon voyage avec <strong>BusExpress Cameroun</strong>
            </p>
        </div>
    </body>
    </html>";

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erreur paiement : " . $e->getMessage());
    echo "<div class='error-page'>Une erreur est survenue. Réessayez plus tard.</div>";
}
?>

<style>
    .error-page { background:#ffebee; color:#c62828; padding:50px; border-radius:20px; text-align:center; max-width:600px; margin:50px auto; font-size:20px; }
</style>
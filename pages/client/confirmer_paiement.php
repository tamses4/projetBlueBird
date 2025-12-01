<?php
include '../../config/db.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("<div class='error-alert'>Lien invalide ou manquant.</div>");
}

// ON SUPPRIME TOTALEMENT LA CONDITION D'EXPIRATION
$stmt = $pdo->prepare("
    SELECT 
        p.id_paiement, p.statut as statut_paiement,
        r.id_reservation,
        c.nom_client, c.telephone, c.email,
        t.ville_depart, t.ville_arrivee, t.prix_base,
        v.date_depart,
        s.numero_siege
    FROM Paiement p
    JOIN reservation r ON p.id_reservation = r.id_reservation
    JOIN Client c ON r.id_client = c.id_client
    JOIN Voyage v ON r.id_voyage = v.id_voyage
    JOIN Trajet t ON v.id_trajet = t.id_trajet
    JOIN Billet b ON r.id_reservation = b.id_reservation
    JOIN Siege s ON b.id_siege = s.id_siege
    WHERE p.token = ?
      AND p.statut = 'en_attente'
    LIMIT 1
");
$stmt->execute([$token]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("<div class='error-alert'>Lien invalide ou paiement déjà effectué.</div>");
}
?>

<!DOCTYPE ... (le même beau design qu'avant) ... ?>
<div class="container">
    <h2 style="color:#9c27b0;">Confirmez votre réservation</h2>

    <div style="background:#f8f9fa;padding:25px;border-radius:12px;margin:25px 0;text-align:left;font-size:16px;">
        <p><strong>Client :</strong> <?= htmlspecialchars($data['nom_client'] ?? 'Non renseigné') ?></p>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($data['telephone'] ?? '') ?></p>
        <p><strong>Email :</strong> <?= htmlspecialchars($data['email'] ?? '') ?></p>
        <hr>
        <p><strong>Trajet :</strong> <?= htmlspecialchars($data['ville_depart']) ?> → <?= htmlspecialchars($data['ville_arrivee']) ?></p>
        <p><strong>Départ :</strong> <?= date('d/m/Y à H:i', strtotime($data['date_depart'])) ?></p>
        <p><strong>Siège :</strong> 
            <span style="color:#27ae60;font-size:28px;font-weight:bold;">
                <?= htmlspecialchars($data['numero_siege']) ?>
            </span>
        </p>
        <p style="text-align:center;font-size:26px;margin:20px 0;">
            <strong style="color:#e91e63;">
                <?= number_format($data['prix_base'], 0, ',', ' ') ?> FCFA
            </strong>
        </p>
    </div>

    <form method="POST" action="finaliser_paiement.php">
        <input type="hidden" name="token" value="<?= $token ?>">
        <button type="submit" style="background:#9c27b0;color:white;border:none;padding:18px 50px;border-radius:50px;font-size:20px;cursor:pointer;">
            Payer maintenant
        </button>
    </form>

    <p style="margin-top:30px;color:#777;">
        <small>Paiement sécurisé par Mobile Money (Orange Money • MTN MoMo)</small>
    </p>
</div>
</body>
</html>
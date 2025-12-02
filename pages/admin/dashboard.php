<?php
include '../../config/db.php';

include 'check.php';  // ← PROTÈGE L'ACCÈS
include '../../config/db.php';
include '../../includes/header_admin.php';
include '../../functions/update_voyage_status.php';


$pdo->query("SELECT id_voyage FROM voyage")->fetchAll();
foreach ($pdo->query("SELECT id_voyage FROM voyage") as $row) {
    updateVoyageStatus($pdo, $row['id_voyage']);
}

// Récupération complète des réservations avec statut de paiement
$stmt = $pdo->query("
    SELECT 
        r.id_reservation,
        r.date_reservation,
        r.statut_reservation,
        c.nom_client,
        c.telephone,
        c.email,
        t.ville_depart,
        t.ville_arrivee,
        t.prix_base,
        v.date_depart,
        s.numero_siege,
        COALESCE(p.statut, 'en_attente') AS statut_paiement
    FROM reservation r
    JOIN client c ON r.id_client = c.id_client
    JOIN voyage v ON r.id_voyage = v.id_voyage
    JOIN trajet t ON v.id_trajet = t.id_trajet
    JOIN billet b ON r.id_reservation = b.id_reservation
    JOIN siege s ON b.id_siege = s.id_siege
    LEFT JOIN paiement p ON r.id_reservation = p.id_reservation
    ORDER BY r.date_reservation DESC
");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_reservations,
        SUM(bi.montant) as ca_total,
        AVG(bi.montant) as prix_moyen
    FROM reservation r
    JOIN billet bi ON r.id_reservation = bi.id_reservation
")->fetch();

$voyages_complets = $pdo->query("SELECT COUNT(*) FROM voyage WHERE statut_voyage = 'Complet'")->fetchColumn();
$voyages_en_cours = $pdo->query("SELECT COUNT(*) FROM voyage WHERE statut_voyage = 'En cours'")->fetchColumn();
?>

<h2>Tableau de bord Admin</h2>

<div style="margin-bottom:20px;">
    <a href="ajouter_voyage.php" class="btn btn-primary" style="margin-right:10px;">+ Voyage</a>
    <a href="ajouter_agence.php" class="btn btn-primary" style="margin-right:10px;">+ Agence</a>
    <a href="ajouter_bus.php" class="btn btn-primary" style="margin-right:10px;">+ Bus</a>
    <a href="ajouter_trajet.php" class="btn btn-primary">+ Trajet</a>
</div>
<!-- Statistiques -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <div class="voyage-card" style="text-align:center;">
        <h3><?= $stats['total_reservations'] ?></h3>
        <p>Réservations totales</p>
    </div>
    <div class="voyage-card" style="text-align:center;">
        <h3><?= number_format($stats['ca_total'] ?? 0, 0, ',', ' ') ?> FCFA</h3>
        <p>Chiffre d'affaires</p>
    </div>
    <div class="voyage-card" style="text-align:center;">
        <h3><?= $voyages_complets ?></h3>
        <p>Voyages complets</p>
    </div>
    <div class="voyage-card" style="text-align:center;">
        <h3><?= $voyages_en_cours ?></h3>
        <p>Voyages en cours</p>
    </div>
</div>

<h3>Dernières réservations</h3>
<?php if (isset($_GET['annule'])): ?>
    <div style="background:#27ae60; color:white; padding:12px; border-radius:5px; margin-bottom:1rem;">
        Réservation annulée avec succès.
    </div>
<?php endif; ?>

<?php if (empty($reservations)): ?>
    <p>Aucune réservation pour le moment.</p>
<?php else: ?>
    <table style="width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.1);">
        <thead style="background:#2c3e50; color:white;">
            <tr>
                <th style="padding:12px;">Date</th>
                <th style="padding:12px;">Client</th>
                <th style="padding:12px;">Trajet</th>
                <th style="padding:12px;">Siège</th>
                <th style="padding:12px;">Prix</th>
                <th style="padding:12px;">Statut Paiement</th>
                <th style="padding:12px;">Action</th>
            </tr>
        </thead>
      <tbody>
            <?php foreach ($reservations as $r): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:12px; text-align:center;">
                        <?= date('d/m/Y H:i', strtotime($r['date_reservation'])) ?>
                    </td>
                    <td style="padding:12px;">
                        <strong><?= htmlspecialchars($r['nom_client']) ?></strong><br>
                        <small><?= htmlspecialchars($r['telephone']) ?> | <?= htmlspecialchars($r['email']) ?></small>
                    </td>
                    <td style="padding:12px;">
                        <?= htmlspecialchars($r['ville_depart']) ?> → <?= htmlspecialchars($r['ville_arrivee']) ?><br>
                        <small><?= date('d/m H:i', strtotime($r['date_depart'])) ?></small>
                    </td>
                    <td style="padding:12px; text-align:center; font-weight:bold;">
                        <?= $r['numero_siege'] ?>
                    </td>
                    <td style="padding:12px; text-align:center;">
                        <strong><?= number_format($r['prix_base'], 0, ',', ' ') ?> FCFA</strong>
                    </td>
                    <td style="padding:12px; text-align:center;">
                        <?php if ($r['statut_paiement'] === 'paye'): ?>
                            <span class="badge success">Payé</span>
                        <?php else: ?>
                            <span class="badge warning">En attente</span>
                        <?php endif; ?>
                    </td>
                    
                    <td style="padding:12px; text-align:center;">
                        <?php if ($r['statut_paiement'] === 'paye'): ?>
                            <a href="../client/imprimer_billet.php?id=<?= $r['id_reservation'] ?>" 
                               target="_blank" 
                               class="btn-success btn-small" 
                               style="padding:8px 15px; font-size:12px;">
                               PDF
                            </a>
                        <?php else: ?>
                            <em>En attente de paiement</em>
                        <?php endif; ?>

                        <?php if ($r['statut_reservation'] !== 'annulée'): ?>
                            <br><br>
                            <a href="annuler_reservation.php?id=<?= $r['id_reservation'] ?>" 
                               onclick="return confirm('Annuler cette réservation ?')"
                               style="color:#e74c3c; font-size:12px;">
                               Annuler
                            </a>
                        <?php else: ?>
                            <br><br><em style="color:#95a5a6;">Annulée</em>
                        <?php endif; ?>
                        
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
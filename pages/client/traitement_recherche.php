<?php
include '../../config/db.php';

$depart = $_GET['depart'] ?? '';
$arrivee = $_GET['arrivee'] ?? '';
$date = $_GET['date'] ?? '';
$prix = $_GET['prix'] ?? '';

$sql = "
    SELECT v.*, t.ville_depart, t.ville_arrivee, t.prix_base, 
           b.immatriculation, b.nombre_place, a.nom_agence,
           (b.nombre_place - COALESCE(reservees.reservees, 0)) as places_libres
    FROM Voyage v
    JOIN Trajet t ON v.id_trajet = t.id_trajet
    JOIN Bus b ON v.id_bus = b.id_bus
    JOIN Agence a ON v.id_agence = a.id_agence
    LEFT JOIN (
        SELECT r.id_voyage, COUNT(*) as reservees 
        FROM Reservation r 
        JOIN Billet bi ON r.id_reservation = bi.id_reservation 
        WHERE r.statut_reservation != 'annulée'
        GROUP BY r.id_voyage
    ) reservees ON v.id_voyage = reservees.id_voyage
    WHERE 1=1
";

$params = [];
if ($depart) { $sql .= " AND t.ville_depart = ?"; $params[] = $depart; }
if ($arrivee) { $sql .= " AND t.ville_arrivee = ?"; $params[] = $arrivee; }
if ($date) { $sql .= " AND DATE(v.date_depart) = ?"; $params[] = $date; }
if ($prix) { $sql .= " AND t.prix_base <= ?"; $params[] = $prix; }

$sql .= " AND v.statut_voyage = 'Disponible' AND v.date_depart > NOW() ORDER BY v.date_depart ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$voyages = $stmt->fetchAll();

if (empty($voyages)): ?>
    <div class="empty-result">
        <p>Aucun voyage trouvé.</p>
        <button onclick="history.back()" class="btn-secondary">Retour</button>
    </div>
<?php else: ?>
    <div class="voyages-grid">
        <?php foreach ($voyages as $v): ?>
            <div class="voyage-card">
                <div class="card-header">
                    <h3><?= htmlspecialchars($v['ville_depart']) ?> → <?= htmlspecialchars($v['ville_arrivee']) ?></h3>
                    <span class="badge success">Disponible</span>
                </div>
                <div class="card-body">
                    <p><strong>Départ :</strong> <?= date('d/m/Y à H:i', strtotime($v['date_depart'])) ?></p>
                    <p><strong>Bus :</strong> <?= htmlspecialchars($v['immatriculation']) ?></p>
                    <p><strong>Places libres :</strong> <span class="places-count"><?= $v['places_libres'] ?></span></p>
                </div>
                <div class="card-footer">
                    <div class="price"><?= number_format($v['prix_base'], 0, ',', ' ') ?> FCFA</div>
                    <a href="reserver.php?voyage=<?= $v['id_voyage'] ?>" class="btn-reserver">
                        Réserver
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
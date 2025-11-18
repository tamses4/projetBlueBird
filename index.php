<?php include 'includes/header_public.php'; ?>
<?php
include 'config/db.php';
include 'functions/update_voyage_status.php';

// === DEBUG : VÉRIFIER LA CONNEXION ===


// === METTRE À JOUR TOUS LES STATUTS ===
$stmt = $pdo->query("SELECT id_voyage FROM Voyage");
$ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $id) {
    updateVoyageStatus($pdo, $id);
}


// === VOYAGES À AFFICHER (DISPONIBLES SEULEMENT) ===
// === VOYAGES À AFFICHER (DISPONIBLES + FUTURS) ===
$stmt = $pdo->query("
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
    WHERE v.statut_voyage = 'Disponible'
      AND v.date_depart > NOW()
    ORDER BY v.date_depart ASC
    LIMIT 6
");

$voyages = $stmt->fetchAll();
?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Réservez votre <span class="highlight">voyage en bus</span> en 1 clic</h1>
            <p class="hero-subtitle">Yaoundé • Douala • Bafoussam • Bamenda • Garoua</p>
            <a href="pages/client/recherche_voyages.php" class="btn-reserver-hero">Réserver maintenant</a>
        </div>
    </div>
</section>

<!-- VOYAGES -->
<section class="voyages-section">
    <div class="container">
        <h2 class="section-title">Voyages Disponibles</h2>
        <?php if (empty($voyages)): ?>
            <div class="empty-state">
                <p>Aucun voyage disponible pour le moment.</p>
                <a href="pages/client/recherche_voyages.php" class="btn-primary">Voir tous les voyages</a>
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
                            <a href="pages/client/reserver.php?voyage=<?= $v['id_voyage'] ?>" class="btn-reserver">
                                Réserver
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
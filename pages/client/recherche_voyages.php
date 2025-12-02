<?php include '../../includes/header_public1.php'; ?>
<?php
include '../../config/db.php';
include '../../functions/update_voyage_status.php';

// Mettre à jour les statuts
$stmt = $pdo->query("SELECT id_voyage FROM voyage");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
    updateVoyageStatus($pdo, $id);
}

// Récupérer les villes
$stmt = $pdo->query("SELECT DISTINCT ville_depart FROM trajet ORDER BY ville_depart");
$villes_depart = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT ville_arrivee FROM trajet ORDER BY ville_arrivee");
$villes_arrivee = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container search-page">
    <h2 class="section-title">Trouvez votre voyage</h2>

    <!-- FILTRES -->
    <div class="filters-card">
        <div class="filter-group">
            <label>Départ</label>
            <select id="ville_depart" class="form-control">
                <option value="">Toutes les villes</option>
                <?php foreach ($villes_depart as $v): ?>
                    <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Arrivée</label>
            <select id="ville_arrivee" class="form-control">
                <option value="">Toutes les villes</option>
                <?php foreach ($villes_arrivee as $v): ?>
                    <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Date</label>
            <input type="date" id="date_depart" class="form-control">
        </div>

        <div class="filter-group">
            <label>Prix max</label>
            <input type="number" id="prix_max" class="form-control" placeholder="15000" min="0">
        </div>

        <button id="btn-search" class="btn-primary">Rechercher</button>
    </div>

    <!-- RÉSULTATS -->
    <div id="resultats">
        <div class="loading">Chargement des voyages...</div>
    </div>
</div>

<!-- CHARGER LE JS -->
<script src="../../assets/js/search.js"></script>

<?php include '../../includes/footer.php'; ?>
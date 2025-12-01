<?php
include 'check.php';
include '../../config/db.php';
include '../../includes/header_admin.php';

$message = '';

if ($_POST) {
    $ville_depart = trim($_POST['ville_depart']);
    $ville_arrivee = trim($_POST['ville_arrivee']);
    $prix_base = (float)str_replace(' ', '', $_POST['prix_base']);

    if ($prix_base <= 0) {
        $message = "<div style='color:red;'>Prix invalide.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO trajet (ville_depart, ville_arrivee, prix_base) VALUES (?, ?, ?)");
            $stmt->execute([$ville_depart, $ville_arrivee, $prix_base]);
            $message = "<div style='color:green;'>Trajet ajouté avec succès !</div>";
        } catch (Exception $e) {
            $message = "<div style='color:red;'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Ajouter un Trajet</h2>
<?= $message ?>

<div class="form-container">
    <h2>Ajouter un Voyage</h2>
    <form method="POST">
          <div class="form-group">
        <label>Ville de départ</label>
        <input type="text" name="ville_depart" class="form-control" required placeholder="Yaoundé">
    </div>
    <div class="form-group">
        <label>Ville d'arrivée</label>
        <input type="text" name="ville_arrivee" class="form-control" required placeholder="Douala">
    </div>
    <div class="form-group">
        <label>Prix de base (FCFA)</label>
        <input type="number" name="prix_base" class="form-control" required min="1000" placeholder="7500">
    </div>

    <button type="submit" class="btn btn-primary">Ajouter le trajet</button>
   </form>
    <a href="dashboard.php" class="btn-secondary">Retour</a>
</div>

<?php include '../../includes/footer.php'; ?>
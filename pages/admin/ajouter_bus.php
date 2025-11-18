<?php
include 'check.php';
include '../../config/db.php';
include '../../includes/header_admin.php';

$message = '';

if ($_POST) {
    $immatriculation = trim($_POST['immatriculation']);
    $marque = trim($_POST['marque']);
    $nombre_place = (int)$_POST['nombre_place'];

    if ($nombre_place < 10 || $nombre_place > 60) {
        $message = "<div style='color:red;'>Nombre de places entre 10 et 60.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Bus (immatriculation, marque, nombre_place) VALUES (?, ?, ?)");
            $stmt->execute([$immatriculation, $marque, $nombre_place]);

            // Créer les sièges
            $id_bus = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO Siege (id_bus, numero_siege, statut) VALUES (?, ?, 'libre')");
            for ($i = 1; $i <= $nombre_place; $i++) {
                $stmt->execute([$id_bus, $i]);
            }

            $message = "<div style='color:green;'>Bus ajouté ! $nombre_place sièges créés.</div>";
        } catch (Exception $e) {
            $message = "<div style='color:red;'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Ajouter un Bus</h2>
<?= $message ?>

<div class="form-container">
    <h2>Ajouter un Voyage</h2>
    <form method="POST">
           <div class="form-group">
        <label>Immatriculation</label>
        <input type="text" name="immatriculation" class="form-control" required placeholder="CE 123 AB">
    </div>
    <div class="form-group">
        <label>Marque</label>
        <input type="text" name="marque" class="form-control" placeholder="Toyota Coaster">
    </div>
    <div class="form-group">
        <label>Nombre de places</label>
        <input type="number" name="nombre_place" class="form-control" min="10" max="60" required>
    </div>

    <button type="submit" class="btn btn-primary">Ajouter le bus</button>
   </form>
    <a href="dashboard.php" class="btn-secondary">Retour</a>
</div>

<?php include '../../includes/footer.php'; ?>
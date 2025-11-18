<?php
include 'check.php';
include '../../config/db.php';
include '../../includes/header_admin.php';

$message = '';

$trajets = $pdo->query("SELECT * FROM Trajet ORDER BY ville_depart")->fetchAll();
$buses = $pdo->query("SELECT * FROM Bus ORDER BY immatriculation")->fetchAll();
$agences = $pdo->query("SELECT * FROM Agence ORDER BY nom_agence")->fetchAll();

if ($_POST) {
    $id_trajet = $_POST['id_trajet'];
    $id_bus = $_POST['id_bus'];
    $id_agence = $_POST['id_agence'];
    $date_depart = $_POST['date_depart'];
    $date_arrivee = $_POST['date_arrivee'];

    if (strtotime($date_arrivee) <= strtotime($date_depart)) {
        $message = "<div style='color:red;'>L'arrivée doit être après le départ.</div>";
    } else {
        try {
            // Insérer le voyage
            $stmt = $pdo->prepare("INSERT INTO Voyage (id_trajet, id_bus, id_agence, date_depart, date_arrivee) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_trajet, $id_bus, $id_agence, $date_depart, $date_arrivee]);
            $id_voyage = $pdo->lastInsertId();

            // VÉRIFIER SI LES SIÈGES EXISTENT DÉJÀ POUR CE BUS
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Siege WHERE id_bus = ?");
            $stmt->execute([$id_bus]);
            $sieges_existants = $stmt->fetchColumn();

            if ($sieges_existants == 0) {
                // Créer les sièges UNIQUEMENT si le bus est neuf
                $stmt = $pdo->prepare("SELECT nombre_place FROM Bus WHERE id_bus = ?");
                $stmt->execute([$id_bus]);
                $nombre_place = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO Siege (id_bus, numero_siege, statut) VALUES (?, ?, 'libre')");
                for ($i = 1; $i <= $nombre_place; $i++) {
                    $stmt->execute([$id_bus, $i]);
                }

                $message = "<div style='color:green;'>Voyage ajouté ! $nombre_place sièges créés.</div>";
            } else {
                $message = "<div style='color:green;'>Voyage ajouté ! Les sièges existent déjà.</div>";
            }

        } catch (Exception $e) {
            $message = "<div style='color:red;'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Ajouter un Nouveau Voyage</h2>

<?= $message ?>

<div class="form-container">
    <h2>Ajouter un Voyage</h2>
    <form method="POST">
         <div class="form-group">
        <label>Trajet</label>
        <select name="id_trajet" class="form-control" required>
            <option value="">-- Choisir un trajet --</option>
            <?php foreach ($trajets as $t): ?>
                <option value="<?= $t['id_trajet'] ?>">
                    <?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?>
                    (<?= number_format($t['prix_base'], 0, ',', ' ') ?> FCFA)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Bus</label>
        <select name="id_bus" class="form-control" required>
            <option value="">-- Choisir un bus --</option>
            <?php foreach ($buses as $b): ?>
                <option value="<?= $b['id_bus'] ?>">
                    <?= htmlspecialchars($b['immatriculation']) ?> - <?= $b['nombre_place'] ?> places
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Agence</label>
        <select name="id_agence" class="form-control" required>
            <option value="">-- Choisir une agence --</option>
            <?php foreach ($agences as $a): ?>
                <option value="<?= $a['id_agence'] ?>">
                    <?= htmlspecialchars($a['nom_agence']) ?> (<?= htmlspecialchars($a['ville']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Date et heure de départ</label>
        <input type="datetime-local" name="date_depart" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Date et heure d'arrivée</label>
        <input type="datetime-local" name="date_arrivee" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary" style="padding:12px 30px;font-size:16px;">
        Ajouter le voyage
    </button>
    </form>
    <a href="dashboard.php" class="btn-secondary">Retour</a>
</div>

<?php include '../../includes/footer.php'; ?>
<?php
include 'check.php';
include '../../config/db.php';
include '../../includes/header_admin.php';

$message = '';

if ($_POST) {
    $nom = trim($_POST['nom_agence']);
    $adresse = trim($_POST['adresse_agence']);
    $tel = trim($_POST['tel_agence']);
    $email = trim($_POST['email_agence']);
    $ville = trim($_POST['ville']);

    if (empty($nom) || empty($ville)) {
        $message = "<div style='color:red;'>Nom et ville obligatoires.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Agence (nom_agence, adresse_agence, tel_agence, email_agence, ville) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $adresse, $tel, $email, $ville]);
            $message = "<div style='color:green;'>Agence ajoutée avec succès !</div>";
        } catch (Exception $e) {
            $message = "<div style='color:red;'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>

<h2>Ajouter une Agence</h2>
<?= $message ?>

<<div class="form-container">
    <h2>Ajouter un Voyage</h2>
    <form method="POST"> <div class="form-group">
        <label>Nom de l'agence *</label>
        <input type="text" name="nom_agence" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Adresse</label>
        <input type="text" name="adresse_agence" class="form-control">
    </div>
    <div class="form-group">
        <label>Téléphone</label>
        <input type="tel" name="tel_agence" class="form-control">
    </div>
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email_agence" class="form-control">
    </div>
    <div class="form-group">
        <label>Ville *</label>
        <input type="text" name="ville" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Ajouter l'agence</button>
    </form>
    <a href="dashboard.php" class="btn-secondary">Retour</a>
</div>

<?php include '../../includes/footer.php'; ?>
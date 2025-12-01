<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['client_id'])) {
    header("Location: login.php");
    exit;
}

$client_id = $_SESSION['client_id'];
$success = $error = "";

// Récupérer les infos actuelles
$stmt = $pdo->prepare("SELECT nom_client, telephone, email FROM client WHERE id_client = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $telephone = trim($_POST['telephone']);
    $email = trim($_POST['email']);
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';

    // Validation
    if (empty($nom) || empty($telephone) || empty($email)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif ($nouveau_mdp !== '' && strlen($nouveau_mdp) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } else {
        try {
            $sql = "UPDATE Client SET nom_client = ?, telephone = ?, email = ? WHERE id_client = ?";
            $params = [$nom, $telephone, $email, $client_id];

            // Si changement de mot de passe
            if ($nouveau_mdp !== '') {
                $hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                $sql = "UPDATE Client SET nom_client = ?, telephone = ?, email = ?, mot_de_passe = ? WHERE id_client = ?";
                $params = [$nom, $telephone, $email, $hash, $client_id];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $success = "Profil mis à jour avec succès !";
            // Rafraîchir les données
            $client['nom_client'] = $nom;
            $client['telephone'] = $telephone;
            $client['email'] = $email;
            $_SESSION['client_nom'] = $nom;

        } catch (Exception $e) {
            $error = "Erreur : email déjà utilisé ou problème technique.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - BusExpress</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root { --primary: #9c27b0; --success: #4CAF50; --danger: #f44336; }
        .container { max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        h2 { color: var(--primary); text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box;
        }
        .btn { 
            background: var(--primary); color: white; padding: 15px 30px; border: none; border-radius: 50px; 
            font-size: 18px; cursor: pointer; width: 100%; margin-top: 20px;
        }
        .btn:hover { background: #7b1fa2; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center; font-weight: bold; }
        .error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .back { display: block; text-align: center; margin-top: 25px; color: var(--primary); font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>Modifier mon profil</h2>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nom complet</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($client['nom_client']) ?>" required>
        </div>

        <div class="form-group">
            <label>Téléphone</label>
            <input type="text" name="telephone" value="<?= htmlspecialchars($client['telephone']) ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>" required>
        </div>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

        <div class="form-group">
            <label>Nouveau mot de passe (laisser vide pour garder l'ancien)</label>
            <input type="password" name="nouveau_mdp" placeholder="Minimum 6 caractères">
        </div>

        <div class="form-group">
            <label>Confirmer le nouveau mot de passe</label>
            <input type="password" name="confirmer_mdp">
        </div>

        <button type="submit" class="btn">Mettre à jour mon profil</button>
    </form>

    <a href="dashboard.php" class="back">Retour au tableau de bord</a>
</div>

</body>
</html>
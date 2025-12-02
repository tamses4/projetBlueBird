<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $tel = trim($_POST['telephone']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Vérifier si l'email existe déjà
    $check = $pdo->prepare("SELECT id_client FROM client WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } else {
        // ADAPTÉ À TA BASE : nom_client au lieu de nom_complet
        $stmt = $pdo->prepare("INSERT INTO client (nom_client, telephone, email, mot_de_passe) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nom, $tel, $email, $password])) {
            $success = "Compte créé avec succès ! Vous pouvez vous connecter.";
        } else {
            $erreur = "Erreur lors de la création du compte.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - BusExpress</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .login-box { max-width: 420px; margin: 80px auto; padding: 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        input { width: 100%; padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        button { background: #9c27b0; color: white; padding: 15px; border: none; border-radius: 50px; width: 100%; font-size: 18px; cursor: pointer; margin-top: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; text-align:center; font-weight:bold; }
        .error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: 15px 0; text-align:center; }
    </style>
</head>
<body>

<div class="login-box">
    <h2 style="color:#9c27b0; text-align:center;">Créer un compte client</h2>

    <?php if (isset($success)): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($erreur)): ?>
        <div class="error"><?= $erreur ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="nom" placeholder="Nom complet" required>
        <input type="tel" name="telephone" placeholder="Téléphone (ex: 690000000)" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required minlength="6">
        <button type="submit">S'inscrire</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
        Déjà un compte ? <a href="login.php" style="color:#9c27b0; font-weight:bold;">Se connecter</a>
    </p>
</div>

</body>
</html>
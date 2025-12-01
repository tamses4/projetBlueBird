<?php
session_start();
require_once '../../config/db.php';

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_client, nom_client, mot_de_passe FROM client WHERE email = ?");
            $stmt->execute([$email]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérification sécurisée même si mot_de_passe est NULL ou vide
            $hash = $client['mot_de_passe'] ?? '';
            
            if ($client && $hash !== '' && $hash !== null && password_verify($password, $hash)) {
                // Connexion réussie
                $_SESSION['client_id'] = $client['id_client'];
                $_SESSION['client_nom'] = $client['nom_client'];
                header("Location: dashboard.php");
                exit;
            } else {
                // Si le mot de passe est en clair (ancien système) → on accepte temporairement
                // À SUPPRIMER plus tard quand tout le monde aura un mot de passe hashé
                if ($client && $password === $hash) {
                    // On re-hashe le mot de passe pour la sécurité
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE Client SET mot_de_passe = ? WHERE id_client = ?");
                    $update->execute([$new_hash, $client['id_client']]);

                    $_SESSION['client_id'] = $client['id_client'];
                    $_SESSION['client_nom'] = $client['nom_client'];
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $erreur = "Email ou mot de passe incorrect.";
                }
            }
        } catch (Exception $e) {
            $erreur = "Erreur de connexion. Réessayez.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Client - BusExpress</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #9c27b0, #e91e63); min-height: 100vh; display: flex; align-items: center; }
        .login-box { 
            max-width: 420px; margin: 0 auto; padding: 40px; background: white; 
            border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align:center; 
        }
        h2 { color: #9c27b0; margin-bottom: 30px; font-size: 28px; }
        input { 
            width: 100%; padding: 16px; margin: 12px 0; border: 2px solid #eee; 
            border-radius: 50px; font-size: 16px; box-sizing: border-box; transition: 0.3s;
        }
        input:focus { border-color: #9c27b0; outline: none; }
        button { 
            background: #9c27b0; color: white; padding: 16px; border: none; 
            border-radius: 50px; width: 100%; font-size: 18px; cursor: pointer; 
            margin-top: 10px; font-weight: bold; transition: 0.3s;
        }
        button:hover { background: #7b1fa2; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(156,39,176,0.4); }
        .error { 
            background: #ffebee; color: #c62828; padding: 15px; border-radius: 12px; 
            margin: 15px 0; font-weight: bold; border: 1px solid #ffcdd2;
        }
        .register-link { margin-top: 25px; }
        .register-link a { color: #9c27b0; font-weight: bold; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Connexion Client</h2>
    
    <?php if (!empty($erreur)): ?>
        <div class="error"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Votre email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>

    <div class="register-link">
        <p>Pas de compte ? <a href="register.php">Inscrivez-vous gratuitement</a></p>
    </div>
</div>

</body>
</html>
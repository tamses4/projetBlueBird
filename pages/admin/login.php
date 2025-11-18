<?php
session_start();
include '../../config/db.php';

$error = '';

if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && $password === $admin['mot_de_passe']) {
        $_SESSION['admin_id'] = $admin['id_admin'];
        $_SESSION['admin_nom'] = $admin['nom'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin | BusExpress</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .login-container {
            max-width: 420px;
            margin: 80px auto;
            background: white;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-logo {
            width: 80px;
            height: 80px;
            background: #3498db;
            color: white;
            font-size: 36px;
            font-weight: bold;
            line-height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .login-subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background: #2980b9;
        }
        .error-alert {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        .login-footer {
            margin-top: 30px;
            font-size: 13px;
            color: #95a5a6;
        }
        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-logo">B</div>
    <h1 class="login-title">Connexion Admin</h1>
    <p class="login-subtitle">Accédez au tableau de bord de gestion</p>

    <?php if ($error): ?>
        <div class="error-alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@busexpress.cm">
        </div>

        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>

        <button type="submit" class="btn-login">
            Se connecter
        </button>
    </form>

    <div class="login-footer">
        <p><a href="../../index.php">← Retour à l'accueil</a></p>
    </div>
</div>

</body>
</html>
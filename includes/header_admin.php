<?php include '../admin/check.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - BusExpress</title>
    <link rel="stylesheet" href="../../assets/css/style copy.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<script src="../../assets/js/menu.js"></script>
<body>
    <header class="header-admin">
        <div class="container">
            <div class="logo">
                <h1><i class="fas fa-cog"></i> <span class="highlight">Admin</span></h1>
            </div>
            <nav class="nav-admin" id="admin-nav">
                <a href="dashboard.php" class="nav-link">Tableau</a>
                <a href="ajouter_voyage.php" class="nav-link">+ Voyage</a>
                <a href="ajouter_agence.php" class="nav-link">+ Agence</a>
                <a href="ajouter_bus.php" class="nav-link">+ Bus</a>
                <a href="ajouter_trajet.php" class="nav-link">+ Trajet</a>
                <a href="logout.php" class="nav-link logout">Déconnexion</a>
            </nav>
            <button class="menu-toggle" id="admin-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    <main class="container main-content admin-content">
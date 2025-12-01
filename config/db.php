<?php

// ==============================
//  CONFIGURATION AUTO (ENV → LOCAL)
// ==============================

// Render / Aiven (production)
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'gestion_bus';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Callita4';
$port = getenv('DB_PORT') ?: 3306;

// ==============================
//  CONNEXION PDO
// ==============================

try {
    // Le DSN DOIT contenir le port pour AIVEN
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8";

    $pdo = new PDO($dsn, $user, $pass);

    // Options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Force UTF-8 (sécurité)
    $pdo->exec("SET NAMES utf8");

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

?>

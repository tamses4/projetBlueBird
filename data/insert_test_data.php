<?php
include '../config/db.php';

// === 1. Agence ===
$agence = "INSERT INTO Agence (nom_agence, adresse_agence, tel_agence, email_agence, ville) VALUES 
    ('TransCam', 'Rue des Palmiers, Douala', '677889900', 'contact@transcam.cm', 'Douala')";
$pdo->exec($agence);
$id_agence = $pdo->lastInsertId();

// === 2. Trajet ===
$trajet = "INSERT INTO Trajet (ville_depart, ville_arrivee, prix_base) VALUES 
    ('Yaoundé', 'Douala', 7500)";
$pdo->exec($trajet);
$id_trajet = $pdo->lastInsertId();

// === 3. Bus ===
$bus = "INSERT INTO Bus (immatriculation, marque, nombre_place) VALUES 
    ('CE 123 AB', 'Toyota Coaster', 30)";
$pdo->exec($bus);
$id_bus = $pdo->lastInsertId();

// === 4. Sièges (30 sièges) ===
$stmt = $pdo->prepare("INSERT INTO Siege (id_bus, numero_siege, statut) VALUES (?, ?, 'libre')");
for ($i = 1; $i <= 30; $i++) {
    $stmt->execute([$id_bus, $i]);
}

// === 5. Voyage (demain à 8h) ===
$depart = date('Y-m-d H:i:s', strtotime('+1 day 8:00'));
$arrivee = date('Y-m-d H:i:s', strtotime('+1 day 12:00'));

$voyage = "INSERT INTO Voyage (id_trajet, id_bus, id_agence, date_depart, date_arrivee) VALUES 
    (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($voyage);
$stmt->execute([$id_trajet, $id_bus, $id_agence, $depart, $arrivee]);

echo "<h2>Données de test insérées avec succès !</h2>";
echo "<p><a href='../index.php'>Retour à l'accueil</a></p>";
?>
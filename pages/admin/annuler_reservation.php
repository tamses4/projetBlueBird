<?php
include '../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("ID invalide.");
}

// Vérifier que la réservation existe
$stmt = $pdo->prepare("SELECT id_voyage FROM reservation WHERE id_reservation = ?");
$stmt->execute([$id]);
$reservation = $stmt->fetch();

if (!$reservation) {
    die("Réservation introuvable.");
}

// Annuler
$pdo->prepare("UPDATE reservation SET statut_reservation = 'annulée' WHERE id_reservation = ?")
    ->execute([$id]);

// Libérer le siège
$pdo->prepare("
    UPDATE siege s
    JOIN billet bi ON s.id_siege = bi.id_siege
    SET s.statut = 'libre'
    WHERE bi.id_reservation = ?
")->execute([$id]);

// Mettre à jour le statut du voyage
include '../../functions/update_voyage_status.php';
updateVoyageStatus($pdo, $reservation['id_voyage']);

header("Location: dashboard.php?annule=1");
exit;
?>
<?php
// pages/admin/supprimer_reservation.php
session_start();
require_once '../../config/db.php';

// Protection admin
if (!isset($_SESSION['admin_id'])) {
    die("Accès refusé.");
}

$id_reservation = (int)($_GET['id'] ?? 0);

if ($id_reservation <= 0) {
    header("Location: dashboard.php?suppr=error");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Récupérer le siège à libérer
    $stmt = $pdo->prepare("SELECT id_siege FROM Billet WHERE id_reservation = ?");
    $stmt->execute([$id_reservation]);
    $id_siege = $stmt->fetchColumn();

    // 2. Supprimer TOUT dans le bon ordre (enfant → parent)
    $pdo->prepare("DELETE FROM Paiement WHERE id_reservation = ?")->execute([$id_reservation]);
    $pdo->prepare("DELETE FROM Billet WHERE id_reservation = ?")->execute([$id_reservation]);
    $pdo->prepare("DELETE FROM Reservation WHERE id_reservation = ?")->execute([$id_reservation]);

    // 3. Libérer le siège (seulement si on l'a bien récupéré)
    if ($id_siege) {
        $pdo->prepare("UPDATE Siege SET statut = 'disponible' WHERE id_siege = ?")->execute([$id_siege]);
    }

    $pdo->commit();

    header("Location: dashboard.php?suppr=ok");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    // Pour débugger : décommente la ligne suivante si tu veux voir l'erreur exacte
    // error_log("ERREUR SUPPRESSION ID $id_reservation : " . $e->getMessage());
    header("Location: dashboard.php?suppr=error");
    exit;
}
?>
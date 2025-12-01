<?php
// functions/update_voyage_status.php
function updateVoyageStatus($pdo, $id_voyage) {
    $stmt = $pdo->prepare("
        SELECT 
            v.date_depart, v.date_arrivee, 
            b.nombre_place,
            COALESCE(COUNT(bi.id_billet), 0) as billets_vendus
        FROM voyage v
        JOIN bus b ON v.id_bus = b.id_bus
        LEFT JOIN billet bi ON bi.id_reservation IN (
            SELECT id_reservation 
            FROM reservation 
            WHERE id_voyage = v.id_voyage 
            AND statut_reservation != 'annulée'
        )
        WHERE v.id_voyage = ?
        GROUP BY v.id_voyage, v.date_depart, v.date_arrivee, b.nombre_place
    ");
    $stmt->execute([$id_voyage]);
    $data = $stmt->fetch();

    if (!$data) return;

    $now = new DateTime();
    $depart = new DateTime($data['date_depart']);
    $arrivee = new DateTime($data['date_arrivee']);

    $nouveau_statut = 'Disponible';

    if ($data['billets_vendus'] >= $data['nombre_place']) {
        $nouveau_statut = 'Complet';
    } elseif ($now >= $depart && $now < $arrivee) {
        $nouveau_statut = 'En cours';
    } elseif ($now >= $arrivee) {
        $nouveau_statut = 'Terminé';
    }

    $pdo->prepare("UPDATE Voyage SET statut_voyage = ? WHERE id_voyage = ?")
        ->execute([$nouveau_statut, $id_voyage]);
}
?>
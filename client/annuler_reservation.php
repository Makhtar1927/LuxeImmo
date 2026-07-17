<?php
/**
 * client/annuler_reservation.php — Annulation d'une réservation par le client
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('client');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Vérifier que la réservation appartient au client et est annulable
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND client_id = ? AND statut = 'en_attente'");
$stmt->execute([$id, $_SESSION['user_id']]);
$reservation = $stmt->fetch();

if ($reservation) {
    $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?")->execute([$id]);
    header('Location: dashboard.php?msg=reservation_annulee');
} else {
    header('Location: dashboard.php?error=annulation_impossible');
}
exit;

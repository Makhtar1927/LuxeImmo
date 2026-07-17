<?php
/**
 * client/toggle_favori.php
 * Endpoint AJAX pour ajouter/retirer un bien des favoris
 * Méthode : POST — Retourne JSON
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

// Doit être connecté en tant que client
if (!is_logged_in() || !has_role('client')) {
    echo json_encode(['success' => false, 'redirect' => '../login.php']);
    exit;
}

$bien_id = isset($_POST['bien_id']) ? (int)$_POST['bien_id'] : 0;
if (!$bien_id) {
    echo json_encode(['success' => false, 'message' => 'Bien invalide.']);
    exit;
}

// Vérifier si le bien existe
$check = $pdo->prepare("SELECT id FROM biens WHERE id = ?");
$check->execute([$bien_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Bien introuvable.']);
    exit;
}

// Vérifier si déjà en favori
$existing = $pdo->prepare("SELECT id FROM favoris WHERE client_id = ? AND bien_id = ?");
$existing->execute([$_SESSION['user_id'], $bien_id]);
$fav = $existing->fetch();

if ($fav) {
    // Supprimer le favori
    $pdo->prepare("DELETE FROM favoris WHERE client_id = ? AND bien_id = ?")->execute([$_SESSION['user_id'], $bien_id]);
    echo json_encode(['success' => true, 'is_favori' => false, 'message' => 'Retiré de vos favoris.']);
} else {
    // Ajouter aux favoris
    $pdo->prepare("INSERT INTO favoris (client_id, bien_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $bien_id]);
    echo json_encode(['success' => true, 'is_favori' => true, 'message' => 'Ajouté à vos favoris !']);
}

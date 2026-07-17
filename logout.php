<?php
/**
 * logout.php — Déconnexion sécurisée
 */
require_once 'includes/auth_check.php';

// Détruire toutes les données de session
$_SESSION = [];

// Détruire le cookie de session si présent
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: login.php?message=deconnecte');
exit;

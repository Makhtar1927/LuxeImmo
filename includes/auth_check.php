<?php
/**
 * includes/auth_check.php
 * Middleware de contrôle d'accès.
 * 
 * Usage :
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   require_login();            // Oblige connexion (tout rôle)
 *   require_role('commercial'); // Oblige rôle commercial
 *   require_role('client');     // Oblige rôle client
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Exige que l'utilisateur soit connecté.
 * Si non connecté, redirige vers login.php
 */
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . get_base_url() . 'login.php?message=connexion_requise');
        exit;
    }
}

/**
 * Exige un rôle spécifique.
 * @param string $role  'client' ou 'commercial'
 */
function require_role(string $role): void {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        header('Location: ' . get_base_url() . 'index.php?error=acces_refuse');
        exit;
    }
    // Vérifier également que le compte est actif
    if (isset($_SESSION['statut']) && $_SESSION['statut'] !== 'actif') {
        session_destroy();
        header('Location: ' . get_base_url() . 'login.php?message=compte_inactif');
        exit;
    }
}

/**
 * Retourne l'URL de base du projet (chemin relatif vers la racine)
 * depuis n'importe quel sous-dossier.
 */
function get_base_url(): string {
    $script  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
    $root    = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $depth   = substr_count(str_replace($root, '', $script), '/');
    return str_repeat('../', $depth);
}

/**
 * Vérifie si un utilisateur est connecté (sans rediriger)
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Retourne vrai si l'utilisateur a le rôle donné
 */
function has_role(string $role): bool {
    return is_logged_in() && $_SESSION['role'] === $role;
}

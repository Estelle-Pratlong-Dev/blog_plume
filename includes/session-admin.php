<?php
// Session privée partagée entre l’administration et son raccourci sur le blog.
// Aucune session n’est ouverte pour un visiteur sans cookie de connexion.
function ouvrir_session_admin(bool $create = false): bool
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (!$create && !isset($_COOKIE['plume_admin'])) {
        return false;
    }
    ini_set('session.use_strict_mode', '1');
    session_name('plume_admin');
    $parameters = [
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Strict',
        'path' => url(''),
    ];
    session_set_cookie_params($parameters);
    session_start();
    if ($create) {
        // Déplace l’ancien cookie limité à admin/ vers le périmètre du blog.
        setcookie(session_name(), session_id(), $parameters);
        setcookie(session_name(), '', [...$parameters, 'expires' => time() - 3600, 'path' => url('admin/')]);
    }
    return true;
}

function session_admin_valide(bool $refresh = true): bool
{
    if (($_SESSION['compte'] ?? '') !== 'admin' || !isset($_SESSION['activite']) || time() - (int) $_SESSION['activite'] > 1800) {
        unset($_SESSION['compte'], $_SESSION['activite']);
        return false;
    }
    require_once __DIR__ . '/contenus.php';
    if (chercher_compte('admin') === null) {
        unset($_SESSION['compte'], $_SESSION['activite']);
        return false;
    }
    if ($refresh) {
        $_SESSION['activite'] = time();
    }
    return true;
}

function afficher_raccourci_admin(): bool
{
    if (!ouvrir_session_admin()) {
        return false;
    }
    $visible = session_admin_valide();
    session_write_close();
    return $visible;
}

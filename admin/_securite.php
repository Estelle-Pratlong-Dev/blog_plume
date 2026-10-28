<?php
// Préparation propre à l’administration : session, compte unique, CSRF et limitation des essais.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contenus.php';

if ($site['production'] && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    http_response_code(403);
    exit('L’administration nécessite une connexion HTTPS en production.');
}

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
ouvrir_session_admin(true);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function champ_post(string $name): string
{
    return is_string($_POST[$name] ?? null) ? $_POST[$name] : '';
}

function verifier_csrf(): void
{
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('La session du formulaire a expiré. Rechargez la page avant de réessayer.');
    }
}

function admin_connecte(): bool
{
    return session_admin_valide();
}

function exiger_admin(bool $json = false): void
{
    if (admin_connecte()) {
        return;
    }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erreur' => 'Reconnectez-vous avant d’importer un document.']);
        exit;
    }
    header('Location: ' . url('admin/connexion.php'), true, 303);
    exit;
}

function adresse_tentative(): string
{
    // L’adresse n’est pas conservée en clair, et les compteurs expirent après quinze minutes.
    return hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'inconnue'));
}

function trop_de_tentatives(): bool
{
    // Réservation sous le même verrou : pas de contournement par requêtes simultanées.
    return gerer_tentatives(adresse_tentative(), 'reserver') > 5;
}

<?php
require __DIR__ . '/_securite.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
verifier_csrf();
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => url(''), 'httponly' => true, 'samesite' => 'Strict']);
header('Location: ' . url('admin/connexion.php'), true, 303);

<?php
// Initialisation d’un compte unique hors navigateur ; jamais de mot de passe en argument.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/contenus.php';
$replace = in_array('--remplacer', $argv, true);
if (nombre_comptes() > 0 && !$replace) {
    fwrite(STDERR, "Un compte existe déjà. Utiliser --remplacer pour changer ses accès explicitement.\n");
    exit(1);
}
$password = rtrim(stream_get_contents(STDIN), "\r\n");
if (strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Le mot de passe doit contenir entre 12 et 72 octets.\n");
    exit(1);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
depot_operation(static function (array &$state) use ($hash, $replace): void {
    if ($state['comptes'] !== [] && !$replace) {
        throw new RuntimeException('Un compte vient déjà d’être configuré.');
    }
    $state['comptes'] = ['admin' => ['identifiant' => 'admin', 'mot_de_passe' => $hash]];
    $state['tentatives'] = [];
}, true);
fwrite(STDOUT, "Le compte unique admin est configuré.\n");

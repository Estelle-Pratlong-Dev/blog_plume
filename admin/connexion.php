<?php
require __DIR__ . '/_securite.php';
if (admin_connecte()) {
    header('Location: ' . url('admin/index.php'), true, 303);
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    if (trop_de_tentatives()) {
        http_response_code(429);
        $error = 'Trop de tentatives. Réessayez dans quinze minutes.';
    } else {
        $account = chercher_compte(trim(champ_post('identifiant')));
        $valid = password_verify(champ_post('mot_de_passe'), $account['mot_de_passe'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if ($account && $valid) {
            session_regenerate_id(true);
            $_SESSION['compte'] = $account['identifiant'];
            $_SESSION['activite'] = time();
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            gerer_tentatives(adresse_tentative(), 'effacer');
            header('Location: ' . url('admin/index.php'), true, 303);
            exit;
        }
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}
$adminTitle = 'Entrer dans l’atelier';
$authenticated = false;
require __DIR__ . '/_entete.php';
?>
        <!-- =============================== CONNEXION =============================== -->
        <?php if ($error !== ''): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif; ?>
        <form class="admin-form auth-form" method="post">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <label for="identifiant">Identifiant</label>
            <input id="identifiant" name="identifiant" autocomplete="username" required maxlength="50">
            <label for="mot-de-passe">Mot de passe</label>
            <input id="mot-de-passe" type="password" name="mot_de_passe" autocomplete="current-password" required maxlength="200">
            <button class="button button-dark" type="submit">Se connecter</button>
        </form>

<?php require __DIR__ . '/_pied.php'; ?>

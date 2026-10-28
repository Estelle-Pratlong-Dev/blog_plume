<?php
/* Variables attendues : $site, $adminTitle et $authenticated de la page appelante.
 * Mise en page réservée à l’espace d’édition.
 */
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitle) ?> — Administration</title>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <?php if (!empty($editorScript)): ?>
        <link rel="stylesheet" href="<?= e(url('css/lecture.css')) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(url('css/admin.css') . '?v=' . filemtime(__DIR__ . '/../css/admin.css')) ?>">
</head>
<body class="admin-body">
    <!-- ======================== NAVIGATION D’ADMINISTRATION ======================== -->
    <header class="admin-header">
        <div class="container">
            <a href="<?= e(url('admin/index.php')) ?>"><?= e($site['name']) ?> <small>Atelier de l’autrice</small></a>
            <nav aria-label="Administration">
                <a href="<?= e(url('index.php')) ?>">Voir le blog</a>
                <?php if ($authenticated): ?>
                    <a href="<?= e(url('admin/index.php')) ?>">Mes textes</a>
                    <form method="post" action="<?= e(url('admin/deconnexion.php')) ?>">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <button type="submit">Se déconnecter</button>
                    </form>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container admin-main" id="contenu">
        <h1><?= e($adminTitle) ?></h1>

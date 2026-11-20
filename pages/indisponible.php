<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contenus.php';
$now = horloge_publication($site);
$adventYear = isset($text) ? annee_avent_texte($text, $site) : (int) $now->format('Y');
$calendarEnded = $now >= fin_calendrier_avent($site, $adventYear);
$months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$currentMonth = $months[(int) $now->format('n') - 1];
$currentPage = 'ephemere.php';
http_response_code(403);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $calendarEnded ? 'Calendrier terminé' : 'Lecture à venir' ?> — <?= e($site['name']) ?></title>
    <meta name="description" content="La page demandée n’existe pas. Retrouvez les rubriques du blog de L . Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/obligatoires/404.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('img/identite/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ PAGE INTROUVABLE ============================ -->
        <section class="page-intro container">
            <p class="eyebrow"><?= e($currentMonth) ?></p>
            <h1><?= $calendarEnded ? 'Le calendrier est terminé' : 'Un peu de patience…' ?></h1>
            <p class="intro-text"><?= $calendarEnded ? 'Les histoires étaient accessibles jusqu’à la fin du calendrier.' : 'Cette fenêtre s’ouvrira à la date prévue, à minuit, heure de Paris.' ?></p>
            <a class="button" href="<?= e(url('index.php')) ?>">Revenir à l’accueil <span aria-hidden="true">→</span></a>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

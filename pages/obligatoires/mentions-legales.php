<?php
require __DIR__ . '/../../includes/bootstrap.php';
$currentPage = 'mentions-legales.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mentions légales — <?= e($site['name']) ?></title>
    <meta name="description" content="Informations relatives à l’édition et à la réalisation du blog de L. Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/obligatoires/mentions-legales.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ INFORMATIONS LÉGALES ============================ -->
        <article class="reading-layout">
            <h1>Mentions légales</h1>
            <p class="notice">Version de travail : les informations d’édition et d’hébergement restent à compléter avant toute mise en ligne.</p>
            <div class="prose">
                <h2>Édition et publication</h2>
                <dl class="legal-details">
                    <?php foreach (['publisher' => 'Éditeur', 'publication_director' => 'Responsable de publication', 'address' => 'Adresse légale'] as $field => $label): ?>
                        <dt><?= e($label) ?></dt><dd><?= e($site['legal'][$field] ?: 'À renseigner avant publication') ?></dd>
                    <?php endforeach; ?>
                </dl>
                <h2>Hébergement</h2>
                <p><?= e($site['legal']['host_name'] ?: 'Hébergeur à confirmer avant publication.') ?></p>
                <p><?= e($site['legal']['host_address']) ?></p>
                <p><?= e($site['legal']['host_contact']) ?></p>
                <h2>Réalisation</h2>
                <p><?= e($site['creator']['name']) ?><?php if ($site['creator']['url'] !== ''): ?> — <a href="<?= e($site['creator']['url']) ?>">Site de la créatrice</a><?php endif; ?>.</p>
                <h2>Textes et photographies</h2>
                <p>Les textes et photographies restent soumis aux droits de leurs titulaires. Leur présence sur ce site n’autorise pas leur réutilisation. Les crédits et autorisations seront vérifiés avant la publication.</p>
            </div>
        </article>
    </main>
    <?php require __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

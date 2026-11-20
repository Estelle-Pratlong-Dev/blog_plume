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
    <meta name="description" content="Informations relatives à l’édition et à la réalisation du blog de L . Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/obligatoires/mentions-legales.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('img/identite/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ INFORMATIONS LÉGALES ============================ -->
        <article class="reading-layout">
            <h1>Mentions légales</h1>
            <div class="prose">
                <!-- ========================== ÉDITION ET CONTACT ========================== -->
                <h2>Édition du blog</h2>
                <p><?= e($site['name']) ?> est un blog personnel et non professionnel sur lequel <?= e($site['author']) ?> partage ses écrits.</p>
                <?php if ($site['public_url'] !== ''): ?>
                    <p>Adresse du blog <a href="<?= e($site['public_url']) ?>"><?= e($site['public_url']) ?></a>.</p>
                <?php endif; ?>
                <p>Pour joindre <?= e($site['author']) ?> ou signaler un problème, vous pouvez utiliser le <a href="<?= e(url('pages/contact.php')) ?>">formulaire de contact</a><?php if ($site['email'] !== ''): ?> ou écrire à <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a><?php endif; ?>.</p>
                <?php if ($site['legal']['non_professional'] ?? false): ?>
                    <p>L’article 1-1, II de la loi pour la confiance dans l’économie numérique permet à un éditeur non professionnel de préserver son anonymat public en indiquant les coordonnées de son hébergeur, sous réserve de lui avoir communiqué les informations d’identification requises.</p>
                    <?php if (!($site['legal']['host_identity_confirmed'] ?? false)): ?>
                        <p class="notice">À confirmer avant l’ouverture du blog au public — la transmission à IONOS des informations d’identification de la personne qui édite ce site.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <dl class="legal-details">
                        <?php foreach (['publisher' => 'Éditeur', 'publication_director' => 'Responsable de publication', 'address' => 'Adresse légale'] as $field => $label): ?>
                            <dt><?= e($label) ?></dt>
                            <dd><?= e($site['legal'][$field] ?: 'À compléter avant publication') ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>

                <!-- ========================== HÉBERGEMENT ========================== -->
                <h2>Hébergement</h2>
                <p><?= e($site['legal']['host_name']) ?><br><?= e($site['legal']['host_address']) ?><br><?= e($site['legal']['host_contact']) ?></p>
                <p><a href="https://www.ionos.fr/terms-gtc/terms-imprint/">Coordonnées et informations légales d’IONOS</a>.</p>

                <!-- ========================== DROITS SUR LES CONTENUS ========================== -->
                <h2>Textes et illustrations</h2>
                <p>Les textes de <?= e($site['author']) ?> sont protégés par le droit d’auteur. Les contributions des lecteurs, les photographies et les illustrations restent soumises aux droits de leurs titulaires respectifs.</p>
                <p>Toute reproduction ou réutilisation nécessite l’autorisation du titulaire des droits, sous réserve des exceptions prévues par la loi. La publication d’un contenu sur ce blog n’autorise pas sa copie ni sa redistribution.</p>
                <p>Les titulaires des droits réservent expressément les usages de fouille de textes et de données sur les contenus pour lesquels ils disposent de ces droits, notamment à des fins d’entraînement de systèmes d’intelligence artificielle, dans les conditions prévues par la loi.</p>

                <h2>Fonctionnement et liens externes</h2>
                <p>Malgré le soin apporté au blog, des erreurs ou des interruptions techniques peuvent survenir. Vous pouvez les signaler à <?= e($site['author']) ?>.</p>
                <p>Les sites accessibles par des liens externes sont édités par leurs propres responsables et leur contenu peut évoluer indépendamment de ce blog. Les responsabilités prévues par la loi restent applicables.</p>

                <h2>Données personnelles</h2>
                <p>La <a href="<?= e(url('pages/obligatoires/confidentialite.php')) ?>">politique de confidentialité</a> explique comment vos messages et vos propositions de textes sont traités et comment exercer vos droits.</p>

                <h2>Réalisation</h2>
                <p>Réalisation par <?= e($site['creator']['name']) ?><?php if ($site['creator']['url'] !== ''): ?> — <a href="<?= e($site['creator']['url']) ?>">consulter son site</a><?php endif; ?>.</p>
            </div>
        </article>
    </main>
    <?php require __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

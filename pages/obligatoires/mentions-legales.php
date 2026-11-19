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
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ INFORMATIONS LÉGALES ============================ -->
        <article class="reading-layout">
            <h1>Mentions légales</h1>
            <p class="notice">Version de travail : les conditions de préservation de l’anonymat et les informations relatives aux données personnelles restent à vérifier avant validation définitive.</p>
            <div class="prose">
                <h2>Édition et publication</h2>
                <?php if ($site['legal']['non_professional'] ?? false): ?>
                    <p>Ce blog personnel et non professionnel permet à <?= e($site['author']) ?> de partager ses textes écrits en amateur.</p>
                    <p>L’article 1-1, II de la loi pour la confiance dans l’économie numérique permet aux éditeurs non professionnels de préserver leur anonymat public en indiquant les coordonnées de leur hébergeur, à condition de lui avoir communiqué leur identité réelle.</p>
                    <?php if (!($site['legal']['host_identity_confirmed'] ?? false)): ?>
                        <p class="notice">À vérifier avant validation : la véritable identité de la personne qui édite ce blog doit avoir été communiquée à IONOS. Les seules coordonnées du titulaire technique du compte d’hébergement ne suffisent pas si ce n’est pas la même personne.</p>
                    <?php endif; ?>
                    <p>Pour contacter <?= e($site['author']) ?> : <a href="<?= e(url('pages/contact.php')) ?>">formulaire de contact</a>.</p>
                <?php else: ?>
                    <dl class="legal-details">
                        <?php foreach (['publisher' => 'Éditeur', 'publication_director' => 'Responsable de publication', 'address' => 'Adresse légale'] as $field => $label): ?>
                            <dt><?= e($label) ?></dt><dd><?= e($site['legal'][$field] ?: 'À renseigner avant publication') ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <h2>Hébergement</h2>
                <p><?= e($site['legal']['host_name'] ?: 'Hébergeur à confirmer avant publication.') ?></p>
                <p><?= e($site['legal']['host_address']) ?></p>
                <p><?= e($site['legal']['host_contact']) ?></p>
                <h2>Responsabilité et liens externes</h2>
                <p>Malgré le soin apporté au site, des erreurs ou des interruptions techniques peuvent survenir. Vous pouvez les signaler à <?= e($site['author']) ?> via le formulaire de contact.</p>
                <p>Les sites accessibles par des liens externes sont édités par leurs propres responsables. Leur contenu peut évoluer indépendamment de ce blog. Ces précisions ne limitent pas les responsabilités prévues par la loi.</p>
                <h2>Données personnelles</h2>
                <p>Pour connaître le fonctionnement du formulaire de contact, les données utilisées, leur conservation et les modalités d’exercice de vos droits dans le cadre du RGPD et de la loi Informatique et Libertés, consultez la <a href="<?= e(url('pages/obligatoires/confidentialite.php')) ?>">politique de confidentialité</a>.</p>
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

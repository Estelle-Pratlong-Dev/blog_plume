<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'a-propos.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Une envie d’écrire — <?= e($site['name']) ?></title>
    <meta name="description" content="Le parcours et les envies d’écriture de L. Yann, des premiers cahiers au blog littéraire.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/a-propos.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================= GENÈSE DE L’AUTRICE ============================= -->
        <article class="reading-layout">
            <header class="reading-heading">
                <p class="eyebrow">Le parcours de l’autrice</p>
                <h1>Une envie d’écrire</h1>
                <p class="intro-text">Depuis aussi longtemps que je me souvienne, j’écris…</p>
            </header>
            <div class="prose">
                <p>Dès le plus jeune âge sur un cahier de « travaux pratiques », comme celui des récitations de mon école primaire : le texte sur une page et l’illustration en vis-à-vis.</p>
                <p>Puis des articles pour le quotidien régional où je travaillais comme pigiste puis journaliste. Ensuite une thèse, pour clore mon cursus universitaire, que j’ai eu le bonheur de publier. Enfin des recherches, des contrats… en français, en anglais…</p>
                <p>Arrivée à l’âge où on n’a plus à s’inquiéter de mettre un réveil le matin, je suis revenue à une écriture divertissante.</p>
                <p>Mon kiff, comme disent les nouvelles générations, ce sont les micro-fictions, les nouvelles. Mon imagination est fertile, mon écriture rapide, visuelle, concentrée. Mais je m’essaie également aux romans : thrillers, polars, avec toujours un peu d’humour pour alléger ces mondes de brutes. Tous les styles, tous les genres sont bons à explorer. Tant qu’il y a du plaisir à écrire.</p>
                <p>Partager mes écrits était une évidence, mais publier un ouvrage n’était pas ma priorité. D’où ce blog d’auteur.</p>
                <p>Je vais vous proposer régulièrement des textes — de toutes longueurs — sortis de mon imagination ou créés à partir d’exercices d’écriture que vous pourrez, si cela vous amuse, tester à votre tour.</p>
                <p>Et, au fil des mois, j’espère également accueillir des amis autrices et auteurs et vous faire découvrir leur style, leur imaginaire et leurs publications.</p>
                <p>Voyagez dans les rubriques, laissez-vous porter par les textes. Bonne lecture !</p>
                <p class="author-signature"><?= e($site['author']) ?></p>
            </div>
            <a class="text-link" href="<?= e(url('index.php#ecrits')) ?>">Explorer les écrits <span aria-hidden="true">→</span></a>
        </article>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

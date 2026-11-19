<?php
require __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/contenus.php';
$currentPage = 'index.php';
$now = horloge_publication($site);
$monthlyPages = pages_mensuelles(textes_publics($site, $now));
$monthlyPage = reset($monthlyPages);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil — <?= e($site['name']) ?></title>
    <meta name="description" content="Micro-fictions, nouvelles et romans : entrez dans les imaginaires de L . Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/home.css') . '?v=' . filemtime(__DIR__ . '/../css/home.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ================================ HERO ================================ -->
        <section class="home-hero" aria-labelledby="titre-accueil">
            <img class="hero-image" src="<?= e(url('img/ambiance/bureau-printemps.jpg')) ?>" width="1800" height="600" alt="" fetchpriority="high">
            <div class="container hero-content">
                <p class="eyebrow">Des histoires à lire au fil des saisons</p>
                <h1 id="titre-accueil">Des histoires brèves aux romans,<br>un espace pour lire, rêver<br>et prendre le temps.</h1>
            </div>
        </section>

        <!-- ===================== PRÉSENTATION ET RENDEZ-VOUS ===================== -->
        <div class="container home-editorial section-space">
            <section class="home-biography" aria-labelledby="titre-parcours">
                <h2 id="titre-parcours">Une envie d’écrire</h2>
                <p>Depuis aussi longtemps que je me souvienne, j’écris…</p>
                <div class="biography-copy">
                    <p>Dès le plus jeune âge sur un cahier de « travaux pratiques », comme celui des récitations de mon école primaire : le texte sur une page et l’illustration en vis-à-vis.</p>
                <p>Puis des articles pour le quotidien régional où je travaillais comme pigiste puis journaliste. Ensuite une thèse, pour clore mon cursus universitaire, que j’ai eu le bonheur de publier. Enfin des recherches, des contrats… en français, en anglais…</p>
                <p>Arrivée à l’âge où on n’a plus à s’inquiéter de mettre un réveil le matin, je suis revenue à une écriture divertissante.</p>
                <p>Mon kiff, comme disent les nouvelles générations, ce sont les micro-fictions, les nouvelles. Mon imagination est fertile, mon écriture rapide, visuelle, concentrée. Mais je m’essaie également aux romans : thrillers, polars, avec toujours un peu d’humour pour alléger ces mondes de brutes. Tous les styles, tous les genres sont bons à explorer. Tant qu’il y a du plaisir à écrire.</p>
                <p>Partager mes écrits était une évidence, mais publier un ouvrage n’était pas ma priorité. D’où ce blog d’auteur.</p>
                <p>Je vais vous proposer régulièrement des textes — de toutes longueurs — sortis de mon imagination ou créés à partir d’exercices d’écriture que vous pourrez, si cela vous amuse, tester à votre tour.</p>
                <p>Et, au fil des mois, j’espère également accueillir des plumes amies et vous faire découvrir leur style, leur imaginaire et leurs publications.</p>
                <p>Bonne lecture !</p>
                <p class="author-signature"><?= e($site['author']) ?></p>
                </div>
            </section>
            <div class="home-readings">
                <!-- ========================== DE MOI EN MOIS ========================== -->
                <section class="home-month" aria-labelledby="titre-mois">
                    <h2 id="titre-mois">De moi en mois</h2>
                    <?php if (calendrier_avent_en_cours($site, $now)): ?>
                    <p class="month-date">Décembre <?= $now->format('Y') ?></p>
                    <div class="month-visual">
                        <img src="<?= e(url('img/ambiance/lanterne-avent.jpg')) ?>" width="1200" height="800" alt="Une lanterne allumée et des livres pour accompagner les histoires de Noël" loading="lazy">
                        <h3>Des histoires pour attendre Noël</h3>
                    </div>
                    <div class="month-introduction">
                        <p><em>Un calendrier de l’Avent à déguster… sans prendre un gramme !</em></p>
                        <p>Le principe : un jour une histoire, ou partie d’histoire. Certaines sont drôles, satiriques, tendres, fantastiques, émouvantes ; d’autres chargées de cet Esprit de Noël qu’on nous vante tant.</p>
                    </div>
                    <a class="text-link" href="<?= e(url('pages/ephemere.php')) ?>">Découvrir le rendez-vous <span aria-hidden="true">→</span></a>
                    <?php elseif ($monthlyPage): ?>
                        <p class="month-date"><?= e($monthlyPage['titre']) ?></p>
                        <a class="text-link" href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($monthlyPage['id']))) ?>">Lire la page du mois <span aria-hidden="true">→</span></a>
                    <?php else: ?>
                        <a class="text-link" href="<?= e(url('pages/ephemere.php')) ?>">Découvrir les rendez-vous <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                </section>

                <!-- ======================== LES TROIS FORMATS DE LECTURE ======================== -->
                <section class="home-publications" id="ecrits" aria-labelledby="titre-ecrits">
                    <h2 id="titre-ecrits">À chaque envie, une histoire</h2>
                    <div class="publication-grid">
                        <article class="publication-card">
                            <h3><a href="<?= e(url('pages/microfictions.php')) ?>">Micro-fictions</a></h3>
                            <a class="publication-image" href="<?= e(url('pages/microfictions.php')) ?>" aria-label="Découvrir les micro-fictions">
                                <img src="<?= e(url('img/ambiance/bruyere-cevennes.jpg')) ?>" width="1200" height="540" alt="Bruyère en fleurs roses et violettes parmi les branches" loading="lazy">
                            </a>
                            <p>Un petit creux pour lire dans votre emploi du temps : des histoires brèves à parcourir en quelques minutes.</p>
                        </article>
                        <article class="publication-card">
                            <h3><a href="<?= e(url('pages/nouvelles.php')) ?>">Nouvelles</a></h3>
                            <a class="publication-image" href="<?= e(url('pages/nouvelles.php')) ?>" aria-label="Découvrir les nouvelles">
                                <img src="<?= e(url('img/ambiance/courge-mur-de-pierre.jpg')) ?>" width="1200" height="540" alt="Courge orange et fleurs jaunes devant un mur de pierres" loading="lazy">
                            </a>
                            <p>Un peu plus de temps pour lire : des récits plus longs, aventure, science-fiction, chapitres de vie, polar, etc.</p>
                        </article>
                        <article class="publication-card">
                            <h3><a href="<?= e(url('pages/romans.php')) ?>">Romans</a></h3>
                            <a class="publication-image" href="<?= e(url('pages/romans.php')) ?>" aria-label="Découvrir les romans">
                                <img src="<?= e(url('img/ambiance/bois-gouttes-cevennes.jpg')) ?>" width="1200" height="540" alt="Gouttes translucides suspendues sous une tranche de bois" loading="lazy">
                            </a>
                            <p>Déclinés sous forme feuilletonnée : thrillers, romans pas si noirs que ça,<br>et bien d’autres genres.</p>
                        </article>
                    </div>
                </section>
                <!-- ======================== UN MOT À PARTAGER ======================== -->
                <aside class="contact-panel home-contact" aria-labelledby="titre-contact-accueil">
                    <span class="botanical-mark" aria-hidden="true">❧</span>
                    <h2 id="titre-contact-accueil">Un mot à partager ?</h2>
                    <p>Une impression de lecture, une question, une invitation…</p>
                    <a class="button button-dark" href="<?= e(url('pages/contact.php?categorie=questions-diverses')) ?>">Formulaire de contact <span aria-hidden="true">→</span></a>
                </aside>
            </div>
        </div>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>
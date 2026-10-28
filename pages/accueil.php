<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'index.php';
require_once __DIR__ . '/../includes/contenus.php';
$guest = invite_du_mois($site);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accueil — <?= e($site['name']) ?></title>
    <meta name="description" content="Microfictions, nouvelles, feuilleton et romans : entrez dans les imaginaires de L. Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/home.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- ================================ HERO ================================ -->
        <section class="home-hero" aria-labelledby="titre-accueil">
            <img class="hero-image" src="<?= e(url('img/ambiance/bureau-automne.jpg')) ?>" width="1800" height="600" alt="" fetchpriority="high">
            <div class="container hero-content">
                <p class="eyebrow">Des histoires à lire au fil des saisons</p>
                <h1 id="titre-accueil">Écrire, partager,<br>continuer le voyage</h1>
                <p class="hero-intro">Des histoires minuscules aux romans,<br>un espace pour lire, rêver et prendre le temps.</p>
                <a class="button button-light" href="#ecrits">Découvrir mes écrits <span aria-hidden="true">→</span></a>
            </div>
        </section>

        <!-- ======================== APERÇUS DES RUBRIQUES ======================== -->
        <section class="container home-publications section-space" id="ecrits" aria-labelledby="titre-ecrits">
            <p class="eyebrow">Au gré de vos envies</p>
            <h2 id="titre-ecrits">À chaque envie, une histoire</h2>
            <div class="publication-grid">
                <article class="publication-card">
                    <img src="<?= e(url('img/ambiance/feuille-de-chene.jpg')) ?>" width="650" height="292" alt="Feuille de chêne parmi les branches de conifère" loading="lazy">
                    <div>
                        <p class="eyebrow">Microfictions</p>
                        <h3>Trois éléments,<br>une histoire</h3>
                        <p>Des contraintes, un élan d’imagination et des textes courts pour changer de regard.</p>
                        <a class="text-link" href="<?= e(url('pages/microfictions.php')) ?>">Découvrir <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <article class="publication-card">
                    <img src="<?= e(url('img/ambiance/riviere-brume.jpg')) ?>" width="650" height="292" alt="Brume matinale au-dessus de la rivière" loading="lazy">
                    <div>
                        <p class="eyebrow">Nouvelles</p>
                        <h3>Le temps<br>d’une échappée</h3>
                        <p>Des personnages, des rencontres et des chemins à explorer le temps d’une lecture.</p>
                        <a class="text-link" href="<?= e(url('pages/nouvelles.php')) ?>">Découvrir <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <article class="publication-card">
                    <img src="<?= e(url('img/ambiance/vallee-cevenole.jpg')) ?>" width="650" height="175" alt="Vue sur une vallée des Cévennes" loading="lazy">
                    <div>
                        <p class="eyebrow">Le feuilleton</p>
                        <h3>Le plaisir<br>de se retrouver</h3>
                        <p>Une histoire au long cours, à découvrir chapitre après chapitre.</p>
                        <a class="text-link" href="<?= e(url('pages/feuilleton.php')) ?>">Découvrir <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <article class="publication-card">
                    <img src="<?= e(url('img/ambiance/montagnes-hiver.jpg')) ?>" width="650" height="292" alt="Succession de montagnes bleutées dans les Cévennes" loading="lazy">
                    <div>
                        <p class="eyebrow">Romans</p>
                        <h3>Des mondes<br>à habiter</h3>
                        <p>Prendre le temps d’entrer dans un univers et de suivre ses personnages.</p>
                        <a class="text-link" href="<?= e(url('pages/romans.php')) ?>">Découvrir <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            </div>
        </section>

        <!-- ========================= RUBRIQUE ÉPHÉMÈRE ========================= -->
        <section class="season-section" aria-labelledby="titre-saison">
            <img class="season-image" src="<?= e(url('img/ambiance/lanterne-avent.jpg')) ?>" width="1200" height="800" alt="" loading="lazy">
            <div class="container season-layout">
                <div>
                    <p class="eyebrow">La rubrique éphémère</p>
                    <h2 id="titre-saison">Un jour, une fenêtre, une histoire</h2>
                    <p>Le calendrier de l’Avent littéraire : une mini-histoire par jour.</p>
                    <div class="season-bottom">
                        <div class="calendar-preview" aria-hidden="true">
                            <?php foreach ($site['advent_motifs'] as $index => $motif): ?>
                                <span class="calendar-tile">
                                    <span><?= sprintf('%02d', $index + 1) ?></span>
                                    <img src="<?= e(url($motif)) ?>" width="80" height="64" alt="" loading="lazy">
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <a class="text-link" href="<?= e(url('pages/ephemere.php')) ?>">Découvrir le rendez-vous <span aria-hidden="true">→</span></a>
                    </div>
                </div>
                <?php if ($guest !== null): ?>
                    <div class="guest-preview">
                        <p class="eyebrow">L’invité·e du mois</p>
                        <p><?= e($guest['titre']) ?></p>
                        <a class="text-link" href="<?= e(url('pages/ephemere.php#invite')) ?>">Rencontrer une autre plume <span aria-hidden="true">→</span></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ======================= GENÈSE ET CORRESPONDANCE ======================= -->
        <section class="container home-about section-space" aria-labelledby="titre-parcours">
            <img class="about-branch" src="<?= e(url('img/decorations/branche-automne.png')) ?>" width="400" height="600" alt="" loading="lazy">
            <div class="about-copy">
                <p class="eyebrow">À propos de l’autrice</p>
                <h2 id="titre-parcours">Une envie d’écrire</h2>
                <p>Depuis aussi longtemps que je me souvienne, j’écris…</p>
                <p>Dès le plus jeune âge sur un cahier de « travaux pratiques », puis des articles, une thèse, des recherches… Je suis revenue à une écriture divertissante, pour le plaisir d’imaginer et de partager.</p>
                <a class="button" href="<?= e(url('pages/a-propos.php')) ?>">En savoir plus sur mon parcours <span aria-hidden="true">→</span></a>
            </div>
            <aside class="contact-panel" aria-labelledby="titre-contact">
                <span class="botanical-mark" aria-hidden="true">❧</span>
                <h2 id="titre-contact">Un mot à partager ?</h2>
                <p>Une impression de lecture, une question, une invitation…</p>
                <a class="button button-dark" href="<?= e(url('pages/contact.php?categorie=questions-diverses')) ?>">Écrire à l’autrice <span aria-hidden="true">→</span></a>
            </aside>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'contact.php';
$recipient = filter_var($site['email'], FILTER_VALIDATE_EMAIL) ? $site['email'] : '';
$requestCategories = ['questions-diverses' => 'Questions diverses', 'microfiction' => 'Microfiction'];
$requestedCategory = is_string($_GET['categorie'] ?? null) ? $_GET['categorie'] : '';
$selectedCategory = isset($requestCategories[$requestedCategory]) ? $requestedCategory : 'questions-diverses';
$prefilledSubject = '';
$prefilledMessage = '';
$reference = null;
// Un identifiant public suffit : aucun texte ni élément libre n’est accepté depuis l’URL.
$referenceId = is_string($_GET['texte'] ?? null) ? $_GET['texte'] : '';
if ($selectedCategory === 'microfiction' && $referenceId !== '') {
    $publicTexts = require __DIR__ . '/../data/textes.php';
    $candidate = $publicTexts[$referenceId] ?? null;
    if ($candidate !== null && $candidate['categorie'] === 'microfictions' && count($candidate['elements'] ?? []) === 3) {
        $reference = $candidate;
        $prefilledSubject = mb_substr('Proposition : ' . $reference['titre'], 0, 120);
        $prefilledMessage = "Bonjour,\n\nJe vous propose une microfiction inspirée de « " . $reference['titre'] . " ».\n\nLes trois éléments :\n";
        foreach ($reference['elements'] as $index => $element) {
            $prefilledMessage .= ($index + 1) . '. ' . $element . "\n";
        }
        $prefilledMessage .= "\nMon texte :\n\n";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact — <?= e($site['name']) ?></title>
    <meta name="description" content="Retrouvez les informations pour entrer en contact avec L. Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/contact.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/contact.css') . '?v=' . filemtime(__DIR__ . '/../css/contact.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- =========================== CORRESPONDANCE =========================== -->
        <section class="page-intro container">
            <p class="eyebrow">D’une plume à l’autre</p>
            <h1>Un mot à partager ?</h1>
            <p class="intro-text">Une impression de lecture, une question, une invitation… Le plaisir d’écrire se prolonge dans les échanges.</p>
        </section>
        <section class="container collection-section" aria-labelledby="titre-correspondance">
            <div class="contact-layout">
                <div class="contact-introduction">
                    <h2 id="titre-correspondance">Écrire à l’autrice</h2>
                    <p>Remplissez ces quelques lignes : votre application mail s’ouvrira avec le message préparé. Vous pourrez le relire et l’envoyer vous-même.</p>
                    <p>Ce formulaire ne transmet ni ne conserve votre message sur le site.</p>
                    <?php if ($recipient !== ''): ?>
                        <p>Vous pouvez aussi écrire directement à<br><a href="mailto:<?= e($recipient) ?>"><?= e($recipient) ?></a>.</p>
                    <?php endif; ?>
                </div>

                <!-- ======================= MESSAGE À PRÉPARER ======================= -->
                <form class="contact-form" id="contact-form" action="mailto:<?= e($recipient) ?>" method="get" data-recipient="<?= e($recipient) ?>" data-site-name="<?= e($site['name']) ?>" aria-describedby="contact-help">
                    <div class="contact-fields">
                        <div>
                            <label for="contact-name">Votre nom <span>(obligatoire)</span></label>
                            <input id="contact-name" name="nom" autocomplete="name" maxlength="100" required>
                        </div>
                        <div>
                            <label for="contact-email">Votre adresse e-mail <span>(obligatoire)</span></label>
                            <input id="contact-email" name="email" type="email" autocomplete="email" maxlength="254" required>
                        </div>
                    </div>
                    <label for="contact-category">Catégorie de votre message</label>
                    <select id="contact-category" name="type_demande" required>
                        <?php foreach ($requestCategories as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $selectedCategory === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($reference !== null): ?>
                        <p class="contact-help">Les éléments de « <?= e($reference['titre']) ?> » sont repris ci-dessous. Complétez le message avec votre propre histoire.</p>
                    <?php endif; ?>
                    <label for="contact-subject">Objet de votre message <span>(obligatoire)</span></label>
                    <input id="contact-subject" name="objet" maxlength="120" value="<?= e($prefilledSubject) ?>" required>
                    <label for="contact-message">Votre message <span>(obligatoire)</span></label>
                    <textarea id="contact-message" name="message" rows="12" maxlength="3000" required><?= e($prefilledMessage) ?></textarea>
                    <p class="contact-help" id="contact-help">Une application mail doit être configurée sur votre appareil. L’envoi se fera depuis celle-ci, après votre validation.</p>
                    <button class="button button-dark" id="contact-submit" type="submit" disabled>Ouvrir ma messagerie <span aria-hidden="true">→</span></button>
                    <p class="contact-status" id="contact-status" role="status" aria-live="polite"></p>
                    <noscript><p>Pour préparer le message avec ce formulaire, activez JavaScript ou utilisez le lien e-mail ci-contre.</p></noscript>
                    <p class="contact-help contact-privacy">Consultez la <a href="<?= e(url('pages/obligatoires/confidentialite.php')) ?>">politique de confidentialité</a>.</p>
                </form>
            </div>
        </section>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
    <script src="<?= e(url('js/contact.js') . '?v=' . filemtime(__DIR__ . '/../js/contact.js')) ?>" defer></script>
</body>
</html>

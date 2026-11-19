<?php
require __DIR__ . '/../includes/bootstrap.php';
$currentPage = 'contact.php';
require_once __DIR__ . '/../includes/contenus.php';
$now = horloge_publication($site);
$isAdventContact = ($_GET['participation'] ?? '') === 'avent';
$contactPath = 'pages/contact.php' . ($isAdventContact ? '?participation=avent' : '');
$recipient = filter_var($site['email'], FILTER_VALIDATE_EMAIL) ? $site['email'] : '';
$requestCategories = $isAdventContact
    ? ['noel' => 'Histoire de Noël']
    : ['microfiction' => 'Micro-fiction', 'questions-diverses' => 'Autres'];
$requestedCategory = is_string($_GET['categorie'] ?? null) ? $_GET['categorie'] : '';
$selectedCategory = $isAdventContact ? 'noel' : (isset($requestCategories[$requestedCategory]) ? $requestedCategory : 'questions-diverses');
$prefilledSubject = '';
$prefilledMessage = '';
$reference = null;
if ($selectedCategory === 'noel') {
    $prefilledSubject = 'Mon histoire de Noël';
}
// Un identifiant public suffit : aucun texte ni élément libre n’est accepté depuis l’URL.
$referenceId = is_string($_GET['texte'] ?? null) ? $_GET['texte'] : '';
if ($selectedCategory === 'microfiction' && $referenceId !== '') {
    $publicTexts = require __DIR__ . '/../data/textes.php';
    $candidate = $publicTexts[$referenceId] ?? null;
    if ($candidate !== null && $candidate['categorie'] === 'microfictions' && (count($candidate['elements'] ?? []) === 3 || !empty($candidate['consigne']))) {
        $reference = $candidate;
        $prefilledSubject = mb_substr('Proposition : ' . $reference['titre'], 0, 120);
        $prefilledMessage = "Bonjour,\n\nJe vous propose une micro-fiction inspirée de « " . $reference['titre'] . " ».\n\n";
        if (!empty($reference['consigne'])) {
            $prefilledMessage .= "Consigne :\n" . $reference['consigne'] . "\n\n";
        }
        if (count($reference['elements'] ?? []) === 3) {
            $prefilledMessage .= "Les trois éléments :\n";
            foreach ($reference['elements'] as $index => $element) {
                $prefilledMessage .= ($index + 1) . '. ' . $element . "\n";
            }
        }
        $prefilledMessage .= "\nMon texte :\n\n";
    }
}
require __DIR__ . '/../includes/contact-envoi.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact — <?= e($site['name']) ?></title>
    <meta name="description" content="Retrouvez les informations pour entrer en contact avec L . Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/contact.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/contact.css') . '?v=' . filemtime(__DIR__ . '/../css/contact.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- =========================== CORRESPONDANCE =========================== -->
        <section class="page-intro container">
            <h1>Un mot à partager ?</h1>
            <p class="intro-text">Une impression de lecture, une question, une proposition…<br>Le plaisir d’écrire et de lire se prolonge dans les échanges.</p>
        </section>
        <section class="container collection-section" aria-labelledby="titre-correspondance">
            <?php if ($contactSent !== ''): ?>
                <div class="contact-confirmation" id="contact-confirmation" role="status">
                    <h2>Message transmis au service de messagerie</h2>
                    <p>Merci pour votre message.</p>
                    <?php if ($contactSent === 'receipt_sent'): ?>
                        <p>Un courriel de confirmation a été envoyé à l’adresse indiquée.</p>
                    <?php elseif ($contactSent === 'message_only'): ?>
                        <p>L’accusé de réception par e-mail n’a pas pu être envoyé.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="contact-layout">
                <div class="contact-introduction">
                    <h2 id="titre-correspondance">Écrire à L . Yann</h2>
                    <p>Envoyez votre message directement à L . Yann depuis cette page.</p>

                    <?php if ($recipient !== ''): ?>
                        <p>Vous pouvez aussi écrire directement à<br><a href="mailto:<?= e($recipient) ?>"><?= e($recipient) ?></a>.</p>
                    <?php endif; ?>
                </div>

                <!-- ======================= ENVOI DU MESSAGE ======================= -->
                <form class="contact-form" id="contact-form" action="<?= e(url($contactPath)) ?>#contact-result" method="post" aria-describedby="contact-help">
                    <div id="contact-result">
                        <?php if ($contactError !== ''): ?>
                            <p class="contact-status" role="alert"><?= e($contactError) ?></p>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="jeton" value="<?= e($contactToken) ?>">
                    <div class="contact-fields">
                        <div>
                            <label for="contact-name">Votre nom <span>(obligatoire)</span></label>
                            <input id="contact-name" name="nom" autocomplete="name" maxlength="100" value="<?= e($contactName) ?>" required>
                        </div>
                        <div>
                            <label for="contact-email">Votre adresse e-mail <span>(obligatoire)</span></label>
                            <input id="contact-email" name="email" type="email" autocomplete="email" maxlength="254" value="<?= e($contactEmail) ?>" required>
                        </div>
                    </div>
                    <?php if ($isAdventContact): ?>
                        <input type="hidden" name="type_demande" value="noel">
                    <?php else: ?>
                        <label for="contact-category">Catégorie de votre message</label>
                        <select id="contact-category" name="type_demande" required>
                            <?php foreach ($requestCategories as $key => $label): ?>
                                <option value="<?= e($key) ?>"<?= $selectedCategory === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <noscript>
                            <p>Choisissez le formulaire adapté à votre message :
                                <?php foreach ($requestCategories as $key => $label): ?>
                                    <a href="<?= e(url('pages/contact.php') . '?categorie=' . $key) ?>"><?= e($label) ?></a>
                                <?php endforeach; ?>
                            </p>
                        </noscript>
                    <?php endif; ?>
                    <?php if ($reference !== null): ?>
                        <p class="contact-help">Les éléments de « <?= e($reference['titre']) ?> » sont repris ci-dessous. Complétez le message avec votre propre histoire.</p>
                    <?php endif; ?>
                    <?php if ($isAdventContact): ?>
                        <div class="contact-noel-rules">
                            <img class="contact-noel-motif" src="<?= e(url($site['advent_motifs'][3])) ?>" width="72" height="72" alt="">
                            <p>Vous avez écrit votre propre histoire de Noël.</p><p>Pourquoi ne la partageriez-vous pas avec nous ?</p>
                            <p>350 mots maximum</p>
                            <p>Date limite d’envoi : le <?= (int) $site['ephemere']['calendrier']['limite_envoi'] ?> décembre</p>
                            <p id="contact-word-count" role="status" aria-live="polite"></p>
                        </div>
                    <?php endif; ?>
                    <label for="contact-subject">Objet de votre message <span>(obligatoire)</span></label>
                    <input id="contact-subject" name="objet" maxlength="120" value="<?= e($prefilledSubject) ?>" required>
                    <label for="contact-message">Votre message <span>(obligatoire)</span></label>
                    <textarea id="contact-message" name="message" rows="12" maxlength="10000"<?= $isAdventContact ? ' aria-describedby="contact-word-count"' : '' ?> required><?= e($prefilledMessage) ?></textarea>
                    <label class="contact-consent" for="contact-publication"<?= $selectedCategory === 'questions-diverses' ? ' hidden' : '' ?>>
                        <input id="contact-publication" name="autorisation_publication" type="checkbox"<?= in_array($selectedCategory, ['microfiction', 'noel'], true) ? ' required' : ' disabled' ?>>
                        <span><?= e($publicationTerms) ?></span>
                    </label>
                    <p class="contact-help" id="contact-help">Votre nom et votre adresse servent à traiter votre message. Pour une proposition, l’autorisation accompagne le texte transmis à L . Yann.</p>
                    <?php if (!$contactReady): ?>
                        <p class="contact-help">L’envoi depuis le formulaire sera disponible prochainement. Pour une question, vous pouvez utiliser l’adresse indiquée ci-dessus.</p>
                    <?php endif; ?>
                    <button class="button button-dark" id="contact-submit" type="submit"<?= $contactReady ? '' : ' disabled' ?>>Envoyer mon message <span aria-hidden="true">→</span></button>
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

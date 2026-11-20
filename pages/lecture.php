<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$isAdminPreview = $isAdminPreview ?? false;
$currentPage = 'lecture.php';
require_once __DIR__ . '/../includes/contenus.php';
header($isAdminPreview ? 'Cache-Control: no-store, private' : 'Cache-Control: no-store');
$textes = tous_textes();
$slug = is_string($_GET['texte'] ?? null) ? $_GET['texte'] : '';
if (!isset($textes[$slug]) || (!$isAdminPreview && $textes[$slug]['statut'] !== 'publie')) {
    require __DIR__ . '/obligatoires/404.php';
    exit;
}
$text = $textes[$slug];
$isMicrofiction = $text['categorie'] === 'microfictions';
$hasConstraints = !empty($text['consigne']) || ($isMicrofiction && count($text['elements'] ?? []) === 3);
$isAbbey = $slug === 'microfiction-taupe-abbaye';
$isLifeStory = $slug === 'microfiction-vie-est-mechante';
$isChristmasMail = $slug === 'avent-02';
$readingFont = isset(polices_lecture()[$text['police'] ?? 'defaut']) ? ($text['police'] ?? 'defaut') : 'defaut';
if (!$isAdminPreview && !texte_visible($text, $site)) {
    if ($text['categorie'] === 'avent') {
        require __DIR__ . '/indisponible.php';
    } else {
        require __DIR__ . '/obligatoires/404.php';
    }
    exit;
}
$categoryLabels = ['microfictions' => 'Micro-fictions', 'nouvelles' => 'Nouvelles', 'romans' => 'Romans', 'avent' => 'Histoires de l’Avent', 'invite' => 'Invité·e du mois', 'ephemere' => 'De moi en mois'];
$categoryFiles = ['microfictions' => 'pages/microfictions.php', 'nouvelles' => 'pages/nouvelles.php', 'romans' => 'pages/romans.php', 'avent' => 'pages/ephemere.php', 'invite' => 'pages/ephemere.php', 'ephemere' => 'pages/ephemere.php'];
$currentPage = basename($categoryFiles[$text['categorie']]);
$publicForArchives = array_filter($textes, static fn(array $candidate): bool => texte_visible($candidate, $site));
$isArchived = isset(classement_lectures($publicForArchives, $site['nouvelles_visibles'])['archives'][$slug]);
$isMonthlyPage = $text['categorie'] === 'ephemere' && !empty($text['mois_invite']);
$returnPath = $isArchived ? 'pages/lire-ou-relire.php' : $categoryFiles[$text['categorie']];
$returnLabel = $isArchived ? 'Retour à Lire ou relire' : 'Retour à la rubrique';
if ($isAdminPreview) {
    $returnPath = 'admin/index.php';
    $returnLabel = 'Retour à Mes textes';
} elseif ($isMonthlyPage) {
    $returnPath = 'index.php';
    $returnLabel = 'Retour à l’accueil';
}
// Conserver les séparations Word sans leur donner la hauteur d’une ligne de texte.
$readingHtml = preg_replace_callback('/<p\b[^>]*>(.*?)<\/p>/is', static function (array $paragraph): string {
    $plain = html_entity_decode(strip_tags($paragraph[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_match('/^[\s\x{00A0}\x{200B}]*$/u', $plain)
        ? '<p class="reading-gap" aria-hidden="true"></p>'
        : $paragraph[0];
}, nettoyer_contenu($text['contenu']));
$imageSlot = '<p>[[illustration]]</p>';
$hasImageSlot = $isMonthlyPage && str_contains($readingHtml, $imageSlot);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($text['titre']) ?> — <?= e($site['name']) ?></title>
    <meta name="description" content="<?= e($text['introduction'] ?: $text['titre']) ?>">
    <meta name="robots" content="<?= $site['production'] && !$isAdminPreview ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if (!$isAdminPreview && $site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/lecture.php?texte=' . rawurlencode($slug)) ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('img/identite/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css') . '?v=' . filemtime(__DIR__ . '/../css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <main id="contenu">
        <!-- =========================== TEXTE ET NAVIGATION =========================== -->
        <article class="reading-layout">
            <?php if ($isAdminPreview): ?>
                <p class="notice">Aperçu privé. <a href="<?= e(url('admin/editer.php?id=' . rawurlencode($slug))) ?>">Modifier le texte</a></p>
            <?php endif; ?>
            <header class="reading-heading<?= $isMicrofiction ? ' reading-heading--microfiction' : ($text['categorie'] === 'avent' ? ' reading-heading--avent' : '') ?>">
                <p class="eyebrow"><?= e($categoryLabels[$text['categorie']]) ?></p>
                <?php if ($isChristmasMail): ?>
                    <h1><?php [$firstLine, $lastLine] = explode(' DU ', $text['titre'], 2) + [1 => '']; ?><?= e($firstLine) ?><?php if ($lastLine !== ''): ?><br> DU <?= e($lastLine) ?><?php endif; ?></h1>
                <?php else: ?>
                    <h1><?= e($text['titre']) ?></h1>
                <?php endif; ?>
                <?php if ($text['introduction'] !== '' && !$isAbbey && !$isLifeStory): ?>
                    <?php if (in_array($slug, ['nouvelle-improbable-proces-lady-m', 'nouvelle-heritage-invisible'], true)): ?>
                        <p class="intro-text"><i><?= e($text['introduction']) ?></i></p>
                    <?php else: ?>
                        <p class="intro-text"><?= e($text['introduction']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </header>
            <?php if ($hasConstraints): ?>
                <?php require __DIR__ . '/../includes/lecture/contraintes-lecture.php'; ?>
            <?php endif; ?>
            <!-- ========================= ILLUSTRATION DU RÉCIT ========================= -->
            <?php
            ob_start();
            require __DIR__ . '/../includes/lecture/illustration-lecture.php';
            $illustration = ob_get_clean();
            if ($hasImageSlot) {
                $slotPosition = strpos($readingHtml, $imageSlot);
                $readingHtml = substr_replace($readingHtml, $illustration, $slotPosition, strlen($imageSlot));
                $readingHtml = str_replace($imageSlot, '', $readingHtml);
            } else {
                echo $illustration;
            }
            ?>
            <!-- ======================== CONFORT DE LECTURE ======================== -->
            <div class="reading-controls" id="reading-controls" role="group" aria-label="Taille du texte">
                <button class="reading-size-button" id="reading-smaller" type="button" title="Réduire la taille du texte" aria-label="Réduire la taille du texte" aria-controls="texte-lecture">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6.5"/><path d="m15 15 6 6M7 10h6"/></svg>
                </button>
                <button class="reading-size-button" id="reading-larger" type="button" title="Agrandir la taille du texte" aria-label="Agrandir la taille du texte" aria-controls="texte-lecture">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6.5"/><path d="m15 15 6 6M7 10h6M10 7v6"/></svg>
                </button>
                <span class="reading-size-status" id="reading-size-value" role="status" aria-live="polite">Taille du texte : 100 %</span>
            </div>
            <div id="texte-lecture" class="prose reading-text<?= $isMonthlyPage ? ' reading-text--monthly' : '' ?><?= $text['categorie'] === 'avent' ? ' reading-text--avent' : '' ?> reading-font-<?= e($readingFont) ?><?= $site['limiter_copie'] ? ' protected-text' : '' ?>">
                <?php // HTML filtré à l’import et à l’enregistrement, puis défensivement à la lecture. ?>
                <?= $readingHtml ?>
            </div>
            <?php
            $publicationDate = date_publication_texte($text, $site) ?? new DateTimeImmutable($text['modifie_le']);
            $publicationYear = $publicationDate->setTimezone(new DateTimeZone('Europe/Paris'))->format('Y');
            ?>
            <?php if (!empty($text['participation_noel'])): ?>
                <p><a class="button button-dark" href="<?= e(url('pages/contact.php?participation=avent')) ?>">Envoyer mon histoire <span aria-hidden="true">→</span></a></p>
            <?php endif; ?>
            <p class="reading-rights">© <?= e($site['author']) ?> <?= e($publicationYear) ?> – Tous droits réservés.</p>
            <?php if (!$isAdminPreview): ?>
                <?php require __DIR__ . '/../includes/lecture/participation-microfiction.php'; ?>
            <?php endif; ?>
            <a class="text-link" href="<?= e(url($returnPath)) ?>"><?= e($returnLabel) ?> <span aria-hidden="true">→</span></a>
        </article>
    </main>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
    <script src="<?= e(url('js/lecture.js') . '?v=' . filemtime(__DIR__ . '/../js/lecture.js')) ?>" defer></script>
</body>
</html>

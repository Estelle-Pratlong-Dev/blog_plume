<?php
/* Variables attendues : $textes et $category, chargés explicitement par la page.
 * Option $afficherImages : false pour une liste sans illustration (true par défaut).
 * Option $cartesGrille : cartes verticales pour les pages Nouvelles et Micro-fictions.
 * Responsabilité commune : lister les lectures disponibles dans une rubrique.
 */
$selection = array_filter($textes, static fn(array $text): bool => $text['categorie'] === $category);
$horizontalCards = $category !== 'avent';
if ($horizontalCards) {
    // Une correction ne doit pas faire remonter une ancienne publication.
    $selection = trier_publications($selection);
}
if (($cartesGrille ?? false) && $selection !== []) {
    require __DIR__ . '/cartes-lectures.php';
    return;
}
?>
<?php if ($selection === []): ?>
    <div class="empty-state">
        <p class="eyebrow">Les textes prennent place</p>
        <h2>Les premières lectures arrivent bientôt</h2>
        <p>Cette rubrique est prête à accueillir les textes de L . Yann.</p>
        <a class="text-link" href="<?= e(url('index.php#ecrits')) ?>">Explorer les autres rubriques <span aria-hidden="true">→</span></a>
    </div>
<?php else: ?>
    <div class="reading-list<?= $horizontalCards ? ' reading-list--horizontal' : '' ?>">
        <?php foreach ($selection as $slug => $text): ?>
            <?php
            $isMicrofiction = $text['categorie'] === 'microfictions';
            $displayImage = ($afficherImages ?? true) && !empty($text['image']['fichier'])
                && (!$isMicrofiction || ($text['image']['origine'] ?? '') !== 'imagegen');
            ?>
            <article class="reading-card<?= $horizontalCards ? ' reading-card--horizontal' : '' ?><?= !$displayImage ? ' reading-card--without-image' : '' ?><?= $isMicrofiction ? ' reading-card--microfiction' : '' ?>">
                <?php if ($displayImage): ?>
                    <?php if ($isMicrofiction): ?>
                        <figure class="microfiction-visual">
                            <a class="reading-card-illustration" href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($slug))) ?>">
                                <img src="<?= e(url('pages/image-texte.php?texte=' . rawurlencode($slug) . '&v=' . (int) $text['version'])) ?>" width="<?= (int) $text['image']['largeur'] ?>" height="<?= (int) $text['image']['hauteur'] ?>" alt="<?= e($text['image']['description']) ?>" loading="lazy">
                            </a>
                            <figcaption class="image-credit"><?= nl2br(e($text['image']['credit'] ?? '')) ?></figcaption>
                        </figure>
                    <?php else: ?>
                        <a class="reading-card-illustration" href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($slug))) ?>">
                            <img src="<?= e(url('pages/image-texte.php?texte=' . rawurlencode($slug) . '&v=' . (int) $text['version'])) ?>" width="<?= (int) $text['image']['largeur'] ?>" height="<?= (int) $text['image']['hauteur'] ?>" alt="<?= e($text['image']['description']) ?>" loading="lazy">
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="reading-card-content">
                    <?php if ($horizontalCards && !empty($text['publie_le'] ?? $text['modifie_le'] ?? null)): ?>
                        <?php $publishedAt = new DateTimeImmutable($text['publie_le'] ?? $text['modifie_le']); ?>
                        <p class="reading-card-date">Publié le <time datetime="<?= e($publishedAt->format('Y-m-d')) ?>"><?= e($publishedAt->format('d/m/Y')) ?></time></p>
                    <?php endif; ?>
                    <h2><a href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($slug))) ?>"><?= e($text['titre']) ?></a></h2>
                    <p><?= e($text['introduction']) ?></p>
                    <a class="text-link" href="<?= e(url('pages/lecture.php?texte=' . rawurlencode($slug))) ?>">Lire le texte <span aria-hidden="true">→</span></a>
                    <?php require __DIR__ . '/../lecture/participation-microfiction.php'; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

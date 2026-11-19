<?php if (!empty($text['image']['fichier']) && ($text['categorie'] !== 'microfictions' || ($text['image']['origine'] ?? '') !== 'imagegen')): ?>
    <figure class="story-illustration<?= ($text['image']['origine'] ?? '') === 'document' ? ' story-illustration--reference' : '' ?>">
        <img src="<?= e(url(($isAdminPreview ? 'admin/image.php?texte=' : 'pages/image-texte.php?texte=') . rawurlencode($text['id']) . '&v=' . (int) $text['version'])) ?>" width="<?= (int) $text['image']['largeur'] ?>" height="<?= (int) $text['image']['hauteur'] ?>" alt="<?= e($text['image']['description']) ?>" fetchpriority="high">
        <?php if ($isAbbey && $text['introduction'] !== ''): ?>
            <figcaption><?= e($text['introduction']) ?><?php if (!empty($text['image']['credit'])): ?><br><span class="image-credit"><?= nl2br(e($text['image']['credit'])) ?></span><?php endif; ?></figcaption>
        <?php elseif (!empty($text['image']['credit'])): ?>
            <figcaption class="image-credit"><?= nl2br(e($text['image']['credit'])) ?></figcaption>
        <?php endif; ?>
    </figure>
<?php endif; ?>

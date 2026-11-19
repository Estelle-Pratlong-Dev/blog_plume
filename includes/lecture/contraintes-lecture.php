<div class="reading-constraints">
    <?php if (!empty($text['consigne'])): ?>
        <p><i>Contrainte : <?= nl2br(e($text['consigne'])) ?></i></p>
    <?php endif; ?>
    <?php if (count($text['elements'] ?? []) === 3): ?>
        <p>Les mots imposés : <?= e(implode(', ', $text['elements'])) ?></p>
    <?php endif; ?>
</div>

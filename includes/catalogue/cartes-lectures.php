<?php
/* Variable attendue : $selection, publications triées de la rubrique. */
?>
<div class="lecture-grid">
    <?php foreach ($selection as $slug => $text): ?>
        <?php $readingUrl = url('pages/lecture.php?texte=' . rawurlencode($slug)); ?>
        <article class="lecture-tile">
            <div class="lecture-tile-content">
                <h2><?= e($text['titre']) ?></h2>
                <a class="text-link" href="<?= e($readingUrl) ?>" aria-label="<?= e('Lire le texte : ' . $text['titre']) ?>">Lire le texte <span aria-hidden="true">→</span></a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php
/* Variable attendue : $text, publication publique avec id et trois éléments.
 * Bloc commun à la liste des microfictions et à leur page de lecture.
 */
?>
<?php if ($text['categorie'] === 'microfictions' && count($text['elements'] ?? []) === 3): ?>
    <div class="microfiction-participation">
        <p class="eyebrow">Les trois éléments d’inspiration</p>
        <ul>
            <?php foreach ($text['elements'] as $element): ?>
                <li><?= e($element) ?></li>
            <?php endforeach; ?>
        </ul>
        <p>Et vous, quelle histoire auriez-vous imaginée ?</p>
        <a class="button" href="<?= e(url('pages/contact.php?categorie=microfiction&texte=' . rawurlencode($text['id']))) ?>">Soumettre une microfiction <span aria-hidden="true">→</span></a>
    </div>
<?php endif; ?>

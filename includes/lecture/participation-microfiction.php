<?php
/* Variable attendue : $text, publication avec trois éléments et/ou une consigne.
 * Bloc commun à la liste des micro-fictions et à leur page de lecture.
 */
?>
<?php if ($text['categorie'] === 'microfictions' && (count($text['elements'] ?? []) === 3 || !empty($text['consigne']))): ?>
    <div class="microfiction-participation">
        <?php if (empty($hasConstraints)): ?>
            <p class="eyebrow">Contraintes du jour :</p>
            <?php if (!empty($text['consigne'])): ?>
                <p><?= nl2br(e($text['consigne'])) ?></p>
            <?php endif; ?>
            <?php if (count($text['elements'] ?? []) === 3): ?>
                <p>Les mots imposés</p>
                <ul>
                    <?php foreach ($text['elements'] as $element): ?>
                        <li><?= e($element) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
        <p>Et vous, quelle histoire auriez-vous imaginée ?</p>
        <a class="button" href="<?= e(url('pages/contact.php?categorie=microfiction&texte=' . rawurlencode($text['id']))) ?>">Soumettre une micro-fiction <span aria-hidden="true">→</span></a>
    </div>
<?php endif; ?>
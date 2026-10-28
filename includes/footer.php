<?php
/* Variables attendues : $site, chargé par bootstrap.php.
 * Trois zones communes : identité, rubriques et liens pratiques/légaux.
 */
?>
<!-- =========================== PIED DE PAGE =========================== -->
<footer class="site-footer">
    <div class="container footer-top">
        <div class="footer-identity">
            <a class="signature" href="<?= e(url('index.php')) ?>">
                <span class="signature-name"><?= e($site['name']) ?></span>
                <span class="signature-subtitle"><?= e($site['author']) ?> · <?= e($site['subtitle']) ?></span>
            </a>
            <p>Lire. Imaginer. Partager.</p>
        </div>
        <nav class="footer-writings" aria-label="Les rubriques du blog">
            <p class="eyebrow">Au fil des textes</p>
            <ul>
                <li><a href="<?= e(url('index.php')) ?>">Accueil</a></li>
                <li><a href="<?= e(url('pages/ephemere.php')) ?>">Rubrique éphémère</a></li>
                <li><a href="<?= e(url('pages/microfictions.php')) ?>">Microfictions</a></li>
                <li><a href="<?= e(url('pages/nouvelles.php')) ?>">Nouvelles</a></li>
                <li><a href="<?= e(url('pages/feuilleton.php')) ?>">Le feuilleton</a></li>
                <li><a href="<?= e(url('pages/romans.php')) ?>">Romans</a></li>
            </ul>
        </nav>
        <nav class="footer-information" aria-label="Contact et informations légales">
            <p class="eyebrow">Pour prolonger la lecture</p>
            <ul>
                <li><a href="<?= e(url('pages/a-propos.php')) ?>">Le parcours de l’autrice</a></li>
                <li><a href="<?= e(url('pages/contact.php')) ?>">Contact</a></li>
                <li><a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">Mentions légales</a></li>
                <li><a href="<?= e(url('pages/obligatoires/confidentialite.php')) ?>">Politique de confidentialité</a></li>
            </ul>
        </nav>
    </div>
    <div class="container footer-bottom">
        <p>© <?= date('Y') ?> <?= e($site['author']) ?> — Tous droits réservés.</p>
        <p>
            <?= e($site['creator']['label']) ?> :
            <?php if ($site['creator']['url'] !== ''): ?>
                <a href="<?= e($site['creator']['url']) ?>"><?= e($site['creator']['name']) ?></a>
            <?php else: ?>
                <?= e($site['creator']['name']) ?>
            <?php endif; ?>
        </p>
    </div>
</footer>

<?php
/* Variables attendues : $site, chargé par bootstrap.php.
 * Identité et liens de contact et informations légales.
 */
?>
<!-- =========================== PIED DE PAGE =========================== -->
<footer class="site-footer">
    <div class="container footer-top">
        <div class="footer-identity">
            <a class="signature" href="<?= e(url('index.php')) ?>">
                <span class="signature-name"><?= e($site['name']) ?></span>
                <span class="signature-author"><?= e($site['author']) ?></span>
            </a>
            <p>Lire. Imaginer. Partager.</p>
        </div>
        <nav class="footer-information" aria-label="Contact et informations légales">
            <ul>
                <li><a href="<?= e(url('pages/contact.php')) ?>">Contact</a></li>
                <li><a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">Mentions légales</a></li>
                <li><a href="<?= e(url('pages/obligatoires/confidentialite.php')) ?>">Politique de confidentialité</a></li>
            </ul>
        </nav>
    </div>
    <div class="container footer-bottom">
        <p>© <?= (int) $site['copyright_year'] ?> <?= e($site['author']) ?> – Tous droits réservés.</p>
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

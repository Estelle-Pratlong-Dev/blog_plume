<?php
/* Variables attendues : $site (bootstrap), $currentPage (page appelante).
 * L’en-tête ne gère ni métadonnées ni ressources spécifiques aux pages.
 */
$navigation = [
    'index.php' => 'Accueil',
    'ephemere.php' => 'Rubrique éphémère',
    'microfictions.php' => 'Microfictions',
    'nouvelles.php' => 'Nouvelles',
    'feuilleton.php' => 'Le feuilleton',
    'romans.php' => 'Romans',
    'contact.php' => 'Contact',
];
?>
<!-- ====================== EN-TÊTE ET NAVIGATION ====================== -->
<a class="skip-link" href="#contenu">Aller au contenu</a>
<header class="site-header">
    <div class="header-inner container">
        <a class="signature" href="<?= e(url('index.php')) ?>" aria-label="<?= e($site['name']) ?> — accueil">
            <span class="signature-name"><?= e($site['name']) ?></span>
            <span class="signature-subtitle"><?= e($site['author']) ?> · <?= e($site['subtitle']) ?></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navigation" hidden>Menu <span aria-hidden="true">☰</span></button>
        <nav class="main-navigation" id="navigation" aria-label="Navigation principale">
            <ul>
                <?php foreach ($navigation as $file => $label): ?>
                    <li><a href="<?= e(url($file === 'index.php' ? $file : 'pages/' . $file)) ?>"<?= $currentPage === $file ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endforeach; ?>
                <?php if (!empty($adminVisible)): ?>
                    <li><a class="admin-shortcut" href="<?= e(url('admin/index.php')) ?>">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

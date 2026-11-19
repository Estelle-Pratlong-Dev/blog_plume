<?php
require __DIR__ . '/_securite.php';
exiger_admin();
$adventView = ($_GET['rubrique'] ?? '') === 'avent';
$texts = tous_textes();
if ($adventView) {
    $currentYear = (int) (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('Y');
    $editions = [$currentYear, $currentYear + 1];
    foreach ($texts as $text) {
        if ($text['categorie'] === 'avent') {
            $editions[] = annee_avent_texte($text, $site);
        }
    }
    $editions = array_unique($editions);
    sort($editions, SORT_NUMERIC);
    $requestedYear = filter_var($_GET['annee'] ?? null, FILTER_VALIDATE_INT);
    $year = in_array($requestedYear, $editions, true) ? $requestedYear : $currentYear;
    $texts = array_filter($texts, static fn(array $text): bool => $text['categorie'] === 'avent' && annee_avent_texte($text, $site) === $year);
} else {
    $texts = array_filter($texts, static fn(array $text): bool => $text['categorie'] !== 'avent');
}
$visibleTexts = array_filter($texts, static fn(array $text): bool => texte_visible($text, $site));
$archivedTexts = classement_lectures($visibleTexts, $site['nouvelles_visibles'])['archives'];
$adminTitle = $adventView ? 'Calendrier de l’Avent' : 'Mes textes';
$authenticated = true;
require __DIR__ . '/_entete.php';
?>
        <!-- ============================== TABLEAU DE BORD ============================== -->
        <?php if ($adventView): ?>
            <p class="notice">Les histoires de l’Avent s’ouvrent en décembre, à minuit, heure de Paris. Un jour futur reste inaccessible au public, même avec un lien direct.</p>
            <nav class="admin-year-tabs" aria-label="Éditions du calendrier de l’Avent">
                <?php foreach ($editions as $edition): ?>
                    <a href="<?= e(url('admin/index.php?rubrique=avent&annee=' . $edition)) ?>"<?= $edition === $year ? ' aria-current="page"' : '' ?>><?= $edition ?></a>
                <?php endforeach; ?>
            </nav>
            <p><?= count($texts) ?> / <?= (int) $site['ephemere']['calendrier']['dernier_jour'] ?> jours préparés pour décembre <?= $year ?>.</p>
            <p><a class="button button-dark" href="<?= e(url('admin/editer.php?rubrique=avent&annee=' . $year)) ?>">Ajouter un récit à cette édition</a></p>
        <?php else: ?>
            <p>Choisissez la rubrique du texte que vous souhaitez ajouter.</p>
            <div class="editor-actions admin-create-actions">
                <a class="button button-dark" href="<?= e(url('admin/editer.php?rubrique=microfictions')) ?>">Ajouter une micro-fiction</a>
                <a class="button button-dark" href="<?= e(url('admin/editer.php?rubrique=nouvelles')) ?>">Ajouter une nouvelle</a>
                <a class="button button-dark" href="<?= e(url('admin/editer.php?rubrique=romans')) ?>">Ajouter un roman</a>
                <a class="button" href="<?= e(url('admin/editer.php?rubrique=ephemere')) ?>">Ajouter une page mensuelle</a>
            </div>
        <?php endif; ?>
        <!-- ======================= FILTRES ET TRI DES TEXTES ======================= -->
        <?php if (!$adventView): ?>
            <div class="admin-category-filters" role="group" aria-label="Filtrer les textes par rubrique" hidden>
                <button type="button" data-category="" aria-pressed="true" aria-controls="admin-texts-table">Tout <span data-filter-count></span></button>
                <?php foreach (categories() as $categoryKey => $categoryLabel): ?>
                    <?php if (in_array($categoryKey, ['invite', 'avent'], true)) { continue; } ?>
                    <button type="button" data-category="<?= e($categoryKey) ?>" aria-pressed="false" aria-controls="admin-texts-table"><?= e($categoryLabel) ?> <span data-filter-count></span></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p class="admin-table-status" role="status" aria-live="polite" hidden></p>
        <div class="admin-table-wrap">
            <table class="admin-table" id="admin-texts-table">
                <caption>Textes enregistrés</caption>
                <thead><tr><th scope="col" data-sort="0">Titre</th><th scope="col" data-sort="1">Rubrique</th><th scope="col" data-sort="2">Emplacement</th><th scope="col" data-sort="3">Visibilité</th><th scope="col" data-sort="4">Publication</th><th scope="col">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($texts as $text): ?>
                        <?php $publication = date_publication_texte($text, $site); ?>
                        <tr data-category="<?= e($text['categorie']) ?>">
                            <th scope="row"><?= e($text['titre']) ?></th>
                            <td><?= e(categories()[$text['categorie']]) ?></td>
                            <td><?= $text['categorie'] === 'avent' ? 'Jour ' . (int) $text['jour'] . ' · ' . annee_avent_texte($text, $site) : e($text['mois_invite']) ?></td>
                            <td><?= $text['statut'] === 'brouillon' ? 'Brouillon' : (texte_visible($text, $site) ? (isset($archivedTexts[$text['id']]) ? 'Lire ou relire' : 'Visible') : 'Programmé / hors période') ?></td>
                            <td data-sort-value="<?= $publication ? e($publication->format('Y-m-d')) : '' ?>"><?php if ($publication): ?><time datetime="<?= e($publication->format('Y-m-d')) ?>"><?= e($publication->format('d/m/Y')) ?></time><?php else: ?>—<?php endif; ?></td>
                            <td><a href="<?= e(url('admin/voir.php?texte=' . rawurlencode($text['id']))) ?>" aria-label="Voir <?= e($text['titre']) ?>">Voir</a> · <a href="<?= e(url('admin/editer.php?id=' . rawurlencode($text['id']))) ?>" aria-label="Modifier <?= e($text['titre']) ?>">Modifier</a> · <a href="<?= e(url('admin/supprimer.php?id=' . rawurlencode($text['id']))) ?>" aria-label="Supprimer <?= e($text['titre']) ?>">Supprimer</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script src="<?= e(url('js/admin-liste.js') . '?v=' . filemtime(__DIR__ . '/../js/admin-liste.js')) ?>" defer></script>
<?php require __DIR__ . '/_pied.php'; ?>

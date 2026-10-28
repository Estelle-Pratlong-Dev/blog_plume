<?php
require __DIR__ . '/_securite.php';
exiger_admin();
$texts = tous_textes();
$adminTitle = 'Mes textes';
$authenticated = true;
require __DIR__ . '/_entete.php';
?>
        <!-- ============================== TABLEAU DE BORD ============================== -->
        <p>Collez un texte ou importez un document Word, vérifiez sa présentation, puis choisissez sa rubrique et son statut.</p>
        <p><a class="button button-dark" href="<?= e(url('admin/editer.php')) ?>">Créer un nouveau texte</a></p>
        <p class="notice">Les histoires de l’Avent s’ouvrent en décembre, à minuit, heure de Paris. Un jour futur reste inaccessible au public, même avec un lien direct.</p>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption>Textes enregistrés</caption>
                <thead><tr><th scope="col">Titre</th><th scope="col">Rubrique</th><th scope="col">Emplacement</th><th scope="col">Visibilité</th><th scope="col">Action</th></tr></thead>
                <tbody>
                    <?php foreach ($texts as $text): ?>
                        <tr>
                            <th scope="row"><?= e($text['titre']) ?></th>
                            <td><?= e(categories()[$text['categorie']]) ?></td>
                            <td><?= $text['categorie'] === 'avent' ? 'Jour ' . (int) $text['jour'] : e($text['mois_invite']) ?></td>
                            <td><?= $text['statut'] === 'brouillon' ? 'Brouillon' : (texte_visible($text, $site) ? 'Visible' : 'Programmé / hors période') ?></td>
                            <td><a href="<?= e(url('admin/editer.php?id=' . rawurlencode($text['id']))) ?>" aria-label="Modifier <?= e($text['titre']) ?>">Modifier</a> · <a href="<?= e(url('admin/supprimer.php?id=' . rawurlencode($text['id']))) ?>" aria-label="Supprimer <?= e($text['titre']) ?>">Supprimer</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require __DIR__ . '/_pied.php'; ?>

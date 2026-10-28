<?php
require __DIR__ . '/_securite.php';
exiger_admin();
require __DIR__ . '/../includes/images-texte.php';
$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : null;
$texts = tous_textes();
if ($id !== null && !isset($texts[$id])) {
    http_response_code(404);
    exit('Texte introuvable.');
}
$text = $id === null ? ['titre' => '', 'categorie' => 'microfictions', 'introduction' => '', 'contenu' => '<p></p>', 'statut' => 'brouillon', 'jour' => '', 'mois_invite' => horloge_publication($site)->format('Y-m'), 'ordre' => 0, 'version' => 0] : $texts[$id];
$text['police'] ??= 'defaut';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    $newImage = null;
    try {
        $image = $text['image'] ?? null;
        $description = champ_post('image_description');
        if (mb_strlen($description) > 250) {
            throw new InvalidArgumentException('La description de l’image ne doit pas dépasser 250 caractères.');
        }
        $hasUpload = isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $keepsImage = $image !== null && champ_post('retirer_image') !== '1';
        if (($hasUpload || $keepsImage) && trim($description) === '') {
            throw new InvalidArgumentException('La description de l’image est obligatoire lorsqu’une image est associée au texte.');
        }
        if (champ_post('retirer_image') === '1') {
            $image = null;
        }
        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newImage = recevoir_image_texte($_FILES['image'], trim($description));
            $image = $newImage;
        } elseif ($image !== null) {
            $image['description'] = trim($description);
        }
        $savedId = enregistrer_texte($_POST, $id, null, $image, true);
        $previousImage = $text['image'] ?? null;
        if ($previousImage !== null && ($previousImage['fichier'] ?? '') !== ($image['fichier'] ?? '')) {
            supprimer_image_texte($previousImage);
        }
        header('Location: ' . url('admin/editer.php?id=' . rawurlencode($savedId) . '&enregistre=1'), true, 303);
        exit;
    } catch (InvalidArgumentException | RuntimeException $exception) {
        supprimer_image_texte($newImage);
        $error = $exception->getMessage();
        // Ne jamais refléter un HTML non filtré, même après une erreur de validation.
        $text['elements'] = array_map(static fn(int $index): string => is_string($_POST['element_' . $index] ?? null) ? $_POST['element_' . $index] : '', [1, 2, 3]);
        $submitted = array_filter($_POST, 'is_string');
        $text = array_replace($text, array_intersect_key($submitted, $text));
        try {
            $text['contenu'] = nettoyer_contenu((string) $text['contenu']);
        } catch (InvalidArgumentException $ignored) {
            $text['contenu'] = '<p></p>';
        }
    }
}
// Tout texte réserve son jour, même en brouillon ; le jour du texte en cours reste modifiable.
$occupiedDays = [];
foreach ($texts as $otherId => $otherText) {
    if ($otherId !== $id && $otherText['categorie'] === 'avent') {
        $occupiedDays[(int) $otherText['jour']] = $otherText['statut'] === 'brouillon' ? 'Brouillon existant' : 'Texte existant';
    }
}
$adminTitle = $id === null ? 'Nouveau texte' : 'Modifier le texte';
$authenticated = true;
$editorScript = true;
require __DIR__ . '/_entete.php';
?>
        <!-- =============================== ÉDITION =============================== -->
        <?php if ($error !== ''): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if (isset($_GET['enregistre'])): ?><p class="save-status" role="status">Texte enregistré. <?= texte_visible($text, $site) ? 'Il est visible sur le blog.' : 'Il reste invisible au public tant que son statut ou sa date ne le permet pas.' ?></p><?php endif; ?>
        <form class="admin-form" id="edition" method="post" enctype="multipart/form-data" data-fonts="<?= e(json_encode(array_keys(polices_lecture()))) ?>" data-import-url="<?= e(url('admin/importer.php')) ?>">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="police" id="police" value="<?= e($text['police']) ?>">
            <input type="hidden" name="version" value="<?= (int) $text['version'] ?>">
            <div class="editor-fields">
                <div>
                    <label for="titre">Titre du texte</label>
                    <input id="titre" name="titre" value="<?= e($text['titre']) ?>" maxlength="180" required>
                </div>
                <div>
                    <label for="categorie">Rubrique</label>
                    <select id="categorie" name="categorie">
                        <?php foreach (categories() as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $key === $text['categorie'] ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div data-category-only="avent">
                    <label for="jour">Jour de l’Avent</label>
                    <select id="jour" name="jour" data-required="true" aria-describedby="jour-help">
                        <option value="">Choisir un jour disponible</option>
                        <?php for ($day = 1; $day <= 24; $day++): ?>
                            <?php $occupied = isset($occupiedDays[$day]); ?>
                            <option value="<?= $day ?>"<?= (int) $text['jour'] === $day ? ' selected' : '' ?><?= $occupied ? ' disabled' : '' ?>>Jour <?= $day ?><?= $occupied ? ' — ' . e($occupiedDays[$day]) : '' ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="muted" id="jour-help">Les jours grisés sont déjà réservés par un texte publié ou un brouillon. Pour les modifier, ouvrez le texte existant depuis « Mes textes ».</p>
                </div>
                <div data-category-only="invite">
                    <label for="mois-invite">Mois de présentation de l’invité</label>
                    <input id="mois-invite" name="mois_invite" data-required="true" type="month" value="<?= e($text['mois_invite']) ?>">
                </div>
                <div data-category-only="feuilleton">
                    <label for="ordre">Numéro du chapitre / ordre de lecture</label>
                    <input id="ordre" name="ordre" type="number" min="0" max="9999" value="<?= (int) $text['ordre'] ?>">
                </div>
                <div>
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut">
                        <option value="brouillon"<?= $text['statut'] === 'brouillon' ? ' selected' : '' ?>>Brouillon — invisible aux visiteurs</option>
                        <option value="publie"<?= $text['statut'] === 'publie' ? ' selected' : '' ?>>Publié — selon la date du calendrier</option>
                    </select>
                </div>
            </div>
            <fieldset data-category-only="microfictions">
                <legend>Microfiction : les trois éléments d’inspiration</legend>
                <p class="muted">À remplir pour une microfiction uniquement. Ces éléments accompagneront le texte et les propositions des lecteurs.</p>
                <?php for ($index = 1; $index <= 3; $index++): ?>
                    <label for="element-<?= $index ?>">Élément <?= $index ?></label>
                    <input id="element-<?= $index ?>" name="element_<?= $index ?>" data-required="true" maxlength="120" value="<?= e($text['elements'][$index - 1] ?? '') ?>">
                <?php endfor; ?>
            </fieldset>
            <label for="introduction">Court texte de présentation — facultatif, 600 caractères maximum</label>
            <textarea id="introduction" name="introduction" rows="3" maxlength="600"><?= e($text['introduction']) ?></textarea>

            <!-- ========================== IMAGE ASSOCIÉE ========================== -->
            <fieldset class="editor-image">
                <legend>Image associée au texte — facultative</legend>
                <label for="image">Ajouter ou remplacer l’image</label>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="image-help">
                <p class="muted" id="image-help">JPEG, PNG ou WebP, 5 Mo et 20 millions de pixels maximum. L’image est conservée avec le texte ; son affichage public sera défini ultérieurement.</p>
                <label for="image-description">Description alternative de l’image — obligatoire avec une image</label>
                <input id="image-description" name="image_description" maxlength="250" data-has-image="<?= !empty($text['image']) ? 'true' : 'false' ?>"<?= !empty($text['image']) ? ' required' : '' ?> value="<?= e($text['image']['description'] ?? '') ?>">
                <?php if (!empty($text['image']) && $id !== null): ?>
                    <img class="editor-image-preview" src="<?= e(url('admin/image.php?texte=' . rawurlencode($id) . '&v=' . (int) $text['version'])) ?>" alt="<?= e($text['image']['description'] ?? '') ?>">
                    <label><input type="checkbox" name="retirer_image" value="1"> Retirer l’image lors de l’enregistrement</label>
                <?php endif; ?>
            </fieldset>

            <!-- =========================== IMPORT WORD =========================== -->
            <div class="docx-drop" id="docx-drop">
                <label for="document">Glissez votre document Word ici, ou sélectionnez un fichier</label>
                <input id="document" type="file" accept=".docx">
                <button id="importer-document" class="button" type="button">Importer le document sélectionné</button>
                <p class="muted">DOCX, 5 Mo maximum. L’import remplace le contenu de l’éditeur après votre confirmation. Le fichier original n’est pas conservé sur le site.</p>
                <p id="import-status" role="status" aria-live="polite"></p>
            </div>

            <!-- ====================== TEXTE ET MISE EN FORME ====================== -->
            <label for="contenu-source">Contenu du texte</label>
            <textarea id="contenu-source" name="contenu" rows="16" required><?= e($text['contenu']) ?></textarea>
            <div id="rich-editor" hidden>
                <div class="editor-toolbar" role="group" aria-label="Mise en forme du texte">
                    <button type="button" data-command="bold">Gras</button>
                    <button type="button" data-command="italic">Italique</button>
                    <button type="button" data-command="underline">Souligné</button>
                    <button type="button" data-command="formatBlock" data-value="p">Paragraphe</button>
                    <button type="button" data-command="formatBlock" data-value="h2">Titre</button>
                    <button type="button" data-command="formatBlock" data-value="h3">Sous-titre</button>
                    <button type="button" data-command="insertUnorderedList">Liste</button>
                    <button type="button" data-command="insertOrderedList">Liste numérotée</button>
                    <button type="button" data-command="justifyLeft">À gauche</button>
                    <button type="button" data-command="justifyCenter">Centrer</button>
                    <button type="button" data-command="justifyRight">À droite</button>
                </div>
                <div id="editeur" class="prose editor-content" contenteditable="true" role="textbox" aria-label="Texte à publier" aria-multiline="true"></div>
            </div>
            <div class="editor-actions">
                <button class="button button-dark" type="submit">Enregistrer le texte</button>
                <button class="button" id="preview-button" type="button" hidden>Prévisualiser</button>
                <a class="text-link" href="<?= e(url('admin/index.php')) ?>">Retour à mes textes</a>
                <?php if ($id !== null): ?>
                    <a class="text-link" href="<?= e(url('admin/supprimer.php?id=' . rawurlencode($id))) ?>">Supprimer ce texte</a>
                <?php endif; ?>
            </div>
            <p class="muted" data-category-only="avent">Publier un texte de l’Avent avant sa date le programme : il ne sera pas encore lisible. Pour retirer un texte, remettez-le en brouillon.</p>
        </form>
        <section id="preview" class="editor-preview" aria-label="Prévisualisation du texte" hidden>
            <h2>Prévisualisation</h2>
            <div class="prose" id="preview-content"></div>
        </section>
<?php require __DIR__ . '/_pied.php'; ?>

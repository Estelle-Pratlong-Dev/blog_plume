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
$newCategory = in_array($_GET['rubrique'] ?? '', ['microfictions', 'nouvelles', 'romans', 'ephemere', 'avent'], true) ? $_GET['rubrique'] : 'microfictions';
$category = $id === null ? $newCategory : $texts[$id]['categorie'];
$isMonthly = $category === 'ephemere';
$defaultYear = (int) horloge_publication($site)->format('Y');
$selectedYear = filter_var($_GET['annee'] ?? $defaultYear, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2099]]) ?: $defaultYear;
$text = $id === null ? ['titre' => '', 'categorie' => $newCategory, 'introduction' => '', 'contenu' => '<p></p>', 'statut' => 'brouillon', 'jour' => '', 'mois_invite' => $isMonthly ? '' : horloge_publication($site)->format('Y-m'), 'ordre' => 0, 'version' => 0] : $texts[$id];
$text['police'] ??= 'defaut';
$text['annee_avent'] = $id !== null && $text['categorie'] === 'avent' ? annee_avent_texte($text, $site) : $selectedYear;
$text['consigne'] ??= '';
if (!empty($text['elements'])) {
    $text['consigne'] = trim($text['consigne'] . "\n\nLes mots imposés : " . implode(', ', $text['elements']));
}
$text['date_publication'] = (date_publication_texte($text, $site) ?? horloge_publication($site))->format('Y-m-d');
$error = '';
$imageCredit = $text['image']['credit'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_csrf();
    $newImage = null;
    try {
        $image = $text['image'] ?? null;
        $description = champ_post('image_description');
        $imageCredit = trim(champ_post('image_credit'));
        if (mb_strlen($imageCredit) > 500) {
            throw new InvalidArgumentException('Le crédit de l’image doit contenir au maximum 500 caractères.');
        }
        if (mb_strlen($description) > 250) {
            throw new InvalidArgumentException('Le titre de l’image ne doit pas dépasser 250 caractères.');
        }
        $hasUpload = isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $keepsImage = $image !== null && champ_post('retirer_image') !== '1';
        if (($hasUpload || $keepsImage) && trim($description) === '') {
            throw new InvalidArgumentException('Le titre de l’image est obligatoire lorsqu’une image est associée au texte.');
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
        if ($image !== null) {
            $image['credit'] = $imageCredit;
        }
        $input = $_POST;
        $input['categorie'] = $category;
        if ($category !== 'microfictions') {
            $input['consigne'] = $text['consigne'];
        }
        $input['introduction'] = $text['introduction'] ?? '';
        $savedId = enregistrer_texte($input, $id, null, $image, true);
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
        $submitted = array_filter($input ?? $_POST, 'is_string');
        unset($submitted['categorie'], $submitted['version']);
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
        $occupiedDays[annee_avent_texte($otherText, $site)][(int) $otherText['jour']] = $otherText['statut'] === 'brouillon' ? 'Brouillon existant' : 'Texte existant';
    }
}
$adminTitle = $id === null
    ? (['microfictions' => 'Ajouter une micro-fiction', 'nouvelles' => 'Ajouter une nouvelle', 'romans' => 'Ajouter un roman', 'avent' => 'Ajouter une histoire de l’Avent', 'ephemere' => 'Ajouter une page mensuelle'][$category])
    : 'Modifier · ' . categories()[$category];
$authenticated = true;
$editorScript = true;
require __DIR__ . '/_entete.php';
?>
        <!-- =============================== ÉDITION =============================== -->
        <?php if ($error !== ''): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if (isset($_GET['enregistre'])): ?><p class="save-status" role="status">Texte enregistré. <?= texte_visible($text, $site) ? 'Il est visible sur le blog.' : 'Il reste invisible au public tant que son statut ou sa date ne le permet pas.' ?></p><?php endif; ?>
        <form class="admin-form" id="edition" method="post" enctype="multipart/form-data" data-fonts="<?= e(json_encode(array_keys(polices_lecture()))) ?>" data-import-url="<?= e(url('admin/importer.php')) ?>" data-advent-days="<?= e(json_encode($occupiedDays, JSON_FORCE_OBJECT)) ?>">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="police" id="police" value="<?= e($text['police']) ?>">
            <input type="hidden" name="version" value="<?= (int) $text['version'] ?>">
            <input type="hidden" name="categorie" value="<?= e($category) ?>">
            <p class="muted">Les champs marqués d’un astérisque (*) sont obligatoires.</p>
            <?php if (!$isMonthly): ?>
                <!-- =========================== IMPORT WORD =========================== -->
                <div class="docx-drop" id="docx-drop">
                    <label for="document">Importer un document Word (facultatif)</label>
                    <div class="docx-target" id="docx-target" aria-hidden="true">
                        Glissez-déposez votre fichier Word ici
                    </div>
                    <input id="document" type="file" accept=".docx" aria-describedby="docx-help">
                    <button id="importer-document" class="button" type="button">Importer le document sélectionné</button>
                    <p class="muted" id="docx-help">Format DOCX, 15 Mo maximum. L’import préremplit le titre, la contrainte repérée, le récit et, si possible, une image intégrée. Vérifiez le résultat avant d’enregistrer. Le fichier original n’est pas conservé sur le site.</p>
                    <p id="import-status" role="status" aria-live="polite"></p>
                    <p id="import-success" class="import-success" aria-hidden="true" hidden>Import Word réussi. Vérifiez les champs avant d’enregistrer.</p>
                </div>
            <?php endif; ?>

            <div class="editor-fields">
                <div>
                    <label for="titre">Titre du texte <span aria-hidden="true">*</span></label>
                    <input id="titre" name="titre" value="<?= e($text['titre']) ?>" maxlength="180" required>
                </div>
                <?php if (in_array($category, ['microfictions', 'nouvelles', 'romans', 'invite'], true)): ?>
                    <div>
                        <label for="date-publication">Date de publication <span aria-hidden="true">*</span></label>
                        <input id="date-publication" name="date_publication" type="date" required value="<?= e($text['date_publication']) ?>" aria-describedby="date-publication-help">
                        <p class="muted" id="date-publication-help">Un texte au statut Publié devient visible à cette date, à minuit, heure de Paris. Un brouillon reste privé. Cette date détermine aussi l’ordre des textes sur le blog.</p>
                    </div>
                <?php endif; ?>
                <?php if ($category === 'avent'): ?>
                    <div>
                        <label for="annee-avent">Année du calendrier <span aria-hidden="true">*</span></label>
                        <input id="annee-avent" name="annee_avent" type="number" min="2000" max="2099" step="1" required value="<?= e((string) $text['annee_avent']) ?>">
                        <label for="jour">Jour de l’Avent <span aria-hidden="true">*</span></label>
                        <select id="jour" name="jour" required aria-describedby="jour-help">
                            <option value="">Choisir un jour disponible</option>
                            <?php for ($day = 1; $day <= (int) $site['ephemere']['calendrier']['dernier_jour']; $day++): ?>
                                <?php $occupied = $occupiedDays[(int) $text['annee_avent']][$day] ?? ''; ?>
                                <option value="<?= $day ?>"<?= (int) $text['jour'] === $day ? ' selected' : '' ?><?= $occupied !== '' ? ' disabled' : '' ?>>Jour <?= $day ?><?= $occupied !== '' ? ' — ' . e($occupied) : '' ?></option>
                            <?php endfor; ?>
                        </select>
                        <p class="muted" id="jour-help">Les jours grisés sont déjà réservés par un texte publié ou un brouillon. Pour les modifier, ouvrez le texte existant depuis « Mes textes ».</p>
                    </div>
                <?php endif; ?>
                <?php if ($isMonthly || $category === 'invite'): ?>
                    <div>
                        <label for="mois-invite"><?= $isMonthly ? 'Mois de publication' : 'Mois de l’invitation' ?> <span aria-hidden="true">*</span></label>
                        <input id="mois-invite" name="mois_invite" required type="month" min="2000-01" max="2099-12" value="<?= e($text['mois_invite'] ?? '') ?>">
                        <?php if ($isMonthly): ?><p class="muted">La page se publie automatiquement le premier jour du mois choisi.</p><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!$isMonthly): ?>
                    <div>
                        <label for="statut">Statut <span aria-hidden="true">*</span></label>
                        <select id="statut" name="statut" required>
                            <option value="brouillon"<?= $text['statut'] === 'brouillon' ? ' selected' : '' ?>>Brouillon — invisible aux visiteurs</option>
                            <option value="publie"<?= $text['statut'] === 'publie' ? ' selected' : '' ?>>Publié — selon la date de publication</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($category === 'microfictions'): ?>
            <label for="consigne">Contrainte <span aria-hidden="true">*</span></label>
            <textarea id="consigne" name="consigne" rows="3" required maxlength="2000" aria-describedby="contrainte-help"><?= e($text['consigne']) ?></textarea>
            <p class="muted" id="contrainte-help">Indiquez aussi les mots imposés ici, s’il y en a.</p>
            <?php endif; ?>
            <!-- ========================== IMAGE ASSOCIÉE ========================== -->
            <fieldset class="editor-image" id="editor-image"<?= $isMonthly ? ' hidden' : '' ?>>
                <legend>Image associée au texte — facultative</legend>
                <?php if (!$isMonthly): ?><label for="image">Ajouter ou remplacer l’image</label><?php endif; ?>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"<?= $isMonthly ? ' hidden' : ' aria-describedby="image-help"' ?>>
                <?php if (!$isMonthly): ?><p class="muted" id="image-help">JPEG, PNG ou WebP, 15 Mo et 20 millions de pixels maximum. L’image est conservée avec le texte ; son affichage public sera défini ultérieurement.</p><?php endif; ?>
                <label for="image-description">Titre de l’image <span id="image-required-mark" aria-hidden="true"<?= empty($text['image']) ? ' hidden' : '' ?>>*</span></label>
                <input id="image-description" name="image_description" maxlength="250" aria-describedby="image-title-help" data-has-image="<?= !empty($text['image']) ? 'true' : 'false' ?>"<?= !empty($text['image']) ? ' required' : '' ?> value="<?= e($text['image']['description'] ?? '') ?>">
                <p class="muted" id="image-title-help">Décrivez brièvement ce que montre l’image. Ce texte sert aussi aux personnes qui utilisent un lecteur d’écran. Ce titre est important pour le référencement Google.</p>
                <label for="image-credit">Crédit de l’image — auteur, source et licence si connus</label>
                <textarea id="image-credit" name="image_credit" rows="2" maxlength="500" aria-describedby="image-credit-help"><?= e($imageCredit) ?></textarea>
                <p class="muted" id="image-credit-help">Ce crédit est affiché sous le visuel. Renseignez les informations et les conditions d’utilisation fournies avec l’image ; le titre de l’image reste distinct.</p>
                <?php if (!empty($text['image']) && $id !== null): ?>
                    <img class="editor-image-preview" src="<?= e(url('admin/image.php?texte=' . rawurlencode($id) . '&v=' . (int) $text['version'])) ?>" alt="<?= e($text['image']['description'] ?? '') ?>">
                    <label><input type="checkbox" name="retirer_image" value="1"> Retirer l’image lors de l’enregistrement</label>
                <?php endif; ?>
            </fieldset>

            <!-- ====================== TEXTE ET MISE EN FORME ====================== -->
            <label for="contenu-source"><?= $isMonthly ? 'Contenu de la page' : 'Contenu du texte' ?> <span aria-hidden="true">*</span></label>
            <?php if ($category === 'avent'): ?>
                <label for="participation-noel">
                    <input id="participation-noel" name="participation_noel" type="checkbox" value="1"<?= !empty($text['participation_noel']) ? ' checked' : '' ?>>
                    Afficher le bouton « Envoyer mon histoire »
                </label>
            <?php endif; ?>
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
            <?php if ($isMonthly): ?><p id="editor-status" role="status" aria-live="polite"></p><?php endif; ?>
            <?php if (in_array($category, ['microfictions', 'nouvelles', 'romans'], true)): ?>
                <fieldset class="editor-archive">
                    <legend>Emplacement de publication</legend>
                    <input type="hidden" name="archive" value="0">
                    <label class="editor-archive-choice">
                        <input type="checkbox" name="archive" value="1"<?= !empty($text['archive']) ? ' checked' : '' ?>>
                        Classer ce texte dans « Lire ou relire »
                    </label>
                    <p class="muted">Une fois publié, le texte reste lisible dans « Lire ou relire ».</p>
                    <?php if ($category === 'nouvelles'): ?>
                        <p class="muted">Les <?= (int) $site['nouvelles_visibles'] ?> nouvelles les plus récentes restent sur la page Nouvelles. Les plus anciennes rejoignent automatiquement « Lire ou relire ».</p>
                    <?php elseif ($category === 'romans'): ?>
                        <p class="muted">Publier un nouveau roman en cours place automatiquement le précédent dans « Lire ou relire ».</p>
                    <?php endif; ?>
                </fieldset>
            <?php endif; ?>
            <div class="editor-actions">
                <button class="button button-dark" type="submit">Enregistrer le texte</button>
                <button class="button" id="preview-button" type="button" hidden>Prévisualiser</button>
                <a class="text-link" href="<?= e(url('admin/index.php')) ?>">Retour à mes textes</a>
                <?php if ($id !== null): ?>
                    <a class="text-link" href="<?= e(url('admin/supprimer.php?id=' . rawurlencode($id))) ?>">Supprimer ce texte</a>
                <?php endif; ?>
            </div>
            <?php if ($category === 'avent'): ?><p class="muted">Publier un texte de l’Avent avant sa date le programme : il ne sera pas encore lisible. Pour retirer un texte, remettez-le en brouillon.</p><?php endif; ?>
        </form>
        <section id="preview" class="editor-preview" aria-label="Prévisualisation du texte" hidden>
            <h2>Prévisualisation</h2>
            <div class="prose reading-text" id="preview-content"></div>
        </section>
<?php require __DIR__ . '/_pied.php'; ?>

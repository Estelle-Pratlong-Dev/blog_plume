<?php
/* Stockage privé sans extension de base de données.
 * Le verrou couvre toute lecture/modification ; l’écriture passe par un fichier
 * temporaire puis un remplacement atomique, sans écraser un fichier corrompu.
 */
function depot_operation(callable $operation, bool $write = false): mixed
{
    $directory = __DIR__ . '/../storage';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Le dossier de données doit être accessible en écriture.');
    }
    $lock = fopen($directory . '/contenus.lock', 'c');
    if ($lock === false || !flock($lock, $write ? LOCK_EX : LOCK_SH)) {
        throw new RuntimeException('Le stockage est temporairement indisponible.');
    }
    $temporary = null;
    try {
        $filename = $directory . '/contenus.json';
        $state = ['textes' => [], 'comptes' => [], 'tentatives' => []];
        if (is_file($filename)) {
            $decoded = json_decode(file_get_contents($filename), true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || !isset($decoded['textes'], $decoded['comptes'], $decoded['tentatives'])) {
                throw new RuntimeException('Le fichier de données doit être vérifié avant toute écriture.');
            }
            $state = $decoded;
        }
        $result = $operation($state);
        if ($write) {
            $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $json = preg_replace_callback('/^ +/m', static fn(array $match): string => str_repeat(' ', (int) (strlen($match[0]) / 2)), $json);
            $temporary = tempnam($directory, 'ecriture-');
            if ($temporary === false || file_put_contents($temporary, $json . "\n") === false || !rename($temporary, $filename)) {
                throw new RuntimeException('L’enregistrement a échoué. Les données précédentes sont conservées.');
            }
            $temporary = null;
            chmod($filename, 0600);
        }
        return $result;
    } finally {
        if (is_string($temporary) && is_file($temporary)) {
            unlink($temporary);
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function nombre_comptes(): int
{
    return depot_operation(static fn(array $state): int => count($state['comptes']));
}

function chercher_compte(string $username): ?array
{
    return depot_operation(static fn(array $state): ?array => $state['comptes'][$username] ?? null);
}

function gerer_tentatives(string $address, string $action): int
{
    return depot_operation(static function (array &$state) use ($address, $action): int {
        $state['tentatives'] = array_values(array_filter($state['tentatives'], static fn(array $attempt): bool => $attempt['instant'] >= time() - 900 && ($action !== 'effacer' || $attempt['adresse'] !== $address)));
        $count = count(array_filter($state['tentatives'], static fn(array $attempt): bool => $attempt['adresse'] === $address));
        if ($action === 'reserver' && $count >= 5) {
            return 6;
        }
        if ($action === 'reserver') {
            $state['tentatives'][] = ['adresse' => $address, 'instant' => time()];
        }
        return count(array_filter($state['tentatives'], static fn(array $attempt): bool => $attempt['adresse'] === $address));
    }, true);
}

// Choix fermés : aucune famille CSS arbitraire n’est acceptée depuis le formulaire.
function polices_lecture(): array
{
    return [
        'defaut' => 'Police du site',
        'arial' => 'Arial',
        'verdana' => 'Verdana',
        'calibri' => 'Calibri',
        'gabriola' => 'Gabriola',
        'apple-chancery' => 'Apple Chancery',
        'brush-script' => 'Brush Script MT',
        'lucida-calligraphy' => 'Lucida Calligraphy',
        'zapfino' => 'Zapfino',
        'optima' => 'Optima',
        'arsenal' => 'Arsenal',
        'eurostile' => 'Eurostile',
    ];
}

// Reconnaît les noms du document sans autoriser de CSS arbitraire.
function police_depuis_nom(string $name): string
{
    $normalized = preg_replace('/[^a-z0-9]/', '', mb_strtolower($name));
    // Les documents Word en Optima utilisent son remplacement Web libre.
    if (str_starts_with($normalized, 'optima')) {
        return 'arsenal';
    }
    foreach (polices_lecture() as $key => $label) {
        if ($key === 'defaut') {
            continue;
        }
        $candidate = preg_replace('/[^a-z0-9]/', '', mb_strtolower($label));
        if ($normalized === $candidate || ($candidate !== '' && str_starts_with($normalized, $candidate))) {
            return $key;
        }
    }
    return 'defaut';
}

function categories(): array
{
    return [
        'microfictions' => 'Micro-fictions',
        'nouvelles' => 'Nouvelles',
        'romans' => 'Romans',
        'avent' => 'Histoires de l’Avent',
        'invite' => 'Invité·e du mois',
        'ephemere' => 'De moi en mois — page mensuelle',
    ];
}

function tous_textes(): array
{
    $texts = depot_operation(static fn(array $state): array => $state['textes']);
    uasort($texts, static fn(array $left, array $right): int => [$left['ordre'], $left['jour'] ?? 0, $right['modifie_le']] <=> [$right['ordre'], $right['jour'] ?? 0, $left['modifie_le']]);
    return $texts;
}

// Seule la date éditoriale est simulée : les sessions et limites de connexion gardent l’heure réelle.
// La simulation est ignorée en production pour ne jamais publier un jour trop tôt.
function horloge_publication(array $site): DateTimeImmutable
{
    $zone = new DateTimeZone('Europe/Paris');
    if (!$site['production'] && !empty($site['date_simulee'])) {
        return new DateTimeImmutable($site['date_simulee'], $zone);
    }
    return new DateTimeImmutable('now', $zone);
}

// L’horloge est injectable pour vérifier les frontières de dates sans changer le serveur.
function debut_jour_avent(array $site, int $day, ?int $year = null): DateTimeImmutable
{
    $calendar = $site['ephemere']['calendrier'];
    return new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $year ?? (int) horloge_publication($site)->format('Y'), $calendar['mois'], $day), new DateTimeZone('Europe/Paris'));
}

// L’année configurée identifie les récits enregistrés avant la gestion par édition.
function annee_avent_texte(array $text, array $site): int
{
    return (int) ($text['annee_avent'] ?? $site['ephemere']['calendrier']['annee']);
}

function calendrier_avent_en_cours(array $site, DateTimeImmutable $now): bool
{
    return $site['ephemere']['mode'] === 'avent'
        && (int) $now->format('n') === (int) $site['ephemere']['calendrier']['mois'];
}

function fin_calendrier_avent(array $site, ?int $year = null): DateTimeImmutable
{
    return debut_jour_avent($site, 1, $year)->modify('+1 month');
}

// Date éditoriale : le calendrier fait autorité pour les histoires de l’Avent.
function date_publication_texte(array $text, array $site): ?DateTimeImmutable
{
    if ($text['categorie'] === 'avent') {
        return debut_jour_avent($site, (int) $text['jour'], annee_avent_texte($text, $site));
    }
    if ($text['categorie'] === 'ephemere' && !empty($text['mois_invite'])) {
        return new DateTimeImmutable($text['mois_invite'] . '-01 00:00:00', new DateTimeZone('Europe/Paris'));
    }
    return !empty($text['publie_le']) ? new DateTimeImmutable($text['publie_le']) : null;
}

function texte_visible(array $text, array $site, ?DateTimeImmutable $now = null): bool
{
    $now ??= horloge_publication($site);
    if ($text['statut'] !== 'publie') {
        return false;
    }
    if ($text['categorie'] === 'ephemere' && !empty($text['mois_invite'])) {
        return $text['mois_invite'] === $now->setTimezone(new DateTimeZone('Europe/Paris'))->format('Y-m');
    }
    if ($text['categorie'] === 'avent') {
        $year = annee_avent_texte($text, $site);
        return (int) $text['jour'] >= 1 && (int) $text['jour'] <= (int) $site['ephemere']['calendrier']['dernier_jour']
            && $now >= debut_jour_avent($site, (int) $text['jour'], $year) && $now < fin_calendrier_avent($site, $year);
    }
    $publication = date_publication_texte($text, $site);
    if ($publication !== null && $now < $publication) {
        return false;
    }
    if ($text['categorie'] === 'invite') {
        return $text['mois_invite'] === $now->format('Y-m');
    }
    return true;
}

function textes_publics(array $site, ?DateTimeImmutable $now = null): array
{
    return array_filter(tous_textes(), static fn(array $text): bool => texte_visible($text, $site, $now));
}

// Un tri commun garde la première publication comme référence, même après correction.
function trier_publications(array $texts): array
{
    uasort($texts, static function (array $left, array $right): int {
        $leftDate = strtotime($left['publie_le'] ?? $left['modifie_le'] ?? '') ?: 0;
        $rightDate = strtotime($right['publie_le'] ?? $right['modifie_le'] ?? '') ?: 0;
        return ($rightDate <=> $leftDate) ?: strcmp($right['id'], $left['id']);
    });
    return $texts;
}

// Attend exclusivement des publications déjà filtrées par textes_publics().
function classement_lectures(array $texts, int $storyLimit = 3): array
{
    $currentNovel = [];
    $stories = [];
    $archives = [];
    foreach (trier_publications($texts) as $id => $text) {
        if ($text['categorie'] === 'romans') {
            if (empty($text['archive']) && $currentNovel === []) {
                $currentNovel[$id] = $text;
            } else {
                $archives[$id] = $text;
            }
        } elseif ($text['categorie'] === 'microfictions' && !empty($text['archive'])) {
            $archives[$id] = $text;
        } elseif ($text['categorie'] === 'nouvelles') {
            if (!empty($text['archive']) || count($stories) >= max(1, $storyLimit)) {
                $archives[$id] = $text;
            } else {
                $stories[$id] = $text;
            }
        }
    }
    return ['romans' => $currentNovel, 'nouvelles' => $stories, 'archives' => $archives];
}
function pages_mensuelles(array $publicTexts): array
{
    $months = array_filter($publicTexts, static fn(array $text): bool => $text['categorie'] === 'ephemere' && !empty($text['mois_invite']));
    uasort($months, static fn(array $a, array $b): int => strcmp($b['mois_invite'], $a['mois_invite']));
    return $months;
}

// HTML éditorial limité : aucun script, lien externe, style arbitraire ou média embarqué.
// Seuls des retraits numériques bornés sont admis ; aucun style libre n’est enregistré.
function retraits_editoriaux(string $style): string
{
    $result = [];
    foreach (explode(';', $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $property = trim(strtolower($parts[0]));
        $value = trim(strtolower($parts[1]));
        if (!in_array($property, ['margin-left', 'margin-right', 'text-indent'], true)
            || !preg_match('/^(-?\d+(?:\.\d+)?)em$/D', $value, $match)) {
            continue;
        }
        $number = (float) $match[1];
        $minimum = $property === 'text-indent' ? -8 : 0;
        if ($number < $minimum || $number > 8) {
            continue;
        }
        $result[$property] = $property . ': ' . $number . 'em';
    }
    return implode('; ', $result);
}
function nettoyer_contenu(string $html): string
{
    if (strlen($html) > 500000) {
        throw new InvalidArgumentException('Le texte dépasse la taille maximale autorisée.');
    }
    // Espacement du pseudonyme validé pour la signature et les textes.
    $html = preg_replace('/\bL(?:\s*\.\s*|\s+)Yann\b/u', 'L . Yann', $html);
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $allowed = ['p', 'h2', 'h3', 'strong', 'b', 'em', 'i', 'u', 'br', 'ul', 'ol', 'li', 'blockquote', 'span', 'sup'];
    $blocked = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'img', 'video', 'audio'];
    $allowedClasses = ['align-center', 'align-right', 'align-justify', 'dialogue-list', 'docx-note', 'docx-extrait', 'docx-vedette', ...array_map(static fn(string $key): string => 'reading-font-' . $key, array_keys(polices_lecture()))];
    $walk = function (DOMNode $node) use (&$walk, $allowed, $blocked, $allowedClasses): string {
        if ($node instanceof DOMText) {
            return e($node->textContent);
        }
        if (!$node instanceof DOMElement || in_array(strtolower($node->tagName), $blocked, true)) {
            return '';
        }
        $inside = '';
        foreach ($node->childNodes as $child) {
            $inside .= $walk($child);
        }
        $tag = strtolower($node->tagName);
        if (!in_array($tag, $allowed, true)) {
            return $inside;
        }
        $class = '';
        $classes = array_values(array_intersect(preg_split('/\s+/', $node->getAttribute('class')) ?: [], $allowedClasses));
        if ($classes !== []) {
            $class = ' class="' . e(implode(' ', $classes)) . '"';
        }
        $indent = in_array($tag, ['p', 'h2', 'h3', 'li', 'blockquote'], true) ? retraits_editoriaux($node->getAttribute('style')) : '';
        $style = $indent !== '' ? ' style="' . e($indent) . '"' : '';
        return $tag === 'br' ? '<br>' : '<' . $tag . $class . $style . '>' . $inside . '</' . $tag . '>';
    };
    $output = '';
    // Le wrapper est réservé au parseur : son contenu seul est conservé.
    foreach ($document->childNodes as $node) {
        $output .= $walk($node);
    }
    if (trim(html_entity_decode(strip_tags($output), ENT_QUOTES, 'UTF-8')) === '') {
        throw new InvalidArgumentException('Ajoutez du texte avant d’enregistrer.');
    }
    return $output;
}

function enregistrer_texte(array $input, ?string $existingId = null, ?string $initialId = null, ?array $image = null, bool $replaceImage = false): string
{
    foreach (['titre', 'categorie', 'statut', 'introduction', 'contenu', 'mois_invite', 'consigne', 'date_publication'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) {
            throw new InvalidArgumentException('Un champ du formulaire est invalide.');
        }
    }
    if ($replaceImage && $image !== null && (!is_string($image['description'] ?? null) || trim($image['description']) === '' || mb_strlen($image['description']) > 250)) {
        throw new InvalidArgumentException('Une image associée doit avoir une description alternative de 1 à 250 caractères.');
    }
    if ($replaceImage && $image !== null && (!is_string($image['credit'] ?? '') || mb_strlen($image['credit'] ?? '') > 500)) {
        throw new InvalidArgumentException('Le crédit de l’image doit contenir au maximum 500 caractères.');
    }
    $font = $input['police'] ?? 'defaut';
    if (!is_string($font) || !isset(polices_lecture()[$font])) {
        throw new InvalidArgumentException('Choisissez une police proposée dans la liste.');
    }
    $title = trim((string) ($input['titre'] ?? ''));
    $category = (string) ($input['categorie'] ?? '');
    $status = $category === 'ephemere' ? 'publie' : (string) ($input['statut'] ?? 'brouillon');
    $participationNoel = ($input['categorie'] ?? '') === 'avent' && filter_var($input['participation_noel'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $archive = in_array($category, ['microfictions', 'romans', 'nouvelles'], true)
        && filter_var($input['archive'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $intro = trim((string) ($input['introduction'] ?? ''));
    if ($title === '' || mb_strlen($title) > 180 || mb_strlen($intro) > 600 || !isset(categories()[$category]) || !in_array($status, ['brouillon', 'publie'], true)) {
        throw new InvalidArgumentException('Vérifiez le titre, la rubrique et le statut.');
    }
    $publicationSite = require (is_file(__DIR__ . '/../config/site.php') ? __DIR__ . '/../config/site.php' : __DIR__ . '/../config/site.example.php');
    $adventYear = $category === 'avent' ? filter_var(
        $input['annee_avent'] ?? $publicationSite['ephemere']['calendrier']['annee'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 2000, 'max_range' => 2099]]
    ) : null;
    if ($adventYear === false) {
        throw new InvalidArgumentException('Choisissez une année entre 2000 et 2099.');
    }
    $lastAdventDay = (int) $publicationSite['ephemere']['calendrier']['dernier_jour'];
    $day = $category === 'avent' ? filter_var($input['jour'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $lastAdventDay]]) : null;
    if ($category === 'avent' && $day === false) {
        throw new InvalidArgumentException("Choisissez un jour de l’Avent entre 1 et $lastAdventDay.");
    }
    $guestMonth = in_array($category, ['invite', 'ephemere'], true) ? (string) ($input['mois_invite'] ?? '') : '';
    if (in_array($category, ['invite', 'ephemere'], true) && !preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/', $guestMonth)) {
        throw new InvalidArgumentException('Choisissez un mois valide.');
    }
    $order = filter_var($input['ordre'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
    if ($order === false) {
        throw new InvalidArgumentException('L’ordre de lecture doit être compris entre 0 et 9999.');
    }
    // Une micro-fiction peut partir de trois éléments ou d’une consigne libre.
    $elements = [];
    $instruction = trim((string) ($input['consigne'] ?? ''));
    if (mb_strlen($instruction) > 2000) {
        throw new InvalidArgumentException('La contrainte doit contenir au maximum 2 000 caractères.');
    }
    if ($category === 'microfictions') {
        for ($index = 1; $index <= 3; $index++) {
            $value = $input['element_' . $index] ?? '';
            if (!is_string($value) || mb_strlen(trim($value)) > 120) {
                throw new InvalidArgumentException('Chaque élément doit contenir au maximum 120 caractères.');
            }
            if (trim($value) !== '') {
                $elements[] = trim($value);
            }
        }
        if (($elements !== [] && count($elements) !== 3) || ($elements === [] && $instruction === '')) {
            throw new InvalidArgumentException('Renseignez la contrainte de cette micro-fiction.');
        }
    }
    $publicationDate = null;
    if (!in_array($category, ['avent', 'ephemere'], true) && array_key_exists('date_publication', $input)) {
        $value = $input['date_publication'];
        $parsed = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Europe/Paris')) : false;
        if (!$parsed || $parsed->format('Y-m-d') !== $value || !preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $value)) {
            throw new InvalidArgumentException('Choisissez une date de publication valide.');
        }
        $publicationDate = $parsed->format(DATE_ATOM);
    }
    $publicationClock = horloge_publication($publicationSite);
    $content = nettoyer_contenu((string) ($input['contenu'] ?? ''));
    $id = $existingId ?? $initialId ?? bin2hex(random_bytes(12));
    return depot_operation(static function (array &$state) use ($participationNoel, $input, $existingId, $id, $title, $category, $intro, $content, $status, $day, $guestMonth, $order, $elements, $instruction, $archive, $font, $image, $replaceImage, $publicationDate, $publicationClock, $publicationSite, $adventYear): string {
        $previous = $state['textes'][$id] ?? null;
        if ($existingId !== null && ($previous === null || $previous['version'] !== (int) ($input['version'] ?? 0))) {
            throw new RuntimeException('Ce texte a été modifié dans un autre onglet. Rechargez la page avant de poursuivre.');
        }
        if ($existingId === null && $previous !== null) {
            throw new InvalidArgumentException('Cet identifiant de texte existe déjà.');
        }
        foreach ($state['textes'] as $other) {
            if ($other['id'] === $id) {
                continue;
            }
            if (($category === 'avent' && $other['categorie'] === 'avent' && (int) $other['jour'] === $day && annee_avent_texte($other, $publicationSite) === $adventYear)
                || (in_array($category, ['invite', 'ephemere'], true) && $other['categorie'] === $category && ($other['mois_invite'] ?? '') === $guestMonth)) {
                throw new InvalidArgumentException('Un texte occupe déjà ce jour ou ce mois invité. Modifiez le texte existant ou choisissez un autre emplacement.');
            }
        }
        $publishedAt = $previous['publie_le'] ?? null;
        if ($publishedAt === null && ($previous['statut'] ?? '') === 'publie') {
            $publishedAt = $previous['modifie_le'];
        }
        if ($publicationDate !== null) {
            $publishedAt = $publicationDate;
        }
        if ($publishedAt === null && $status === 'publie') {
            $publishedAt = date(DATE_ATOM);
        }
        // Un seul roman en cours : le précédent reste public dans Lire ou relire.
        if ($category === 'romans' && $status === 'publie' && !$archive && new DateTimeImmutable($publishedAt) <= $publicationClock) {
            foreach ($state['textes'] as $otherId => &$other) {
                if ($otherId !== $id && $other['categorie'] === 'romans' && $other['statut'] === 'publie' && empty($other['archive']) && new DateTimeImmutable($other['publie_le'] ?? $other['modifie_le']) <= $publicationClock) {
                    $other['archive'] = true;
                    $other['version'] = ($other['version'] ?? 0) + 1;
                    $other['modifie_le'] = date(DATE_ATOM);
                }
            }
            unset($other);
        }
        $state['textes'][$id] = [
            'id' => $id,
            'titre' => $title,
            'categorie' => $category,
            'introduction' => $intro,
            'contenu' => $content,
            'elements' => $elements,
            'consigne' => $instruction,
            'police' => $font,
            'image' => $replaceImage ? $image : ($previous['image'] ?? null),
            'statut' => $status,
            'archive' => $archive,
            'jour' => $day,
            'annee_avent' => $adventYear,
            'mois_invite' => $guestMonth,
            'ordre' => $order,
            'participation_noel' => $participationNoel,
            'version' => ($previous['version'] ?? 0) + 1,
            'modifie_le' => date(DATE_ATOM),
            'publie_le' => $publishedAt,
        ];
        return $id;
    }, true);
}

// Suppression explicite, version vérifiée sous verrou pour éviter une suppression sur un onglet périmé.
function supprimer_texte(string $id, int $version): ?array
{
    return depot_operation(static function (array &$state) use ($id, $version): ?array {
        $text = $state['textes'][$id] ?? null;
        if ($text === null || $text['version'] !== $version) {
            throw new RuntimeException('Le texte a été modifié ou supprimé. Revenez à la liste avant de poursuivre.');
        }
        $image = $text['image'] ?? null;
        unset($state['textes'][$id]);
        return $image;
    }, true);
}

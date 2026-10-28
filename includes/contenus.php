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
        'calibri' => 'Calibri',
        'gabriola' => 'Gabriola',
        'apple-chancery' => 'Apple Chancery',
        'brush-script' => 'Brush Script MT',
        'lucida-calligraphy' => 'Lucida Calligraphy',
        'zapfino' => 'Zapfino',
        'optima' => 'Optima',
        'eurostile' => 'Eurostile',
    ];
}

// Reconnaît les noms du document sans autoriser de CSS arbitraire.
function police_depuis_nom(string $name): string
{
    $normalized = preg_replace('/[^a-z0-9]/', '', mb_strtolower($name));
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
        'microfictions' => 'Microfictions',
        'nouvelles' => 'Nouvelles',
        'feuilleton' => 'Le feuilleton',
        'romans' => 'Romans',
        'avent' => 'Histoires de l’Avent',
        'invite' => 'Invité·e du mois',
        'ephemere' => 'Autre contenu éphémère',
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
function debut_jour_avent(array $site, int $day): DateTimeImmutable
{
    $calendar = $site['ephemere']['calendrier'];
    return new DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $calendar['annee'], $calendar['mois'], $day), new DateTimeZone('Europe/Paris'));
}

function texte_visible(array $text, array $site, ?DateTimeImmutable $now = null): bool
{
    $now ??= horloge_publication($site);
    if ($text['statut'] !== 'publie') {
        return false;
    }
    if ($text['categorie'] === 'avent') {
        return (int) $text['jour'] >= 1 && (int) $text['jour'] <= 24 && $now >= debut_jour_avent($site, (int) $text['jour']);
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

function invite_du_mois(array $site): ?array
{
    foreach (textes_publics($site) as $text) {
        if ($text['categorie'] === 'invite') {
            return $text;
        }
    }
    return null;
}

// HTML éditorial limité : aucun script, lien externe, style arbitraire ou média embarqué.
function nettoyer_contenu(string $html): string
{
    if (strlen($html) > 500000) {
        throw new InvalidArgumentException('Le texte dépasse la taille maximale autorisée.');
    }
    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $allowed = ['p', 'h2', 'h3', 'strong', 'b', 'em', 'i', 'u', 'br', 'ul', 'ol', 'li', 'blockquote', 'span'];
    $blocked = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'img', 'video', 'audio'];
    $allowedClasses = ['align-center', 'align-right', 'align-justify', ...array_map(static fn(string $key): string => 'reading-font-' . $key, array_keys(polices_lecture()))];
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
        return $tag === 'br' ? '<br>' : '<' . $tag . $class . '>' . $inside . '</' . $tag . '>';
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
    foreach (['titre', 'categorie', 'statut', 'introduction', 'contenu', 'mois_invite'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) {
            throw new InvalidArgumentException('Un champ du formulaire est invalide.');
        }
    }
    if ($replaceImage && $image !== null && (!is_string($image['description'] ?? null) || trim($image['description']) === '' || mb_strlen($image['description']) > 250)) {
        throw new InvalidArgumentException('Une image associée doit avoir une description alternative de 1 à 250 caractères.');
    }
    $font = $input['police'] ?? 'defaut';
    if (!is_string($font) || !isset(polices_lecture()[$font])) {
        throw new InvalidArgumentException('Choisissez une police proposée dans la liste.');
    }
    $title = trim((string) ($input['titre'] ?? ''));
    $category = (string) ($input['categorie'] ?? '');
    $status = (string) ($input['statut'] ?? 'brouillon');
    $intro = trim((string) ($input['introduction'] ?? ''));
    if ($title === '' || mb_strlen($title) > 180 || mb_strlen($intro) > 600 || !isset(categories()[$category]) || !in_array($status, ['brouillon', 'publie'], true)) {
        throw new InvalidArgumentException('Vérifiez le titre, la rubrique et le statut.');
    }
    $day = $category === 'avent' ? filter_var($input['jour'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 24]]) : null;
    if ($category === 'avent' && $day === false) {
        throw new InvalidArgumentException('Choisissez un jour de l’Avent entre 1 et 24.');
    }
    $guestMonth = $category === 'invite' ? (string) ($input['mois_invite'] ?? '') : '';
    if ($category === 'invite' && !preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/', $guestMonth)) {
        throw new InvalidArgumentException('Choisissez le mois de présentation de l’invité.');
    }
    $order = filter_var($input['ordre'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
    if ($order === false) {
        throw new InvalidArgumentException('L’ordre de lecture doit être compris entre 0 et 9999.');
    }
    // Les inspirations appartiennent au texte : une seule source pour l’affichage et le contact.
    $elements = [];
    if ($category === 'microfictions') {
        for ($index = 1; $index <= 3; $index++) {
            $value = $input['element_' . $index] ?? '';
            if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > 120) {
                throw new InvalidArgumentException('Renseignez les trois éléments d’inspiration, de 120 caractères maximum chacun.');
            }
            $elements[] = trim($value);
        }
    }
    $content = nettoyer_contenu((string) ($input['contenu'] ?? ''));
    $id = $existingId ?? $initialId ?? bin2hex(random_bytes(12));
    return depot_operation(static function (array &$state) use ($input, $existingId, $id, $title, $category, $intro, $content, $status, $day, $guestMonth, $order, $elements, $font, $image, $replaceImage): string {
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
            if (($category === 'avent' && $other['categorie'] === 'avent' && $other['jour'] === $day)
                || ($category === 'invite' && $other['categorie'] === 'invite' && $other['mois_invite'] === $guestMonth)) {
                throw new InvalidArgumentException('Un texte occupe déjà ce jour ou ce mois invité. Modifiez le texte existant ou choisissez un autre emplacement.');
            }
        }
        $publishedAt = $previous['publie_le'] ?? null;
        if ($publishedAt === null && ($previous['statut'] ?? '') === 'publie') {
            $publishedAt = $previous['modifie_le'];
        }
        if ($publishedAt === null && $status === 'publie') {
            $publishedAt = date(DATE_ATOM);
        }
        $state['textes'][$id] = [
            'id' => $id,
            'titre' => $title,
            'categorie' => $category,
            'introduction' => $intro,
            'contenu' => $content,
            'elements' => $elements,
            'police' => $font,
            'image' => $replaceImage ? $image : ($previous['image'] ?? null),
            'statut' => $status,
            'jour' => $day,
            'mois_invite' => $guestMonth,
            'ordre' => $order,
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

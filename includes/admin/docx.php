<?php
/* Import DOCX sans exécution ni extraction sur disque.
 * Lecture bornée du ZIP, XML sans accès réseau, rendu dans le vocabulaire HTML autorisé.
 * Le lecteur ZIP borné évite de dépendre d’une extension ZIP non installée.
 */
require_once __DIR__ . '/../images-texte.php';

const DOCX_MAX_BYTES = 15 * 1024 * 1024;

function partie_docx(string $archive, string $wanted, int $partMaxBytes = 3 * 1024 * 1024): ?string
{
    if (strlen($archive) > DOCX_MAX_BYTES) {
        throw new InvalidArgumentException('Le fichier Word doit faire au maximum 15 Mo.');
    }
    $end = strrpos($archive, "PK\x05\x06");
    if ($end === false || strlen($archive) - $end < 22) {
        throw new InvalidArgumentException('Le fichier n’est pas un document DOCX valide.');
    }
    $directory = unpack('vdisk/vstart/vlocal/vcount/Vsize/Voffset/vcomment', substr($archive, $end + 4, 18));
    if ($directory['disk'] !== 0 || $directory['start'] !== 0 || $directory['count'] > 1000 || $directory['offset'] + $directory['size'] > $end) {
        throw new InvalidArgumentException('Archive Word non prise en charge.');
    }
    $offset = $directory['offset'];
    $total = 0;
    $found = null;
    for ($index = 0; $index < $directory['count']; $index++) {
        if (substr($archive, $offset, 4) !== "PK\x01\x02" || strlen($archive) - $offset < 46) {
            throw new InvalidArgumentException('Le document Word est endommagé.');
        }
        $entry = unpack('vversion/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vname/vextra/vcomment/vdisk/vinternal/Vexternal/Voffset', substr($archive, $offset + 4, 42));
        $name = substr($archive, $offset + 46, $entry['name']);
        $offset += 46 + $entry['name'] + $entry['extra'] + $entry['comment'];
        $total += $entry['size'];
        if ($offset > $end || $total > 30 * 1024 * 1024 || ($entry['flags'] & 1) || !in_array($entry['method'], [0, 8], true)) {
            throw new InvalidArgumentException('Document chiffré, trop volumineux ou compression non prise en charge.');
        }
        if (str_ends_with(strtolower($name), 'vbaproject.bin')) {
            throw new InvalidArgumentException('Les documents avec macros ne sont pas acceptés.');
        }
        if ($name !== $wanted) {
            continue;
        }
        if ($found !== null || $entry['size'] > $partMaxBytes || substr($archive, $entry['offset'], 4) !== "PK\x03\x04") {
            throw new InvalidArgumentException('La structure du document Word est invalide.');
        }
        $local = unpack('vname/vextra', substr($archive, $entry['offset'] + 26, 4));
        $start = $entry['offset'] + 30 + $local['name'] + $local['extra'];
        if ($start + $entry['compressed'] > $directory['offset']) {
            throw new InvalidArgumentException('La structure du document Word est invalide.');
        }
        $compressed = substr($archive, $start, $entry['compressed']);
        $found = $entry['method'] === 0 ? $compressed : @gzinflate($compressed, $partMaxBytes);
        if (!is_string($found) || strlen($found) !== $entry['size'] || sprintf('%u', crc32($found)) !== sprintf('%u', $entry['crc'])) {
            throw new InvalidArgumentException('Le contenu du document Word est corrompu.');
        }
    }
    return $found;
}

function xml_docx(string $xml): DOMDocument
{
    if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
        throw new InvalidArgumentException('Les déclarations XML externes ne sont pas autorisées.');
    }
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadXML($xml, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        throw new InvalidArgumentException('Le document Word contient du XML invalide.');
    }
    return $document;
}

// Sélectionne uniquement une image incorporée au corps, référencée dans le document.
function image_incorporee_docx(string $archive, DOMXPath $xpath, array &$warnings): ?array
{
    $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
    $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
    $relationshipNs = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    $xpath->registerNamespace('r', $relationshipNs);
    $images = [];
    foreach ($xpath->query('//w:body//*[not(ancestor::w:del)]/a:blip | //w:body//w:pict[not(ancestor::w:del)]//v:imagedata') as $drawing) {
        $id = $drawing->getAttributeNS($relationshipNs, 'embed') ?: $drawing->getAttributeNS($relationshipNs, 'id');
        if ($id !== '') {
            $images[$id] = true;
        }
    }
    if ($images === []) {
        return null;
    }
    if (count($images) !== 1) {
        $warnings[] = 'Le document contient plusieurs images. Ajoutez manuellement celle à associer au texte.';
        return null;
    }
    $id = array_key_first($images);
    try {
        $relationships = partie_docx($archive, 'word/_rels/document.xml.rels');
        if ($relationships !== null) {
            foreach (xml_docx($relationships)->documentElement->childNodes as $link) {
                if (!$link instanceof DOMElement || $link->getAttribute('Id') !== $id
                    || $link->getAttribute('Type') !== $relationshipNs . '/image' || $link->getAttribute('TargetMode') === 'External') {
                    continue;
                }
                $target = $link->getAttribute('Target');
                if (!preg_match('~^media/[a-zA-Z0-9_.-]+$~D', $target)) {
                    break;
                }
                $bytes = partie_docx($archive, 'word/' . $target, IMAGE_TEXTE_MAX_BYTES);
                $info = $bytes !== null ? @getimagesizefromstring($bytes) : false;
                if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $format = strtoupper(pathinfo($target, PATHINFO_EXTENSION));
                    $warnings[] = "Image non importée : le format $format est refusé ou l’image est illisible. Formats acceptés : JPEG, PNG et WebP. Le texte a été importé ; convertissez l’image puis ajoutez-la séparément.";
                    return null;
                }
                if ($info[0] * $info[1] > 20_000_000) {
                    $warnings[] = 'Image non importée : elle dépasse 20 millions de pixels. Le texte a été importé ; réduisez les dimensions de l’image puis ajoutez-la séparément.';
                    return null;
                }
                $warnings[] = 'Renseignez le titre de l’image avant d’enregistrer.';
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                return ['nom' => 'illustration-word.' . $extensions[$info['mime']], 'mime' => $info['mime'],
                    'reference' => $id, 'base64' => base64_encode($bytes)];
            }
        }
    } catch (InvalidArgumentException $ignored) {
        // Une image incompatible ne doit pas faire échouer l’import du récit.
    }
    $warnings[] = 'L’image intégrée est trop volumineuse ou son format est incompatible. Ajoutez-la manuellement.';
    return null;
}

function importer_docx(string $archive, bool $structured = false, bool $monthly = false): array
{
    $xml = partie_docx($archive, 'word/document.xml');
    if ($xml === null) {
        throw new InvalidArgumentException('Le fichier ne contient pas de texte Word.');
    }
    $document = xml_docx($xml);
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $attribute = static function (?DOMNode $node, string $name = 'val'): string {
        return $node instanceof DOMElement ? $node->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', $name) : '';
    };

    // Retraits, première ligne et alignement : valeurs directes puis héritées des styles.
    $paragraphProperties = static function (DOMXPath $path, DOMNode $parent) use ($attribute): array {
        $properties = [];
        $indent = $path->query('w:pPr/w:ind', $parent)->item(0);
        foreach (['left' => 'margin-left', 'right' => 'margin-right', 'start' => 'margin-left', 'end' => 'margin-right'] as $word => $css) {
            $value = $attribute($indent, $word);
            if (preg_match('/^\d+$/D', $value)) {
                $properties[$css] = min(8, (int) $value / 300);
            }
        }
        $first = $attribute($indent, 'firstLine');
        $hanging = $attribute($indent, 'hanging');
        if (preg_match('/^\d+$/D', $first)) {
            $properties['text-indent'] = min(8, (int) $first / 300);
        } elseif (preg_match('/^\d+$/D', $hanging)) {
            $properties['text-indent'] = -min(8, (int) $hanging / 300);
        }
        $alignment = $attribute($path->query('w:pPr/w:jc', $parent)->item(0));
        if ($alignment !== '') {
            $properties['alignment'] = $alignment;
        }
        return $properties;
    };
    $defaultStyle = '';
    $defaultParagraph = [];
    // Les styles nommés sont lus pour retrouver les titres et la mise en forme héritée.
    $styles = [];
    $styleXml = partie_docx($archive, 'word/styles.xml');
    if ($styleXml !== null) {
        $stylePath = new DOMXPath(xml_docx($styleXml));
        $stylePath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($stylePath->query('//w:style') as $style) {
            $id = $attribute($style, 'styleId');
            if ($attribute($style, 'type') === 'paragraph' && $attribute($style, 'default') === '1') {
                $defaultStyle = $id;
            }
            $styles[$id] = [
                'paragraph' => $paragraphProperties($stylePath, $style),
                'name' => $attribute($stylePath->query('w:name', $style)->item(0)),
                'parent' => $attribute($stylePath->query('w:basedOn', $style)->item(0)),
                'font' => $attribute($stylePath->query('w:rPr/w:rFonts', $style)->item(0), 'ascii') ?: $attribute($stylePath->query('w:rPr/w:rFonts', $style)->item(0), 'hAnsi'),
                'bold' => $stylePath->query('w:rPr/w:b', $style)->length > 0 && !in_array($attribute($stylePath->query('w:rPr/w:b', $style)->item(0)), ['0', 'false', 'off'], true),
                'italic' => $stylePath->query('w:rPr/w:i', $style)->length > 0 && !in_array($attribute($stylePath->query('w:rPr/w:i', $style)->item(0)), ['0', 'false', 'off'], true),
            ];
        }
    }
    $styleParagraph = static function (string $id) use ($styles): array {
        $chain = [];
        $visited = [];
        while (isset($styles[$id]) && !isset($visited[$id])) {
            $visited[$id] = true;
            array_unshift($chain, $styles[$id]['paragraph']);
            $id = $styles[$id]['parent'];
        }
        return $chain === [] ? [] : array_replace(...$chain);
    };
    $defaultFont = '';
    if (isset($stylePath)) {
        $defaultParagraphNode = $stylePath->query('//w:docDefaults/w:pPrDefault')->item(0);
        if ($defaultParagraphNode !== null) {
            $defaultParagraph = $paragraphProperties($stylePath, $defaultParagraphNode);
        }
        $defaultNode = $stylePath->query('//w:docDefaults/w:rPrDefault/w:rPr/w:rFonts')->item(0);
        $defaultFont = $attribute($defaultNode, 'ascii') ?: $attribute($defaultNode, 'hAnsi');
    }
    $styleFont = static function (string $id) use ($styles): string {
        $visited = [];
        while (isset($styles[$id]) && !isset($visited[$id])) {
            $visited[$id] = true;
            if ($styles[$id]['font'] !== '') {
                return $styles[$id]['font'];
            }
            $id = $styles[$id]['parent'];
        }
        return '';
    };
    // Associe les listes Word à leur rendu numéroté ou à puces.
    $numberFormats = [];
    $numberParagraphs = [];
    $numberMarkers = [];
    $numberingXml = partie_docx($archive, 'word/numbering.xml');
    if ($numberingXml !== null) {
        $numberPath = new DOMXPath(xml_docx($numberingXml));
        $numberPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $abstract = [];
        $abstractParagraphs = [];
        $abstractMarkers = [];
        foreach ($numberPath->query('//w:abstractNum') as $definition) {
            $abstractId = $attribute($definition, 'abstractNumId');
            $level = $numberPath->query('w:lvl[@w:ilvl="0"]', $definition)->item(0);
            $abstractParagraphs[$abstractId] = $level !== null ? $paragraphProperties($numberPath, $level) : [];
            $abstractMarkers[$abstractId] = $attribute($numberPath->query('w:lvl[@w:ilvl="0"]/w:lvlText', $definition)->item(0));
            $abstract[$attribute($definition, 'abstractNumId')] = $attribute($numberPath->query('w:lvl/w:numFmt', $definition)->item(0));
        }
        foreach ($numberPath->query('//w:num') as $definition) {
            $abstractId = $attribute($numberPath->query('w:abstractNumId', $definition)->item(0));
            $numberFormats[$attribute($definition, 'numId')] = ($abstract[$abstractId] ?? 'bullet') === 'bullet' ? 'ul' : 'ol';
            $numberParagraphs[$attribute($definition, 'numId')] = $abstractParagraphs[$abstractId] ?? [];
            $numberMarkers[$attribute($definition, 'numId')] = $abstractMarkers[$abstractId] ?? '';
        }
    }
    $html = '';
    $paragraphs = [];
    $listOpen = '';
    $openListStyle = '';
    $warnings = [];
    $noteParts = ['footnoteReference' => 'footnotes', 'endnoteReference' => 'endnotes'];
    $noteDefinitions = [];
    $noteNumbers = [];
    $notes = [];
    foreach ($noteParts as $reference => $partName) {
        if ($xpath->query('//w:body//w:' . $reference)->length === 0) {
            continue;
        }
        $noteXml = partie_docx($archive, 'word/' . $partName . '.xml');
        if ($noteXml === null) {
            continue;
        }
        $notePath = new DOMXPath(xml_docx($noteXml));
        $notePath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($notePath->query('/*/*[not(@w:type)]') as $note) {
            $lines = [];
            foreach ($notePath->query('.//w:p', $note) as $paragraph) {
                $line = '';
                foreach ($notePath->query('.//w:t[not(ancestor::w:del)]', $paragraph) as $fragment) {
                    $line .= $fragment->textContent;
                }
                $line = trim(preg_replace('/\s+/u', ' ', $line));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
            if ($lines !== []) {
                $noteDefinitions[$reference . ':' . $attribute($note, 'id')] = implode("\n", $lines);
            }
        }
    }
    if ($xpath->query('//w:tbl')->length > 0) {
        $warnings[] = 'Le texte des tableaux est repris en paragraphes. Vérifiez la disposition.';
    }
    $image = $structured ? image_incorporee_docx($archive, $xpath, $warnings) : null;
    if ($image === null && $warnings === [] && $xpath->query('//w:drawing|//w:pict')->length > 0) {
        $warnings[] = 'Vérifiez l’illustration du document et ajoutez-la manuellement si nécessaire.';
    }
    $title = '';
    $constraint = [];
    $readingConstraint = false;
    $bodyStarted = false;
    $imageSlotWritten = false;
    foreach ($xpath->query('//w:body//w:p[not(ancestor::w:del)]') as $paragraph) {
        $styleId = $attribute($xpath->query('w:pPr/w:pStyle', $paragraph)->item(0));
        $styleId = $styleId !== '' ? $styleId : $defaultStyle;
        $style = $styles[$styleId] ?? [];
        $paragraphFormat = array_replace($defaultParagraph, $styleParagraph($styleId), $paragraphProperties($xpath, $paragraph));
        $styleName = mb_strtolower($style['name'] ?? $styleId);
        $tag = preg_match('/heading ?1|titre ?1|^title$|^titre$/u', $styleName) ? 'h2' : (preg_match('/heading|titre/u', $styleName) ? 'h3' : 'p');
        $inside = '';
        $plain = '';
        foreach ($xpath->query('.//w:r[not(ancestor::w:del)]', $paragraph) as $run) {
            $text = '';
            foreach ($run->childNodes as $child) {
                if ($child->namespaceURI !== 'http://schemas.openxmlformats.org/wordprocessingml/2006/main') {
                    continue;
                }
                if ($child->localName === 't') {
                    $text .= e($child->textContent);
                    $plain .= $child->textContent;
                } elseif ($child->localName === 'br' || $child->localName === 'cr') {
                    $text .= '<br>';
                    $plain .= "\n";
                } elseif (isset($noteParts[$child->localName])) {
                    $key = $child->localName . ':' . $attribute($child, 'id');
                    if (isset($noteDefinitions[$key])) {
                        if (!isset($noteNumbers[$key])) {
                            $notes[] = $noteDefinitions[$key];
                            $noteNumbers[$key] = count($notes);
                        }
                        $text .= '<sup>' . $noteNumbers[$key] . '</sup>';
                    }
                } elseif ($child->localName === 'tab') {
                    $text .= '&#8195;&#8195;';
                    $plain .= '    ';
                }
            }
            foreach (['b' => 'strong', 'i' => 'em', 'u' => 'u'] as $property => $htmlTag) {
                $node = $xpath->query('w:rPr/w:' . $property, $run)->item(0);
                $fallback = $property === 'b' ? ($style['bold'] ?? false) : ($property === 'i' && ($style['italic'] ?? false));
                $enabled = $node !== null ? !in_array($attribute($node), ['0', 'false', 'off', 'none'], true) : $fallback;
                if ($enabled && $text !== '') {
                    $text = '<' . $htmlTag . '>' . $text . '</' . $htmlTag . '>';
                }
            }
            $runFont = $xpath->query('w:rPr/w:rFonts', $run)->item(0);
            $paragraphFont = $xpath->query('w:pPr/w:rPr/w:rFonts', $paragraph)->item(0);
            $runStyle = $attribute($xpath->query('w:rPr/w:rStyle', $run)->item(0));
            $fontName = $attribute($runFont, 'ascii') ?: $attribute($runFont, 'hAnsi');
            $fontName = $fontName ?: $styleFont($runStyle);
            $fontName = $fontName ?: ($attribute($paragraphFont, 'ascii') ?: $attribute($paragraphFont, 'hAnsi'));
            $fontName = $fontName ?: $styleFont($styleId);
            $fontName = $fontName ?: $defaultFont;
            $fontKey = police_depuis_nom($fontName);
            if ($monthly && $text !== '') {
                $size = $attribute($xpath->query('w:rPr/w:sz', $run)->item(0));
                $tone = $attribute($xpath->query('w:rPr/w:color', $run)->item(0));
                if ($fontKey === 'verdana' && $size === '18' && strcasecmp($tone, '5E5737') === 0) {
                    $text = '<span class="docx-extrait">' . $text . '</span>';
                } elseif ($fontKey === 'arial' && $size === '28') {
                    $text = '<span class="docx-vedette">' . $text . '</span>';
                }
            }
            if ($text !== '' && $fontKey !== 'defaut') {
                $text = '<span class="reading-font-' . $fontKey . '">' . $text . '</span>';
            }
            $inside .= $text;
        }
        $line = trim($plain);
        if ($monthly && $image && !$imageSlotWritten) {
            foreach ($xpath->query('.//a:blip | .//v:imagedata', $paragraph) as $drawing) {
                $related = $drawing->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed')
                    ?: $drawing->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                if ($related === $image['reference']) {
                    if ($listOpen !== '') {
                        $html .= '</' . $listOpen . '>';
                        $listOpen = $openListStyle = '';
                    }
                    $html .= '<p>[[illustration]]</p>';
                    $imageSlotWritten = true;
                    if ($line === '') {
                        continue 2;
                    }
                    break;
                }
            }
        }
        if ($structured && !$bodyStarted) {
            if ($line === '') {
                if ($monthly && $title !== '') {
                    $html .= '<p><br></p>';
                }
                if ($constraint !== []) {
                    $readingConstraint = false;
                }
                continue;
            }
            if ($title === '' && mb_strlen($line) <= 180
                && ($tag === 'h2' || (mb_strtoupper($line) === $line && preg_match('/\p{Lu}/u', $line)))) {
                $title = $line;
                continue;
            }
            if (!$monthly && $constraint === [] && preg_match('/^(?:contraintes?|consignes?)(?:[\h]*:[\h]*(.*))?$/isu', $line, $parts)) {
                $readingConstraint = true;
                $constraint[] = trim($parts[1] ?? '');
                continue;
            }
            if ($readingConstraint) {
                $constraint[] = $line;
                continue;
            }
            $bodyStarted = true;
        }
        if ($line === '') {
            $html .= '<p><br></p>';
            continue;
        }
        $paragraphs[] = $line;
        $isList = $xpath->query('w:pPr/w:numPr', $paragraph)->length > 0;
        $numberId = $attribute($xpath->query('w:pPr/w:numPr/w:numId', $paragraph)->item(0));
        $listType = $isList ? ($numberFormats[$numberId] ?? 'ul') : '';
        $listStyle = $isList && in_array($numberMarkers[$numberId] ?? '', ['-', '–', '—'], true) ? 'dialogue-list' : '';
        if ($listStyle !== '') {
            $paragraphFormat = array_replace($numberParagraphs[$numberId] ?? [], $paragraphFormat);
        }
        if ($listOpen !== '' && ($listType !== $listOpen || $listStyle !== $openListStyle)) {
            $html .= '</' . $listOpen . '>';
            $listOpen = '';
    $openListStyle = '';
        }
        if ($isList && $listOpen === '') {
            $html .= '<' . $listType . ($listStyle !== '' ? ' class="dialogue-list"' : '') . '>';
            $listOpen = $listType;
            $openListStyle = $listStyle;
        }
        $alignment = $paragraphFormat['alignment'] ?? '';
        $class = ['center' => 'align-center', 'right' => 'align-right', 'both' => 'align-justify'][$alignment] ?? '';
        $tag = $isList ? 'li' : $tag;
        $indentCss = [];
        foreach (['margin-left', 'margin-right', 'text-indent'] as $property) {
            if (isset($paragraphFormat[$property])) {
                $indentCss[] = $property . ': ' . $paragraphFormat[$property] . 'em';
            }
        }
        $indentStyle = retraits_editoriaux(implode('; ', $indentCss));
        $html .= '<' . $tag . ($class !== '' ? ' class="' . $class . '"' : '') . ($indentStyle !== '' ? ' style="' . e($indentStyle) . '"' : '') . '>' . $inside . '</' . $tag . '>';
    }
    if ($listOpen !== '') {
        $html .= '</' . $listOpen . '>';
    }
    $warnings[] = 'Paragraphes, titres, listes simples, gras, italique, soulignement, alignements, retraits de paragraphe, tabulations et lignes vides sont repris. Les polices reconnues sont conservées ; les autres et la pagination sont adaptées au site. Leur affichage dépend des polices disponibles sur l’appareil.';
    $constraintText = trim(implode("\n", $constraint));
    if (mb_strlen($constraintText) > 2000) {
        $warnings[] = 'La contrainte dépasse 2 000 caractères. Elle a été conservée dans le contenu importé.';
        $html = '<p>Contrainte : ' . nl2br(e($constraintText)) . '</p>' . $html;
        $constraintText = '';
    }
    if ($structured && $title === '') {
        $warnings[] = 'Aucun titre distinct reconnu. Renseignez le titre dans le formulaire.';
    }
    foreach ($notes as $index => $note) {
        $html .= '<p class="docx-note"><sup>' . ($index + 1) . '</sup> ' . nl2br(e($note), false) . '</p>';
    }
    return ['html' => nettoyer_contenu($html), 'titre' => $structured ? $title : ($paragraphs[0] ?? ''),
        'paragraphes' => $paragraphs, 'contrainte' => $constraintText, 'image' => $image, 'avertissements' => $warnings];
}

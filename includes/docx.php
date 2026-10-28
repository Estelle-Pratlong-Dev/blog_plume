<?php
/* Import DOCX sans exécution ni extraction sur disque.
 * Lecture bornée du ZIP, XML sans accès réseau, rendu dans le vocabulaire HTML autorisé.
 * Le lecteur ZIP borné évite de dépendre d’une extension ZIP non installée.
 */
function partie_docx(string $archive, string $wanted): ?string
{
    if (strlen($archive) > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Le fichier Word doit faire moins de 5 Mo.');
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
        if ($found !== null || $entry['size'] > 3 * 1024 * 1024 || substr($archive, $entry['offset'], 4) !== "PK\x03\x04") {
            throw new InvalidArgumentException('La structure du document Word est invalide.');
        }
        $local = unpack('vname/vextra', substr($archive, $entry['offset'] + 26, 4));
        $start = $entry['offset'] + 30 + $local['name'] + $local['extra'];
        if ($start + $entry['compressed'] > $directory['offset']) {
            throw new InvalidArgumentException('La structure du document Word est invalide.');
        }
        $compressed = substr($archive, $start, $entry['compressed']);
        $found = $entry['method'] === 0 ? $compressed : @gzinflate($compressed, 3 * 1024 * 1024);
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

function importer_docx(string $archive): array
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

    // Les styles nommés sont lus pour retrouver les titres et la mise en forme héritée.
    $styles = [];
    $styleXml = partie_docx($archive, 'word/styles.xml');
    if ($styleXml !== null) {
        $stylePath = new DOMXPath(xml_docx($styleXml));
        $stylePath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($stylePath->query('//w:style') as $style) {
            $id = $attribute($style, 'styleId');
            $styles[$id] = [
                'name' => $attribute($stylePath->query('w:name', $style)->item(0)),
                'parent' => $attribute($stylePath->query('w:basedOn', $style)->item(0)),
                'font' => $attribute($stylePath->query('w:rPr/w:rFonts', $style)->item(0), 'ascii') ?: $attribute($stylePath->query('w:rPr/w:rFonts', $style)->item(0), 'hAnsi'),
                'bold' => $stylePath->query('w:rPr/w:b', $style)->length > 0 && !in_array($attribute($stylePath->query('w:rPr/w:b', $style)->item(0)), ['0', 'false', 'off'], true),
                'italic' => $stylePath->query('w:rPr/w:i', $style)->length > 0 && !in_array($attribute($stylePath->query('w:rPr/w:i', $style)->item(0)), ['0', 'false', 'off'], true),
            ];
        }
    }
    $defaultFont = '';
    if (isset($stylePath)) {
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
    $numberingXml = partie_docx($archive, 'word/numbering.xml');
    if ($numberingXml !== null) {
        $numberPath = new DOMXPath(xml_docx($numberingXml));
        $numberPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $abstract = [];
        foreach ($numberPath->query('//w:abstractNum') as $definition) {
            $abstract[$attribute($definition, 'abstractNumId')] = $attribute($numberPath->query('w:lvl/w:numFmt', $definition)->item(0));
        }
        foreach ($numberPath->query('//w:num') as $definition) {
            $abstractId = $attribute($numberPath->query('w:abstractNumId', $definition)->item(0));
            $numberFormats[$attribute($definition, 'numId')] = ($abstract[$abstractId] ?? 'bullet') === 'bullet' ? 'ul' : 'ol';
        }
    }
    $html = '';
    $paragraphs = [];
    $listOpen = '';
    $warnings = [];
    if ($xpath->query('//w:tbl|//w:drawing|//w:pict')->length > 0) {
        $warnings[] = 'Les images ne sont pas importées ; le texte des tableaux est repris en paragraphes. Vérifiez la disposition.';
    }
    foreach ($xpath->query('//w:body//w:p[not(ancestor::w:del)]') as $paragraph) {
        $styleId = $attribute($xpath->query('w:pPr/w:pStyle', $paragraph)->item(0));
        $style = $styles[$styleId] ?? [];
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
                } elseif ($child->localName === 'tab') {
                    $text .= '    ';
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
            if ($text !== '' && $fontKey !== 'defaut') {
                $text = '<span class="reading-font-' . $fontKey . '">' . $text . '</span>';
            }
            $inside .= $text;
        }
        if (trim($plain) === '') {
            continue;
        }
        $paragraphs[] = trim($plain);
        $isList = $xpath->query('w:pPr/w:numPr', $paragraph)->length > 0;
        $numberId = $attribute($xpath->query('w:pPr/w:numPr/w:numId', $paragraph)->item(0));
        $listType = $isList ? ($numberFormats[$numberId] ?? 'ul') : '';
        if ($listOpen !== '' && $listType !== $listOpen) {
            $html .= '</' . $listOpen . '>';
            $listOpen = '';
        }
        if ($isList && $listOpen === '') {
            $html .= '<' . $listType . '>';
            $listOpen = $listType;
        }
        $alignment = $attribute($xpath->query('w:pPr/w:jc', $paragraph)->item(0));
        $class = ['center' => 'align-center', 'right' => 'align-right', 'both' => 'align-justify'][$alignment] ?? '';
        $tag = $isList ? 'li' : $tag;
        $html .= '<' . $tag . ($class !== '' ? ' class="' . $class . '"' : '') . '>' . $inside . '</' . $tag . '>';
    }
    if ($listOpen !== '') {
        $html .= '</' . $listOpen . '>';
    }
    $warnings[] = 'Paragraphes, titres, listes simples, gras, italique, soulignement et alignements sont repris. Les polices reconnues sont conservées ; les autres et la pagination sont adaptées au site. Leur affichage dépend des polices disponibles sur l’appareil.';
    return ['html' => nettoyer_contenu($html), 'titre' => $paragraphs[0] ?? '', 'paragraphes' => $paragraphs, 'avertissements' => $warnings];
}

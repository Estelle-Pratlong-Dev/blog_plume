<?php
// Traitement du formulaire de contact, avant le rendu de la page.
$sender = filter_var($site['contact_sender'] ?? '', FILTER_VALIDATE_EMAIL);
$contactReady = $recipient !== '' && $sender !== false;
$contactName = '';
$contactEmail = '';
$contactError = '';
$contactTokenKey = $isAdventContact ? 'contact_token_avent' : 'contact_token';

// Le texte affiché et la preuve jointe au message partagent la même source.
$publicationTerms = 'Je déclare être titulaire des droits sur le texte soumis. '
    . 'S’il est retenu, j’autorise ' . $site['author'] . ' à le reproduire et à le rendre accessible au public '
    . 'dans le monde entier sur le blog ' . $site['name'] . ', à titre gratuit, non exclusif et sans date de fin fixée. '
    . 'Je peux demander son retrait du blog via le formulaire de contact.';
$termsHash = hash('sha256', $publicationTerms);

ini_set('session.use_strict_mode', '1');
session_name('plume_contact');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => url('pages/contact.php'),
]);
session_start();
$contactSent = $_SESSION['contact_sent'] ?? '';
unset($_SESSION['contact_sent']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $posted = static fn (string $field): string => is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    $category = $isAdventContact ? 'noel' : $posted('type_demande');
    $selectedCategory = isset($requestCategories[$category]) ? $category : 'questions-diverses';
    $isProposal = in_array($category, ['microfiction', 'noel'], true);
    $contactName = $posted('nom');
    $contactEmail = $posted('email');
    $prefilledSubject = $posted('objet');
    $prefilledMessage = str_replace(["\r\n", "\r"], "\n", $posted('message'));
    $submittedToken = $posted('jeton');
    $validToken = hash_equals($_SESSION[$contactTokenKey] ?? '', $submittedToken) && $submittedToken !== '';
    $lastAttempt = (int) ($_SESSION['contact_last_attempt'] ?? 0);

    if (!$validToken || ($isProposal && ($_SESSION['contact_terms'] ?? '') !== $termsHash)) {
        $contactError = 'Le formulaire a expiré ou les conditions ont changé. Vérifiez votre message et réessayez.';
    } elseif (!isset($requestCategories[$category])
        || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL) || strlen($contactEmail) > 254
        || $contactName === '' || $prefilledSubject === '' || $prefilledMessage === ''
        || mb_strlen($contactName) > 100 || mb_strlen($prefilledSubject) > 120 || mb_strlen($prefilledMessage) > 10000
        || preg_match('/[\x00-\x1F\x7F]/', $contactName . $prefilledSubject)) {
        $contactError = 'Vérifiez votre nom, votre adresse e-mail, l’objet et le message.';
    } elseif ($category === 'noel' && preg_match_all("/[\p{L}\p{N}]+(?:[’'-][\p{L}\p{N}]+)*/u", $prefilledMessage) > 350) {
        $contactError = 'Votre histoire de Noël doit contenir au maximum 350 mots.';
    } elseif ($isProposal && $posted('autorisation_publication') !== 'on') {
        $contactError = 'Cochez l’autorisation de publication pour envoyer votre proposition.';
    } elseif (!$contactReady) {
        $contactError = 'L’envoi depuis le formulaire n’est pas encore disponible.';
    } elseif (time() - $lastAttempt < 30) {
        $contactError = 'Patientez quelques instants avant de réessayer.';
    } else {
        $_SESSION['contact_last_attempt'] = time();
        $referenceNumber = bin2hex(random_bytes(8));
        $mailDate = (new DateTimeImmutable())->format('d/m/Y à H:i (P)');
        $categoryLabel = $requestCategories[$category];
        $details = "Envoyé par $contactName ($contactEmail) le $mailDate";
        $metadata = "Référence : $referenceNumber";
        $authorization = $isProposal ? "Autorisation de publication\n$publicationTerms\n"
            . 'Empreinte SHA-256 du texte soumis : ' . hash('sha256', $prefilledMessage) : '';
        $text = "$categoryLabel\n$prefilledSubject\n$details\n\n$prefilledMessage\n\n$metadata";
        if ($authorization !== '') {
            $text .= "\n\n$authorization";
        }

        // Deux versions du message pour rester lisible dans toutes les messageries.
        $html = '<!doctype html><html lang="fr"><meta charset="utf-8">'
            . '<body style="margin:0;padding:24px;background:#eff6e3;color:#2c3928;font:16px/1.55 Arial,sans-serif">'
            . '<div style="max-width:620px;margin:0 auto;padding:24px;background:#fffefa;border-top:4px solid #42662d">'
            . '<p style="margin:0;color:#42662d"><strong>' . e($site['name']) . ' · ' . e($categoryLabel) . '</strong></p>'
            . '<h1 style="margin:8px 0;font-size:24px">' . e($prefilledSubject) . '</h1>'
            . '<p style="margin:0 0 24px;color:#58634f">' . e($details) . '</p>'
            . '<p style="margin:0 0 24px;overflow-wrap:anywhere">' . nl2br(e($prefilledMessage), false) . '</p>'
            . '<div style="border-top:1px solid #cddbbd;padding-top:12px;font-size:13px;color:#58634f;overflow-wrap:anywhere">'
            . '<p style="margin:0">' . e($metadata) . '</p>';
        if ($authorization !== '') {
            $html .= '<p>' . nl2br(e($authorization), false) . '</p>';
        }
        $html .= '</div></div></body></html>';

        $boundary = 'plume-' . bin2hex(random_bytes(16));
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";
        $headers = [
            'From' => 'Plume et Plume et Colegram <' . $sender . '>',
            'Reply-To' => $contactEmail,
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
        ];
        $subject = mb_encode_mimeheader($categoryLabel . ' — ' . $prefilledSubject, 'UTF-8', 'B', "\r\n");
        if (@mail($recipient, $subject, $body, $headers)) {
            // Un texte fixe évite de renvoyer à une adresse non vérifiée le contenu saisi.
            $receiptText = "Bonjour,\n\nNous avons bien reçu votre message. Merci de nous avoir écrit.\n"
                . "Nous reviendrons vers vous rapidement.\n\n{$site['name']}";
            $receiptHeaders = [
                'From' => $headers['From'],
                'Auto-Submitted' => 'auto-replied',
                'X-Auto-Response-Suppress' => 'All',
                'MIME-Version' => '1.0',
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Transfer-Encoding' => 'base64',
            ];
            $receiptSubject = mb_encode_mimeheader('Confirmation — ' . $site['name'], 'UTF-8', 'B', "\r\n");
            $receiptSent = @mail($contactEmail, $receiptSubject, chunk_split(base64_encode($receiptText)), $receiptHeaders);
            $_SESSION['contact_sent'] = $receiptSent ? 'receipt_sent' : 'message_only';
            unset($_SESSION[$contactTokenKey]);
            session_write_close();
            header('Location: ' . url($contactPath) . '#contact-confirmation', true, 303);
            exit;
        }
        $contactError = 'Le service de messagerie n’a pas accepté l’envoi. Votre texte est toujours disponible ci-dessous. Réessayez dans quelques instants.';
    }
}

if (($_SESSION['contact_terms'] ?? '') !== $termsHash) {
    unset($_SESSION['contact_token'], $_SESSION['contact_token_avent']);
}
$contactToken = $_SESSION[$contactTokenKey] ??= bin2hex(random_bytes(32));
$_SESSION['contact_terms'] = $termsHash;
session_write_close();
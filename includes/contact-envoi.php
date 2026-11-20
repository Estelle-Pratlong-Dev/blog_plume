<?php
// Traitement du formulaire de contact, avant le rendu de la page.

// Quotas partagés entre les sessions, réservés sous le verrou du dépôt privé.
function reserver_envoi_contact(string $address, string $email, ?int $now = null): bool
{
    $now ??= time();
    return depot_operation(static function (array &$state) use ($address, $email, $now): bool {
        $state['contact_limites'] ??= [];
        $state['contact_secret'] ??= bin2hex(random_bytes(32));
        foreach ($state['contact_limites'] as $key => $entry) {
            if ($entry['expiration'] <= $now) {
                unset($state['contact_limites'][$key]);
            }
        }
        $origin = hash_hmac('sha256', $address, $state['contact_secret']);
        $recipient = hash_hmac('sha256', mb_strtolower($email), $state['contact_secret']);
        $quotas = [
            ['origine-minute-' . $origin, 60, 1],
            ['origine-heure-' . $origin, 3600, 5],
            ['destinataire-heure-' . $recipient, 3600, 3],
            ['global-heure', 3600, 30],
        ];
        foreach ($quotas as [$key, $duration, $limit]) {
            if (($state['contact_limites'][$key]['nombre'] ?? 0) >= $limit) {
                return false;
            }
        }
        foreach ($quotas as [$key, $duration, $limit]) {
            $entry = $state['contact_limites'][$key] ?? ['nombre' => 0, 'expiration' => $now + $duration];
            $entry['nombre']++;
            $state['contact_limites'][$key] = $entry;
        }
        return true;
    }, true);
}
$sender = filter_var($site['contact_sender'] ?? '', FILTER_VALIDATE_EMAIL);
// L’expéditeur d’enveloppe doit appartenir à la boîte configurée, sans option de commande.
$mailParameters = $sender && preg_match('/^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+$/D', $sender)
    ? '-f' . escapeshellarg($sender) : '';
$contactReady = $recipient !== '' && $sender !== false && $mailParameters !== '';
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
        try {
            $allowed = reserver_envoi_contact((string) ($_SERVER['REMOTE_ADDR'] ?? 'inconnue'), $contactEmail);
        } catch (Throwable $exception) {
            $allowed = false;
        }
        if (!$allowed) {
            http_response_code(429);
            header('Retry-After: 60');
            $contactError = 'Le nombre de messages autorisés est atteint ou le service est temporairement indisponible. Patientez avant de réessayer. Votre texte est conservé ci-dessous.';
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
            $messageSent = @mail($recipient, $subject, $body, $headers, $mailParameters);
            // Diagnostic privé sans adresse e-mail ni contenu des messages.
            @error_log(json_encode([
                'date' => (new DateTimeImmutable())->format(DATE_ATOM),
                'reference' => $referenceNumber, 'etape' => 'message', 'accepte' => $messageSent,
            ]) . PHP_EOL, 3, __DIR__ . '/../storage/envois-contact.log');
            if ($messageSent) {
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
                $receiptSent = @mail($contactEmail, $receiptSubject, chunk_split(base64_encode($receiptText)), $receiptHeaders, $mailParameters);
                @error_log(json_encode([
                    'date' => (new DateTimeImmutable())->format(DATE_ATOM),
                    'reference' => $referenceNumber, 'etape' => 'confirmation', 'accepte' => $receiptSent,
                ]) . PHP_EOL, 3, __DIR__ . '/../storage/envois-contact.log');
                $_SESSION['contact_sent'] = $receiptSent ? 'receipt_sent' : 'message_only';
                unset($_SESSION[$contactTokenKey]);
                session_write_close();
                header('Location: ' . url($contactPath) . '#contact-confirmation', true, 303);
                exit;
            }
            $contactError = 'Le service de messagerie n’a pas accepté l’envoi. Votre texte est toujours disponible ci-dessous. Réessayez dans quelques instants.';
        }
    }
}

if (($_SESSION['contact_terms'] ?? '') !== $termsHash) {
    unset($_SESSION['contact_token'], $_SESSION['contact_token_avent']);
}
$contactToken = $_SESSION[$contactTokenKey] ??= bin2hex(random_bytes(32));
$_SESSION['contact_terms'] = $termsHash;
session_write_close();
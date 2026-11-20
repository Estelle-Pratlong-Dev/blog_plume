<?php
require __DIR__ . '/../../includes/bootstrap.php';
$currentPage = 'confidentialite.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confidentialité — <?= e($site['name']) ?></title>
    <meta name="description" content="Fonctionnement et données personnelles sur le blog de L . Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/obligatoires/confidentialite.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('img/identite/apple-touch-icon.png')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css') . '?v=' . filemtime(__DIR__ . '/../../css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ CONFIDENTIALITÉ ============================ -->
        <article class="reading-layout">
            <h1>Politique de confidentialité</h1>
            <div class="prose">
                <p>Cette page explique comment vos informations sont utilisées lorsque vous consultez <?= e($site['name']) ?>, envoyez un message ou proposez un texte à <?= e($site['author']) ?>.</p>

                <!-- ========================== RESPONSABLE ET CONTACT ========================== -->
                <h2>Qui traite vos données ?</h2>
                <?php if ($site['legal']['publisher'] !== ''): ?>
                    <p>Le responsable du traitement est <?= e($site['legal']['publisher']) ?>. Ses coordonnées figurent dans les <a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">mentions légales</a>.</p>
                <?php else: ?>
                    <p>Les données sont gérées par la personne qui édite ce blog sous le pseudonyme <?= e($site['author']) ?>.</p>
                <?php endif; ?>
                <p>Pour toute question concernant vos données, écrivez à <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a> ou utilisez le <a href="<?= e(url('pages/contact.php')) ?>">formulaire de contact</a>.</p>

                <!-- ========================== MESSAGES ET PROPOSITIONS ========================== -->
                <h2>Lorsque vous envoyez un message</h2>
                <p>Le formulaire demande votre nom, votre adresse e-mail, la catégorie, l’objet et le contenu de votre message. Les champs signalés comme obligatoires permettent de traiter votre demande et de vous répondre. Si vous ne les renseignez pas, le formulaire ne peut pas envoyer votre message.</p>
                <p>Les informations sont transmises au serveur du blog pour être vérifiées, puis envoyées par e-mail à <?= e($site['author']) ?>. Vous recevez un accusé de réception automatique à l’adresse saisie. Cet accusé contient une confirmation et ne reprend pas votre message.</p>
                <?php
                // Les échanges sont sollicités par les visiteurs et limités à leur demande.
                // Données minimales, accès restreint, conservation limitée et opposition possible.
                ?>
                <p>La lecture des messages, les réponses et l’examen des propositions reposent sur l’intérêt légitime de <?= e($site['author']) ?> à gérer les échanges demandés par les lecteurs et les contributions au blog. La protection du formulaire et de l’espace auteur repose sur l’intérêt légitime à assurer la sécurité du site.</p>
                <p>La publication de votre proposition et des informations permettant de vous créditer repose sur votre consentement. Vous pouvez retirer ce consentement et demander le retrait de votre contribution en écrivant à <?= e($site['author']) ?>. Ce retrait ne remet pas en cause les utilisations effectuées auparavant sur la base de votre consentement.</p>

                <h2>Lorsque vous proposez un texte</h2>
                <p>Une proposition de micro-fiction peut être associée au titre et à la consigne d’un texte du blog. Les propositions d’histoires de Noël sont envoyées depuis le formulaire dédié.</p>
                <p>Pour envoyer une proposition, vous devez cocher l’autorisation de publication affichée dans le formulaire. Le texte de cette autorisation est transmis à <?= e($site['author']) ?> avec votre proposition, la date d’envoi, une référence et une empreinte numérique du contenu soumis.</p>
                <p>Cette autorisation prévoit une publication gratuite et non exclusive sur le blog, accessible dans le monde entier et sans date de fin fixée. Chaque proposition est examinée avant une éventuelle publication. Vous conservez vos droits sur votre texte et pouvez demander son retrait via le formulaire de contact.</p>
                <p>Votre adresse e-mail sert aux échanges et n’est pas affichée avec le texte publié. Le nom ou pseudonyme sous lequel vous souhaitez être crédité doit être précisé avant publication.</p>

                <h2>Qui reçoit ces informations ?</h2>
                <p>Les messages sont destinés à <?= e($site['author']) ?>. Les prestataires techniques interviennent pour le fonctionnement du site, l’acheminement et le stockage des courriels.</p>
                <p>Le site est hébergé par <?= e($site['legal']['host_name']) ?>.<?php if ($site['privacy']['mail_provider'] !== ''): ?> La messagerie du blog est fournie par <?= e($site['privacy']['mail_provider']) ?>.<?php endif; ?> Les coordonnées de l’hébergeur figurent dans les <a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">mentions légales</a>.</p>
                <p>Les éventuels transferts de données hors de l’Espace économique européen et les garanties applicables restent à vérifier selon les services effectivement utilisés.</p>

                <h2>Combien de temps sont-elles conservées ?</h2>
                <p>Les messages reçus sont conservés dans la messagerie. La durée actuellement prévue pour les échanges ordinaires est de <?= (int) $site['privacy']['contact_retention_months'] ?> mois après la fin de l’échange. Leur suppression est effectuée dans cette messagerie.</p>
                <p>Le formulaire n’ajoute pas le contenu de votre message au stockage permanent du blog. Un texte retenu pour publication est ensuite enregistré avec les contenus du blog.</p>
                <p>Les propositions de textes non retenues sont conservées pendant <?= (int) $site['privacy']['contact_retention_months'] ?> mois après la décision, puis supprimées de la messagerie.</p>
                <p>Le justificatif d’autorisation est conservé pendant la publication du texte, puis pendant <?= (int) $site['privacy']['contact_retention_months'] ?> mois après son retrait. Ce délai permet de suivre la demande de retrait et les échanges associés. L’autorisation de publication reste sans date de fin fixée tant que vous ne demandez pas le retrait.</p>

                <!-- ========================== COOKIES ET SÉCURITÉ ========================== -->
                <h2>Cookies et préférence de lecture</h2>
                <p>La consultation du blog ne nécessite aucun compte lecteur. Le site utilise un cookie de session pour sécuriser le formulaire de contact et afficher sa confirmation. Un autre cookie de session est réservé à l’espace auteur, dont la connexion expire après trente minutes d’inactivité.</p>
                <p>Lorsque vous modifiez la taille des caractères, votre préférence est mémorisée dans votre navigateur pour vos prochaines lectures, jusqu’à sa suppression dans les données du navigateur. Cette préférence n’est pas transmise au serveur.</p>
                <p>À ce jour, le blog n’intègre ni outil de mesure d’audience, ni traceur publicitaire. Les polices, les images et les feuilles de style sont hébergées avec le site.</p>

                <h2>Protection contre les abus</h2>
                <p>Pour limiter les envois répétitifs, le blog utilise des compteurs associés à des empreintes cryptographiques de l’adresse réseau et de l’adresse e-mail saisie. Ces compteurs expirent au bout d’une minute ou d’une heure selon la limite appliquée, puis sont supprimés lors d’une utilisation ultérieure du formulaire. Ils ne servent pas à suivre votre navigation.</p>
                <p>L’accès à l’espace auteur est également protégé par une limitation des tentatives de connexion. Les documents Word déposés sont traités temporairement pour extraire leur contenu et les éléments pris en charge.</p>
                <p>Les durées et les destinataires des journaux techniques de l’hébergeur restent à préciser avec IONOS. La protection des courriels durant leur acheminement dépend des services de messagerie utilisés.</p>

                <!-- ========================== DROITS DES PERSONNES ========================== -->
                <h2>Vos droits</h2>
                <p>Dans les conditions prévues par le RGPD, vous pouvez demander l’accès à vos données, leur rectification, leur effacement ou la limitation de leur traitement. Vous pouvez vous opposer aux traitements fondés sur l’intérêt légitime pour des raisons tenant à votre situation. Pour les traitements fondés sur votre consentement, vous pouvez le retirer à tout moment et exercer votre droit à la portabilité dans les conditions prévues par le RGPD.</p>
                <p>Adressez votre demande à <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a> ou via le <a href="<?= e(url('pages/contact.php')) ?>">formulaire de contact</a>. Vous pouvez aussi adresser une réclamation à la <a href="https://www.cnil.fr/fr/adresser-une-plainte">CNIL</a>.</p>
            </div>
        </article>
    </main>
    <?php require __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

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
    <link rel="stylesheet" href="<?= e(url('css/styles.css') . '?v=' . filemtime(__DIR__ . '/../../css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css') . '?v=' . filemtime(__DIR__ . '/../../css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ CONFIDENTIALITÉ ============================ -->
        <article class="reading-layout">
            <h1>Confidentialité</h1>
            <div class="prose">
                <p>Cette page explique quelles informations sont utilisées lors de votre visite ou de vos échanges avec <?= e($site['author']) ?>, et ce qu’elles deviennent.</p>

                <!-- ========================== CONSULTATION DU BLOG ========================== -->
                <h2>Nature du site</h2>
                <p><?= e($site['name']) ?> est un blog littéraire consacré au partage d’écrits. Sa consultation ne nécessite aucun compte lecteur. Le site ne propose ni commentaires, ni inscription à une lettre d’information, ni outil de mesure d’audience ou traceur publicitaire.</p>
                <p>Un espace de connexion privé est réservé à la gestion des publications.</p>

                <!-- ========================== CONTACT ET MESSAGERIE ========================== -->
                <h2>Formulaire de contact</h2>
                <p>Le formulaire vous demande votre nom, votre adresse e-mail, une catégorie, un objet et votre message. Pour une proposition de micro-fiction, il peut reprendre le titre du récit, sa consigne et les éventuels éléments d’inspiration.</p>
                <p>Il prépare un message dans votre application mail. Vous pouvez le relire, le modifier et décider de l’envoyer vous-même. Les champs du formulaire ne sont ni transmis au serveur du blog ni enregistrés par le formulaire.</p>
                <p>Si vous envoyez le message, il est traité par les services de messagerie de l’expéditeur et du destinataire, puis conservé dans les boîtes mail correspondantes.</p>

                <h2>Utilisation des échanges</h2>
                <p>Les informations reçues permettent à <?= e($site['author']) ?> de lire votre demande, de vous répondre et, si vous proposez un texte, d’échanger sur cette proposition. Le formulaire ne déclenche aucune inscription à une liste commerciale ni aucun envoi publicitaire automatique.</p>
                <?php if ($site['privacy']['contact_legal_basis'] !== ''): ?>
                    <p>Base légale retenue pour les échanges : <?= e($site['privacy']['contact_legal_basis']) ?>.</p>
                <?php else: ?>
                    <p>La base légale du traitement des échanges reste à confirmer par le responsable du traitement avant la validation définitive de cette politique.</p>
                <?php endif; ?>

                <h2>Destinataires et prestataires</h2>
                <p>Les messages sont destinés à <?= e($site['author']) ?>. Les prestataires de messagerie interviennent dans leur acheminement et leur stockage selon leurs propres conditions. L’hébergeur intervient également dans le fonctionnement technique du site.</p>
                <?php if ($site['privacy']['mail_provider'] !== ''): ?>
                    <p>Prestataire de la messagerie de réception : <?= e($site['privacy']['mail_provider']) ?>.</p>
                <?php else: ?>
                    <p>Le prestataire de la messagerie de réception et les informations sur les éventuels transferts de données restent à confirmer. Les informations d’hébergement sont à compléter dans les <a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">mentions légales</a>.</p>
                <?php endif; ?>

                <h2>Conservation des messages</h2>
                <p>Pour les échanges ordinaires, la durée retenue est de <?= (int) $site['privacy']['contact_retention_months'] ?> mois après la fin de l’échange. La suppression doit être effectuée dans la messagerie de réception : le blog ne conserve pas ces messages et ne les efface pas automatiquement de la boîte mail.</p>
                <p>La durée de conservation des propositions de textes non publiées et des justificatifs d’autorisation reste à définir. Les éventuelles obligations de conservation et les modalités propres aux prestataires devront être prises en compte.</p>

                <h2>Propositions de textes et publication</h2>
                <p>Le formulaire de proposition comporte une case d’autorisation de publication. Le choix indiqué est joint au message préparé ; il n’entraîne aucune publication automatique sur le blog.</p>
                <p>Les conditions d’une éventuelle publication, notamment l’attribution du texte, les usages autorisés et les modalités de retrait, restent à formaliser avec la personne qui le propose. Cette autorisation de publication doit être distinguée de l’information sur le traitement des données personnelles.</p>

                <!-- ========================== PRÉFÉRENCES ET ESPACE PRIVÉ ========================== -->
                <h2>Préférence de lecture</h2>
                <p>Si vous réglez la taille des textes, ce choix est mémorisé uniquement dans votre navigateur pour vos prochaines lectures. Il n’est pas transmis au serveur et ne sert pas au suivi de votre navigation.</p>

                <h2>Espace auteur</h2>
                <p>Un compte privé permet à <?= e($site['author']) ?> de gérer les publications. Un cookie de session strictement nécessaire est utilisé pour la connexion. La session expire après trente minutes d’inactivité. La limitation des tentatives de connexion utilise temporairement une empreinte de l’adresse réseau.</p>
                <p>Les documents Word importés sont temporaires : seuls les textes et leur mise en forme filtrée sont enregistrés. Aucun document Word n’est proposé au téléchargement public.</p>

                <h2>Sécurité et ressources techniques</h2>
                <p>Les images, les polices et les feuilles de style sont servies depuis le site, sans chargement de police distante ni intégration de réseau social.</p>
                <p>Le protocole HTTPS sécurise la liaison entre votre navigateur et le site lorsqu’il est utilisé. Il ne constitue pas une garantie de chiffrement de bout en bout des e-mails : leur acheminement et leur protection dépendent des services de messagerie utilisés.</p>
                <p>Les éventuels journaux techniques d’hébergement, leurs destinataires et leur durée de conservation restent à préciser avec l’hébergeur.</p>

                <!-- ========================== RESPONSABLE ET EXERCICE DES DROITS ========================== -->
                <h2>Responsable du traitement et vos droits</h2>
                <?php if ($site['legal']['publisher'] !== ''): ?>
                    <p>Responsable du traitement : <?= e($site['legal']['publisher']) ?>. Ses coordonnées figurent dans les <a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">mentions légales</a>.</p>
                <?php else: ?>
                    <p>L’identité et les coordonnées du responsable du traitement doivent encore être renseignées dans les <a href="<?= e(url('pages/obligatoires/mentions-legales.php')) ?>">mentions légales</a>. La possibilité de préserver l’anonymat de l’éditeur non professionnel dans les mentions légales ne règle pas à elle seule l’information sur l’identité du responsable du traitement. Ce point reste à valider pour les échanges reçus par messagerie.</p>
                <?php endif; ?>
                <p>Dans les conditions prévues par le RGPD, vous pouvez demander l’accès à vos données, leur rectification ou leur effacement, ainsi que la limitation de leur traitement. Le droit d’opposition et le retrait du consentement s’appliquent selon la base légale retenue.</p>
                <p>Pour poser une question ou exercer vos droits, vous pouvez écrire à <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a> ou utiliser le <a href="<?= e(url('pages/contact.php?categorie=questions-diverses')) ?>">formulaire de contact</a>.</p>
                <p>Vous pouvez également adresser une réclamation à la <a href="https://www.cnil.fr/fr/adresser-une-plainte">CNIL</a> si vous estimez que vos droits ne sont pas respectés.</p>

                <h2>Informations encore à confirmer</h2>
                <p>Cette politique reste à finaliser sur les points suivants : l’identité du responsable, la base légale des échanges, les prestataires et éventuels transferts de données, les journaux techniques, ainsi que la conservation et les conditions de publication des propositions de textes.</p>
            </div>
        </article>
    </main>
    <?php require __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

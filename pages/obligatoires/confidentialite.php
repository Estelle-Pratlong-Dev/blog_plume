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
    <meta name="description" content="Fonctionnement et données personnelles sur le blog de L. Yann.">
    <meta name="robots" content="<?= $site['production'] ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if ($site['public_url'] !== ''): ?>
        <link rel="canonical" href="<?= e(rtrim($site['public_url'], '/') . '/pages/obligatoires/confidentialite.php') ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e(url('img/identite/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(url('css/styles.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('css/lecture.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../../includes/header.php'; ?>
    <main id="contenu">
        <!-- ============================ CONFIDENTIALITÉ ============================ -->
        <article class="reading-layout">
            <h1>Confidentialité</h1>
            <div class="prose">
                <h2>Consultation du blog</h2>
                <p>La consultation ne nécessite aucun compte lecteur. Le site ne comporte ni commentaire, ni inscription à une lettre d’information. Aucun outil de mesure d’audience ou traceur publicitaire n’est intégré.</p>
                <h2>Préférence de lecture</h2>
                <p>Si vous réglez la taille des textes, ce choix est mémorisé uniquement dans votre navigateur pour vos prochaines lectures. Il n’est pas transmis au serveur et ne sert pas au suivi de votre navigation.</p>
                <h2>Espace autrice</h2>
                <p>Un compte privé permet à l’autrice de gérer ses publications. Son mot de passe est conservé sous forme de hachage ; un cookie de session strictement nécessaire est utilisé pour la connexion. La session expire après trente minutes d’inactivité. Les compteurs de tentatives de connexion expirent après quinze minutes ; ils utilisent une empreinte de l’adresse réseau.</p>
                <p>Les documents Word importés sont temporaires : seuls les textes et leur mise en forme filtrée sont enregistrés. Aucun fichier Word n’est proposé au téléchargement public.</p>
                <h2>Contact</h2>
                <p>Le formulaire de contact prépare un message dans votre application mail avec le nom, l’adresse e-mail, la catégorie, l’objet et le texte renseignés. Une proposition de microfiction peut reprendre le titre et les trois éléments d’inspiration de la publication choisie. Ces informations ne sont ni envoyées au serveur du site ni conservées par le formulaire. Le message n’est envoyé que lorsque vous le validez dans votre messagerie. Les modalités de traitement des échanges reçus par courrier électronique restent à préciser avant la mise en ligne.</p>
                <h2>Ressources et hébergement</h2>
                <p>Les images et feuilles de style sont servies depuis le site. Aucune police distante ou intégration de réseau social n’est chargée. Les éventuels journaux techniques et leurs modalités de conservation dépendront de l’hébergement retenu.</p>
                <h2>Informations à compléter</h2>
                <p>Le responsable du traitement, les coordonnées d’exercice des droits et les conditions de conservation des messages restent à confirmer avant publication.</p>
            </div>
        </article>
    </main>
    <?php require __DIR__ . '/../../includes/footer.php'; ?>
    <script src="<?= e(url('js/script.js')) ?>" defer></script>
</body>
</html>

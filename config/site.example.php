<?php
// Identité issue des documents fournis ; coordonnées à compléter avant publication.
return [
    'name' => 'Plume et Plume et Colegram',
    'author' => 'L . Yann',
    'copyright_year' => 2026,
    'nouvelles_visibles' => 3,
    'email' => '',
    // Adresse expéditrice appartenant au domaine du contrat IONOS.
    'contact_sender' => '',
    'public_url' => '',
    'production' => false,
    'limiter_copie' => true,
    // Date éditoriale simulée en développement uniquement ; null rétablit la date réelle.
    'date_simulee' => '2026-12-26 00:00:00',
    // Le calendrier reste réglé sur décembre, même pendant les essais.
    'ephemere' => [
        'mode' => 'avent',
        'calendrier' => [
            'annee' => 2026,
            'mois' => 12,
            'dernier_jour' => 25,
            'limite_envoi' => 30,
        ],
    ],
    // Une source commune pour les décorations de l’Avent, sur l’accueil et le calendrier.
    'advent_motifs' => [
        'img/decorations/avent-cadeau.svg',
        'img/decorations/avent-boules.svg',
        'img/decorations/avent-etoile.svg',
        'img/decorations/avent-sapin-fete.svg',
    ],
    'creator' => [
        'name' => 'Estelle',
        'label' => 'Réalisation',
        'url' => 'https://dev.estelle-pratlong.fr/',
    ],
    // Conservation des échanges ordinaires : suppression à gérer dans la messagerie.
    'privacy' => [
        'contact_retention_months' => 6,
        'contact_legal_basis' => '',
        'mail_provider' => '',
    ],
    'legal' => [
        // Blog amateur ; vérifier auprès d’IONOS la transmission de l’identité réelle.
        'non_professional' => true,
        'host_identity_confirmed' => false,
        'publisher' => '',
        'publication_director' => '',
        'address' => '',
        'host_name' => 'IONOS SARL',
        'host_address' => '7, place de la Gare — BP 70109 — 57200 Sarreguemines Cedex — France',
        'host_contact' => 'Téléphone : 0970 808 911',
    ],
];

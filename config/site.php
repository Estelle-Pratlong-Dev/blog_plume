<?php
// Identité issue des documents fournis ; coordonnées à compléter avant publication.
return [
    'name' => 'Plume et Plume et Colegram',
    'author' => 'L Yann',
    'subtitle' => 'Histoires et imaginaires',
    'email' => 'elianecouprie@gmail.com',
    'public_url' => '',
    'production' => false,
    'limiter_copie' => true,
    // Date éditoriale simulée en développement uniquement ; null rétablit la date réelle.
    'date_simulee' => '2026-12-10 00:00:00',
    // Le calendrier reste réglé sur décembre, même pendant les essais.
    'ephemere' => [
        'mode' => 'avent',
        'calendrier' => [
            'annee' => 2026,
            'mois' => 12,
        ],
    ],
    // Une source commune pour les décorations de l’Avent, sur l’accueil et le calendrier.
    'advent_motifs' => [
        'img/decorations/avent-branche.svg',
        'img/decorations/avent-badiane.svg',
        'img/decorations/avent-baies.svg',
        'img/decorations/avent-sapin.svg',
    ],
    'creator' => [
        'name' => 'Estelle',
        'label' => 'Réalisation',
        'url' => 'https://dev.estelle-pratlong.fr/',
    ],
    'legal' => [
        'publisher' => '',
        'publication_director' => '',
        'address' => '',
        'host_name' => '',
        'host_address' => '',
        'host_contact' => '',
    ],
];

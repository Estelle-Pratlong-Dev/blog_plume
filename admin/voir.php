<?php
require __DIR__ . '/_securite.php';
exiger_admin();

// Afficher le gabarit du récit dans une session d’administration.
$isAdminPreview = true;
require __DIR__ . '/../pages/lecture.php';

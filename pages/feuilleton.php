<?php
// L’ancienne rubrique est retirée ; les favoris retrouvent l’accueil du blog.
require __DIR__ . '/../includes/bootstrap.php';
header('Location: ' . url('index.php'), true, 301);
exit;
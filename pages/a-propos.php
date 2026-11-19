<?php
// Le parcours de L . Yann est présenté sur la page d’accueil.
require __DIR__ . '/../includes/bootstrap.php';
header('Location: ' . url('index.php#titre-parcours'), true, 301);
exit;

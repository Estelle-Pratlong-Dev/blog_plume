<?php
// Compatibilité du premier lien de la case du 25.
require __DIR__ . '/../includes/bootstrap.php';
header('Location: ' . url('pages/lecture.php?texte=avent-25'), true, 301);
exit;
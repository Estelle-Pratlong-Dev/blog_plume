<?php
/* $site vient explicitement du bootstrap de la page appelante.
 * Seuls les contenus publiés et accessibles à la date courante quittent le dépôt.
 */
require_once __DIR__ . '/../includes/contenus.php';
return textes_publics($site);

<?php
// Ce fichier est un wrapper Dolibarr qui affiche roundcube_iframe.php dans une iframe

require '../../main.inc.php'; 

$langs->load("admin");

$title = "Webmail Roundcube";
llxHeader('', $title);

$url = dol_buildpath("/custom/roundcubemodule/roundcube_iframe.php", 1);

print '<div class="center">';
print '<iframe src="' . $url . '" width="100%" height="800px" frameborder="0"></iframe>';
print '</div>';

llxFooter();

<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("mailboxmodule@mailboxmodule");
$id = $user->id;

// Vérification des identifiants existants
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user_roundcube WHERE fk_user = ".((int)$id);
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    // Connexion automatique
    $secret = 'MyAx37okNmcBQWxsVIGDW29WDXiiuRkqZVZJQ364oyGFjCDzTvSznzQflQvsYpdW';
    // Utilisation de DOL_URL_ROOT pour une URL dynamique
    $redirect_url = DOL_URL_ROOT.'/custom/roundcubemodule/roundcube/?_autologin=1&dolibarr_id='.$id.'&secret='.urlencode($secret);
    header("Location: ".$redirect_url);
    exit;
} else {
    // Configuration requise
    header("Location: ".dol_buildpath('/custom/mailboxmodule/card_mails.php?id='.$id, 2));
    exit;
}
?>
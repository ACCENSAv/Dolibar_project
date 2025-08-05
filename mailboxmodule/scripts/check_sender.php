<?php
// Pas de session ni affichage HTML
define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);

require '../../../main.inc.php'; 

header('Content-Type: application/json');

global $db;


$email = $_POST['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['found' => false, 'message' => 'Email invalide']);
    exit;
}


$sql = 'SELECT rowid, nom FROM '.MAIN_DB_PREFIX.'societe WHERE email = \''. $db->escape($email) .'\' AND entity IN ('.getEntity('societe').') LIMIT 1';

$resql = $db->query($sql);

if ($resql) {
    if ($db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        echo json_encode([
            'found' => true,
            'name' => $obj->nom,
            'id' => $obj->rowid,
            'message' => 'Tiers trouvé'
        ]);
        exit;
    } else {
        echo json_encode(['found' => false, 'message' => 'Tiers non trouvé']);
        exit;
    }
} else {
    echo json_encode(['found' => false, 'message' => 'Erreur base de données']);
    exit;
}
?>
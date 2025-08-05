<?php

define('NOLOGIN', 1);

if (!defined('DOL_ROOT_PATH')) {
    define('DOL_ROOT_PATH', '../../../');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once DOL_ROOT_PATH . 'main.inc.php';

global $db;

$active_modules = [];
$debug_log_content = "Début du log Dolibarr Modules:\n";

$module_map = [
    'MAIN_MODULE_SOCIETE' => ['value' => 'thirdparty', 'label' => 'Tiers'],
    'MAIN_MODULE_CONTACT' => ['value' => 'contact', 'label' => 'Contact'], 
    'MAIN_MODULE_PROJET' => ['value' => 'project', 'label' => 'Projet / Opportunité'],
    'MAIN_MODULE_PROPALE' => ['value' => 'propal', 'label' => 'Proposition commerciale'],
    'MAIN_MODULE_COMMANDE' => ['value' => 'commande', 'label' => 'Commande client'],
    'MAIN_MODULE_EXPEDITION' => ['value' => 'expedition', 'label' => 'Expédition'],
    'MAIN_MODULE_CONTRAT' => ['value' => 'contract', 'label' => 'Contrat'],
    'MAIN_MODULE_FICHEINTER' => ['value' => 'fichinter', 'label' => 'Intervention'],
    'MAIN_MODULE_TICKET' => ['value' => 'ticket', 'label' => 'Ticket'],
    'MAIN_MODULE_FOURNISSEUR' => ['value' => 'supplier_order', 'label' => 'Commande Fournisseur'],
    'MAIN_MODULE_SUPPLIERPROPOSAL' => ['value' => 'supplier_proposal', 'label' => 'Proposition fournisseur'],
    'MAIN_MODULE_FACTUREFOURN' => ['value' => 'supplier_invoice', 'label' => 'Facture fournisseur'],
    'MAIN_MODULE_RECEPTION' => ['value' => 'reception', 'label' => 'Réception'],
    'MAIN_MODULE_FACTURE' => ['value' => 'invoice', 'label' => 'Facture client'],
    'MAIN_MODULE_SALARIES' => ['value' => 'salary', 'label' => 'Salaire'],
    'MAIN_MODULE_LOAN' => ['value' => 'loan', 'label' => 'Emprunt'],
    'MAIN_MODULE_DON' => ['value' => 'don', 'label' => 'Don'],
    'MAIN_MODULE_HOLIDAY' => ['value' => 'holiday', 'label' => 'Congé'],
    'MAIN_MODULE_EXPENSEREPORT' => ['value' => 'expensereport', 'label' => 'Note de frais'],
    'MAIN_MODULE_USER' => ['value' => 'user', 'label' => 'Utilisateur'],
    'MAIN_MODULE_USER' => ['value' => 'usergroup', 'label' => 'Groupe'],
    'MAIN_MODULE_ADHERENT' => ['value' => 'adherent', 'label' => 'Adhérent'],
    'MAIN_MODULE_AGENDA' => ['value' => 'event', 'label' => 'Agenda / Événement'],
    'MAIN_MODULE_COMPTABILITE' => ['value' => 'accounting', 'label' => 'Comptabilité'],
    'MAIN_MODULE_KJRAFFAIRE' => ['value' => 'affaire', 'label' => 'Affaires'],
];

foreach ($module_map as $constant_name => $details) {
    if ($constant_name === 'MAIN_MODULE_CONTACT') {
        $sql_check_tiers = "SELECT value FROM " . MAIN_DB_PREFIX . "const WHERE name = 'MAIN_MODULE_SOCIETE'";
        $res_check_tiers = $db->query($sql_check_tiers);
        $tiers_active = false;
        if ($res_check_tiers) {
            $obj_tiers = $db->fetch_object($res_check_tiers);
            if ($obj_tiers && $obj_tiers->value == 1) {
                $tiers_active = true;
            }
        }

        if ($tiers_active) {
            $active_modules[] = [
                'value' => $details['value'],
                'label' => $details['label']
            ];
            $debug_log_content .= "Module AJOUTÉ: " . $details['label'] . " (dépend de Tiers)\n";
        } else {
            $debug_log_content .= "Module Contact NON AJOUTÉ (Tiers non actif).\n";
        }
        $debug_log_content .= "---\n";
        continue; // Passer au prochain élément de la boucle
    }

    // Logique normale pour les autres modules
    $sql = "SELECT value FROM " . MAIN_DB_PREFIX . "const WHERE name = '" . $db->escape($constant_name) . "'";
    $res = $db->query($sql);

    $debug_log_content .= "Vérification constante: " . $constant_name . "\n";
    $debug_log_content .= "Requête SQL: " . $sql . "\n";

    if ($res) {
        $obj = $db->fetch_object($res);
        if ($obj) {
            $debug_log_content .= "Trouvé dans la BDD, valeur: " . $obj->value . " (Attendu: 1)\n";
            if ($obj->value == 1) {
                $active_modules[] = [
                    'value' => $details['value'],
                    'label' => $details['label']
                ];
                $debug_log_content .= "Module AJOUTÉ: " . $details['label'] . "\n";
            }
        } else {
            $debug_log_content .= "Constante NON TROUVÉE dans la BDD.\n";
        }
    } else {
        $debug_log_content .= "Échec de la requête SQL: " . $db->error() . "\n";
    }
    $debug_log_content .= "---\n";
}


header('Content-Type: application/json');
echo json_encode($active_modules);
exit;

?>
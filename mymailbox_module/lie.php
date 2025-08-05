<?php
require '../../main.inc.php';

// Récupération des paramètres
$module_type = GETPOST('module', 'alpha'); // Renommé pour éviter la confusion avec les objets modules Dolibarr
$object_id = (int) GETPOST('id', 'int');
$param_track_id = GETPOST('track_id', 'alpha'); 

// Vérification minimale des paramètres
if (empty($module_type) || $object_id <= 0) {
    dol_print_error('', $langs->trans("ErrorMissingParameters"));
    exit;
}

// Tableau de correspondance pour les classes et fichiers de Dolibarr
// Ajoutez ici tous les modules pour lesquels vous voulez cette ergonomie
$module_config = array(
    'project' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/projet/class/project.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php',
        'class_name' => 'Project',
        'head_function' => 'project_prepare_head',
        'trans_key' => 'Project'
    ),
    'invoice' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php',
        'class_name' => 'Facture',
        'head_function' => 'facture_prepare_head',
        'trans_key' => 'Invoice'
    ),
    'order' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php',
        'class_name' => 'Commande',
        'head_function' => 'commande_prepare_head',
        'trans_key' => 'Order'
    ),
    'propal' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/comm/propal/lib/propal.lib.php',
        'class_name' => 'Propal',
        'head_function' => 'propal_prepare_head',
        'trans_key' => 'CommercialProposal'
    ),
    'contract' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/contrat/lib/contrat.lib.php',
        'class_name' => 'Contrat',
        'head_function' => 'contrat_prepare_head',
        'trans_key' => 'Contract'
    ),
    'user' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/user/class/user.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php',
        'class_name' => 'User',
        'head_function' => 'user_prepare_head',
        'trans_key' => 'User'
    ),
    'societe' => array( // Pour être explicite si vous voulez différencier du 'thirdparty' précédent
        'class_file' => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php', // Ce fichier contient societe_prepare_head()
        'class_name' => 'Societe',
        'head_function' => 'societe_prepare_head',
        'trans_key' => 'ThirdParty'
    ),
    'thirdparty' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php',
        'class_name' => 'Societe',
        'head_function' => 'societe_prepare_head',
        'trans_key' => 'ThirdParty'
    ),
    'contact' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php',
        'class_name' => 'Contact',
        'head_function' => 'contact_prepare_head',
        'trans_key' => 'Contact'
    ),
    'expedition' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/expedition/lib/expedition.lib.php',
        'class_name' => 'Expedition',
        'head_function' => 'expedition_prepare_head',
        'trans_key' => 'Shipment'
    ),
    'fichinter' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/fichinter/lib/fichinter.lib.php',
        'class_name' => 'Fichinter',
        'head_function' => 'fichinter_prepare_head',
        'trans_key' => 'Intervention'
    ),
    'ticket' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php',
        'class_name' => 'Ticket',
        'head_function' => 'ticket_prepare_head',
        'trans_key' => 'Ticket'
    ),
    'supplier_proposal' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/fourn/propale/class/propale.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php',
        'class_name' => 'Fourn\Propale\Propale',
        'head_function' => 'fournpropal_prepare_head',
        'trans_key' => 'SupplierProposal'
    ),
    'supplier_order' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/fourn/commande/class/commande.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/fourn/commande/lib/commande.lib.php',
        'class_name' => 'Fourn\Commande\Commande',
        'head_function' => 'fourncommande_prepare_head',
        'trans_key' => 'SupplierOrder'
    ),
    'supplier_invoice' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php',
        'class_name' => 'FactureFournisseur',
        'head_function' => 'facturefourn_prepare_head',
        'trans_key' => 'SupplierInvoice'
    ),
    'reception' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/fourn/reception/class/reception.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/fourn/reception/lib/reception.lib.php',
        'class_name' => 'Fourn\Reception\Reception',
        'head_function' => 'reception_prepare_head',
        'trans_key' => 'Receipt'
    ),
    'salary' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/salaries/class/salaries.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php',
        'class_name' => 'Salaries',
        'head_function' => 'salary_prepare_head',
        'trans_key' => 'Salary'
    ),
    'loan' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/loan/lib/loan.lib.php',
        'class_name' => 'Loan',
        'head_function' => 'loan_prepare_head',
        'trans_key' => 'Loan'
    ),
    'don' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/don/class/don.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php',
        'class_name' => 'Don',
        'head_function' => 'donation_prepare_head',
        'trans_key' => 'Donation'
    ),
    'holiday' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php',
        'class_name' => 'Holiday',
        'head_function' => 'holiday_prepare_head',
        'trans_key' => 'Holiday'
    ),
    'expensereport' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/expensereport/lib/expensereport.lib.php',
        'class_name' => 'ExpenseReport',
        'head_function' => 'expensereport_prepare_head',
        'trans_key' => 'ExpenseReport'
    ),
    'usergroup' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php',
        'class_name' => 'UserGroup',
        'head_function' => 'group_prepare_head',
        'trans_key' => 'UserGroup'
    ),
    'adherent' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/adherents/lib/adherent.lib.php',
        'class_name' => 'Adherent',
        'head_function' => 'adherent_prepare_head',
        'trans_key' => 'Member'
    ),
    'event' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/comm/action/lib/actioncomm.lib.php',
        'class_name' => 'ActionComm',
        'head_function' => 'actioncomm_prepare_head',
        'trans_key' => 'ActionComm'
    ),
    'accounting' => array(
        'class_file' => DOL_DOCUMENT_ROOT.'/compta/accounting/class/accountingaccount.class.php',
        'lib_file' => '', // Pas de fichier de librairie spécifique pour l'entête
        'class_name' => 'AccountingAccount',
        'head_function' => 'accountingaccount_prepare_head',
        'trans_key' => 'Accounting'
    ),
    'affaire' => array(
        // Ce module est souvent personnalisé, les chemins et noms de classes peuvent varier.
        // Assurez-vous d'ajuster les chemins et le nom de la classe en fonction de votre installation.
        'class_file' => DOL_DOCUMENT_ROOT.'/custom/affaire/class/affaire.class.php',
        'lib_file' => DOL_DOCUMENT_ROOT.'/custom/affaire/lib/affaire.lib.php',
        'class_name' => 'Affaire',
        'head_function' => 'affaire_prepare_head',
        'trans_key' => 'Affaire'
    )
);

// Vérifier si le type de module est supporté
if (!isset($module_config[$module_type])) {
    dol_print_error('', $langs->trans("ErrorModuleNotSupported") . ': ' . $module_type);
    exit;
}

$current_module = $module_config[$module_type];

// Inclure la classe spécifique et le fichier de librairie de l'objet
require_once $current_module['class_file'];
if (!empty($current_module['lib_file'])) {
    require_once $current_module['lib_file'];
}
$object = new $current_module['class_name']($db);

// Charger l'objet
if ($object->fetch($object_id) <= 0) {
    dol_print_error($db, $langs->trans("ErrorRecordNotFound"));
    exit;
}

// Chargement des langues
$langs->load($current_module['trans_key']); // Charge le fichier de langue du module spécifique
$langs->load("errors");
$langs->load("mails");
$langs->load("other"); // Pour des termes génériques comme "Date"

// Entête de page
llxHeader('', $langs->trans("LinkedMails") . ' - ' . $object->ref); // Utilisez la référence de l'objet pour le titre

// Affichage de l'entête de la fiche Dolibarr (onglets, etc.)
$head = $current_module['head_function']($object);
print dol_get_fiche_head($head, 'mailtab', $langs->trans($current_module['trans_key']), -1, $module_type);

// Affichage du bandeau de l'objet (informations principales)
$linkback = ''; 
dol_banner_tab($object, 'rowid', $linkback, ($user->socid ? 0 : 1)); // rowid est souvent l'ID principal
?>

<style>
/* Votre CSS original - je ne modifie rien */
.fichecenter { margin: 10px 0; }
.container-flex { display: flex; gap: 20px; margin-top: 20px; }
#email_list_table_container { flex: 1; min-width: 40%; }
#mail_content_display { flex: 1; border: 1px solid #ddd; padding: 15px; background: #f8f8f8; display: none; }
.mail-subject-link { color: #333; text-decoration: none; }
.mail-subject-link:hover { text-decoration: underline; }
.close-email { float: right; cursor: pointer; color: #666; }
.mail-displayed #email_list_table_container { max-width: 40%; }
.mail-displayed #mail_content_display { display: block; }
</style>

<div class="fichecenter">
    <div class="underbanner clearboth"></div>
    <table class="border centpercent tableforfield">
        <?php
        
        if ($module_type === 'project') {
            echo '<tr><td class="titlefield">'.$langs->trans("Project").'</td><td>'.dol_escape_htmltag($object->title).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'invoice') {
            echo '<tr><td class="titlefield">'.$langs->trans("Invoice").'</td><td>'.dol_escape_htmltag($object->ref).'</td></td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Total").'</td><td>'.price($object->total_ttc).'</td></tr>';
            if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'order') {
            echo '<tr><td class="titlefield">'.$langs->trans("Order").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Total").'</td><td>'.price($object->total_ttc).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'propal') {
            echo '<tr><td class="titlefield">'.$langs->trans("CommercialProposal").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Total").'</td><td>'.price($object->total_ttc).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'contract') {
            echo '<tr><td class="titlefield">'.$langs->trans("Contract").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'user') {
            echo '<tr><td class="titlefield">'.$langs->trans("Login").'</td><td>'.dol_escape_htmltag($object->login).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("FullName").'</td><td>'.dol_escape_htmltag($object->firstname . ' ' . $object->lastname).'</td></tr>';
        } elseif ($module_type === 'societe' || $module_type === 'thirdparty') {
            echo '<tr><td class="titlefield">'.$langs->trans("NatureOfThirdParty").'</td><td>'.$object->getTypeUrl(1).'</td></tr>';
            if ($object->client) {
                echo '<tr><td class="titlefield">'.$langs->trans('CustomerCode').'</td><td>'.dol_escape_htmltag($object->code_client).'</td></tr>';
            }
        } elseif ($module_type === 'contact') {
            echo '<tr><td class="titlefield">'.$langs->trans("Contact").'</td><td>'.dol_escape_htmltag($object->getFullName($langs)).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Societe").'</td><td>'.$object->show_thirdparty(1).'</td></tr>';
        } elseif ($module_type === 'expedition') {
            echo '<tr><td class="titlefield">'.$langs->trans("ShipmentRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
            if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'fichinter') {
            echo '<tr><td class="titlefield">'.$langs->trans("InterventionRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
            if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'ticket') {
            echo '<tr><td class="titlefield">'.$langs->trans("TicketRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
            if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'supplier_proposal') {
            echo '<tr><td class="titlefield">'.$langs->trans("SupplierProposal").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Total").'</td><td>'.price($object->total_ttc).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'supplier_order') {
            echo '<tr><td class="titlefield">'.$langs->trans("SupplierOrder").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Total").'</td><td>'.price($object->total_ttc).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'supplier_invoice') {
            echo '<tr><td class="titlefield">'.$langs->trans("SupplierInvoice").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Total").'</td><td>'.price($object->total_ttc).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'reception') {
            echo '<tr><td class="titlefield">'.$langs->trans("ReceiptRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
             if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        } elseif ($module_type === 'salary') {
            echo '<tr><td class="titlefield">'.$langs->trans("SalaryRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
        } elseif ($module_type === 'loan') {
            echo '<tr><td class="titlefield">'.$langs->trans("LoanRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
        } elseif ($module_type === 'don') {
            echo '<tr><td class="titlefield">'.$langs->trans("DonationRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
        } elseif ($module_type === 'holiday') {
            echo '<tr><td class="titlefield">'.$langs->trans("HolidayRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
        } elseif ($module_type === 'expensereport') {
            echo '<tr><td class="titlefield">'.$langs->trans("ExpenseReportRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
        } elseif ($module_type === 'usergroup') {
            echo '<tr><td class="titlefield">'.$langs->trans("Group").'</td><td>'.dol_escape_htmltag($object->nom).'</td></tr>';
        } elseif ($module_type === 'adherent') {
            echo '<tr><td class="titlefield">'.$langs->trans("MemberRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("FullName").'</td><td>'.dol_escape_htmltag($object->firstname . ' ' . $object->lastname).'</td></tr>';
        } elseif ($module_type === 'event') {
            echo '<tr><td class="titlefield">'.$langs->trans("Event").'</td><td>'.dol_escape_htmltag($object->label).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
        } elseif ($module_type === 'accounting') {
            echo '<tr><td class="titlefield">'.$langs->trans("AccountLabel").'</td><td>'.dol_escape_htmltag($object->label).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("AccountancyCode").'</td><td>'.dol_escape_htmltag($object->account_number).'</td></tr>';
        } elseif ($module_type === 'affaire') {
            echo '<tr><td class="titlefield">'.$langs->trans("AffaireRef").'</td><td>'.dol_escape_htmltag($object->ref).'</td></tr>';
            echo '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(3).'</td></tr>';
            if (!empty($object->socid)) {
                 require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                 $societe = new Societe($db);
                 if ($societe->fetch($object->socid) > 0) {
                     echo '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td>'.$societe->getNomUrl(1).'</td></tr>';
                 }
            }
        }
        // Ajoutez d'autres blocs 'elseif' pour les informations d'autres modules si nécessaire
        ?>
    </table>
</div>

<?php
print load_fiche_titre($langs->trans("LinkedMails"));
?>

<div class="fichecenter">
    <div class="container-flex" id="main_container">
        <div id="email_list_table_container">
            <?php
            // Requête SQL pour récupérer les e-mails liés à l'objet spécifié
            $sql = "SELECT m.rowid, m.subject, m.from_email, m.date_received, m.direction
                    FROM ".MAIN_DB_PREFIX."mailboxmodule_mail m
                    INNER JOIN ".MAIN_DB_PREFIX."element_element e ON e.fk_source = m.rowid
                    WHERE e.sourcetype = 'mailboxmodule_mail'
                    AND e.targettype = '".$db->escape($module_type)."'
                    AND e.fk_target = ".((int)$object_id)."
                    ORDER BY m.date_received DESC";

            $resql = $db->query($sql);

            if ($resql) {
                if ($db->num_rows($resql) > 0) {
                    print '<table class="noborder" width="100%">';
                    print '<tr class="liste_titre"><th>' . $langs->trans("Subject") . '</th><th>' . $langs->trans("Email") . '</th><th>' . $langs->trans("Date") . '</th><th>' . $langs->trans("Direction") . '</th></tr>';

                    while ($obj = $db->fetch_object($resql)) {
                        print '<tr id="mail_row_'.$obj->rowid.'">';
                        print '<td><a href="#" class="mail-subject-link" data-mail-id="'.$obj->rowid.'">'.dol_escape_htmltag($obj->subject).'</a></td>';
                        print '<td>'.dol_escape_htmltag($obj->from_email).'</td>';
                        print '<td>'.dol_print_date($db->jdate($obj->date_received), 'dayhour').'</td>';
                        print '<td>'.($obj->direction === 'sent' ? $langs->trans("Sent") : $langs->trans("Received")).'</td>';
                        print '</tr>';
                    }
                    print '</table>';
                } else {
                    print '<p>' . $langs->trans("NoLinkedMailsFound") . '</p>';
                }
            } else {
                dol_print_error($db); // Affiche l'erreur si la requête échoue
            }
            ?>
        </div>

        <div id="mail_content_display">
            <span class="close-email" id="close_email_display">&times;</span>
            <p><?php echo $langs->trans("SelectAnEmailToDisplayContent"); ?></p>
        </div>
    </div>
</div>

<script>
// VOTRE CODE JS ORIGINAL (je ne modifie rien)
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.mail-subject-link');
    const contentDiv = document.getElementById('mail_content_display');
    const container = document.getElementById('main_container');

    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const mailId = this.getAttribute('data-mail-id');

            document.querySelectorAll('#email_list_table_container tr').forEach(row => {
                row.style.backgroundColor = '';
            });
            this.parentElement.parentElement.style.backgroundColor = '#f0f0ff';

            contentDiv.innerHTML = 'Chargement...';
            container.classList.add('mail-displayed');

            fetch('view_mail.php?id=' + mailId) // Assurez-vous que view_mail.php est accessible et fonctionnel
                .then(response => response.text())
                .then(html => {
                    contentDiv.innerHTML = '<span class="close-email" id="close_email_display">&times;</span>' + html;
                    document.getElementById('close_email_display').onclick = closeMail;
                })
                .catch(err => {
                    contentDiv.innerHTML = 'Erreur de chargement';
                    console.error(err);
                });
        });
    });

    function closeMail() {
        container.classList.remove('mail-displayed');
        document.querySelectorAll('#email_list_table_container tr').forEach(row => {
            row.style.backgroundColor = '';
        });
    }

    document.getElementById('close_email_display').onclick = closeMail;
});
</script>

<?php
print dol_get_fiche_end();
llxFooter();
$db->close();
?>
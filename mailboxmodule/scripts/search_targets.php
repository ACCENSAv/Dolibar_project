<?php
define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);

require '../../../main.inc.php';

header('Content-Type: application/json');
global $db;
$q = trim(GETPOST('q', 'alpha'));
$type = trim(GETPOST('type', 'alpha'));

if (empty($q) || empty($type)) {
    echo json_encode([]);
    exit;
}

$max_results = 20;
$result = [];

try {
    if ($type === 'project') {
        $sql = "SELECT p.rowid as id, p.title as label
            FROM ".MAIN_DB_PREFIX."projet p
            LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = p.rowid
            WHERE pe.fk_object IS NULL
              AND p.title LIKE '%".$db->escape($q)."%'
              or p.ref LIKE '%".$db->escape($q)."%'
            ORDER BY p.datec DESC
            LIMIT $max_results";

    } elseif ($type === 'facture') {
        $sql = "SELECT rowid as id, CONCAT(ref, ' - ', IFNULL(label, '')) as label
                FROM ".MAIN_DB_PREFIX."facture
                WHERE ref LIKE '%".$db->escape($q)."%'
                    OR label LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'commande') {
        $sql = "SELECT rowid as id, CONCAT(ref, ' - ', IFNULL(note_private, '')) as label
                FROM ".MAIN_DB_PREFIX."commande
                WHERE ref LIKE '%".$db->escape($q)."%'
                    OR note_private LIKE '%".$db->escape($q)."%'
                ORDER BY date_creation DESC
                LIMIT $max_results";

    } elseif ($type === 'thirdparty') {
        $sql = "SELECT rowid as id, nom as label
                FROM ".MAIN_DB_PREFIX."societe
                WHERE nom LIKE '%".$db->escape($q)."%'
                    OR name_alias LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'user') {
        $sql = "SELECT rowid as id, CONCAT(login, ' - ', lastname, ' ', firstname) as label
                FROM ".MAIN_DB_PREFIX."user
                WHERE login LIKE '%".$db->escape($q)."%'
                    OR firstname LIKE '%".$db->escape($q)."%'
                    OR lastname LIKE '%".$db->escape($q)."%'
                ORDER BY rowid DESC
                LIMIT $max_results";

    } elseif ($type === 'usergroup') {
        $sql = "SELECT rowid as id, nom as label
                FROM ".MAIN_DB_PREFIX."usergroup
                WHERE nom LIKE '%".$db->escape($q)."%'
                ORDER BY rowid DESC
                LIMIT $max_results";

    } elseif ($type === 'adherent') {
        $sql = "SELECT rowid as id, CONCAT(lastname, ' ', firstname) as label
                FROM ".MAIN_DB_PREFIX."adherent
                WHERE firstname LIKE '%".$db->escape($q)."%'
                    OR lastname LIKE '%".$db->escape($q)."%'
                ORDER BY rowid DESC
                LIMIT $max_results";

    } elseif ($type === 'holiday') {
        $sql = "SELECT rowid as id, motif as label
                FROM ".MAIN_DB_PREFIX."holiday
                WHERE motif LIKE '%".$db->escape($q)."%'
                ORDER BY date_create DESC
                LIMIT $max_results";

    } elseif ($type === 'expensereport') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."expensereport
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY date_create DESC
                LIMIT $max_results";

    } elseif ($type === 'propal') {
        $sql = "SELECT rowid as id, CONCAT(ref, ' - ', note) as label
                FROM ".MAIN_DB_PREFIX."propal
                WHERE ref LIKE '%".$db->escape($q)."%'
                    OR note LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'contract') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."contrat
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'ticket') {
        $sql = "SELECT rowid as id, subject as label
                FROM ".MAIN_DB_PREFIX."ticket
                WHERE subject LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'fichinter') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."fichinter
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'supplier_proposal') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."supplier_proposal
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'supplier_order') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."commande_fournisseur
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY date_creation DESC
                LIMIT $max_results";

    } elseif ($type === 'supplier_invoice') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."facture_fourn
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'reception') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."reception
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY date_creation DESC
                LIMIT $max_results";

    } elseif ($type === 'salary') {
        $sql = "SELECT rowid as id, label as label
                FROM ".MAIN_DB_PREFIX."salary
                WHERE label LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'loan') {
        $sql = "SELECT rowid as id, label as label
                FROM ".MAIN_DB_PREFIX."loan
                WHERE label LIKE '%".$db->escape($q)."%'
                ORDER BY dateo DESC
                LIMIT $max_results";

    } elseif ($type === 'don') {
        $sql = "SELECT rowid as id, ref as label
                FROM ".MAIN_DB_PREFIX."don
                WHERE ref LIKE '%".$db->escape($q)."%'
                ORDER BY datec DESC
                LIMIT $max_results";

    } elseif ($type === 'event') {
        $sql = "SELECT id as id, label
                FROM ".MAIN_DB_PREFIX."actioncomm
                WHERE label LIKE '%".$db->escape($q)."%'
                ORDER BY datep DESC
                LIMIT $max_results";

    } elseif ($type === 'accounting') {
        $sql = "SELECT rowid as id, piece as label
                FROM ".MAIN_DB_PREFIX."accounting_bookkeeping
                WHERE piece LIKE '%".$db->escape($q)."%'
                ORDER BY date_doc DESC
                LIMIT $max_results";

    } elseif ($type === 'affaire') {
        $sql = "SELECT p.rowid as id, p.title as label
            FROM ".MAIN_DB_PREFIX."projet p
            INNER JOIN ".MAIN_DB_PREFIX."projet_extrafields pe ON pe.fk_object = p.rowid
            WHERE p.title LIKE '%".$db->escape($q)."%'
            ORDER BY p.datec DESC
            LIMIT $max_results";

    } elseif ($type === 'contact') { 
        $sql = "SELECT s.rowid as id, CONCAT(s.lastname, ' ', s.firstname, ' (', COALESCE(so.nom, '')) as label
                FROM ".MAIN_DB_PREFIX."socpeople as s
                LEFT JOIN ".MAIN_DB_PREFIX."societe as so ON s.fk_soc = so.rowid
                WHERE s.firstname LIKE '%".$db->escape($q)."%'
                    OR s.lastname LIKE '%".$db->escape($q)."%'
                    OR s.email LIKE '%".$db->escape($q)."%'
                    OR so.nom LIKE '%".$db->escape($q)."%'
                ORDER BY s.datec DESC
                LIMIT $max_results";

    } else {
        echo json_encode([]);
        exit;
    }

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $result[] = [
                'id'    => (int)$obj->id,
                'label' => $obj->label
            ];
        }
    }
} catch (Throwable $e) {
    dol_syslog('Erreur search_targets.php : '.$e->getMessage(), LOG_ERR);
    echo json_encode([]);
    exit;
}

echo json_encode($result);
exit;
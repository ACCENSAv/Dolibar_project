<?php

define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);

require '../../../main.inc.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

header('Content-Type: application/json');
global $db, $conf;

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception("Pas de données reçues");

    $uid = $db->escape($input['uid']);
    $mbox = $db->escape($input['mbox']);
    $rc_user_email = $db->escape($input['rc_user_email']);
    $subject = $db->escape($input['subject']);
    $date = $db->escape($input['date']);
    $from_raw = $input['from'];
    $raw_email_content = $input['raw_email'] ?? null;
    $attachments = $input['attachments'] ?? [];
    $links = $input['links'] ?? [];
    $direction = $input['direction'] ?? 'received';
    if (!is_array($links)) $links = [];

    if (empty($from_raw)) throw new Exception("Champ 'from' vide ou absent");
    if (empty($raw_email_content)) throw new Exception("Contenu brut de l'e-mail manquant.");

    if (preg_match('/<([^>]+)>/', $from_raw, $matches)) {
        $from_email = $db->escape(trim($matches[1]));
    } else {
        $from_email = $db->escape(trim($from_raw));
    }
    if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email d'expéditeur invalide");
    }

    // --- Sauvegarde du .eml
    $data_dir = DOL_DOCUMENT_ROOT . '/custom/mailboxmodule/data/mails/';
    if (!is_dir($data_dir)) mkdir($data_dir, 0775, true);

    $filename_base = preg_replace('/[^\w\s\-\.]/', '', $subject);
    $filename_base = substr($filename_base, 0, 50);
    if (empty($filename_base)) $filename_base = 'email_' . md5($uid . microtime());
    $filename_eml = $filename_base . '_' . time() . '.eml';
    $full_file_path = $data_dir . $filename_eml;

    if (file_put_contents($full_file_path, $raw_email_content) === false) {
        throw new Exception("Impossible d'écrire le fichier EML : " . $full_file_path);
    }
    $relative_file_path = 'custom/mailboxmodule/data/mails/' . $filename_eml;

    // --- Recherche du tiers
    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe WHERE email = '".$from_email."'";
    $resql = $db->query($sql);
    if (!$resql) throw new Exception($db->lasterror());

    $fk_soc = null;
    $tiers_name = '';
    if ($db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $fk_soc = $obj->rowid;
        $tiers_name = $obj->nom;
    } else {
        $tiers_name = "Aucun tiers trouvé";
    }

    // --- Insertion du mail
    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mailboxmodule_mail
        (message_id, subject, from_email, date_received, file_path, fk_soc, imap_mailbox, imap_uid,direction)
        VALUES (
            '".$uid."',
            ".($subject !== '' ? "'".$subject."'" : "NULL").",
            '".$from_email."',
            ".($date !== '' ? "'".$date."'" : "NULL").",
            '".$db->escape($relative_file_path)."',
            ".($fk_soc !== null ? intval($fk_soc) : "NULL").",
            '".$mbox."',
            ".(int)$uid.",
            '".$db->escape($direction)."'
        )";
    if (!$db->query($sql_insert)) throw new Exception("Erreur insertion mail : ".$db->lasterror());

    $new_mail_id = $db->last_insert_id(MAIN_DB_PREFIX."mailboxmodule_mail", 'rowid');

    // --- Sauvegarde pièces jointes
    $attachments_dir = DOL_DOCUMENT_ROOT . '/custom/mailboxmodule/data/fichier_join/';
    if (!is_dir($attachments_dir)) mkdir($attachments_dir, 0775, true);

    $nb_attachments = 0;
    foreach ($attachments as $att) {
        $src = $att['path'] ?? '';
        $name = $att['name'] ?? 'unknown.bin';
        if ($src && file_exists($src)) {
            $safe_name = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $name);
            $dest_filename = $uid . '_' . $safe_name;
            $dest_path = $attachments_dir . $dest_filename;

            if (copy($src, $dest_path)) {
                $relative_path = 'custom/mailboxmodule/data/fichier_join/' . $dest_filename;

                $sql_att = "INSERT INTO ".MAIN_DB_PREFIX."mailboxmodule_attachment
                    (fk_mail, filename, filepath)
                    VALUES (
                        ".intval($new_mail_id).",
                        '".$db->escape($safe_name)."',
                        '".$db->escape($relative_path)."'
                    )";
                if ($db->query($sql_att)) $nb_attachments++;
            }
        }
    }

    // --- Cas 1 : Aucun lien, mais un tiers trouvé
    if (count($links) === 0 && $fk_soc !== null) {
        save_files_to_module('societe', $fk_soc, $full_file_path, $filename_eml, $subject, $attachments, $conf, $db);
    }

    // --- Cas 2 & 3 : un ou plusieurs modules
    foreach ($links as $link) {
        $link_type = $db->escape($link['type']);
        $link_id = (int)$link['id'];

        if ($link_type && $link_id > 0) {
             $allowed_target_types = [
            'thirdparty',         // Tiers
            'contact',            // Contact
            'project',            // Projet / Opportunité
            'propal',             // Propositions commerciales
            'commande',           // Commandes clients
            'expedition',         // Expéditions
            'contract',           // Contrats
            'fichinter',          // Interventions
            'ticket',             // Tickets
            'partnership',        // Partenariats
            'supplier_proposal',  // Propositions fournisseurs
            'supplier_order',     // Commandes fournisseurs
            'supplier_invoice',   // Factures fournisseurs
            'reception',          // Réceptions fournisseurs
            'invoice',            // Factures clients
            'salary',             // Salaires
            'loan',               // Emprunts
            'don',                // Dons
            'holiday',            // Congés
            'expensereport',      // Notes de frais
            'user',               // Utilisateur
            'usergroup',          // Groupe
            'adherent',           // Adhérent
            'event',              // Agenda / Événements
            'accounting',         // Comptabilité (simplifiée ou double)
            'affaire'             // Module Affaires (s’il est personnalisé)
            ];

            if (!in_array($link_type, $allowed_target_types)) continue;

            $sql_link = "INSERT INTO ".MAIN_DB_PREFIX."element_element
                (fk_source, sourcetype, fk_target, targettype, relationtype)
                VALUES (
                    $new_mail_id,
                    'mailboxmodule_mail',
                    $link_id,
                    '".$link_type."',
                    'manual'
                )";
            $db->query($sql_link);

            save_files_to_module($link_type, $link_id, $full_file_path, $filename_eml, $subject, $attachments, $conf, $db);
        }
    }

    $msg = "Mail UID=$uid enregistré";
    if ($fk_soc) $msg .= " et lié au tiers : $tiers_name (ID=$fk_soc)";
    else $msg .= " mais sans tiers trouvé";
    $msg .= ". Fichier EML sauvegardé. ";
    if ($nb_attachments > 0) $msg .= "$nb_attachments pièce(s) jointe(s) ajoutée(s).";
    if (count($links) > 0) $msg .= " Mail lié à ".count($links)." module(s).";

    echo json_encode(['status' => 'OK', 'message' => $msg]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'ERROR', 'message' => $e->getMessage()]);
}

exit();



function save_files_to_module($type, $id, $eml_src_path, $eml_filename, $subject, $attachments, $conf, $db) {
    $target_dir = DOL_DATA_ROOT.'/'.$type.'/'.$id.'/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $dest_filename_eml = time().'_'.$eml_filename;
    $dest_path_eml = $target_dir . $dest_filename_eml;

    foreach ($attachments as $att) {
        $src = $att['path'] ?? '';
        $name = $att['name'] ?? 'unknown.bin';

        if ($src && file_exists($src)) {
            $safe_name = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $name);
            $dest_filename_att = time().'_'.$safe_name;
            $dest_path_att = $target_dir . $dest_filename_att;

            // Vérifier si ce fichier existe déjà pour ce module
            $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."ecm_files 
                          WHERE src_object_type = '".$db->escape($type)."'
                          AND src_object_id = ".((int)$id)."
                          AND label = '".$db->escape($safe_name)."'";
            $resql = $db->query($sql_check);
            if ($resql && $db->num_rows($resql) == 0) {
                // Si pas encore enregistré pour ce module, on le copie et insère
                if (copy($src, $dest_path_att)) {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_files 
                        (ref, label, entity, filepath, filename, src_object_type, src_object_id, date_c, tms)
                        VALUES (
                            '".$db->escape($dest_filename_att)."',
                            '".$db->escape($safe_name)."',
                            ".(int) $conf->entity.",
                            '".$db->escape($type.'/'.$id)."',
                            '".$db->escape($dest_filename_att)."',
                            '".$db->escape($type)."',
                            $id,
                            '".$db->idate(dol_now())."',
                            '".$db->idate(dol_now())."'
                        )";
                    $db->query($sql);
                }
            }
        }
    }
}
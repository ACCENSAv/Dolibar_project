<?php

require '../../main.inc.php';
require 'vendor/autoload.php';

use ZBateson\MailMimeParser\MailMimeParser;

$id = GETPOST('id', 'int');
$id = (int) $id;

$sql = "SELECT rowid, subject, from_email, date_received, file_path, imap_mailbox, imap_uid 
        FROM llx_mailboxmodule_mail WHERE rowid = " . $id;
$resql = $db->query($sql);


if ($resql && ($obj = $db->fetch_object($resql))) {

    print '<h3>' . dol_escape_htmltag($obj->subject) . '</h3>';
    print '<p><b>Expéditeur :</b> ' . dol_escape_htmltag($obj->from_email) . '</p>';
    print '<p><b>Date reçue :</b> ' . dol_print_date($db->jdate($obj->date_received), 'dayhour') . '</p>';

    $roundcube_base_url = DOL_URL_ROOT . '/custom/roundcubemodule/roundcube/?_autologin=1&dolibarr_id=' . $user->id . '&secret=' . urlencode('MyAx37okNmcBQWxsVIGDW29WDXiiuRkqZVZJQ364oyGFjCDzTvSznzQflQvsYpdV');
    $uid = (int) $obj->imap_uid;
    $mailbox = urlencode($obj->imap_mailbox);

    if (!empty($obj->file_path) && file_exists(DOL_DOCUMENT_ROOT . '/' . $obj->file_path)) {
        $fullpath = DOL_DOCUMENT_ROOT . '/' . $obj->file_path;
        $extension = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));

        print '<h4>Infos du mail :</h4>';

        if ($extension === 'eml') {
            //  Cas EML
            $emlContent = file_get_contents($fullpath);
            $parser = new MailMimeParser();
            $message = $parser->parse($emlContent, false);

            $from = dol_escape_htmltag($message->getHeaderValue('from'));
            $to = dol_escape_htmltag($message->getHeaderValue('to'));
            $subject = dol_escape_htmltag($message->getHeaderValue('subject'));
            $htmlBody = $message->getHtmlContent();

            if (empty($htmlBody)) {
                $textBody = $message->getTextContent(); // plain/text
                $textBody = mb_convert_encoding($textBody, 'UTF-8', 'auto');


                $htmlBody = nl2br(htmlspecialchars($textBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            } else {
                $htmlBody = mb_convert_encoding($htmlBody, 'UTF-8', 'auto');
            }
            $attachments = $message->getAllAttachmentParts();

        } elseif ($extension === 'msg') {
            //  MSG
            $python = "C:\\Users\\Guy Keny\\AppData\\Local\\Programs\\Python\\Python312\\python.exe";
            $script = DOL_DOCUMENT_ROOT . "/custom/mymailbox_module/parser_msg.py";
            $cmd = "\"$python\" \"$script\" \"$fullpath\"";

            $output = shell_exec($cmd);
            $output = mb_convert_encoding($output, 'UTF-8', 'auto');
            $data = json_decode($output, true);

            if ($data !== null) {
                $from = dol_escape_htmltag($data['sender']);
                $to = dol_escape_htmltag($data['to']);
                $subject = dol_escape_htmltag($data['subject']);
                $htmlBody = nl2br(htmlspecialchars($data['body']));
                $attachments = $data['attachments'];
            } else {
                print '<p style="color:red;">Erreur: impossible de lire le fichier MSG.</p>';
                $from = $to = $subject = $htmlBody = '';
                $attachments = [];
            }
        } else {
            print '<p style="color:red;">Fichier inconnu (extension non supportée).</p>';
            $from = $to = $subject = $htmlBody = '';
            $attachments = [];
        }


        print '<p><b>From:</b> ' . $from . '</p>';
        print '<p><b>To:</b> ' . $to . '</p>';
        print '<p><b>Subject:</b> ' . $subject . '</p>';

        // Nettoyage du contenu
        $cleanBody = html_entity_decode(strip_tags($htmlBody), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanFrom = html_entity_decode($from, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanTo = html_entity_decode($to, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanSubject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanDate = dol_print_date($db->jdate($obj->date_received), 'dayhour');

        // Construction du message original AVEC encodage des sauts de ligne
        $originalMsg = "-------- Message original --------%0D%0A"
            . "De: " . rawurlencode($cleanFrom) . "%0D%0A"
            . "Objet: " . rawurlencode($cleanSubject) . "%0D%0A"
            . "Date: " . rawurlencode($cleanDate) . "%0D%0A%0D%0A"
            . rawurlencode($cleanBody);

        print '<div style="margin: 10px 0;">';
        // Bouton Répondre
        print '<a class="btn btn-primary" style="margin-right:5px;" target="_blank"
       href="' . $roundcube_base_url . '&_task=mail&_action=compose'
            . '&_to=' . rawurlencode($cleanFrom)
            . '&_subject=' . rawurlencode('Re: ' . $cleanSubject)
            . '&_body=%0D%0A%0D%0A' . $originalMsg . '">Répondre</a>';

        // Bouton Répondre à tous
        print '<a class="btn btn-primary" style="margin-right:5px;" target="_blank"
       href="' . $roundcube_base_url . '&_task=mail&_action=compose'
            . '&_to=' . rawurlencode($cleanFrom . (!empty($cleanTo) ? ',' . $cleanTo : ''))
            . '&_subject=' . rawurlencode('Re: ' . $cleanSubject)
            . '&_body=%0D%0A%0D%0A' . $originalMsg . '">Répondre à tous</a>';

        // Bouton Transférer
        print '<a class="btn btn-primary" style="margin-right:5px;" target="_blank"
       href="' . $roundcube_base_url . '&_task=mail&_action=compose'
            . '&_subject=' . rawurlencode('Fwd: ' . $cleanSubject)
            . '&_body=%0D%0A%0D%0A' . $originalMsg . '">Transférer</a>';

        // Bouton Supprimer
        print '<a class="btn btn-danger" style="margin-left:10px;" onclick="return confirm(\'Confirmer la suppression ?\');"
       href="delete.php?id=' . $obj->rowid . '">Supprimer</a>';
        print '</div>';

        //  Afficher le corps
        if ($htmlBody) {
            print '<div style="border:none; padding:10px; max-height:400px; overflow:auto; background:#fff; max-width:800px;">';
            print $htmlBody;
            print '</div>';
        }

        //  Pièces jointes
        if (!empty($attachments)) {
            print '<h4>Pièces jointes :</h4><ul>';
            foreach ($attachments as $att) {
                if ($extension === 'eml') {
                    print '<li>' . dol_escape_htmltag($att->getFilename()) . '</li>';
                } else { // msg
                    $filename = dol_escape_htmltag($att['filename']);
                    $relative = "attachments/" . rawurlencode($att['filename']);
                    print "<li><a href=\"$relative\" target=\"_blank\">$filename</a></li>";
                }
            }
            print '</ul>';
        }

    } else {
        print '<p><i>Fichier non trouvé ou chemin vide.</i></p>';
    }

} else {
    print '<p>Mail introuvable.</p>';
}

$db->close();

exit;
?>
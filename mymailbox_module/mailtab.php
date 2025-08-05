<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php'; // La classe Form est déjà chargée via main.inc.php
// Et surtout, showValueWithClipboardCPButton est dans functions.lib.php, qui est aussi chargé par main.inc.php

$socid = GETPOST('socid', 'int');

$object = new Societe($db);
if ($socid > 0) {
    $object->fetch($socid);
} else {

    $langs->load("errors");
    dol_print_error($db, $langs->trans("ErrorRecordNotFound"));
    exit;
}
$email = '';
if (!empty($object->email)) {
    $email = $object->email;
}
$langs->load("companies");

llxHeader('', 'Mails du tiers');

$head = societe_prepare_head($object);
print dol_get_fiche_head($head, 'mailtab', $langs->trans("ThirdParty"), -1, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');
?>

<style>
    .fichecenter { margin: 10px 0; }
    .container-flex { display: flex; gap: 20px; margin-top: 20px; }
    #email_list_table_container { flex: 1; min-width: 40%; }
    #mail_content_display { flex: 1; border: 1px solid #ddd; padding: 15px; background: #f8f8f8; display: none; }
    .liste { width: 100%; }
    .mail-subject-link { color: #333; text-decoration: none; }
    .mail-subject-link:hover { text-decoration: underline; }
    .close-email { float: right; cursor: pointer; color: #666; }
    .mail-displayed #email_list_table_container { max-width: 40%; }
    .mail-displayed #mail_content_display { display: block; }
    .border { border: 1px solid #ccc; }
    .centpercent { width: 100%; }
    .tableforfield { border-collapse: collapse; }
    .titlefield { background-color: #f0f0f0; padding: 5px; font-weight: bold; }
    .error { color: red; }
    .valignmiddle { vertical-align: middle; }
</style>

<div class="fichecenter">
    <div class="underbanner clearboth"></div>
    <table class="border centpercent tableforfield">
        <tr>
            <td class="titlefield"><?php echo $langs->trans('NatureOfThirdParty'); ?></td>
            <td><?php echo $object->getTypeUrl(1); ?></td>
        </tr>

        <?php if (getDolGlobalString('SOCIETE_USEPREFIX')) { ?>
            <tr>
                <td class="titlefield"><?php echo $langs->trans('Prefix'); ?></td>
                <td colspan="3"><?php echo $object->prefix_comm; ?></td>
            </tr>
        <?php } ?>

        <?php if ($object->client) { ?>
            <tr>
                <td class="titlefield"><?php echo $langs->trans('CustomerCode'); ?></td>
                <td colspan="3">
                    <?php
                    echo showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client)); 
                    $tmpcheck = $object->check_codeclient();
                    if ($tmpcheck != 0 && $tmpcheck != -5) {
                        echo ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
                    }
                    ?>
                </td>
            </tr>
        <?php } ?>

        <?php if ($object->fournisseur) { ?>
            <tr>
                <td class="titlefield"><?php echo $langs->trans('SupplierCode'); ?></td>
                <td colspan="3">
                    <?php
                    echo showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur)); 
                    $tmpcheck = $object->check_codefournisseur();
                    if ($tmpcheck != 0 && $tmpcheck != -5) {
                        echo ' <span class="error">('.$langs->trans("WrongSupplierCode").')';
                    }
                    ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php
print load_fiche_titre('Mails reçus pour : '.dol_escape_htmltag($object->name));
?>

<div class="fichecenter">
    <div class="container-flex" id="main_container">
        <div id="email_list_table_container">
            <?php
// ---- Mails reçus ----
$sql_received = "SELECT rowid, subject, from_email, date_received
    FROM ".MAIN_DB_PREFIX."mailboxmodule_mail
    WHERE fk_soc = ".((int) $socid)."
    AND direction = 'received'
    ORDER BY date_received DESC";

$resql_received = $db->query($sql_received);

if ($resql_received) {
    print '<h3>Mails reçus</h3>';
    print '<table class="noborder" width="100%" id="email_list_table">';
    print '<tr class="liste_titre">';
    print '<th>Sujet</th>';
    print '<th>Expéditeur</th>';
    print '<th>Date</th>';
    print '</tr>';

    while ($obj = $db->fetch_object($resql_received)) {
        print '<tr id="mail_row_'.$obj->rowid.'">';
        print '<td><a href="#" class="mail-subject-link" data-mail-id="'.$obj->rowid.'">'.dol_escape_htmltag($obj->subject).'</a></td>';
        print '<td>'.dol_escape_htmltag($obj->from_email).'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->date_received), 'dayhour').'</td>';
        print '</tr>';
    }

    print '</table>';
} else {
    dol_print_error($db);
}

  // ---- Mails envoyés ----
if (!empty($email)) {
    // Version alternative sans modification de structure
    $sql_sent = "SELECT rowid, subject, from_email, date_received, file_path
                FROM ".MAIN_DB_PREFIX."mailboxmodule_mail
                WHERE direction = 'sent'
                AND (from_email LIKE '%".$db->escape($email)."%' OR fk_soc = ".((int) $socid).")
                ORDER BY date_received DESC";

    $resql_sent = $db->query($sql_sent);

    if ($resql_sent) {
        print '<br><h3>Mails envoyés</h3>';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<th>Sujet</th>';
        print '<th>Expéditeur</th>';
        print '<th>Date</th>';
        print '</tr>';

        while ($obj = $db->fetch_object($resql_sent)) {
            print '<tr id="mail_row_'.$obj->rowid.'">';
            print '<td><a href="#" class="mail-subject-link" data-mail-id="'.$obj->rowid.'">'.dol_escape_htmltag($obj->subject).'</a></td>';
            print '<td>'.dol_escape_htmltag($obj->from_email).'</td>';
            print '<td>'.dol_print_date($db->jdate($obj->date_received), 'dayhour').'</td>';
            print '</tr>';
        }
        print '</table>';
    } else {
        dol_print_error($db);
    }
}
?>

        </div>

        <div id="mail_content_display">
            <span class="close-email" id="close_email_display">&times;</span>
            <p>Sélectionnez un sujet d'e-mail pour afficher son contenu.</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mailSubjectLinks = document.querySelectorAll('.mail-subject-link');
    const mailContentDisplay = document.getElementById('mail_content_display');
    const mainContainer = document.getElementById('main_container');
    const closeEmailBtn = document.getElementById('close_email_display');

    mailSubjectLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const mailId = this.dataset.mailId;

            document.querySelectorAll('#email_list_table tr').forEach(row => {
                row.style.backgroundColor = '';
            });
            document.getElementById('mail_row_'+mailId).style.backgroundColor = '#e0e0ff';

            mailContentDisplay.innerHTML = '<p>Chargement de l\'e-mail...</p>';
            mainContainer.classList.add('mail-displayed');

            fetch('view_mail.php?id=' + mailId)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.text();
                })
                .then(html => {
                    mailContentDisplay.innerHTML = '<span class="close-email" id="close_email_display">&times;</span>' + html;
                    document.getElementById('close_email_display').addEventListener('click', closeMail);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    mailContentDisplay.innerHTML = '<p style="color:red;">Erreur de chargement</p>';
                });
        });
    });

    function closeMail() {
        mainContainer.classList.remove('mail-displayed');
        document.querySelectorAll('#email_list_table tr').forEach(row => {
            row.style.backgroundColor = '';
        });
    }

    closeEmailBtn.addEventListener('click', closeMail);
});
</script>

<?php
print dol_get_fiche_end();
llxFooter();
$db->close();
?>
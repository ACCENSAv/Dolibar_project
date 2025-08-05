<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->loadLangs(array("users", "admin", "mailboxmodule@mailboxmodule"));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'webmailsettings';

if (!$user->admin && $user->id != $id) {
    accessforbidden();
}

$userToEdit = new User($db);
if ($id > 0) {
    $result = $userToEdit->fetch($id);
    if ($result <= 0) {
        accessforbidden();
    }
    $userToEdit->loadRights();
} else {
    accessforbidden();
}
if ($action == 'save' && GETPOST('token') == $_SESSION['newtoken']) {
    $mail_login = trim(GETPOST('mail_login', 'alphanohtml'));
    $mail_password = trim(GETPOST('mail_password', 'none'));
    $mail_host = trim(GETPOST('mail_host', 'alphanohtml'));

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_roundcube 
            (fk_user, mail_login, mail_password, mail_host, date_update)
            VALUES (".((int)$id).", 
            '".$db->escape($mail_login)."', 
            '".$db->escape($mail_password)."', 
            ".($mail_host ? "'".$db->escape($mail_host)."'" : "NULL").",
            NOW())
            ON DUPLICATE KEY UPDATE 
            mail_login = VALUES(mail_login),
            mail_password = VALUES(mail_password),
            mail_host = VALUES(mail_host),
            date_update = VALUES(date_update)";

    if ($db->query($sql)) {
        setEventMessages($langs->trans("SettingsSaved"), null, 'mesgs');
    } else {
        setEventMessages($db->lasterror(), null, 'errors');
    }
}

$title = $langs->trans("WebmailSettings").' - '.$userToEdit->getFullName($langs);
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-user page-webmailsettings');

$head = user_prepare_head($userToEdit);

print dol_get_fiche_head($head, 'webmail', $langs->trans("User"), -1, 'user');

$linkback = '';
if ($user->hasRight('user', 'user', 'lire') || $user->admin) {
    $linkback = '<a href="' . DOL_URL_ROOT . '/user/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
}

$morehtmlref = '<a href="' . DOL_URL_ROOT . '/user/vcard.php?id=' . $userToEdit->id . '&output=file&file=' . urlencode(dol_sanitizeFileName($userToEdit->getFullName($langs) . '.vcf')) . '" class="refid" rel="noopener">';
$morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

$urltovirtualcard = '/user/virtualcard.php?id=' . ((int) $userToEdit->id);
$morehtmlref .= dolButtonToOpenUrlInDialogPopup(
    'publicvirtualcard',
    $langs->transnoentitiesnoconv("PublicVirtualCardUrl") . ' - ' . $userToEdit->getFullName($langs),
    img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'),
    $urltovirtualcard,
    '',
    'nohover'
);

dol_banner_tab($userToEdit, 'id', $linkback, ($user->hasRight('user', 'user', 'lire') || $user->admin), 'rowid', 'ref', $morehtmlref);



print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

print '<tr><td id="anchorforperms" class="titlefield">'.$langs->trans("Login").'</td>';
if (!empty($userToEdit->ldap_sid) && $userToEdit->status == 0) {
    print '<td class="error">';
    print $langs->trans("LoginAccountDisableInDolibarr");
    print '</td>';
} else {
    print '<td>';
    $addadmin = '';
    if (property_exists($userToEdit, 'admin')) {
        if (isModEnabled('multicompany') && !empty($userToEdit->admin) && empty($userToEdit->entity)) {
            $addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft valignmiddle"');
        } elseif (!empty($userToEdit->admin)) {
            $addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft valignmiddle"');
        }
    }
    print showValueWithClipboardCPButton($userToEdit->login).$addadmin;
    print '</td>';
}
print '</tr>'."\n";

print '</table>';
print '</div>';
print dol_get_fiche_end();

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("MailboxConfiguration").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("Email").'</td>';
print '<td><input type="email" class="flat minwidth300" name="mail_login" value="'.dol_escape_htmltag(GETPOST('mail_login')).'" required></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("Password").'</td>';
print '<td><input type="password" class="flat" name="mail_password" value="'.dol_escape_htmltag(GETPOST('mail_password')).'" required></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("MailServer").'</td>';
print '<td><input type="text" class="flat" name="mail_host" value="'.dol_escape_htmltag(GETPOST('mail_host')).'" placeholder="ssl://imap.example.com:993"></td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';
-

print '</div>'; 


print dol_get_fiche_end();
llxFooter();
$db->close();
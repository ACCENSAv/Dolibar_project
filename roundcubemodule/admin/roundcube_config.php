<?php
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';

if (!is_object($langs)) {
    include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
    $langs = new Translate('', $conf);
    $langs->setDefaultLang('auto');
}

if (!$user->admin) {
    accessforbidden();
}

$langs->load("admin");
$langs->load("roundcubemodule@roundcubemodule");

$config_file = DOL_DOCUMENT_ROOT.'/custom/roundcubemodule/roundcube/config/config.inc.php';

$fixed_smtp_user = '%u';
$fixed_smtp_pass = '%p';
$fixed_des_key = 'eKUVOvuwW2ajKmf113ufMwgG';

$current_tab = GETPOST('tab', 'alpha');
if (empty($current_tab)) {
    $current_tab = 'settings';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = GETPOST('token', 'alpha');
    $valid_token = function_exists('checkToken') ? checkToken() : (function_exists('dol_valid_token') ? dol_valid_token($token) : true);

    if (!$valid_token) {
        setEventMessages($langs->trans("ErrorBadCSRFToken"), null, 'errors');
    } else {
        if ($current_tab == 'settings') {
            $params = [
                'imap_host' => GETPOST('imap_host', 'alpha'),
                'smtp_host' => GETPOST('smtp_host', 'alpha')
            ];

            $config_content = file_exists($config_file) ? file_get_contents($config_file) : "<?php\n";

            foreach ($params as $key => $value) {
                $pattern = "/\\\$config\\['".$key."'\]\\s*=\\s*'(.*?)';/";
                $replacement = "\$config['".$key."'] = '".dol_escape_htmltag($value)."';";

                if (preg_match($pattern, $config_content)) {
                    $config_content = preg_replace($pattern, $replacement, $config_content);
                } else {
                    $config_content .= "\n".$replacement;
                }
            }

            $fixed_lines = [
                'smtp_user' => $fixed_smtp_user,
                'smtp_pass' => $fixed_smtp_pass,
                'des_key'   => $fixed_des_key
            ];

            foreach ($fixed_lines as $key => $value) {
                $pattern = "/\\\$config\\['".$key."'\]\\s*=\\s*'(.*?)';/";
                $replacement = "\$config['".$key."'] = '".$value."';";

                if (preg_match($pattern, $config_content)) {
                    $config_content = preg_replace($pattern, $replacement, $config_content);
                } else {
                    $config_content .= "\n".$replacement;
                }
            }

            if (file_put_contents($config_file, $config_content)) {
                setEventMessages($langs->trans("ConfigSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("ErrorConfigNotSaved"), null, 'errors');
            }
        }
    }
}

$current_config = [
    'imap_host' => 'ssl://imap.gmail.com:993',
    'smtp_host' => 'tls://smtp.gmail.com:587',
    'smtp_user' => $fixed_smtp_user,
    'smtp_pass' => $fixed_smtp_pass,
    'des_key'   => $fixed_des_key
];

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $parse_keys = ['imap_host', 'smtp_host'];
    foreach ($parse_keys as $key) {
        if (preg_match("/\\\$config\\['".$key."'\]\\s*=\\s*'(.*?)';/", $config_content, $matches)) {
            $current_config[$key] = $matches[1];
        }
    }
}

$form = new Form($db);
$formcategory = new FormCategory($db);

$help_url = 'EN:Roundcube_Module_Setup|FR:Configuration_Module_Roundcube';
$page_name = 'RoundcubeConfiguration';
llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-admin page-roundcube');
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = array();
$head[] = array('url' => DOL_URL_ROOT.'/custom/roundcubemodule/admin/roundcube_config.php?tab=settings', 'label' => "Paramètres", 'id' => 'settings');
$head[] = array('url' => DOL_URL_ROOT.'/custom/roundcubemodule/admin/roundcube_config.php?tab=advanced', 'label' => "Avancé", 'id' => 'advanced');
$head[] = array('url' => DOL_URL_ROOT.'/custom/roundcubemodule/admin/roundcube_config.php?tab=about', 'label' => "À propos", 'id' => 'about');

print dol_get_fiche_head($head, $current_tab, "Module Roundcube", -1, "roundcubemodule");

print '<span class="opacitymedium">Configuration du module Roundcube.</span><br><br>'."\n";

if ($current_tab == 'settings') {
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?tab='.$current_tab.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="setvar">';

    print load_fiche_titre("Paramètres du serveur de messagerie", '', '');

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td class="titlefieldmiddle">Paramètre</td>';
    print '<td></td>'."\n";
    print '<td class="center" width="40"></td>';
    print "</tr>\n";

    $fields = [
        'imap_host' => [
            'label' => "Hôte IMAP",
            'help' => "Nom d'hôte et port du serveur IMAP (ex: ssl://imap.gmail.com:993)."
        ],
        'smtp_host' => [
            'label' => "Hôte SMTP",
            'help' => "Nom d'hôte et port du serveur SMTP (ex: tls://smtp.gmail.com:587)."
        ]
    ];

    foreach ($fields as $key => $data) {
        print '<tr class="oddeven">';
        print '<td width="25%">'.$data['label'].'</td>';
        print '<td class="left"><input type="text" name="'.$key.'" value="'.dol_escape_htmltag($current_config[$key]).'" class="minwidth300"></td>';
        print '<td class="center">';
        print $formcategory->textwithpicto('', $data['help'], 1, 'help');
        print '</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div><br>';

    print $formcategory->buttonsSaveCancel("Enregistrer", '', array(), 0, 'reposition');

    print '</form>';

} elseif ($current_tab == 'advanced') {
    print '<div class="fichethirdparty"><div class="ficheaddleft">';
    print '<p>Contenu de l\'onglet Avancé sera ajouté ici.</p>';
    print '</div></div>';
} elseif ($current_tab == 'about') {
    print '<div class="fichethirdparty"><div class="ficheaddleft">';
    print '<p>Informations sur le module Roundcube. Version 1.0.</p>';
    print '</div></div>';
}

dol_get_fiche_end();

llxFooter();
$db->close();
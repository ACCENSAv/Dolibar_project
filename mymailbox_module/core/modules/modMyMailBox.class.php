<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modMyMailBox extends DolibarrModules
{
    function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        $this->numero = 104001; // ID unique
        $this->rights_class = 'mymailbox';
        $this->family = 'crm';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Ajoute un onglet Mails dans les fiches tiers";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'email';

        $this->dirs = array("/mymailbox_module/temp");
        $this->config_page_url = array("setup.php@mymailbox_module");

        // Ajout de l'onglet
        $this->tabs = array(
            // Chemin vers le fichier tab : /custom/mymailbox_module/mailtab.php
            'thirdparty:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/mailtab.php?socid=__ID__',
           'contact:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/mailtab.php?contactid=__ID__',

    // Onglet dans les projets
    'project:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=project',

    // Etendre à tous les modules demandés :
    'propal:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=propal',
    'order:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=order',
    'expedition:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=expedition',
    'contract:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=contract',
    'fichinter:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=fichinter',
    'ticket:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=ticket',
    'partnership:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=partnership',
    'supplier_proposal:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=supplier_proposal',
    'supplier_order:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=supplier_order',
    'supplier_invoice:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=supplier_invoice',
    'reception:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=reception',
    'invoice:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=invoice',
    'salary:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=salary',
    'loan:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=loan',
    'don:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=don',
    'holiday:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=holiday',    
    'commande:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=commande',
    'expensereport:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=expensereport',
    'user:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=user',
    'group:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=usergroup',
    'adherent:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=adherent',
    'event:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=event',
    'accounting:+mailtab:Mails:@mymailbox_module:/custom/mymailbox_module/lie.php?id=__ID__&module=accounting',
        );

        $this->module_parts = array();
        $this->rights = array();
        $this->menu = array();
    }
  

}
?>

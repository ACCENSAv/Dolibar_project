<?php
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modRedirectmail extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->numero = 104999; // ID unique
        $this->rights_class = 'redirectmail';
        $this->family = 'crm';
        $this->name = 'redirectmail';
        $this->description = 'Redirige le bouton Envoyer un mail vers un module perso';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'email';

        $this->dirs = array('/redirectmail/temp');
        $this->module_parts = array();
        $this->config_page_url = array();
        $this->langfiles = array("redirectmail@redirectmail");

        $this->module_parts = array(
            'hooks' => array('thirdpartycard', 'projectcard', 'productcard')
        );

        $this->enabled = '1';
        $this->always_enabled = 0;

        $this->phpmin = array(7, 0);
        $this->need_dolibarr_version = array(16, 0);
    }
}

<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modRoundcubeModule extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 104010;
        $this->rights_class = 'roundcubemodule';
        $this->family = "crm";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Accès au webmail Roundcube depuis Dolibarr";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'fa-envelope';
        $this->config_page_url = array('roundcube_config.php@roundcubemodule');
        $this->langfiles = array();
        $this->rights = array();
        $this->module_parts = array(
            'hooks' => array('toprightmenu')
        );
        $this->dirs = array("/roundcubemodule/temp");

        $this->menu = array(
            array(
                'fk_menu' => 0,
                'type' => 'top',
                'titre' => 'Webmail',
                'mainmenu' => 'fa-envelope',
                'leftmenu' => '',
                'url' => '/custom/roundcubemodule/roundcube.php',
                'langs' => 'fr_FR',
                'position' => 100,
                'enabled' => '1',
                'perms' => '1',
                'user' => 2
            )
        );
    }

    public function init($options = '')
    {
        global $conf;

        // Créer le fichier de config par défaut si il n'existe pas
        $config_file = dol_buildpath('/custom/roundcubemodule/roundcube/config/config.inc.php');
        if (!file_exists($config_file)) {
            $default_config = file_get_contents(dol_buildpath('/custom/roundcubemodule/config.inc.default.php'));
            file_put_contents($config_file, $default_config);
        }

        
        try {
            $db_host = 'localhost';
            $db_user = 'root';
            $db_pass = '';
            $db_name = 'roundcubemail';

            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
            if (!$stmt->fetch()) {
                $pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $pdo->exec("USE `$db_name`");

                $sql_file = dol_buildpath('/custom/roundcubemodule/sql/mysql.initial.sql');
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    $pdo->exec($sql);
                } else {
                    dol_syslog("Fichier SQL introuvable : $sql_file", LOG_ERR);
                }
            }
        } catch (PDOException $e) {
            dol_syslog("Erreur PDO lors de la création de la base Roundcube : " . $e->getMessage(), LOG_ERR);
        }

        return parent::init($options);
    }
}

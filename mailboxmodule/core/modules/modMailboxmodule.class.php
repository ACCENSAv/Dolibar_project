<?php
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
class modMailboxmodule extends DolibarrModules
{
    public $rights;
    public $db;

    public function __construct($db)
    {
        global $langs;

        $this->db = $db;

        $this->numero = 104000;
        $this->rights_class = 'mailboxmodule';
        $this->family = "interface";
        $this->name = "mailboxmodule";
        $this->description = "Module pour la gestion des e-mails liés aux objets Dolibarr";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'email';

        // Déclaration des droits
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 104001;          // ID droit
        $this->rights[$r][1] = 'Lire les mails';    // Libellé
        $this->rights[$r][2] = 'r';             // Type
        $this->rights[$r][3] = 1;               // Par défaut
        $this->rights[$r][4] = 'read';          // Code
        $r++;

        $this->tabs = array(
            'user:+webmail:Webmail:@mailboxmodule:/custom/mailboxmodule/card_mails.php?id=__ID__',
        );

        $this->module_parts = array('triggers' => 0, 'hooks' => 1);
    }

    public function init($options = '')
    {
        global $langs;

        $sql = array();

        // Table pour les mails principaux
        $sql[] = "
            CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."mailboxmodule_mail (
                rowid INT(11) AUTO_INCREMENT PRIMARY KEY,
                message_id VARCHAR(255) NOT NULL UNIQUE,
                subject VARCHAR(255),
                from_email VARCHAR(255),
                date_received DATETIME,
                file_path VARCHAR(255),
                fk_soc INT(11) NULL,
                imap_mailbox VARCHAR(255) DEFAULT NULL,
                imap_uid INT(11) DEFAULT NULL,
                direction VARCHAR(10) DEFAULT 'received',
                INDEX idx_mailboxmodule_mail_message_id (message_id),
                INDEX idx_mailboxmodule_mail_fk_soc (fk_soc),
                INDEX idx_mailboxmodule_mail_imap_uid (imap_uid)
            ) ENGINE=INNODB;
        ";


        // Table pour les attachements des mails
        $sql[] = "
            CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."mailboxmodule_attachment (
                rowid INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                fk_mail INT(11) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                mimetype VARCHAR(100) DEFAULT NULL,
                filepath VARCHAR(255) NOT NULL,
                entity INT(11) DEFAULT 1,
                datec DATETIME NOT NULL,
                KEY idx_fk_mail (fk_mail)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // Table pour les informations de compte Roundcube des utilisateurs
        $sql[] = "
            CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."user_roundcube (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                fk_user INT NOT NULL,
                mail_login VARCHAR(255) NOT NULL,
                mail_password TEXT NOT NULL,
                mail_host VARCHAR(255) DEFAULT NULL,
                token VARCHAR(32) NULL DEFAULT NULL,
                token_expire DATETIME NULL DEFAULT NULL,
                date_update DATETIME,
                UNIQUE(fk_user),
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        /*
        $sql[] = "DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."mailboxmodule_mail;";
        $sql[] = "DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."llx_mailboxmodule_attachment;";
        $sql[] = "DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."user_roundcube;";
        */
        return $this->_remove($sql, $options);
    }
}

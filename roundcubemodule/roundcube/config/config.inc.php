<?php

/* Local configuration for Roundcube Webmail */

// ----------------------------------
// SQL DATABASE
// ----------------------------------
// Database connection string (DSN) for read+write operations
// Format (compatible with PEAR MDB2): db_provider://user:password@host/database
// Currently supported db_providers: mysql, pgsql, sqlite, mssql, sqlsrv, oracle
// For examples see https://pear.php.net/manual/en/package.database.mdb2.intro-dsn.php
// Note: for SQLite use absolute path (Linux): 'sqlite:////full/path/to/sqlite.db?mode=0646'
//       or (Windows): 'sqlite:///C:/full/path/to/sqlite.db'
// Note: Various drivers support various additional arguments for connection,
//       for Mysql: key, cipher, cert, capath, ca, verify_server_cert,
//       for Postgres: application_name, sslmode, sslcert, sslkey, sslrootcert, sslcrl, sslcompression, service.
//       e.g. 'mysql://roundcube:@localhost/roundcubemail?verify_server_cert=false'
$config['db_dsnw'] = 'mysql://root:@localhost/roundcubemail';

// ----------------------------------
// IMAP
// ----------------------------------
// The IMAP host (and optionally port number) chosen to perform the log-in.
// Leave blank to show a textbox at login, give a list of hosts
// to display a pulldown menu or set one host as string.
// Enter hostname with prefix ssl:// to use Implicit TLS, or use
// prefix tls:// to use STARTTLS.
// If port number is omitted it will be set to 993 (for ssl://) or 143 otherwise.
// Supported replacement variables:
// %n - hostname ($_SERVER['SERVER_NAME'])
// %t - hostname without the first part
// %d - domain (http hostname $_SERVER['HTTP_HOST'] without the first part)
// %s - domain name after the '@' from e-mail address provided at login screen
// For example %n = mail.domain.tld, %t = domain.tld
// WARNING: After hostname change update of mail_host column in users table is
//          required to match old user data records with the new host.
$config['imap_host'] = 'ssl://imap.gmail.com:993';

// ----------------------------------
// SMTP
// ----------------------------------
// SMTP server host (and optional port number) for sending mails.
// Enter hostname with prefix ssl:// to use Implicit TLS, or use
// prefix tls:// to use STARTTLS.
// If port number is omitted it will be set to 465 (for ssl://) or 587 otherwise.
// Supported replacement variables:
// %h - user's IMAP hostname
// %n - hostname ($_SERVER['SERVER_NAME'])
// %t - hostname without the first part
// %d - domain (http hostname $_SERVER['HTTP_HOST'] without the first part)
// %z - IMAP domain (IMAP hostname without the first part)
// For example %n = mail.domain.tld, %t = domain.tld
// To specify different SMTP servers for different IMAP hosts provide an array
// of IMAP host (no prefix or port) and SMTP server e.g. ['imap.example.com' => 'smtp.example.net']
$config['smtp_host'] = 'tls://smtp.gmail.com:587';
$config['smtp_user'] = '%u';
$config['smtp_pass'] = '%p';
$config['smtp_auth_type'] = 'PLAIN';

// provide an URL where a user can get support for this Roundcube installation
// PLEASE DO NOT LINK TO THE ROUNDCUBE.NET WEBSITE HERE!
$config['support_url'] = '';

// This key is used for encrypting purposes, like storing of imap password
// in the session. For historical reasons it's called DES_key, but it's used
// with any configured cipher_method (see below).
// For the default cipher_method a required key length is 24 characters.
$config['des_key'] = 'eKUVOvuwW2ajKmf113ufMwgG';

// ----------------------------------
// PLUGINS
// ----------------------------------
// List of active plugins (in plugins/ directory)
$config['plugins'] = ['archive', 'password', 'userinfo','save2dolibarr', 'autologon', 'sentlogger'];

// the default locale setting (leave empty for auto-detection)
// RFC1766 formatted language name like en_US, de_DE, de_CH, fr_FR, pt_BR
$config['language'] = 'fr_FR';

$config['debug_level'] = 128;
$config['log_driver'] = 'file';

$config['log_dir'] = 'C:/wamp64/www/AA1_doli/htdocs/custom/roundcubemodule/roundcube/logs/';

// 💡 Crée ce dossier s’il n’existe pas :
/*
mkdir -p /Applications/MAMP/htdocs/roundcube/logs
chmod -R 777 /Applications/MAMP/htdocs/roundcube/logs
*/

// ----------------------------------
// IMAP Connexion SSL (utile en local)
// ----------------------------------
$config['imap_debug'] = true;
$config['smtp_debug'] = true;
$config['imap_conn_options'] = [
  'ssl'         => [
    'verify_peer'       => false,
    'verify_peer_name'  => false,
    'allow_self_signed' => true,
  ],
];
$config['smtp_conn_options'] = [
  'ssl' => [
    'verify_peer'       => false,
    'verify_peer_name'  => false,
    'allow_self_signed' => true,
  ],
];
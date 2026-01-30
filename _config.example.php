<?php
use chetch\Config as Config;

Config::initialise();

Config::set('ERROR_REPORTING', E_ALL);

//Database Config
include('/var/www/conf/dbconfig.php');
Config::set('DBHOST', 'mysql:host='._DB_HOST_);
Config::set('DBNAME', 'network');
Config::set('DBUSERNAME', _DB_USERNAME_);
Config::set('DBPASSWORD', _DB_PASSWORD_);
$dbtblpfx = ''; //table prefix for database

//Email Config
/*Config::set('EMAIL_EXCEPTIONS_TO', 'bill@bulan-baru.com');
Config::set('PHP_MAILER', _SITESROOT_.'webapps/lib/php/phpmailer/class.phpmailer.php');
Config::set('SMTP_HOST', _SMTP_HOST_);
Config::set('SMTP_SECURE', _SMTP_SECURE_);
Config::set('SMTP_USERNAME', _SMTP_USERNAME_);
Config::set('SMTP_PASSWORD', _SMTP_PASSWORD_);
Config::set('SMTP_PORT', _SMTP_PORT_);*/

//API Config
Config::set('API_ALLOW_REQUESTS', 'GET,PUT,POST,DELETE');

//SSH Tunnels
Config::set('SSH_TUNNEL_ENDPOINT', 'bbrpi-dev');
Config::set('OPEN_SSH_TUNNEL', '"ssh -tt -i ~/.ssh/ChetchAWS.Singapore.pem ec2-user@47.129.130.200 -R 2222:localhost:22"');
Config::get('CLOSE_SSH_TUNNEL', "kill -9 {PID}");

//establish table names


?>

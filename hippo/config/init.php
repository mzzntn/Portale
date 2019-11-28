<?

// Constants


//percorso (web) assoluto della cartella web (senza http://server)
define('HOME', '/openweb');
//percorso (filesystem) assoluto della cartella hippo
define('HIPPO', '/var/www/siti/portale/hippo');
//percorso (filesystem) della cartella web
define('HOMEPATH', '/var/www/siti/portale/hippo/web');
//nome del server
define('SERVER', 'tuodominio.it');
//percorso (filesystem) della cartella locale dell'applicazione
//(se utilizzato)
define('SITE', '/var/www/siti/portale/hippo');

define('LIBS', HIPPO.'/libs');
define('BASE', HIPPO.'/base');
define('APPS', SITE.'/apps');
define('APP', APPS.'/contabilita');
define('VARPATH', SITE.'/var');
define('CONFIG', SITE.'/config');
define('SITE_CONFIG', SITE.'/config');
define('APP_CONFIG', HIPPO.'/config/app');
define('DATA', SITE.'/data');
define('PEAR', LIBS.'/PEAR');
define('TEMPLATES', APP.'/templates');

define('URL_JS', HOME.'/js');
define('URL_IMG', HOME.'/img');
define('URL_CSS', HOME.'/css');
define('TOOLS', HOME.'/tools');

#define('PATH_CSS_WIDGETS', '');
#define('URL_CSS_WIDGETS', '');

define('PATH_CSS', HOMEPATH.'/css');

define('PATH_WEBDATA', HOMEPATH.'/data');
define('URL_WEBDATA', HOME.'/data');

define('ADMIN', HOME.'/gestione');
define('LOGIN', ADMIN.'/login.php');
define('STRUCT_ADMIN', ADMIN);

define('SEC_SESS_EXPIRE', 1800);

$config['defaultdb']['type'] = 'mysql';
#$config['defaultdb']['server'] = '10.0.23.4';
$config['defaultdb']['name'] = 'portale';
$config['defaultdb']['user'] = 'portale';
$config['defaultdb']['pass'] = 'Pass.2019!';
$config['charset'] = 'ISO-8859-15';
$config['nosequenze'] = true;


//***********FINE CONFIGURAZIONE*****************

if (preg_match('_/test/_', $_SERVER['REQUEST_URI'])){
  ini_set('display_errors', 1);
  ini_set('error_reporting', E_ALL & ~E_NOTICE);
  define('DEBUG_MODE', true);
}
else define('DEBUG_MODE', false);

ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.PEAR);

include_once(APP_CONFIG.'/includes.php');
include_once(CONFIG.'/app/startup.php');

function shutdown(){
  global $IMP;
  #if ($IMP->canDisplay('html')) include_once(HOMEPATH.'/bottom.php');
  include_once(APP_CONFIG.'/finalize.php');
}

register_shutdown_function('shutdown');



?>

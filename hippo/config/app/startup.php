<?

if (!function_exists('_')){
  function _($s){
    return $s;
  }
}
if (version_compare(phpversion(), '5.0') < 0) {
    eval('
    function clone($object) {
        return $object;
    }
    ');
}

// sanitizzazione input per OWASP
function mysql_escape_mimic($inp) {
    if(is_array($inp))
        return array_map(__METHOD__, $inp);

    if(!empty($inp) && is_string($inp)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }

    return $inp;
}

function sanitizeUserInput($request) {
  $sanitized = $request;
  
  if(is_array($sanitized)) {
    foreach($sanitized as $key=>$value) {
      if(is_array($value)) {
        $sanitized[$key] = sanitizeUserInput($value);
      } else {
        $sanitized[$key] = str_replace("javascript:","",mysql_escape_mimic(strip_tags($value)));
      }
    }
  } else {
    $sanitized = str_replace("javascript:","",mysql_escape_mimic(strip_tags($sanitized)));
  }
  return $sanitized;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $_REQUEST = sanitizeUserInput($_REQUEST);}

/* parsing per evitare attacchi XSS */
if(isset($_SERVER['DOCUMENT_URI'])){$_SERVER['DOCUMENT_URI']=sanitizeUserInput($_SERVER['DOCUMENT_URI']);}
if(isset($_SERVER['PHP_SELF'])){$_SERVER['PHP_SELF']=sanitizeUserInput($_SERVER['PHP_SELF']);}
if(isset($_SERVER['PATH_INFO'])){$_SERVER['PATH_INFO']=sanitizeUserInput($_SERVER['PATH_INFO']);}
if(isset($_SERVER['PATH_TRANSLATED'])){$_SERVER['PATH_TRANSLATED']=sanitizeUserInput($_SERVER['PATH_TRANSLATED']);}

if(substr_compare($_SERVER['PHP_SELF'], ".php", -strlen(".php")) !== 0 && strpos($_SERVER['PHP_SELF'],".php?")===false){$_SERVER['PHP_SELF']= substr($_SERVER['PHP_SELF'],0,strpos($_SERVER['PHP_SELF'],".php")+4);} // rimuove tutto quello che c'è dopo .php nell'url, a meno che non siano parametri get

/* fallback impostazioni sicurezza cookie nel caso non siano impostate in php.ini */
/* ATTENZIONE: si può usare solo con https, se il sito va su http lasciare commentato */
//ini_set( 'session.cookie_httponly', 1 );
//ini_set( 'session.cookie_secure', 1 );

session_set_cookie_params('', '/');
session_start();

//IMP

$IMP = new IMP();
$IMP->debugLevel = -1;
if ($_SERVER['SERVER_PROTOCOL']) $IMP->debugMode = 'html';
$IMP->defaults['binding'] = 'db';
$IMP->defaults['dbType'] = 'mysql';
$IMP->defaults['dbType'] = $config['defaultdb']['type'];
$IMP->defaults['db']['name'] = $config['defaultdb']['name'];
$IMP->defaults['db']['user'] = $config['defaultdb']['user'];
$IMP->defaults['db']['pass'] = $config['defaultdb']['pass'];
$IMP->defaults['db']['server'] = $config['defaultdb']['server'];
$IMP->loadPipelines();

$IMP->config = $config;

$IMP->cache = new Cache();
$IMP->typeSpace = new TypeSpace();
$IMP->bindingManager = new BindingManager();
$IMP->bindingManager->bindings = $config['bindings'];
$IMP->bindingManager->loadBindings(SITE_CONFIG.'/bindings');
$IMP->widgetParams = new WidgetParams();
$sessionPrefix = $IMP->config['securityPrefix'];
#if ($_SESSION[$sessionPrefix.'security']) $IMP->security = $_SESSION[$sessionPrefix.'security'];
#else $IMP->security = new Security();
$IMP->security = new Security();
$IMP->security->civiliaOpen->userCod = $_SESSION['civiliaOpenUser'];
$sessionPrefix = $IMP->config['securityPrefix'];
$filesPolicies = search_dir(SITE_CONFIG.'/security/policies', '*.php');
if (is_array($filesPolicies)) foreach ($filesPolicies as $file){
  include_once($file);
}
#include_once(CONFIG.'/security/policies.php');

$IMP->styleManager = new StyleManager();
$IMP->widgetFactory = new WidgetFactory();
#$IMP->typeSpace->loadStructNames();

$IMP->files = new Files();
$IMP->images = new Images();

$IMP->supports['css_multiple_classes'] = true;

$IMP->config['adminMode'] = 0;
$IMP->config['adminStyles'] = HOME.'/tools/cssEditor.php';

$filesPercorsi = search_dir(SITE_CONFIG.'/applicazioni', '*.php');
if (is_array($filesPercorsi)) foreach ($filesPercorsi as $file){
    include_once($file);
}

include_once(LIBS.'/Data/XML/Binding_xml.php');
include_once(LIBS.'/Data/XML/DataLoader_xml.php');
$IMP->bindingManager->set('portal::docici', 'xml');
$IMP->bindingManager->set('ici::documentazione', 'xml');

if (is_dir(SITE_CONFIG.'/costanti')){
  $filesCostanti = search_dir(SITE_CONFIG.'/costanti', '*.xml');
  foreach ($filesCostanti as $file){
    loadConstants($file);
  }
}
$IMP->appconf = array();
$C = & $IMP->appconf;
$filesImpostazioniDefault = search_dir(SITE_CONFIG.'/impostazioni', '*.default.php');
if (is_array($filesImpostazioniDefault)) foreach($filesImpostazioniDefault as $file) {leggiImpostazioni($file);}
$filesImpostazioni = search_dir(SITE_CONFIG.'/impostazioni', '*.php');
if (is_array($filesImpostazioni)) foreach($filesImpostazioni as $file) {leggiImpostazioni($file);}

if ($config['nosequenze']){

// if(isset($_REQUEST["test"])) {
include_once(LIBS.'/ext/yaml/sfYaml.php');
include_once(LIBS.'/ext/yaml/sfYamlParser.php');
/* INIZIO MODIFICHE PER CONFIG IN DB */
// carico i valori salvati nella tabella setup
$loader = & $IMP->getLoader('setup');
// ma SOLO quelli che non sono null (quindi se tutti i parametri in tabella non hanno un valore assegnato non verrà caricato niente e verranno presi i dati dai file di config
$loader->addParam('valore', null, "<>");
$setupDb = $loader->load();
#   $IMP->debugLevel =-1;
# print_r($setupDb);


if ($setupDb->listSize() > 0){ 

  // converto eventuali FixNum a Integer e Hash2 ad Hash
  $loader = & $IMP->getLoader('setup');
  $bindingSetup = $IMP->bindingManager->getBinding('setup');
  $db = $bindingSetup->getDbObject();
  $sql = "UPDATE {$bindingSetup->table} SET {$bindingSetup->dbField('tipo')} = 'Integer' WHERE {$bindingSetup->table}.{$bindingSetup->dbField('tipo')} = 'FixNum';";
  $db->execute($sql);
  $sql = "UPDATE {$bindingSetup->table} SET {$bindingSetup->dbField('tipo')} = 'Hash' WHERE {$bindingSetup->table}.{$bindingSetup->dbField('tipo')} = 'Hash2';";
  $db->execute($sql);

  $parser = new sfYamlParser();
  // in questo caso sono presenti parametri di configurazione valorizzati nella tabella setup
//   print "ci sono setup a db<br>";
#  $IMP->debugLevel =3;
  // quindi carico tutti quanti i parametri dalla tabella
  $loader = & $IMP->getLoader('setup');
  $l = $loader->load();
#  $IMP->debugLevel =-1;
  // poi per ciascuno dei parametri in tabella
  while ($l->moveNext()){
    // aggiungo applicazione su $IMP->appconf nel caso non sia già presente
    if(!isset($IMP->appconf[$l->get('applicazione')])){$IMP->appconf[$l->get('applicazione')]=array();}
    // recupero il valore della configurazione da db
    $value = $l->get('valore');
    // tento la conversione del valore da json ad array
    $configArray = json_decode($value);

    if (json_last_error() === JSON_ERROR_NONE && (is_object ($configArray)||is_array ($configArray)) && $value!="{  }") {
      // i setup sono in json, convertiamoli in yaml
//       echo "{$l->get('codice')} &egrave; json ({$value}), lo converto in yaml e risalvo in db<br>";
      $value = json_decode(json_encode($configArray), true);;
//       echo "value &egrave; <pre>".print_r($value,true)."</pre><br>";
      if(is_object ($configArray)||is_array ($configArray)) {
        // se si tratta di un array, lo trasformo in un yaml
        $dbvalue = sfYaml::dump($value);
//         echo "convertito {$l->get('codice')} a (e lo salvo in db) <pre>".print_r($dbvalue,true)."</pre><br>";
        // creo lo storer
        $storer = & $IMP->getStorer('setup');
        // verifico applicazione e codice in modo da non inserire duplicati
        $storer->checkMode("applicazione");
        $storer->checkMode("codice");
        
        // imposto applicazione e codice da db, e valore recuperato da file
        $storer->set("applicazione",$l->get('applicazione'));
        $storer->set("codice",$l->get('codice'));
        $storer->set("valore",$dbvalue);
        
//         print "creo storer: {'Applicazione':'{$l->get('applicazione')}','codice':'{$l->get('codice')}','valore':'{$dbvalue}','tipo':'Amministratore'}<br>";
        // salvo la configurazione in db
        $storer->store();
        $value = $parser->parse($dbvalue);
//         echo "imposto configurazione php per {$l->get('applicazione')} {$l->get('codice')} a <pre>".print_r($value,true)."</pre><br>";
      } else {
        // altrimenti il valore non era un array, e lo tengo così come sta
//         echo "no object o array - imposto configurazione php per {$l->get('applicazione')} {$l->get('codice')} a {$value}<br>";
      }
    } else {
      $configArray = $parser->parse($value);

      if ($configArray!==null && is_array($configArray)) {
        // se la conversione ha restituito qualcosa vuol dire che era un array, quindi reimposto value al json convertito
        $value = $configArray;
//         echo "errore conversione json, array - imposto configurazione php per {$l->get('applicazione')} {$l->get('codice')} a <pre>".print_r($value,true)."</pre><br>";
      } else {
        // altrimenti il valore non era un array, e lo tengo così come sta
//         echo "errore conversione json, no array - imposto configurazione php per {$l->get('applicazione')} {$l->get('codice')} a {$value}<br>";
      }
    }

    // imposto in $IMP->appconf la coppia codice configurazione -> valore configurazione, nell'applicazione corrispondente
    $IMP->appconf[$l->get('applicazione')][$l->get('codice')] = $value;
  }
}
else {
  // non ci sono configurazioni valorizzate in db, quindi devo prendere i dati di config dai file e metterli in db
//   print "non ci sono setup a db<br>";
  // carico tutti i setup (perchè voglio inserire solo quelli presenti in db
  // se una configurazione è presente su file ma non in db (come i parametri di connessione al db) non verrà inserita e continuerà ad essere utilizzata da file config
  $loader = & $IMP->getLoader('setup');
  $l = $loader->load();
  //print_r($C);
  while ($l->moveNext()){
    // se la configurazione è presente in $IMP->appconf (cioè su file)
    if(isset($IMP->appconf[$l->get('applicazione')]) && isset($IMP->appconf[$l->get('applicazione')][$l->get('codice')])) {
//        print "{$l->get('applicazione')} - {$l->get('codice')}<br>";
//        print $IMP->appconf[$l->get('applicazione')][$l->get('codice')]."<br>";
      
      // recupero il valore della configurazione dal file
      $value = $IMP->appconf[$l->get('applicazione')][$l->get('codice')];
      if(is_array($value)) {
        // se si tratta di un array, lo trasformo in un yaml
        $value = sfYaml::dump($value);
      }
      
      // creo lo storer
      $storer = & $IMP->getStorer('setup');
      // verifico applicazione e codice in modo da non inserire duplicati
      $storer->checkMode("applicazione");
      $storer->checkMode("codice");
      
      // imposto applicazione e codice da db, e valore recuperato da file
      $storer->set("applicazione",$l->get('applicazione'));
      $storer->set("codice",$l->get('codice'));
      $storer->set("valore",$value);
#       $storer->set("note","");
#       $storer->set("tipo","Amministratore");
      
//        print "creo storer: {'Applicazione':'{$l->get('applicazione')}','codice':'{$l->get('codice')}','valore':'{$value}','tipo':'Amministratore'}<br>";
#      $IMP->debugLevel =3;
      // salvo la configurazione in db
      $storer->store();
#      $IMP->debugLevel = -1;
    }
  }   
}

if(!$IMP->appconf["auth"]["enable_auth_hub"]) {
  $IMP->appconf["portal"]["start_auth"]["show_buttons"] = false;
  $IMP->appconf["portal"]["start_auth"]["auditor"] = false;
} else {
  $IMP->appconf["portal"]["start_auth"]["auditor"] = $IMP->appconf["auth"]["redirect_url_auth_hub"];
}
}

/* FINE MODIFICHE PER CONFIG IN DB */
// }

/*
$IMP->config['webdav']['defaultElement']['news::notizia']['html'] = 'testo';
$IMP->config['webdav']['defaultElement']['news::notizia']['htm'] = 'testo';
$IMP->config['webdav']['defaultDirElement']['news::notizia'] = 'testo';
*/

if ($_REQUEST['o']) $IMP->defaults['display'] = $_REQUEST['o'];
else $IMP->defaults['display'] = 'html.dhtml';
$charset = $IMP->config['charset'];
if (!$charset) $charset = 'ISO-8859-1';
if ($IMP->defaults['display'] == 'xml'){
  $IMP->xmlMode = true;
  header("Content-type: text/xml; charset=$charset");
  print '<?xml version="1.0" encoding="'.$charset.'"?>';
  print "<response>";
}
elseif (!$IMP->defaults['display'] || $IMP->defaults['display'] == 'html' || $IMP->defaults['display'] == 'html.dhtml'){ 
	header("Content-type: text/html; charset=$charset");
}

function leggiImpostazioni($__file){
	global $IMP;
	include_once($__file);
	$confVars = get_defined_vars();
	unset($confVars['__file']);
	global $IMP;
	$__dir = dirname($__file);
	$__dir = str_replace(SITE_CONFIG.'/impostazioni', '', $__dir);
	$__dir = str_replace('//', '/', $__dir);
	$dirparts = explode('/', $__dir);
	$confPointer = & $IMP->appconf;
	foreach ($dirparts as $part){
		if (!$part) continue;
		$confPointer = & $confPointer[$part];
	}
	foreach ($confVars as $key=>$value){
	  $confPointer[$key] = $value;
	}
}
if ($_SESSION[$sessionPrefix.'userId']) $IMP->security->loadUser($_SESSION[$sessionPrefix.'userId']);
else $IMP->security->loadUser(0);

?>

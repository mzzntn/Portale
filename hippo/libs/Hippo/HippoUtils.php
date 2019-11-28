<?
include_once(LIBS.'/Data/DataTypes/dateTime.php');

#if (version_compare(PHP_VERSION, '5.0.0', '>=')){
#    die "Stai usando PHP 4 con le librerie per la versione 5";
#} 

/**
* string pathToUrl(string)
* Transform a path in the ICS directory context to the corresponding url
*
* @return the url
**/
function pathToUrl($path){
  $url = str_replace(BASE, HOME, $path);
  $url = str_replace('\\', '/', $url);
  return $url;
}

/**
* string fixFileName(string)
* Fix an imp-formed filename for use on filesystem
*
* @return the fixed filename
**/
function fixFileName($fileName){
  $fileName = str_replace('::', '_', $fileName);
  return $fileName;
}

/**
* string[] parseClassName(string)
* Split an ICS className into its components
*
* @return array(accessMode, nameSpace, localName, dir)
**/
function parseClassName($className){
  global $IMP;
  #the regular expression representing the className structure
  #accessMode://nameSpace::dir/localName
  $class_preg = '/((\w+):\/\/)?((\w+)::)?(?:([\w\/]+)\/)?((?:\w|-|\.)+)$/';
  if ( !preg_match($class_preg, $className, $matches) ) return 0;
  $accessMode = $matches[2];
  $classLocation = $matches[3].$matches[5];
  $nameSpace = $matches[4];
  $dir = $matches[5];
  $localName = $matches[6];
  $IMP->debug("Class $className parsed to '$accessMode', '$nameSpace', '$localName', '$dir'", 6);
  return array($accessMode, $nameSpace, $localName, $dir);
}

function mergeClassName($accessMode, $nameSpace, $localName, $dir=''){
  $className='';
  if ($accessMode) $className = $accessMode.'://';
  if ($nameSpace) $className .= $nameSpace.'::';
  if ($dir) $className .= $dir.'/';
  $className .= $localName;
  return $className;
}

/**
* string findClass(string, string)
* Get the path of a class (widget, template, displayer, or struct)
*
* @return the path where the class was found
**/
function findClass($class, $lib){
  global $IMP;
  global $_CLASS_PATHS;
  
  $IMP->debug("Looking for CLASS $class, LIB $lib", 4);
  $cacheArray = & $_CLASS_PATHS[$lib];
  $cacheFile = $lib.'Paths';
  #if this is the first time findClass is called, try to load the cache
  if (!$cacheArray){
    $cacheArray = $IMP->cache->load($cacheFile);
  } 
  list($accessMode, $nameSpace, $localName, $dir) = parseClassName($class);
  $classLocation = $localName;
  if ($dir) $classLocation = $dir.'/'.$classLocation;
  if ($nameSpace) $classLocation = $nameSpace.'::'.$classLocation;
  #classLocation is nameSpace::dir/localName
  $path = $cacheArray[$classLocation];
  $IMP->debug("In cache: ".$path, 6);
  if ( $path && file_exists($path) ) return $path; #good, found the file in the cache
  #have to look for the file
  if ($lib == 'structs' || $lib == 'bindings') $extension = '.xml';
  else $extension = '.php';
  $fileName = $localName.$extension;
  #templates and displayers can be found in the 'widgets' dir; other libs have their own top level dir
  if ($lib == 'DataTypes') $searchPath = LIBS.'/Data/DataTypes';
  else{
    if ($lib == 'templates' || $lib == 'displayers') $libDir = 'widgets';
    elseif($lib == 'bindings') $libDir = 'structs';
    else $libDir = $lib;
    if ($nameSpace) $searchPath = APPS.'/'.$nameSpace.'/'.$libDir;
    else $searchPath = BASE.'/'.$libDir;
    if ($lib == 'bindings'){  //:KLUDGE:
      if ($nameSpace){
        if (defined('APPS_BINDINGS')) $searchPath = APPS_BINDINGS.'/'.$nameSpace;
      }
      else if (defined('BASE_BINDINGS')) $searchPath = BASE_BINDINGS;
    }
  }
  if ($dir) $searchPath .= '/'.$dir;
  if ($lib == 'structs' && $nameSpace && defined('STRUCTS')){
    $searchPath = array(STRUCTS, $searchPath);
  }
  $path = find_file($searchPath, $fileName);
  $IMP->debug("Found: ".$path, 6);
  if ($path){
    $cacheArray[$classLocation] = $path;
    $IMP->cache->store($cacheFile, $cacheArray);
    return $path;
  }
  return 0;
}


/**
* void search_include(string, string)
* Look for a class (widget, template, displayer) and <i>include</i> its PHP code
**/
function search_include($class, $lib){
  global $IMP;
  if ($IMP->loadedClasses[$lib][$class]) return; #this is not really needed, but I like it.
  $path = findClass($class, $lib);
  if (!$path) error("Could not find class '$class' in '$lib' for include");
  include_once($path);
  $IMP->loadedClasses[$lib][$class] = $path;
}

/**
* void search_require(string, string)
* Look for a class (widget, template, displayer) and <i>require</i> its PHP code
**/
function search_require($class, $lib){
  $path = findClass($class, $lib);
  if (!$path) error("Could not find class '$class' in '$lib' for require");
  require_once($path);
}

/**
* void include_widget(string)
* Shortcut to search_include($widgetClass, 'widgets')
**/
function include_widget($widgetClass){
  search_include($widgetClass, 'widgets');
}

/**
* void include_displayer(string)
* Shortcut to search_include($displayerClass, 'displayers')
**/
function include_displayer($displayerClass){
  search_include($displayerClass, 'displayers');
}

function error($text){
  print "<b>Error: $text</b>";
  print backtrace();
  exit;
}

function userError($text){
	print "<html><head>Errore</head>";
	print "<body><h4>$text</h4></body></html>";
}


function build_stair($separator, $string, $connector=-1){
  if ($connector == -1) $connector = $separator;
  $parts = explode($separator, $string);
  for ($i=0; $i < sizeof($parts); $i++){
    if ($i>0) $stair[$i] = $stair[$i-1].$connector.$parts[$i];
    else $stair[0] = $parts[0];
  }
  return $stair;
}

function hasChildren($p) {
 if ($p->hasChildNodes()) {
  foreach ($p->childNodes as $c) {
   if ($c->nodeType == XML_ELEMENT_NODE)
    return true;
  }
 }
 return false;
}

function loadConstants($file){
  global $_COSTANTI;
  global $IMP;
  if (!file_exists($file)) return;
  $dom = new DOMDocument();
  if ( !$dom->load($file) ) return false;
  $root = $dom->documentElement;
  $rootName = $root->nodeName;
  $children = $root->childNodes;
  foreach ($children as $child){
    $name = $child->nodeName;
    if (hasChildren($child)){
      $value = domxml_get_array($child);
      $_COSTANTI[$name] = $value;
    }
    else{
      $value = utf8_decode($child->textContent);
      define($name, $value);
    } 
    $IMP->settings[$rootName][$name] = $value;
  }
}

function loadEl($struct, $el, $params){
  global $IMP;
  $loader = & $IMP->getLoader($struct);
  $loader->request($el);
  foreach ($params as $key => $value){
    $loader->addParam($key, $value);
  }
  $res = $loader->load();
  return $res->get($el);
}

//if $params is scalar it is treated as the id
function load($struct, $params){
  global $IMP;
  $loader = & $IMP->getLoader($struct);
  $loader->requestAll();
  if (is_array($params)) foreach ($params as $key => $value){
    $loader->addParam($key, $value);
  }
  else $loader->addParam('id', $params);
  return $loader->load();
}

function domxml_get_array($node, $name=''){
    global $debug;
    $children = $node->childNodes;
    $array = array();
    foreach ($children as $child){
        if ($child->nodeType == XML_TEXT_NODE || $child->nodeType == XML_CDATA_SECTION || $name == '#cdata-section' || $name == '#text') continue;
        $hasChildren = false;
        $nephews = $child->childNodes;
        foreach ($nephews as $nep){
            if ($nep->nodeType != XML_TEXT_NODE && $nep->nodeType != XML_CDATA_SECTION && $nep->nodeName != '#cdata-section' && $nep->nodeName != '#text'){
                $hasChildren = true;
                break;
            }
        }
        if ($hasChildren) $array[$child->nodeName][] = domxml_get_array($child, $child->nodeName);
        else $array[$child->nodeName][] = $child->textContent;
    }
    return $array;
}

class _Exception{
  var $test;
}

function _exception($type, $text='', $e=0){
  global $IMP;
  if (!$e) $e = new _Exception();
  $e->text = $text;
  if ($IMP->catch[$type]) $IMP->catched[$type] = $e;
  else error($text);
  return true;
}

function _catch($exceptions){
  global $IMP;
  if (!is_array($exceptions)) $exceptions = array($exceptions);
  foreach ($exceptions as $exception){
    $IMP->catch[$exception] = true;
  }
}

function _catched(){
  global $IMP;
  $catched = $IMP->catched;
  $IMP->catch = array();
  $IMP->catched = array();
  if (sizeof($catched)) return $catched;
  return false;
  
}

function backtrace()
 {
    $output = "<div style='text-align: left; font-family: monospace;'>\n";
    $output .= "<b>Backtrace:</b><br />\n";
    $backtrace = debug_backtrace();
    foreach ($backtrace as $bt) {
        $args = '';
        if (is_array($bt['args'])) foreach ($bt['args'] as $a) {
            if (!empty($args)) {
                $args .= ', ';
            }
            switch (gettype($a)) {
            case 'integer':
            case 'double':
                $args .= $a;
                break;
            case 'string':
                $a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
                $args .= "\"$a\"";
                break;
            case 'array':
                $args .= 'Array('.count($a).')';
                break;
            case 'object':
                $args .= 'Object('.get_class($a).')';
                break;
            case 'resource':
                $args .= 'Resource('.strstr($a, '#').')';
                break;
            case 'boolean':
                $args .= $a ? 'True' : 'False';
                break;
            case 'NULL':
                $args .= 'Null';
                break;
            default:
                $args .= 'Unknown';
            }
        }
        $output .= "<br />\n";
        $output .= "<b>file:</b> {$bt['line']} - {$bt['file']}<br />\n";
        $output .= "<b>call:</b> {$bt['class']}{$bt['type']}{$bt['function']}($args)<br />\n";
    }
    $output .= "</div>\n";
    return $output;
 }
?>

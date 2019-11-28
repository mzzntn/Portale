<?

class IMP{
  var $debugLevel;
  var $lastLevel;
  var $debugVar;
  var $pipeline;
  var $pipelines;

  function debug($text, $level=0, $section=''){
    if ($this->debugLevel < $level) return;
    $debug = '';
    $time = (float) array_sum(explode(' ', microtime()));
    if (!$this->scriptStart) $this->scriptStart = $time;
    $elapsed = $time - $this->scriptStart;
    if ($this->lastTick) $timeDiff = $time - $this->lastTick;
    else $timeDiff = 0;
    $this->lastTick = $time;
    if (is_object($text) && method_exists($text, '_debug')){
      $debug .= $text->_debug($this->debugMode);
    }
    elseif  (is_object($text) || is_array($text)){
      if ($this->debugMode == 'html') $debug .= "<pre>";
      $debug .= var_export($text, true);
      if ($this->debugMode == 'html') $debug .=  "</pre>";
    }
    else $debug .= $text;
    $text = $debug;
    $debug = "$elapsed ($timeDiff)";
    if ($section) $debug .= "-$section-";
    $debug .= ": $text";
    switch($this->debugMode){
      case 'file':
        $this->debugToFile($debug, $section);
        break;
      case 'html':
        if (function_exists('fb')) fb($debug);
	    else print $debug."<br>\n";
        break;
      default:
        print $debug;  
    }
  }

  function debugToFile($text, $section=''){
    if ($IMP->debugFile) $file = $IMP->debugFile;
    #elseif ($section) $file = '';//:TODO:
    else $file = VARPATH.'/log/debug.txt';
    $fp = fopen($file, 'a');
    fwrite($fp, $text."\n");
    fclose($fp);
  }
  
  function debugTo(& $var){
    $this->debugVar = & $var;
  }
  
  function debugToPrint(){
    unset($this->debugVar);
  }
  
  function createPipeline($name){
    $this->pipelines[$name] = new Pipeline($name);
    $this->pipeline = $name;
  }
  
  function & getPipeline($name=''){
    if (!$name){
      if ($this->pipeline) $name = $this->pipeline;
      else $name = 'main';
    }
    if (!$this->pipelines[$name]) $this->createPipeline($name);
    return $this->pipelines[$name];
  }
 
  function loadPipelines(){
    $this->pipelines = $_SESSION['pipelines'];
  }

  function savePipelines(){
    $_SESSION['pipelines'] = $this->pipelines;
  }

	function getDbObject($type){
		$className = 'Db_'.$type;
		return new $className();
	}
  
  function & getLoader($structName){
    $loader = & $this->bindingManager->getLoader($structName);
    if (!$loader) return;
    $loader->init();
    return $loader;
  }
  
  function & getStorer($structName){
    $storer = & $this->bindingManager->getStorer($structName);
    $storer->init();
    return $storer;
  }
  
  function & getDeleter($structName){
    $deleter = & $this->bindingManager->getDeleter($structName);
    $deleter->init();
    return $deleter;
  }
  
  function getIndex($name,$structName){
    if ($this->config['defaultdb']['type'] == 'mysql' && $this->config['nosequenze']){
      $binding = $this->bindingManager->getBinding($structName);
      $db = $binding->getDbObject();
      
      if(!$this->config['copyMode']){
      //file_put_contents("debug.log", "INSERT INTO {$name} (PERMS) values ('ns')"."\n", FILE_APPEND);
      if($db->execute("INSERT INTO {$name} (PERMS) values ('ns')")) {
	//file_put_contents("debug.log", "returned id is {$id}"."\n", FILE_APPEND);
	$id = mysql_insert_id();
	//file_put_contents("debug.log", "returned id is {$id}"."\n", FILE_APPEND);
	if($id && $id!=0) {
	  return($id);
	} else {
	  die("Attenzione: si &egrave; verificato un errore fatale durante il recupero dell'id. L'id non pu&ograve; essere 0. ".mysql_error());
	}
      } else {
	//file_put_contents("debug.log", mysql_error()."\n", FILE_APPEND);
	die("Attenzione: si &egrave; verificato un errore fatale durante il recupero dell'id: ".mysql_error());
      }
    } 
    }  
      createPath(VARPATH.'/sequences');
      return get_index_from_file(VARPATH.'/sequences/'.$name); 
    }

  function setIndex($name, $index){
    if ($this->config['defaultdb']['type'] != 'mysql' || !$this->config['nosequenze']){
      fixFileName($name);
      createPath(VARPATH.'/sequences');
      $fp = fopen(VARPATH.'/sequences/'.$name, 'w');
      fwrite($fp, $index);
      fclose($fp);
    } // altrimenti non faccio nulla? non posso usare set su un campo autoincrement
  }
  
  function loadJs($script){
    global $IMP;
    if ($this->loadedJs[$script]) return;
    $this->loadedJs[$script] = true;
    if ($script == 'scriptaculous'){
      print "<script type='text/javascript' src='".URL_JS."/scriptaculous-js-1.5.1/lib/prototype.js'></script>";
      print "<script type='text/javascript' src='".URL_JS."/scriptaculous-js-1.5.1/src/scriptaculous.js'></script>";
      return;
    }
    if ($script == 'myHTML/myHTML') $ext = 'php'; #:KLUDGE:!
    else $ext = 'js';
    if ($IMP->config['inlineJs']){
      print "<script type='text/javascript'>";
      include(PATH_JS.'/'.$script.'.'.$ext);
      print "</script>";
    }
    else print "<script type='text/javascript' src='".URL_JS."/{$script}.{$ext}'></script>";
  }
  
  function widgetsForStruct($structName){
    $tryWidget = $structName;
    $location = findClass($tryWidget, 'widgets');
    if ($location) return array($tryWidget);
  }
  
  function & getWidget($fullType, $name='', $structName=''){
    if (!$name) $name = $fullType;
    return $this->widgetFactory->getWidget($fullType, $name, $structName);
  }
  
  function canDisplay($mode){
    global $IMP;
    
    if ($mode == 'html' &&  $IMP->defaults['display'] != 'html' && $IMP->defaults['display'] != 'html.dhtml') return false;
    if ($_REQUEST['target']) return false; //:FIXME: no good
    return true;
  }
  
  function loadConfig($file){
    if (!file_exists($file)) return;
    $dom = new DOMDocument();
    $loaded = $dom->load($file);
    if (!$loaded) return false;
    $root = $dom->documentElement;
    $this->loadConfigDomNode($root);
  }
  
  function loadConfigDomNode($root, $prefix=''){
    if (!is_array($prefix)) $prefix = array();
    $target = & $IMP->config;
    foreach ($prefix as $key){
      $target = & $target[$key];
    }
    $children = $root->childNodes;
    foreach ($children as $child){
      $name = $child->nodeName;
      
      if ($child->hasChildNodes()){
        $newPrefix = array_push($prefix, $name);
        $this->loadConfig($child, $newPrefix);
      }
      else{
        $value = utf8_decode($child->textContent);
        $this->config[$name] = $value;
      } 
    }
  }
  
}


?>

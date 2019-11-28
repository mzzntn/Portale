<?

class WidgetParams{
  var $params;
  var $aliveWidgets;
  var $preserved;
  var $binded;

  /**
  * WidgetParams()
  * Upon creation, the WidgetParams object loads the appropriate variables from session 
  **/
  function WidgetParams(){
    if (isset($_SESSION['widgets'])) $this->params = $_SESSION['widgets'];
    $request = array_merge_recursive2($_REQUEST, $this->getFiles());
    if (is_array($request)) foreach ($request as $widgetName => $widgetParams){ #:KLUDGE:?
      if ( is_array($widgetParams) ){
        foreach ($widgetParams as $key => $value){
          $this->params[$widgetName][$key] = $value;
        }
      }
     //   $this->params[$widgetName] = array_merge($this->params[$widgetName], $widgetParams);
     // else $this->params[$widgetName] = $widgetParams;
    }
    #widget which should be cleared are passed in a _clear[] array in the request
    unset($this->params['_clear']);
    $clear = $_REQUEST['_clear'];
    if ($clear == 'all') unset($this->params);
    elseif ( is_array($clear) ) foreach ( array_keys($clear) as $clearWidget){
      unset($this->params[$clearWidget]);
    }
  }

  /**
  * mixed get(string [, string])
  * Get all the parameters of a widget or a specific value 
  *
  * @return an associative array containing the parameters of $widgetName and their values, 
  * or the value of $paramName if it's given 
  **/
  function get($widgetName, $paramName=''){
    $this->isAlive($widgetName);
    if (!$paramName) return $this->params[$widgetName];
    return $this->params[$widgetName][$paramName];
  }
  
  function take($widgetName, $paramName=''){
    $res = $this->get($widgetName, $paramName);
    $this->clear($widgetName, $paramName);
    return $res;
  }
  
  function getPelican($widgetName){
    return new PHPelican($widgetName, $this->get($widgetName));
  }

  /**
  * void set(string, string, mixed='')
  * Set a parameter
  *
  * Sets parameter $paramName to $paramValue for $widgetName
  **/
  function set($widgetName, $paramName, $paramValue=''){
      if (is_array($paramName)){
          foreach ($paramName as $key => $value) $this->set($widgetName, $key, $value);
      }
      else $this->params[$widgetName][$paramName] = $paramValue;
  }
  
  function clearParam($widgetName, $paramName){
    unset($this->params[$widgetName][$paramName]);
  }
  
  /**
  * void register(string, string, mixed)
  * Register a parameter by reference so that its value will change whenever $paramValue changes
  **/
  function register($widgetName, $paramName, &$paramValue){
    $this->params[$widgetName][$paramName] = & $paramValue;
  }
  
  /**
  * void clear(string)
  * Unset all parameters for a widget
  **/
  function clear($widgetName='', $paramName=''){
    if ($paramName) unset($this->params[$widgetName][$paramName]);
    else if ($widgetName) unset($this->params[$widgetName]);
    else unset($this->params);  
  }
  
  /**
  * bool isAlive(string)
  * Determine if a widget is alive
  **/
  function isAlive($widgetName){
    $this->aliveWidgets[$widgetName] = true;
    if ( is_array($this->binded[$widgetName]) ) foreach ( array_keys($this->bindedWidgetName) as $childWidget){
      $this->isAlive($childWidget);
    }
  }

  /**
  * ?
  **/
  function preserve($widgetName, $paramName=0){
    if (!$paramName) $this->preserved[$widgetName] = true;
    else $this->preserved[$widgetName][$paramName] = true;
    #:TODO: handle preserved widgets
  }
  
  /**
  * void bind(string, string)
  * Bind a widget to another so that if the second is alive the first will be too
  **/
  function bind($parentWidget, $childWidget){
    $this->binded[$parentWidget][$childWidget] = true;
  }
  
  function reduce($widgetName){
    if (!is_array($this->params[$widgetName])) return;
    foreach (array_keys($this->params[$widgetName]) as $key){
      if (!$this->params[$widgetName][$key] && !is_int($this->params[$widgetName][$key]))
        unset($this->params[$widgetName][$key]);
    }
  }
  
  /**
  * void dumpToSession(string)
  * Save the params to session
  **/
  function dumpToSession(){
    global $IMP;
    #IF UNCOMMENTED, widgets are kept in session only if alive
    #unset($_SESSION['widgets']);
    if (is_array($this->aliveWidgets) ) foreach( array_keys($this->aliveWidgets) as $widgetName){
      $this->reduce($widgetName);
      $_SESSION['widgets'][$widgetName] = $this->params[$widgetName];
    }
    $IMP->debug("Dumped to session:", 5);
    $IMP->debug($_SESSION, 5);
  }
  
  
  function getFiles(){
    if (sizeof($_FILES)<1) return array();
    foreach ($_FILES as $key => $value){
      if (is_array($value)) foreach ($value as $fileKey => $value2){
        $newFiles[$fileKey][$key] = $value2;
      }
    }
    $dest = array();
    foreach ($newFiles as $fileKey => $files){
      $this->fixFilesArray($fileKey, $newFiles[$fileKey], $dest);
    }
    return $dest;
  }
  
  function fixFilesArray($fileKey, & $filesArray, & $dest){
    $fileKeys = array('name', 'type', 'size', 'tmp_name', 'error');
    foreach($filesArray as $key => $value){
      if (is_array($value)){
        $this->fixFilesArray($fileKey, $filesArray[$key], $dest[$key]);
      }
      else{
        $dest[$key][$fileKey] = $filesArray[$key];
      }
    }
  }
  
  
}

?>

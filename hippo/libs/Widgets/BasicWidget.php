<?

class BasicWidget{
  var $name;
  var $widgetType;  //set by WidgetFactory
  var $fullType;    //set by WidgetFactory
  var $nameSpace;   //set by WidgetFactory
  var $path;        //set by WidgetFactory
  var $htmlName;
  var $mode;
  var $children;
  var $widgetParams;
  var $widgetFactory;
  var $displayMode;
  var $displayer;
  var $parentWidget;
  var $classes;
  var $attributes;
  var $publicAttributes;
  var $styleManager;
  var $config;
  var $started;
  var $template;
  var $templateFolder;
  var $style;
  var $content;


  function BasicWidget($name){
    global $IMP;
    $this->name = $name;
    $this->htmlName = $this->fixForHtml($name);
    $this->widgetParams = & $IMP->widgetParams;
    $this->styleManager = & $IMP->styleManager;
    $this->widgetFactory = new WidgetFactory();
    $this->children = array();
    $this->classes = array();
    $this->config = array();
  }

  function init(){
    $this->loadConfig();
  }

  function loadConfig(){
    global $IMP;
    if ($this->loadedConfig) return;
    $this->loadedConfig = true;
    if ($IMP->config[$this->fullType]){
     $this->config = array_merge($this->config, $IMP->config[$this->fullType]);
    }
    $this->setDefaults();
  }

  function setDefaults(){
    //virtual: use to set default config values
  }

  /**
  * false isDataWidget()
  * Is the widget a DataWidget?
  *
  * @return false
  **/
  function isDataWidget(){
    return false;
  }

  /**
  * void setConfig(string, mixed)
  * Set a configuration parameter
  **/
  function setConfig($name, $value){
    $this->config[$name] = $value;
  }

  /**
  * void setOption(string, mixed)
  * Alias for @link{setConfig}
  **/
  function setOption($name, $value){
    return $this->setConfig($name, $value);
  }

  function parseOptions($optionString=''){
    if ($optionString){
      $options = explode(',', $optionString);
      foreach ($options as $option){
        list($key, $value) = explode('=', $option);
        $this->config[$key] = $value;
      }
    }
  }

  /**
  * void setDisplayer(Displayer)
  * Set the displayer this widget will use
  **/
  function setDisplayer(& $displayer){
    $this->displayer = & $displayer;
    $this->displayer->name = $this->htmlName;
    $this->d = & $this->displayer;
  }

  /**
  * void setWidgetParams(WidgetParams)
  * Set the WidgetParams object for the object
  **/
  function setWidgetParams(& $widgetParams){
    $this->widgetParams = & $widgetParams;
  }

  /**
  * void setDisplayMode(string)
  * Set a display mode for the widget
  **/
  function setDisplayMode($displayMode){
    $this->displayMode = $displayMode;
  }

  function setTemplate($template){
    $this->template = $template;
  }

  function setTemplateFolder($templateFolder){
    $this->templateFolder = $templateFolder;
  }

  function setStyle($style){
    $this->style = $style;
  }

  function prepareDisplayer(){
    if (!$this->displayer || $this->tmp['reloadDisplayer']) $this->loadDisplayer();
  }


  /**
  * void display()
  * Display the widget using the current displayer
  *
  * Note that the displayer, unless the display function is overloaded, will call back
  * the method wdisplay in
  * the widget, which, if it is defined, contains any post-display code.
  **/
  function display(){
    $this->prepareDisplayer();
    if ($this->cache){
      $cache = $this->getCache();
      if ($cache){
        print $cache;
        return;
      }
      ob_start();
    }
    $this->displayer->display();
    if ($this->cache){
      $this->writeCache(ob_get_flush());
    }
  }

  function d(){
    $this->display();
  }

  /**
  * void addClass(string)
  * Add a CSS class name
  **/
  function addClass($className){
    $this->classes[] = $className;
  }

  /**
  * void addAttribute(string)
  * Add an CSS attribute
  **/
  function addAttribute($attribute){
    $this->attributes[$attribute] = true;
  }

  /**
  * void addPublicAttribute(string)
  * Add an inheritable ICSS attribute
  **/
  function addPublicAttribute($attribute){
    $this->publicAttributes[$attribute] = true;
  }

  /**
  * string fixForHtml(string)
  * Fix an ICS string to be used in HTML
  **/
  function fixForHtml($string){
    $string = strtr($string, "[", "_");
    $string = str_replace("]", "", $string);
    $string = str_replace("::", "_", $string);
    return $string;
  }

  /**
  * mixed getParam(string)
  * Fetch a value from the WidgetParams
  **/
  function getParam($paramName=''){
    return $this->widgetParams->get($this->name, $paramName);
  }

  function getParams(){
    return $this->getParam();
  }

  function takeParam($paramName){
    return $this->widgetParams->take($this->name, $paramName);
  }

  /**
  * void setParam(string, mixed)
  * Set a key-value pair in the WidgetParams
  **/
  function setParam($paramName, $paramValue){
    $this->widgetParams->set($this->name, $paramName, $paramValue);
  }

  function clearParam($paramName){
    $this->widgetParams->clearParam($this->name, $paramName);
  }

  function clearParams(){
    $this->widgetParams->clear($this->name);
    foreach (array_keys($this->children) as $key){
      $this->children[$key]->clearParams();
    }
  }

  /**
  * mixed & createWidget(string, string)
  * Create a new widget from this one.
  *
  * This method returns the reference to a new widget. It is IMPORTANT to always call this method as
  * $newWidget = & $this->createWidget($type, $name), to really get a reference and not mess things up,
  * since the newly created widget will have a reference to this one (PHP 4.x).
  * @return a Widget of the appropriate type.
  **/
  function & createWidget($type, $name=''){
    $widget = & $this->widgetFactory->getWidget($type, $name);
    $this->children[] = & $widget;
    $widget->parentWidget = & $this;
    return $widget;
  }

  function & getWidget($type, $name=''){
    return $this->createWidget();
  }

  /**
  * bool hasDisplayer(string)
  * Check if the widget has a Displayer defined for the mode $mode
  **/
  function hasDisplayer($mode){
    global $IMP;
    $IMP->debug("Preparing to look for a displayer for mode '$mode'", 5);
    list ($displayerBase, $displayerPath, $displayerFileName) = $this->findDisplayer($this->type, $mode);
    if (!$displayerBase || !$displayerPath || !$displayerFileName) return 0;
    return 1;
  }

  /**
  * array recursiveGetVar(string)
  * Get a parameter from this object and all its parents
  * @access private
  *
  * @return an array of all istances of the class' parameter of name $var in this object and all its parents
  **/
  function recursiveGetVar($var){
    if ($this->parentWidget){
      if ( is_array($this->{$var}) ) return array_merge($this->parentWidget->recursiveGetVar($var), $this->{$var});
      else{
       $r = $this->parentWidget->recursiveGetVar($var);
       array_push( $r, $this->{$var} );
       return $r;
      }
    }
    else{
      if ( is_array($this->{$var}) ) return $this->{$var};
      else return array($this->{$var});
    }
  }

  /**
  * string buildClassString([string])
  * Get the string describing the CSS class of the widget, or of any of its elements.
  **/
  function buildClassString($element=''){
    #$classes = $this->recursiveGetVar('classes');
    $classes = $this->classes;
    $classString = '';
    foreach ($classes as $class){
      if ($classString) $classString .= ' ';
      $classString .= $class;
    }
    if ($element){
      if ($classString) $classString .= ' ';
      $classString .= $element;
    }
    return $classString;
  }


  /**
  * array getAttributesArray()
  * Get the attributes applying to the widget
  *
  * @return an associative array whose keys are the attributes applying to the widget
  **/
/*
OBSOLETE
  function getAttributesArray(){
    $attributes = $this->recursiveGetVar('publicAttributes');
    $attributes = array_merge($attributes, $this->attributes);
    return $attributes;
  }
*/

  /**
  * string getCSSClass([string])
  * Get the CSS class for the widget, or for one of its elements
  **/
  function getCSSClass($element=''){
    $classString = $this->buildClassString($element);
    return $classString;
  }


  /**
  * string setMode(string)
  * Set the display mode
  **/
  function setMode($mode){
    $this->mode = $mode;
    $this->tmp['reloadDisplayer'] = true;
  }

  /**
  * void loadDisplayer()
  * Loads the appropriate displayer, given the mode
  * @access private
  **/
  function loadDisplayer(){
    global $IMP;
    $display = $this->displayMode;
    $IMP->debug("Preparing to load displayer $display", 5);
    list($displayerPath, $displayerClass, $foundDisplay, $foundMode) = $this->findDisplayer($display, $this->mode);
    if (!$displayerPath){
      error("Displayer '$display' not found for mode '{$this->mode}'");
    }
    else{
      include_once($displayerPath);
      if ($foundMode) $displayerClass .= '_'.$foundMode;
      $displayer = new $displayerClass($this);
      $this->setDisplayer($displayer);
      //$dir = $displayerPath;
      if (!$this->templateFolder){
        if (!$this->nameSpace || $this->nameSpace == 'base') $dir = BASE.'/widgets';
        else $dir = APPS.'/'.$this->nameSpace.'/widgets';
        if ($this->path) $dir .= '/'.$this->path;
        $dir .= '/'.$this->widgetType;
        $dir .= '/templates';
        $disStair = build_stair('.', $display, '/');
        $tryDir = $dir.'/'.$disStair[sizeof($disStair)-1];
        for ($i = sizeof($disStair)-2; ($i >= 0) && !file_exists($tryDir); $i--){
          $templatePath = $disStair[$i];
          $tryDir = $dir.'/'.$templatePath;
        }
        $dir = $tryDir;
      }
      else $dir = $this->templateFolder;
      $this->displayer->templateFolder = $dir;
      if (!$this->template && $IMP->defaultTemplate[$this->fullType]){
        $this->template = $IMP->defaultTemplate[$this->fullType];
      }
      if ($this->template) $this->displayer->setTemplate($this->template);
      if ($this->style) $this->displayer->setStyle($this->style);
    }
    $this->tmp['reloadDisplayer'] = false;
  }

  /**
  * array(string, string, string) findDisplayer(string[, string])
  * Find a displayer
  * @access private
  **/
  function findDisplayer($display, $mode=''){
    global $IMP;
    if ($_REQUEST['target']){
      if ((is_string($_REQUEST['target']) && $_REQUEST['target'] != $this->name) || (is_array($_REQUEST['target']) && !$_REQUEST['target'][$this->name])){
        return array(LIBS.'/Widgets/displayer_passthrough.php', 'Displayer_passthrough', '', '');
      }
    }
    $IMP->debug("Looking for a displayer $display, $mode", 4);
    $fullDisplay = $display;
    $displayString = str_replace('.', '_', $display);
    $disStair = build_stair('.', $display, '_');
    $displayerPath = '';
    for ($i = sizeof($disStair)-1; ($i >= 0) && !$displayerPath; $i--){
      $display = $disStair[$i];
      $displayerClass = 'd_'.$this->widgetType.'_'.$display;
      $fullClass = $this->getFullClass();
      $fullClass .= '/displayers/';
      if ($mode) $fullClass .= $mode.'/';
      $fullClass .= $displayerClass;
      $displayerPath = findClass($fullClass, 'displayers');
    }
    if ( !$displayerPath ){
      if ($mode) return $this->findDisplayer($display); #check if a basic displayer exists
      //fallback on basic displayer
      if ($displayString == 'html_dhtml') $displayString = 'dhtml';
      $displayerPath = LIBS.'/Widgets/displayer_'.$displayString.'.php';
      if (file_exists($displayerPath)){
        $displayerClass = 'Displayer_'.$displayString;
        $display = $fullDisplay;
      }
      else{
        $displayerPath = LIBS.'/Widgets/displayer_passthrough.php';
        $displayerClass = 'Displayer_passthrough';
        $display = '';
      }
    }
    return array($displayerPath, $displayerClass, $display, $mode);
  }

  /**
  * string getFullClass()
  * Get the full classname for the widget (NameSpace::WidgetType)
  **/
  function getFullClass(){
    if ($this->nameSpace) $fullClass = $this->nameSpace.'::';
    if ($this->path) $fullClass .= $this->path.'/';
    $fullClass .= $this->widgetType;
    return $fullClass;
  }

  function start(){
    $this->started = true;
    #virtual
  }

  function end(){
    #virtual
  }
  
  function cache($options=''){
    global $IMP;
    if ($IMP->config['disableCache']) return;
    $this->cache = true;
    $this->cache_options = $options;
    /*
    if ($options['invalidate_by_structs']){
      foreach ($options['invalidate_by_structs'] as $struct){
        $IMP->cache_invalidate_by_structs[$struct][$this->widgetType][$this->name] = true;
      }
    }
    */
  }
  
  function cachePath(){
    $path = VARPATH.'/widgets_cache/'.$this->nameSpace.'/'.$this->widgetType.'/'.$this->name.'/'.$this->mode.'/'.$this->template;
    if ($this->cache_options['params']){
      
    }
    return $path;
  }
  
  function writeCache($cache){
    $path = $this->cachePath();
    createPath($path);
    $fp = fopen($this->cachePath().'/cache', 'w');
    fwrite($fp, $cache);
    fclose($fp);
  }
  
  function getCache(){
    global $IMP;
    $path = $this->cachePath();
    $file = $path.'/cache';
    $classFile = $IMP->loadedClasses['widgets'][$this->fullType];
    $templateFile = $this->displayer->getTemplateFile();
    if (file_exists($file) && filemtime($file) > filemtime($templateFile) && filemtime($file) > filemtime($classFile)){
      return file_get_contents($file);
    }
  }


}


?>

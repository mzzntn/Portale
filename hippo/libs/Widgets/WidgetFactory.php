<?
class WidgetFactory{
  
  function WidgetFactory(){
  }

  /**
  * & object getWidget(string, string)
  * Create a widget and make it ready for work
  **/
  function & getWidget($fullType, $name='', $structName=''){   
    global $IMP;
    if (!$name) $name = 'w'.(++$IMP->widgetCnt);
    search_include($fullType, 'widgets');
    list($accessMode, $nameSpace, $type, $path) = parseClassName($fullType);
    #:NOTE: classes in different namespaces will conflict!
    $widget = new $type($name, $structName);
    $widget->widgetType = $type;
    $widget->fullType = $fullType;
    $widget->nameSpace = $nameSpace;
    $widget->path = $path;
    $widget->widgetFactory = & $this;
    $widget->setDisplayMode($IMP->defaults['display']);
    $IMP->debug("Set display to ".$IMP->defaults['display']." for widget $name of type $fullType", 6);
    $widget->addClass($type);
    $widget->setDefaults();
    return $widget;
  }

}



?>

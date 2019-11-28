<?

class Widget extends DataWidget{
    
    function Widget($fullType, $name='', $structName=''){
        global $IMP;
        if (!$name) $name = 'w'.(++$IMP->widgetCnt);
        search_include($fullType, 'widgets');
        list($accessMode, $nameSpace, $type, $path) = parseClassName($fullType);
        #:NOTE: classes in different namespaces will conflict!
        $this = new $type($name, $structName);
        $this->widgetType = $type;
        $this->fullType = $fullType;
        $this->nameSpace = $nameSpace;
        $this->path = $path;
        $this->widgetFactory = & $IMP->widgetFactory;
        $this->setDisplayMode($IMP->defaults['display']);
        $IMP->debug("Set display to ".$IMP->defaults['display']." for widget $name of type $fullType", 6);
        $this->addClass($type);
        $this->setDefaults();
    }
    
}

?>
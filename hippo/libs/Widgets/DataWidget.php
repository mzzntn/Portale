<?

class DataWidget extends BasicWidget{
  var $typeSpace;
  var $bindingManager;
  var $security;
  var $structName;
  var $struct;
  
  function DataWidget($name, $structName=''){
    global $IMP;
    parent::BasicWidget($name);
    $this->structName = $structName;
    $this->typeSpace = & $IMP->typeSpace;
    $this->bindingManager = & $IMP->bindingManager;
    $this->security = & $IMP->security;
  }
  
  function isDataWidget(){
    return true;
  }
  
  function setStruct($structName){
    $this->structName = $structName;
  }
  
  function setTypeSpace(& $typeSpace){
    $this->typeSpace = & $typeSpace;
  }
  
  function setBindingManager(& $bindingManager){
    $this->bindingManager = & $bindingManager;
  }
  
  function setSecurity(& $security){
    $this->security = & $security;
  }
  
  function & getLoader($structName=''){
    global $IMP;
    if (!$structName) $structName = $this->structName;
    $IMP->debug("Creating loader for $structName", 5);
    $loader = & $this->bindingManager->getLoader($structName);
    $loader->setTypeSpace($this->typeSpace);
    $loader->setSecurity($this->security);
    $loader->init();
    return $loader;
  }
  
  function & getStorer($structName=''){
    global $IMP;
    if (!$structName) $structName = $this->structName;
    $IMP->debug("Creating storer for $structName", 5);
    $storer = & $this->bindingManager->getStorer($structName);
    $storer->setTypeSpace($this->typeSpace);
    $storer->setSecurity($this->security);
    $storer->init();
    return $storer;
  }
  
  function loadStruct(){
    $this->struct = & $this->typeSpace->getStructure($this->structName);
  }
  
  function & createWidget($type, $name){
    if (!$type || !$name) return; #this shouldn't happen, but it does
    $widget = & parent::createWidget($type, $name);
    if (method_exists($widget, 'setTypeSpace')){ #than it's a DataWidget
      $widget->setTypeSpace($this->typeSpace);
      $widget->setBindingManager($this->bindingManager);
      $widget->setSecurity($this->security);
    }
    return $widget;
  }
  
}


?>

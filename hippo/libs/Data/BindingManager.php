<?

class BindingManager{
  var $bindings;
  var $branchBindings;
  var $cache;
  
  function BindingManager(){
  }
  
  function set($structName, $bindingName, $branch=''){
    if ($branch) $this->branchBindings[$branch][$structName] = $bindingName;
    $this->bindings[$structName] = $bindingName;
  }  
  
  function bindingType($structName, $branch=''){
    global $IMP;
    if ($branch && $branch != 'main') return $this->branchBindings[$branch][$structName];
    $bindType = $this->bindings[$structName];
    list($accessMode, $nameSpace, $localName, $dir) = parseClassName($structName);
    if (strpos($localName, '.') !== false) $bindType = 'inline';
    if (!$bindType) $bindType = $IMP->defaults['binding'];
    return $bindType;
  }
  
  function loadXml($bindingType){
    if ($bindingType == 'db' || $bindingType == 'xml') return true;
    return false;
  }
  
  //removed reference
  function getBinding($structName, $justTry=false){
    if ($this->cache[$structName]) return $this->cache[$structName];
    $bindingType = $this->bindingType($structName);
    if ($this->loadXml($bindingType)){  //:FIXME: the binding must do that on its own
      list($accessMode, $nameSpace, $localName, $dir) = parseClassName($structName);
      $bindingName = mergeClassName($accessMode, $nameSpace, $localName.'-'.$bindingType);
      $file = findClass($bindingName, 'bindings');
      $e = new _Exception();
      $e->structName = $structName;
      if (!$file){
        if ($justTry) return;
        _exception('data.def.file.bindingNotFound',"Binding $bindingName for $structName not found", $e);
        return;
      }
    }
    $className = 'Binding_'.$bindingType;
    $binding = new $className();
    if ($file) $binding->load($file);
    $binding->structName = $structName;
    $this->cache[$structName] = & $binding;
    return $binding;
  }
  
  function & getLoader($structName){
    $binding = & $this->getBinding($structName);
    if (!$binding) return;
    $loader = $binding->getLoader();
    $loader->setBindingManager($this);
    return $loader;
  }
  
  function & getStorer($structName){
    $binding = & $this->getBinding($structName);
    $storer = $binding->getStorer();
    $storer->setBindingManager($this);
    return $storer;
  }
  
  function & getDeleter($structName){
    $binding = & $this->getBinding($structName);
    $deleter = $binding->getDeleter();
    $deleter->setBindingManager($this);
    return $deleter;
  }
  
  function & getConditionBuilder($structName){
    $binding = & $this->getBinding($structName);
    $conditionBuilder = $binding->getConditionBuilder();
    $conditionBuilder->setBindingManager($this);
    return $conditionBuilder;
  }
  

  
  function loadBindings($dir){
    if (!is_dir($dir)) return;
    $d1 = dir($dir);
    while (false != ($entry1 = $d1->read())){
      if ($entry1[0] == '.' || !is_dir($dir.'/'.$entry1)) continue;
      $d2 = dir($dir.'/'.$entry1);
      while (false != ($entry2 = $d2->read())){
        if ($entry2[0] == '.') continue;
        include($dir.'/'.$entry1.'/'.$entry2);
        if (is_array($bindings)) foreach ($bindings as $struct => $binding){
          $this->set($entry1.'::'.$struct, $binding);
        }
        unset($bindings);
      }
    }
  }
  

}


?>

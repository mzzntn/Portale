<?

class DataManager{
  var $structName;
  var $typeSpace;
  var $bindingManager;
  var $binding;
  var $struct;
  var $data;
  var $condition;
  var $conditionBuilder;
  var $security;
  var $versioner;
  var $params;
  var $processedStructs;
  var $lastSeenId;

  function DataManager($structName){
    global $IMP;
    $this->structName = $structName;
    $this->typeSpace = & $IMP->typeSpace;
    $this->bindingManager = & $IMP->bindingManager;
    $this->security = & $IMP->security;
    $this->versioner = & $IMP->versioner;
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

  function setParams($params){
    $this->params = $params;
  }
  
  function getCondition(){
    return $this->condition;
  }
  
  function setCondition($condition){
    $this->condition = $condition;
  }

  function addParam($elementName, $value, $comparison=''){
    if ( !is_object($this->params) ) $this->params = new QueryParams();
    $this->params->set($elementName, $value);
    if ($comparison) $this->params->setComparison($elementName, $comparison);
  }
  
  function isBaseType($type){
    return $this->typeSpace->isBaseType($type);
  }
  
  function processParams($params=0){
    if (!$params) return;
    if (!$this->conditionBuilder) $this->conditionBuilder = & $this->getConditionBuilder($this->structName);
    $this->conditionBuilder->processParams($params);
    $this->condition = $this->conditionBuilder->getCondition();
  }
  
  function setProcessedStructs($structs, $struct=''){
    $this->processedStructs = $structs;
    if ($struct) $this->processedStructs[$struct] = true;
  }
  
  function & getStorer($structName=''){
    if (!$structName) $obj = & $this->binding->getStorer();
    else $obj = $this->bindingManager->getStorer($structName);
    $obj->setTypeSpace($this->typeSpace);
    $obj->setBindingManager($this->bindingManager);
    $obj->setSecurity($this->security);
    return $obj;
  }
  
  function & getLoader($structName=''){
    if (!$structName) $obj = & $this->binding->getLoader();
    else $obj = $this->bindingManager->getLoader($structName);
    $obj->setTypeSpace($this->typeSpace);
    $obj->setBindingManager($this->bindingManager);
    $obj->setSecurity($this->security);
    return $obj;
  }
  
  function & getDeleter($structName=''){
    if (!$structName) $obj = & $this->binding->getDeleter();
    else $obj = $this->bindingManager->getDeleter($structName);
    $obj->setTypeSpace($this->typeSpace);
    $obj->setBindingManager($this->bindingManager);
    $obj->setSecurity($this->security);
    return $obj;
  }
  
  function & getConditionBuilder($structName=''){
    if (!$structName) $obj = $this->binding->getConditionBuilder();
    else $obj = $this->bindingManager->getConditionBuilder($structName);
    $obj->setTypeSpace($this->typeSpace);
    $obj->setBindingManager($this->bindingManager);
    $obj->setSecurity($this->security);
    $obj->init();
    return $obj;
  }
  
  function init(){
    $this->struct = $this->typeSpace->getStructure($this->structName);
    $this->binding = $this->bindingManager->getBinding($this->structName);
    if (!is_object($this->data)) $this->data = new PHPelican($this->structName);
  }
  
  function prepare($element, $value){
    return $value;
  }
  
  function decode($element, $value){
    return $value;
  }
  
  function storeElement($element){
    #virtual
  }
  
}


?>

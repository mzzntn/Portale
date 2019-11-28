<?
class ConditionBuilder_inline extends DataManager{
  var $typeSpace;
  var $structName;
  var $bindingManager;
  var $security;
  var $struct;
  var $binding;
  var $condition;


  
 

  function processParams($params){
    global $IMP;
    
    
  }
  

  function isBaseType($type){
    #if ($type == 'longText' || $type == 'html') return false;
    return $this->typeSpace->isBaseType($type);
  }
  
  function getCondition(){
  }
  
  function getTables(){
    return array();
  }

  
}

?>

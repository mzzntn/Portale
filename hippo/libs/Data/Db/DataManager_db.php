<?

//:NOTE: this class isn't actually used; it should be an interface, when they will be available
class DataManager_db extends DataManager{
  var $externalTexts;
  
  function DataManager_db($structName){
    parent::DataManager($structName);
    $this->sqlConditions['='] = '=';
    $this->sqlConditions['<'] = '<';
    $this->sqlConditions['>'] = '>';
  }
  
  function prepare($element, $value){
    $type = $this->struct->type($element);
    if ($type == 'text'){
      $value = str_replace("\\\\", "\\", $value);
      $value = str_replace('\"', '"', $value);
      $value = str_replace ("\\'", "''", $value);
      $value = "'$value'";
      return $value;
    }
    elseif ($type == 'int'){
      $value = intval($value);
    }
    elseif ($type == 'longText' || $type == 'html'){
      $this->externalTexts[$element] = true;
      if ($value) return 1;
      else return 0;
    }
    return $value;
  }
  
  function finalizeElements(){
    foreach( $this->externalTexts as $element => $text){
      $storer = new DataStorer_file($this->structName);
      $type = $this->struct->type($element);
      $storer->add($type, $element, $text);
      $storer->add('id', $id, $this->idArray);
      $storer->store();
    }
  }
  
  //:TODO: extend to handle contextElements
  function getQueryField($element){
    $this->conditionBuilder->addJoin($element);
    $parts = explode('.', $element);
    $structName = $this->structName;
    $struct = $this->struct;
    $cnt = 0;
    foreach ($parts as $part){
      $cnt++;
      if ($cnt >= sizeof($parts)) break;
      $structName = $struct->type($part);
      $struct = $this->typeSpace->getStruct($structName);
    }
    $binding = $this->bindingManager->getBinding($structName);
    //$part is now the last bit
    if (!$binding->dbField($part)) return '';
    return $binding->table.'.'.$binding->dbField($part);
  }

}



?>
<?

class DataManager_xml extends DataManager{
  
  function DataManager_xml($structName){
    parent::DataManager($structName);
  }
  
  function prepare($element, $value){
    $type = $this->struct->type($element);
    return $value;
  }

}



?>
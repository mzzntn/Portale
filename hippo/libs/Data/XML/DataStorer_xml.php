<?
include_once(LIBS.'/Data/DataStorer.php');

class DataStorer_db extends DataStorer{
  var $binding;
  var $condition;
  var $newSubCondition;
  var $struct;
  var $selectStructs;
  var $loadAll;
  var $tables;
  var $whereBuilder;
  var $data;
  var $unsafeData;
  var $watches;
  var $oldVals;
  
  function generateSql(){
    global $IMP;
    $IMP->debug("Generating sql", 5);
    if ($this->mode == 'insert'){
      $id = $this->binding->assignId();
      $IMP->debug("Got id $id", 7);
      $this->addId($id);
    }
    $keys = 'ID';
    $values = $id;
    $updates = '';
    if ( is_object($this->data) ) foreach( array_keys($this->elements) as $elementName ){
      $dbField = $this->binding->dbField($elementName);
      $value = $this->elements[$elementName]['value'];
      if ($this->mode == 'insert'){
        $keys .= ', '.$dbField;
        $values .= ', '.$value;
      }
      elseif ($this->mode == 'update'){
        if ($updates) $updates .= ', ';
        $updates .= "$dbField = $value";
      }
    }
    if ($this->mode == 'insert'){
      if (!$keys || !$values) return '';
      return "INSERT INTO {$this->binding->table} ($keys) VALUES ($values)";
    }
    else{
      if (!$updates) return '';
      return "UPDATE {$this->binding->table} SET $updates WHERE $this->condition";
    }
  }
  
  function execute(){
    global $IMP;
    $db = $this->binding->getDbObject();
    if ( $this->data ){
      $this->addInternals();
      $xml = $this->generateXML($this->data);
      $IMP->debug("Generated xml: $xml", 5);
    }
    if ($xml) return $this->writeXML($xml);
    return false;
  }
  

  

  function prepare($type, $value){
    if ($type == 'text'){
    }
    elseif ($type == 'int'){
    }
    elseif ($type == 'dateTime'){
    }
    elseif ($type == 'html'){
    }
    elseif ($type == 'password'){
      $value = md5($value);
      $value = "'$value'";
    }
    return $value;
  }

  

}


?>

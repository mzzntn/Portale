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
  
  function DataStorer_db($structName){
    parent::DataStorer($structName);
  }
  
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
      $sql = $this->generateSql($this->data);
      $IMP->debug("Generated sql: $sql", 5);
    }
    if ($sql) return $db->execute($sql);
    return false;
  }
  

  
  function linkN2N($element, $subIdArray, $extendInfo=0){
    global $IMP;
    if ($this->struct->parentElements[$element]){
      $struct = & $this->struct->getAncestorStruct($this->struct->parentElements[$element]);
      $binding = & $this->bindingManager->getBinding($struct->name);
    }
    else{
      $struct = & $this->struct;
      $binding = & $this->binding;
    }
    $refTable = $binding->n2nTable($element);
    $localId = $binding->n2nOwnId($element);
    $remoteId = $binding->n2nForeignId($element);
    $db = $binding->getDbObject();
    $refIds = array();
    foreach ( array_keys($this->idArray) as $id){
      foreach ($subIdArray as $subId){
        if (!$subId) continue;
        $sql = "SELECT ID FROM $refTable WHERE $localId = $id AND $remoteId = $subId";
        $db->execute($sql);
        $sql = '';
        $keys = $values = '';
        $update = false;
        if ($db->fetchrow()){
          $idRef = $db->result('ID');
          $update = true;
        }
        else{
          $idRef = $binding->assignId($refTable);
          $keys = "ID, $localId, $remoteId";
          $values = "$idRef, $id, $subId";
        }
        array_push($refIds, $idRef);
        if (is_array($extendInfo)){
          $IMP->debug('Have extend info', 5);
          $data = $extendInfo[$subId];
          if (is_array($data)) foreach ($data as $extElement => $value){
            $dbField = $binding->getExtendField($element, $extElement);
            $type = $struct->getExtendType($element, $extElement);
            $value = $this->prepare($type, $value);
            if ($update){
              if ($sql) $sql .= ", ";
              $sql .= "{$refTable}.{$dbField} = {$value}";
            }
            else{
              $keys .= ", {$refTable}.{$dbField}";
              $values .= ', '.$value;
            }
          }
        }
        if ($update){
          if ($sql) $sql = "UPDATE $refTable SET ".$sql." WHERE ID = $idRef";
        }
        else{
          $sql = "INSERT INTO $refTable ($keys) VALUES ($values)";
        }
        if ($sql) $db->execute($sql);
      }
      $sql = "DELETE FROM $refTable WHERE $localId = $id";
      foreach ($refIds as $refId){
        $sql .= " AND ID <> $refId";
      }
      $db->execute($sql);
    }
  }
  
  function storeExtensions($element, $data){
    if ($this->struct->parentElements[$element]){
      $struct = & $this->struct->getAncestorStruct($this->struct->parentElements[$element]);
      $binding = & $this->bindingManager->getBinding($struct->name);
    }
    else{
      $struct = & $this->struct;
      $binding = & $this->binding;
    }
    $refTable = $this->binding->n2nTable($element);
    $remoteId = $this->binding->n2nForeignId($element);
    $db = $binding->getDbObject();
    foreach ($data as $id => $rowData){
      foreach ($rowData as $extEl => $value){
        $dbField = $binding->getExtendField($element, $extEl);
        $type = $struct->getExtendType($element, $extEl);
        $value = $this->prepare($type, $value);
        if ($values) $values .= ', ';
        $values = $dbField.'='.$value;
      }
      $sql = "UPDATE $refTable SET $values WHERE $remoteId = $id";
      $db->execute($sql);
    }
  }

}


?>

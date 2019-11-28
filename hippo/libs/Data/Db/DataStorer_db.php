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
  

  function DataStorer_db($structName){
    parent::DataStorer($structName);
  }
  
  function generateSql(){
    global $IMP;
    $IMP->debug("Generating sql", 5);
    if ($this->mode == 'insert'){
      $idField = $this->binding->dbField('id');
      if($this->binding->isExternal()){  //KLUDGE
        foreach( array_keys($this->elements) as $elementName ){
          $elField = $this->binding->dbField($elementName);
          if ($elField == $idField){
            $id = $this->elements[$elementName]['value'];
            break;
          }
        }
      }
      if (!$id){
        if (($this->config['copyMode'] && $this->data->id) || ($this->binding->table == 'V_OWCP_DOCPROC' && $IMP->config['defaultdb']['type'] == 'mysql' && $IMP->config['nosequenze'])){
          $id = $this->data->id;
          $id = $this->prepare($this->struct->type('id'), $id);
        }
        else{
          $id = $this->binding->assignId();
        }
        $IMP->debug("Got id $id", 7);
        
      }
      $this->addId($id);
    }
    #$keys = 'ID';
    $keys = '';
    $values = '';
    $idField = $this->binding->id;
    if ($this->binding->id){
      $keys = $this->binding->id;
      $values = $id;
    }
    $updates = '';
    if (!$this->conditionBuilder) $this->conditionBuilder = & $this->getConditionBuilder();
    if ( is_object($this->data) ) foreach( array_keys($this->elements) as $elementName ){
      $dbField = $this->binding->dbField($elementName);
      if ($dbField == $idField) continue;
      /*if ($this->elements[$elementName]['type'] == 'text' || $this->elements[$elementName]['type'] == 'longText'){
        $this->elements[$elementName]['value'] str_replace('1', 'ZZZ', $this->elements[$elementName]['value']);
      }*/
      $value = $this->elements[$elementName]['value'];
      if ($this->mode == 'insert'){
        if ($IMP->config['defaultdb']['type'] == 'mysql' && $IMP->config['nosequenze'] && !$this->config['copyMode']){
          if ($update) $update .= ', ';
          if ($this->elements[$elementName]['type'] == 'dateTime') {
             if (strlen($value) < 10) {
                  $date_var = 'NULL';
              } else $date_var = $value;
              $update .= "$dbField = $date_var";
        }
        else $update .= "$dbField = $value";
      }
      else {
        if ($keys) $keys .= ', ';
        if ($values !== '') $values .= ', ';
        $keys .= $dbField;
    //    $values .= $value;
        if ($this->elements[$elementName]['type'] == 'dateTime') {
             if (strlen($value) < 10) {
                  $date_var = 'NULL';
              }else $date_var = $value;
              $values .= $date_var;
        }
        else $values.= $value;
       }
      }
      elseif ($this->mode == 'update'){
        if ($updates) $updates .= ', ';
        if ($this->elements[$elementName]['type'] == 'dateTime') {
             if (strlen($value) < 10) {
                  $date_var = 'NULL';
              } else $date_var = $value;
              $updates .= "$dbField = $date_var";
        }
        else $updates .= "$dbField = $value";
      }
    }
    if ($this->mode == 'insert'){
      if ($IMP->config['defaultdb']['type'] == 'mysql' && $IMP->config['nosequenze'] && !$this->config['copyMode']){  
	//file_put_contents("debug.log", "UPDATE {$this->binding->table} SET $update WHERE {$this->binding->id} = {$id}\n", FILE_APPEND);
        return "UPDATE {$this->binding->table} SET $update WHERE {$this->binding->id} = {$id}";
      }
      else{
      if ($this->conditionBuilder->getSecurityCondition('i')) return;
      if (!$keys || !$values) return '';

	     
      return "INSERT INTO {$this->binding->table} ($keys) VALUES ($values)";
      }
    }
    else{
      if (!$updates) return '';
      $sec = $this->conditionBuilder->getSecurityCondition('u', $this->binding->table);
      if ($sec){
        if ($this->condition) $this->condition .= " AND ";
        $this->condition .= "($sec)";
      }
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
    $res = false;
    if (trim($sql)){
      $res = $db->execute($sql);
    } 
    $db->close();
    return $res;
  }
  
  function getParentIds($parentStruct, $idArray){
    $refTable = $this->binding->parentRefTable($parentStruct);
    $childId = $this->binding->parentRefChildId($parentStruct);
    $parentId = $this->binding->parentRefParentId($parentStruct);
    $db = $this->binding->getDbObject();
    $refIds = array();
    foreach (array_keys($idArray) as $id){
      $sql = "SELECT $parentId FROM $refTable WHERE $childId = $id";
      $db->execute($sql);
      if ($db->fetchrow()){
        $refIds[$id] = $db->result($parentId);
        
      }
    }
    $db->close();
    return $refIds;
  }
  
  function setParentIds($parentStruct, $ids){
    $refTable = $this->binding->parentRefTable($parentStruct);
    $childIdField = $this->binding->parentRefChildId($parentStruct);
    $parentIdField = $this->binding->parentRefParentId($parentStruct);
    $db = $this->binding->getDbObject();
    foreach ($ids as $childId => $parentId){
      $sql = "SELECT $parentIdField FROM $refTable WHERE $childIdField = $childId";
      $db->execute($sql);
      if ($db->fetchrow()){
        $sql = "UPDATE $refTable SET $parentIdField = $parentId WHERE $childIdField = $childId";
      }
      else{
        $sql = "INSERT INTO $refTable ($childIdField, $parentIdField) VALUES ($childId, $parentId)";
      }
      $db->execute($sql);
    }
    $db->close();
  }
  

  
  function linkN2N($element, $subIdArray, $extendInfo=0, $deleteOthers=true){
    global $IMP;
    $type = $this->struct->type($element);
    $otherStruct = $this->typeSpace->getStruct($type);
    $refTable = $this->binding->n2nTable($element);
    $localId = $this->binding->n2nOwnId($element);
    $localIdType = $this->struct->type('id');
    $remoteId = $this->binding->n2nForeignId($element);
    $remoteIdType = $otherStruct->type('id');
    $db = $this->binding->getDbObject();
    $refIds = array();
    foreach ( array_keys($this->idArray) as $id){
      $idSql = str_replace("'", "", $id);
      $idSql = $this->prepare($localIdType, $idSql);
      if (is_array($subIdArray)) foreach ($subIdArray as $subId){
        //QUICK FIX
        $subidSql = str_replace("'", "", $id);
        $subIdSql = $this->prepare($remoteIdType, $subId);
        if (!$subId) continue;
        $sql = "SELECT ID FROM $refTable WHERE $localId = $idSql AND $remoteId = $subIdSql";
        $db->execute($sql);
        $sql = '';
        $insertUpdate = '';
        $keys = $values = '';
        $update = false;
        if ($db->fetchrow()){
          $idRef = $db->result('ID');
          $update = true;
        }
        else{
          $idRef = $this->binding->assignId($refTable);
          $keys = "ID, $localId, $remoteId";
          $values = "$idRef, $idSql, $subIdSql";
          $insertUpdate = $localId." = ".$idSql.", ".$remoteId." = ".$subIdSql;
        }
        array_push($refIds, $idRef);
        if (is_array($extendInfo)){
          $IMP->debug('Have extend info', 5);
          $data = $extendInfo[$subId];
          if (is_array($data)) foreach ($data as $extElement => $value){
            $dbField = $this->binding->getExtendField($element, $extElement);
            $type = $this->struct->getExtendType($element, $extElement);
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
          if ($IMP->config['defaultdb']['type'] == 'mysql' && $IMP->config['nosequenze']){
            if ($insertUpdate) $sql = "UPDATE $refTable SET ".$insertUpdate." WHERE ID = $idRef";
          }
          else $sql = "INSERT INTO $refTable ($keys) VALUES ($values)";
        }
        if ($sql) $db->execute($sql);
      }
      if ($deleteOthers){
          $sql = "DELETE FROM $refTable WHERE $localId = $idSql";
          foreach ($refIds as $refId){
            $sql .= " AND ID <> $refId";
          }
          $db->execute($sql);
      }
    }
    $db->close();
  }
  
  function storeExtensions($element, $data){
    $refTable = $this->binding->n2nTable($element);
    $remoteId = $this->binding->n2nForeignId($element);
    $db = $this->binding->getDbObject();
    foreach ($data as $id => $rowData){
      foreach ($rowData as $extEl => $value){
        $dbField = $this->binding->getExtendField($element, $extEl);
        $type = $this->struct->getExtendType($element, $extEl);
        $value = $this->prepare($type, $value);
        if ($values) $values .= ', ';
        $values = $dbField.'='.$value;
      }
      $sql = "UPDATE $refTable SET $values WHERE $remoteId = $id";
      $db->execute($sql);
    }
    $db->close();
  }
  
  function removeN2N($id){
      global $IMP;
      if (!$this->data->id) return;
      $type = $this->struct->type($element);
      $otherStruct = $this->typeSpace->getStruct($type);
      $refTable = $this->binding->n2nTable($element);
      $localId = $this->binding->n2nOwnId($element);
      $localIdType = $this->struct->type('id');
      $remoteId = $this->binding->n2nForeignId($element);
      $remoteIdType = $otherStruct->type('id');
      $db = $this->binding->getDbObject();
      $sql = "DELETE FROM $refTable WHERE $localId = {$this->data->id} AND $remoteId = $id";
      $db->execute($sql);
      $db->close();
  }

}


?>

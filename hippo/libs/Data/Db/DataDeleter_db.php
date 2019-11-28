<?
include_once(LIBS.'/Data/DataDeleter.php');
include_once(LIBS.'/Data/Db/ConditionBuilder_db.php');

  
class DataDeleter_db extends DataDeleter{
  var $db;
                        
  /**
  * string generateSql()
  * Generate the sql based on processed params and requests.
  **/
  function generateSql(){
    global $IMP;
    $IMP->debug("Starting generateSql", 5);
    $strSql = "DELETE FROM {$this->binding->table}";
    $idField = $this->binding->dbField('id');
    if (is_array($this->keyArray)) foreach ($this->keyArray  as $keys){
      $subCond = '';
      foreach ($keys as $keyName => $keyValue){
        $type = $this->struct->type($keyName);
        $obj = & $this->typeSpace->getObject($type);
        $obj->set($keyValue);
        $idStr = $obj->get('db');
	$keyField = $this->binding->dbField($keyName);
	if ($subCond) $subCond .= ' AND ';
	$subCond .= "$keyField = $idStr";
      }
      if ($condition) $condition .= ' OR ';
      $condition .= "($subCond)";
    }
    //$condition = $this->condition;
    if ($condition) $strSql .= " WHERE {$condition}";
    elseif (!$this->config['allowTruncate']) return; #no global delete allowed (at least for now)
    return $strSql;
  }
  
  function getParentIds($parentStruct, $keyArray){
    $refTable = $this->binding->parentRefTable($parentStruct);
    $childId = $this->binding->parentRefChildId($parentStruct);
    $parentId = $this->binding->parentRefParentId($parentStruct);
    $db = $this->binding->getDbObject();
    $refIds = array();
    foreach ($keyArray as $keys){
      $id = $keys['id'];
      $sql = "SELECT $parentId FROM $refTable WHERE $childId = $id";
      $db->execute($sql);
      if ($db->fetchrow()){
        $refIds[$id] = $db->result($parentId);
      }
    }
    return $refIds;
  }
  
  function deleteParentRefereces($parentStruct){
    $refTable = $this->binding->parentRefTable($parentStruct);
    $childId = $this->binding->parentRefChildId($parentStruct);
    $parentId = $this->binding->parentRefParentId($parentStruct);
    $db = $this->binding->getDbObject();
    $sql = "DELETE FROM $refTable WHERE ";
    $cond = '';
    foreach (array_keys($this->idArray) as $id){
      if ($cond) $cond .= ' OR ';
      $cond .= "{$childId} = {$id}";
    }
    if (!$cond) return;
    $sql .= $cond;
    $db->execute($sql);
  }
  
  function deleteReferences($element){
      $type = $this->struct->type($element);
      if ($this->struct->parentElements[$element]){
          $struct = $this->struct->getAncestorStruct($element);
          $binding = & $this->bindingManager->getBinding($struct->name);
          $ids = $this->parentIds[$type];
      }
      else{
          $binding = $this->binding;
          $keys = $this->keyArray;
          $ids = array();
          foreach ($keys as $key){
              $ids[] = $key['id'];
          }
      }
      $refTable = $binding->n2nTable($element);
      $refIdField = $binding->n2nOwnId($element);
      if ($refTable && is_array($ids) && sizeof($ids) > 0){
          $sql = "DELETE FROM $refTable WHERE ";
          $cond = '';
          foreach ($keys as $keysHash){
              if ($cond) $cond .= ' OR ';
              $cond .= "{$refIdField} = {$keysHash['id']}";
          }
      }
      if (!$cond) return;
      $sql .= $cond;
      $db = $binding->getDbObject();
      $db->execute($sql);
  }

  function execute(){
    global $IMP;
    $IMP->debug("Starting dd_db execute", 5);
    $sql = $this->generateSql();
    $this->db = $this->binding->getDbObject();
    return $this->db->execute($sql);
  }
  
}


?>

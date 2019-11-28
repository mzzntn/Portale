<?
include_once(LIBS.'/Data/DataDeleter.php');
include_once(LIBS.'/Data/Db/ConditionBuilder_db.php');

/**
* The class that gets data from a database, using the descriptions given in DataStruct
* and in Binding_db
*/    
class DataDeleter_db extends DataDeleter{
  var $db;
                        
  /**
  * string generateSql()
  * Generate the sql based on processed params and requests.
  **/
  function generateSql(){
    global $IMP;
    $IMP->debug("Starting generateSql", 5);
    if ($this->loadAll) $sqlFields = '*';
    else{
      $sqlFields = $this->binding->table.'.'.$this->binding->id;
      if ( is_array($this->elements) )
        foreach ( array_keys($this->elements) as $element){
          $IMP->debug("$element is selected", 7);
          $dbField = $this->binding->dbField($element);
          $sqlFields .= ", {$this->binding->table}.{$dbField}";
        }
      if ( is_array($this->foreign) )
      foreach ( $this->foreign as $foreignStruct => $foreignElement){
        $binding = $this->bindingManager->getBinding($foreignStruct);
        $foreignTable = $binding->table;
        $foreignField = $binding->dbField($foreignElement);
        $foreignAs = '_'.$foreignField.'_'.$foreignTable;
        $sqlFields .= ", {$foreignTable}.{$foreignField}";
        $sqlFields .= " AS {$foreignAs}";
        $this->tables[$foreignTable] = true;
      }
    }
    $strSql = "DELETE FROM {$this->binding->table}";
    $condition = $this->condition;
    if ($condition) $strSql .= " WHERE {$this->condition}";
    else return; #no global delete allowed (at least for now)
    return $strSql;
  }

  function execute(){
    global $IMP;
    $IMP->debug("Starting dd_db execute", 5);
    $sql = $this->generateSql();
    $this->db = $this->binding->getDbObject();
    $this->db->execute($sql);
  }
  
}


?>

<?


class Db_sqlite{

  var $conn;    
  var $stmt;
  var $lastID;
  var $rst;
  var $first;

  function Db_sqlite($handle='', $dbName=''){
    global $DB_NAME;

    if (!$dbName) $dbName = $DB_NAME;
    if (!$handle){
      $this->conn = sqlite_open($dbName);
    }
    else{
      $this->conn = $handle;
    }
  }


  function execute($strSQL){
    global $DEBUG;
    global $TABEXT;
    
    if ($DEBUG){
      echo "\n\n<!--SQL: $strSQL-->\n\n";
    }
    $this->stmt = sqlite_unbuffered_query($this->conn,$strSQL);
    if (!$this->stmt){
      print "NOEXECUTE!!!<br>";  
      return 0;
    }
    return $this->stmt;
  }
  
  function executeCommit($strSQL){
    global $DEBUG;

    $this->execute($strSQL);
    $this->execute("COMMIT");
  }
  
  function lastError(){
    return sqlite_error_string(sqlite_last_error($this->conn));
  }


  function fetchrow(){
    if ($this->rst){
      $next = @sqlite_next($this->stmt);
      if (!$next) return false;
    }
    $this->rst = sqlite_current($this->stmt);
    if (!$this->rst) return false;
    return true;
  }
  
  function rollback(){
    return $this->execute("ROLLBACK");
  }
       

  function result($columnName){
    return $this->rst[$columnName];
  }
  
  function colResult($i){
    return $this->rst[$i]; #sqlite array is by both names and indexes by default
  }
  
  
  function lock($tabella){
    $strSQL = "LOCK TABLE $tabella IN EXCLUSIVE MODE";
    return $this->execute($strSQL);
  }
  
  function getID(){
    return $this->lastID;
  }
  
  
  function commit(){
    return $this->execute("COMMIT");
  }
  

  
  function close(){
    return sqlite_close($this->conn);
  }
  
  function describeTable($tableName=''){
    if ($tableName){
      $sql = "SELECT * FROM $tableName WHERE ID = 0 AND ID = 1";
      if ( !$this->execute($sql) ) return 0;
    }
    $numFields = sqlite_num_fields($this->stmt);
    for ($i=0; $i<$numFields; $i++){
      $fieldName = sqlite_field_name($this->stmt, $i);
      $fields[$fieldName]['type'] = 'var';
      $fields[$fieldName]['size'] = '';
    }
    return $fields;
  }
  
  function numFields(){
    return sqlite_num_fields($this->stmt);
  }
  
}


  
?>
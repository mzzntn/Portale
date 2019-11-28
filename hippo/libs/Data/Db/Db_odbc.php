<?

class Db_odbc extends Db{
  var $conn;    
  var $stmt;
  var $lastID;
  var $rst;
  var $lastSql;


  function Db_odbc($connection='', $dbName='', $dbUser='', $dbPass=''){
    if (!$connection && $dbName){
			$this->connect($dbName, $dbUser, $dbPass);
    }
    else{
      $this->conn = $connection;
    }
  }
  
  function connect($dbName='', $dbUser='', $dbPass=''){
    $this->conn = odbc_connect($dbName, $dbUser, $dbPass);
  }
  function execute($strSQL){
    global $IMP;
    $IMP->debug("Db_odbc executing sql:", 3);
    $IMP->debug($strSQL, 2);
    $this->lastSql = $strSQL;
    $this->stmt = odbc_prepare($this->conn,$strSQL);
    if (!$this->stmt){
      return 0;
    }
    if (!@odbc_execute($this->stmt)){
      $IMP->debug('Execute failed');
      return 0;
    }
    else{
      return $this->stmt;
    }
  }
  
  function executeCommit($strSQL){
    global $IMP;
    $IMP->debug("Db_odbc executing (and committing) sql:", 3);
    $IMP->debug($strSQL, 2);
    $this->lastSql = $strSQL;
    if (!($stmt = odbc_prepare($this->conn, $strSQL))){
      return 0;
    }
    if (!odbc_execute($stmt)){
      return 0;
    }
    
    $res = odbc_commit($this->conn);
    if (!$res){
      return 0;
    }
    return 1;
  }
  


  function fetchrow(){
    return odbc_fetch_row($this->stmt);
  }
  
  function rewind(){
    $this->execute($this->lastSql);
  }
  
  function rollback(){
    return odbc_rollback($this->conn);
  } 

  function result($columnName){
    return odbc_result($this->stmt, $columnName);
  }
  
  function getID(){
    return $this->lastID;
  }
  
  function numRows(){
    return odbc_num_rows($this->stmt);
  }
  
  
  function commit(){
    return odbc_commit($this->conn);
  }
  
  function close(){
    return odbc_close($this->conn);
  }
  
  function error(){
  }
  
  function describeTable($tableName){
    global $IMP;
    $sql = "SELECT * FROM $tableName WHERE ID = 0 AND ID = 1";
    $IMP->debug($sql);
    if ( !@$this->execute($sql) ) return 0;
    $numFields = odbc_num_fields($this->stmt);
    for ($i=1; $i<=$numFields; $i++){
      $fieldName = odbc_field_name($this->stmt, $i);
      $fields[$fieldName]['type'] = odbc_field_type($this->stmt, $i);
      $fields[$fieldName]['size'] = odbc_field_len($this->stmt, $i);
    }
    return $fields;
  }
  
}


  
?>

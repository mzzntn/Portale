<?
include_once(LIBS.'/Data/Db/Db.php');

class Db_mssql extends Db{
  var $conn;    
  var $stmt;
  var $lastID;
  var $rst;
  var $lastSql;


  function Db_mssql($connection='', $dbName='', $dbUser='', $dbPass=''){
    if (!$conn){
      $this->conn = mssql_connect('localhost', $dbUser, $dbPass);
      mssql_select_db($dbName, $this->conn);
    }
    else{
      $this->conn = $connection;
    }
    mssql_query ( 'SET TEXTSIZE 65536' , $this->conn);
	ini_set ( 'mssql.textlimit' , '65536' );
	ini_set ( 'mssql.textsize' , '65536' );
  }


  function execute($strSQL){
    global $IMP;

    $IMP->debug("Executing SQL (mssql): ".$strSQL, 3);
    $this->lastSql = $strSQL;
    #print "SQL: $strSQL<br>";
    $this->stmt = mssql_query($strSQL, $this->conn);
    if (!$this->stmt) return 0;
    else{
      return $this->stmt;
    }
  }
  
  function commit(){
#	return OCICommit($this->conn);
  }
  

  function fetchrow(){
    #print "SQL: {$this->lastSql}<br>";
    $this->rst = mssql_fetch_assoc($this->stmt);
    if (!$this->rst) return false;
    return true;
  }
  
  function rewind(){
    return $this->execute($this->lastSql);
  }


  function result($columnName){
    return $this->rst[$columnName];
  }
  
  function close(){
    return mssql_close($this->conn);
  }
  
  function describeTable($tableName){
  }
  
  function numRows(){
    return mssql_num_rows($this->stmt);
  }
  
  function error(){
    #$e = ocierror();
    if ($e) return $e['code'];
  }
  
  function errorText(){
    #$e = ocierror();
    if ($e) return $e['message'];
  }
  

  
}


  
?>

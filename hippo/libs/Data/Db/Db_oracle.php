<?
include_once(LIBS.'/Data/Db/Db.php');

class Db_oracle extends Db{
  var $conn;    
  var $stmt;
  var $lastID;
  var $rst;
  var $lastSql;


  function Db_oracle($connection='', $dbName='', $dbUser='', $dbPass='', $dbCharset=''){
    if (!$connection && $dbName){
			$this->connect($dbName, $dbUser, $dbPass, $dbCharset);
    }
    else{
      $this->conn = $connection;
    }
  }

	function connect($dbName='', $dbUser='', $dbPass='', $dbCharset=''){
		$this->conn = ocilogon($dbUser, $dbPass, $dbName, $dbCharset);
		$this->execute("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD\"T\"HH24:MI:SS'");
	}


  function execute($strSQL){
    global $IMP;

    $IMP->debug("Executing SQL: ".$strSQL, 3);
    if (function_exists('dbHooks')) $strSQL = dbHooks($strSQL);
    $this->lastSql = $strSQL;
    $this->stmt = @ OCIParse($this->conn, $strSQL);
    if (!$this->stmt) return 0;
    if (!OCIExecute($this->stmt)){
      $IMP->debug('Query failed:', 1);
      $IMP->debug(ocierror(), 1);
      return 0;
    }
    else{
      return $this->stmt;
    }
  }
  

  
  function commit(){
	 return OCICommit($this->conn);
  }
  
  function numrows(){
#return ocirowcount($this->stmt);
    //print "LASTSQL: ".$this->lastSql."<br>";
    if (!$this->lastSql) return 0;
    $db2 = new Db_oracle($this->conn);
    $sql = 'SELECT COUNT(*) AS C FROM ('.$this->lastSql.')';
    $db2->execute($sql);
    if ($db2->fetchrow()){
      return $db2->result('C');
    }
  }

  function fetchrow(){
    #print "SQL: {$this->lastSql}<br>";
    return OCIFetch($this->stmt);
  }
  
  function rewind(){
    return $this->execute($this->lastSql);
  }


  function result($columnName){
    return OCIResult($this->stmt, $columnName);
  }
  
  function readLOB($columnName){
  	return OCILoadLOB(OCIResult($this->stmt, $columnName));
  }
  
  function storeBlob($filePath, $table, $field, $condition=''){
    global $IMP;
    $lob = OCINewDescriptor($this->conn, OCI_D_LOB);
    $data = fread(fopen($filePath, "r"), filesize($filePath));
    if ($condition){
      $query =  "UPDATE $table SET $field = EMPTY_BLOB() WHERE ($condition) returning $field into :blob_param ";
      $IMP->debug("Executing SQL: ".$query, 3);
    }
    else error("Blob insert not implemented");
    $stmt = OCIParse($this->conn, $query);
    OCIBindByName($stmt, ':blob_param', $lob, -1, OCI_B_BLOB);
    if (OCIExecute($stmt, OCI_DEFAULT)){
      if ($lob->save($data)){
        OCICommit($this->conn);
        OCIFreeStatement($stmt);
      }
      else{
        OCIFreeStatement($stmt);
        die("Couldn't store blob");
      }
    }

  }
  
  function close(){
    return OCILogOff($this->conn);
  }
  
  function describeTable($tableName){
    $sql = "SELECT * FROM $tableName";
    if ( !@$this->execute($sql) ) return 0;
    $numFields = OCINumCols($this->stmt);
    for ($i=1; $i<=$numFields; $i++){
      $fieldName = OCIColumnName($this->stmt, $i);
      $fields[$fieldName]['type'] = OCIColumnType($this->stmt, $i);
      $fields[$fieldName]['size'] = OCIColumnSize($this->stmt, $i);
    }
    return $fields;
  }
  
  function error(){
    $e = ocierror();
    if ($e) return $e['code'];
  }
  
  function errorText(){
    $e = ocierror();
    if ($e) return $e['message'];
  }
  

  
}


  
?>

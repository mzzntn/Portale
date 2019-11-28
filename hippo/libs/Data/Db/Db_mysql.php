<?
include_once(LIBS.'/Data/Db/Db.php');

class Db_mysql extends Db{

  var $conn;    
  var $stmt;
  var $lastID;
  var $rst;


  function Db_mysql($connection='', $dbName='', $dbUser='', $dbPass='', $dbServer=''){
    if (!$connection && $dbUser && $dbPass){
			$this->connect($dbName, $dbUser, $dbPass, $dbServer);
	#mysql_query("SET NAMES latin1",$this->conn);
	$chars = mysql_client_encoding($this->conn);
        //print_r ("RES:".$chars);
    }
    else{
      $this->conn = $connection;
    }
  }

	function connect($dbName, $dbUser, $dbPass, $dbServer=''){
		$this->conn = mysql_connect($dbServer, $dbUser, $dbPass);
		mysql_select_db($dbName, $this->conn);
                #mysql_query("SET NAMES latin1",$this->conn);
                $chars = mysql_client_encoding($this->conn);
       // print ($chars);
	}


  function execute($strSQL){
    global $IMP;
/*    if(trim($strSQL) == "SELECT albo__ms_tipiatto.ID, albo__ms_tipiatto.DESC_ATTO, albo__ms_tipiatto.PUBBLICA_SINO FROM albo__ms_tipiatto, ALBO__MS_AFFIS WHERE (  (ALBO__MS_AFFIS.NPROG IS NOT NULL AND ALBO__MS_AFFIS.NPROG <> 0)) AND (ALBO__MS_AFFIS.ID_TIPOATTO = albo__ms_tipiatto.ID)") {
        $strSQL .= " GROUP BY albo__ms_tipiatto.ID";
    } else {
	$IMP->debug("query didn't match", 3);
    }*/

    $IMP->debug("Executing SQL: ".$strSQL, 3);
    if ($IMP->config['dbReadOnly'] && (stristr($strSQL, 'INSERT') || stristr($strSQL, 'UPDATE')  || stristr($strSQL, 'DELETE'))){
      $IMP->debug('READONLY!', 3);
      return 1;
    }
    $this->stmt = mysql_query($strSQL, $this->conn);
    if (!$this->stmt){
      $IMP->debug('Query failed:', 1);
      $IMP->debug(mysql_error(), 1);
      return 0;
    }
    else{
      return $this->stmt;
    }
  }
  
  function moveTo($row){
    return mysql_data_seek($this->stmt, $row);
  }
  
  function rewind(){
    return @mysql_data_seek($this->stmt, 0);
  }
  
  function numRows(){
    return mysql_num_rows($this->stmt);
  }

  function fetchrow(){
    $this->rst = mysql_fetch_assoc($this->stmt);
    if (!$this->rst) return false;
    return true;
  }

  function result($columnName){
    return $this->rst[$columnName];
  }
  
  function close(){
    $questa = intVal(substr(strVal($this->conn), 13,3));
    $ultima = 0;
    if ($_SESSION['ultima']) $ultima = $_SESSION['ultima'];
    $diff = $questa - $ultima;
    if ($this->conn && ($diff<> 0)){
	$ok = true;
	$_SESSION['ultima'] = intVal(substr(strVal($this->conn), 13,3));
        if ($questa = $_SESSION['negativa']) $ok = false;
        $_SESSION['negativa'] = $_SESSION['ultima']; 
	if ($ok) return mysql_close($this->conn);
    }
  }
  
  function describeTable($tableName){
    $sql = "SELECT * FROM $tableName LIMIT 1";
    if ( !@$this->execute($sql) ) return 0;
    $numFields = mysql_num_fields($this->stmt);
    for ($i=0; $i<$numFields; $i++){
      $fieldName = mysql_field_name($this->stmt, $i);
      $flags = array();
      foreach (explode(' ', mysql_field_flags($this->stmt, $i)) as $flag){
        $flags[$flag] = true;
      }
      $fields[$fieldName]['type'] = mysql_field_type($this->stmt, $i);
      $fields[$fieldName]['size'] = mysql_field_len($this->stmt, $i);
      if ($flags['primary_key']) $fields[$fieldName]['primary'] = true;
      if ($flags['unique_key']) $fields[$fieldName]['unique'] = true;
      if ($flags['multiple_key']) $fields[$fieldName]['index'] = true;
    }
    return $fields;
  }
  
  function error(){
    return mysql_errno();
  }
  
  function errorText(){
    return mysql_error();
  }
  
  
}


  
?>

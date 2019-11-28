<?
include_once(LIBS.'/Data/Db/Db.php');

class Db_pgsql extends Db{

  var $conn;    
  var $stmt;
  var $lastID;
  var $rst;


  function commit(){
        return pg_query($this->conn,"commit");
}

 function begintransaction(){
     return pg_query($this->conn,"begin");
 }


  function Db_pgsql($connection='', $dbName='', $dbUser='', $dbPass='', $dbServer='', $dbCharset= ''){
    if (!$conn){
      $connString = '';
      if ($dbServer) $connString = "host=$dbServer ";
      if ($dbName) $connString .= "dbname=$dbName ";
      if ($dbUser) $connString .= "user=$dbUser ";
      if ($dbPass) $connString .= "password=$dbPass";
      if ($dbCharset) $connString .= " options='--client_encoding=$dbCharset'";
      $this->conn = pg_connect($connString);
      
    }
    else{
      $this->conn = $connection;
    }
  }


  function execute($strSQL){
    global $IMP;
    $IMP->debug("Executing SQL: ".$strSQL, 3);
    if ($IMP->config['dbReadOnly'] && (stristr($strSQL, 'insert') || stristr($strSQL, 'update')  || stristr($strSQL, 'delete'))){
      $IMP->debug('READONLY!', 3);
      return 1;
    }
    $this->stmt = @ pg_query($this->conn, $strSQL);
    if (!$this->stmt){
      $IMP->debug('Query failed:', 1);
      $IMP->debug(pg_last_error(), 1);
      return 0;
    }
    else{
      return $this->stmt;
    }
  }
  
  function moveTo($row){
    return pg_result_seek($this->stmt, $row);
  }
  
  function rewind(){
    return @pg_result_seek($this->stmt, 0);
  }
  
  function numRows(){
    return pg_num_rows($this->stmt);
  }

  function fetchrow(){
    if (!$this->stmt) return false;
    $this->rst = pg_fetch_assoc($this->stmt);
    if (!$this->rst) return false;
    return true;
  }

  function result($columnName){
    return $this->rst[strtolower($columnName)];
  }
  
  function close(){
    return pg_close($this->conn);
  }
  
  function describeTable($tableName){
      global $IMP;
      $sql = "SELECT table_name, column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name='$tableName'";
      $this->execute($sql);
      while ($this->fetchrow()){
          $fieldName = $this->result('column_name');
          $type = $this->result('data_type');
          $types = array(
              'int4' => 'INT',
              'integer' => 'INT',
              'varchar' => 'VARCHAR',
              'bpchar' => 'CHAR',
              'character' => 'CHAR',
              'character varying' => 'VARCHAR'
          );
          if ($types[$type]) $type = $types[$type];
          $fields[$fieldName]['type'] = $type;
          $fields[$fieldName]['size'] = $this->result('character_maximum_length');;
      }
      return $fields;
  }
  
  function error(){
    return pg_result_status();
  }
  
  function errorText(){
    return pg_last_error();
  }
  
  
}


  
?>

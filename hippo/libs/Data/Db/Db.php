<?

class Db{

  function isSeekable(){
    return false;
  }
  
  function moveTo($row){
    #virtual, define if seekable
  }
  
  function numRows(){
    #virtual, define if seekable
  }
  
  function lock($table){
    $strSQL = "LOCK TABLE $table IN EXCLUSIVE MODE";
    return $this->execute($strSQL);
  }
  
  function deleteTable($tableName){
    $sql = "DROP TABLE $tableName";
    $this->execute($sql);
  }
  
  function error(){
  }

  function errorText(){
  }
  
}




?>
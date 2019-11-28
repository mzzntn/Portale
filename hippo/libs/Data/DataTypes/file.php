<?

class DataT_file extends DataT{

  
  function get($for=''){
    $value = $this->data;
    switch($for){
      case 'db':
        $value = removeXSS($value);
        $value = $this->sanitizeSQL($value);
        $value = "'$value'";
        break;
    }
    return $value;
  }
  
  
  

}



?>

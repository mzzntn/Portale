<?
  
class DataT_text extends DataT{

  
  function get($for=''){
    $value = $this->data;
    switch($for){
      case 'db':
        $value = $this->sanitizeSQL($value);
        $value = "'$value'";
        break;
    }
    return $value;
  }

}



?>

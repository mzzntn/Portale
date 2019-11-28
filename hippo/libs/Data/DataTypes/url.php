<?
  
class DataT_url extends DataT{
  
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

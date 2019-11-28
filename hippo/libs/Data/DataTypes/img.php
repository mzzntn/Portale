<?

class DataT_img extends DataT{

  
  function get($for=''){
    $value = $this->data;
    switch($for){
      case 'db':
        #$value = str_replace(URL_WEBDATA, '', $value);
        $value = $this->sanitizeSQL($value);
        $value = "'$value'";
        break;
    }
    return $value;
  }
  
  
  

}



?>

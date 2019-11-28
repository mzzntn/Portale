<?

class DataT_real extends dataT{
  
  function set($value, $from=''){
    $this->data = round(floatval($value), 100);
  }
  
  function get($for=''){
    return $this->data;
  }

}

?>

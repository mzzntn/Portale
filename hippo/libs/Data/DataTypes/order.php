<?

class DataT_order extends dataT{
  
  function set($value, $from=''){
    $this->data = intval($value);
  }
  
  function get($for=''){
    return $this->data;
  }

}

?>
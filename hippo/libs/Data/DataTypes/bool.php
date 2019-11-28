<?

class DataT_bool extends dataT{
  
  function set($value, $from=''){
    $this->data = $value?1:0;
  }
  
  function get($for=''){
    return $this->data;
  }

}

?>

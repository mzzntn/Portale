<?

class DataT_int extends dataT{
  
  function set($value, $from=''){
#print "SETTING $value (".type<br>";
    $this->data = floatval($value);
//    print "DATA IS ".$this->data."<br>";
  }
  
  function get($for=''){
    return $this->data;
  }

}

?>

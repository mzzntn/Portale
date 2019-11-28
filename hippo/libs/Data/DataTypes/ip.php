<?

  
class DataT_int extends dataT{
  var $ip;
  var $mask;


  function dataT_int(){
    parent::dataT();
    $this->errorTexts[DT_ERR_PARSE] = "%s must be a valid ip address";
  }
  
  function parse($data){
    $this->orig = $data;
    $found = preg_match('/(\d{1,4}\.\d{1.4}\.\d{1.4})(?:\/\d{1,2})?/', $data, $matches);
    if ($found){
      $this->ip = $matches[1];
      $this->mask = $matches[2];
    }
    if ( !found){
      return $this->getError(DT_ERR_PARSE);
    }
    if ( is_object($data) || is_array($data) ){
      exception("Array or object passed as ip");
    }
    $this->data = $data;
    if (!$this->data) $this->data = 0;
    return 0;
  }
  

}



?>
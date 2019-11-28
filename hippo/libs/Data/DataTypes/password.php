<?
  
class dataT_password extends dataT{

  function get($for=''){
    $value = $this->data;
    switch($for){
      case 'db':
	      $value = md5($value);
        $value = "'$value'";
        break;
    }
    return $value;
  }
  

}



?>

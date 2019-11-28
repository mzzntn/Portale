<?
  
class DataT_time extends DataT{

  function set($value){
      if (preg_match('/(\d{1,2})(?::(\d{1,2}))?/', $value, $matches)){
          $hours = $matches[1];
	  $minutes = $matches[2];
	  if (strlen($hours) < 2) $hours = '0'.$hours;
	  while (strlen($minutes) < 2) $minutes = '0'.$minutes;
          $this->data = $hours.':'.$minutes;
      }
      else $this->data = '';
  }
  
  function get($for=''){
    $value = $this->data;
    if ($for == 'db'){
      return "'$value'";
    }
    return $value;
  }

}



?>

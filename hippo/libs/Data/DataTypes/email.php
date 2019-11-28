<?
  
class DataT_email extends DataT{

    function set($value, $from=''){
	    $this->data = $value;
	    return true;
        if (preg_match('/^([a-zA-Z0-9_\.\-+])+@(([a-zA-Z0-9-])+.)+([a-zA-Z0-9]{2,4})+$/', $value, $matches)){
            $this->data = $value;
            return true;
        }
        return false;
    }

  function get($for=''){
    $value = $this->data;
    switch($for){
      case 'db':
            $value = removeXSS($value);
        $value = "'$value'";
        break;
    }
    return $value;
  }

}



?>

<?

class Requests extends PHPelican{
  
  function request($elementName, $level=0){
    if (!$level && $this->get($elementName)) return;
    if (!$level) $level = 1;
    $this->set($elementName, $level);
  }
  
  
}


?>

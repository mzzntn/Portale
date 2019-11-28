<?
  
class DataT_phpelican extends DataT{
  var $fileName;
  
  function set($value, $from=''){
    global $IMP;
    switch($from){
      case 'db':
        $this->data = unserialize($value);
        break;
      default:
        $this->data = $value;
    }
  }
  
  function get($for=''){
    global $IMP;
    $value = $this->data;
    switch($for){
      case 'db':
        $index = $IMP->getIndex('DataT_phpelican');
        $this->fileName = DATA.'/pelicans/'.$index;
        $value = "'$this->fileName'";
        break;
    }
    return $value;
  }
  
  function store($binding){
    switch($binding){
      case 'db':
        $handle = fopen($this->fileName, 'w');
        fwrite($handle, serialize($this->data));
        fclose($handle);
    }
  }

}



?>
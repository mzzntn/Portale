<?

class DataStorer_file extends DataStorer{
  var $id;

  function processParams($params){
    if (!$params->id) return;    #for now we just handle simple queries
    $this->id = $params->id;
    if ( !is_array($this->id) ) $this->id = array($this->id);
  }
  
  function execute(){
    foreach ( array_keys($this->elements) as $elementName){
      createPath($this->binding->path($element));
      foreach($this->id as $id){
        $fp = fopen($this->binding->file($element, $id), 'w');
        fputs($fp, $this->elements[$elementName]['value']);
        fclose($fp);
      }
    }
  }


}


?>
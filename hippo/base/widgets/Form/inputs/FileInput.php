<?
/**
Options:
-imageDiv: <div> to use to display image (will display in place if none)
-

*/
class FileInput extends BasicInput{
  var $options;
  var $value;
  var $fileName;
  var $div;
  var $url;
  

  function FileInput($name){
    parent::BasicWidget($name);
    $this->options = array();
    $this->class = 'input.file';
  } 

  function setValue($value){
    if (is_object($value) && isset($value->name)) $this->value = $value->name;
    else $this->value = $value;
  }

  
  function display(){
    global $IMP;
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
     if ($this->readOnly){
    }
    else{
      if($this->config["allowFileDelete"]) {
      print <<<_EOS_
<script>
function {$this->htmlName}_delFile(){
  getObj('{$this->htmlName}_fileLink').style.textDecoration = 'line-through';
  var h = document.createElement('input');
  if (!getObj('{$this->htmlName}_hiddenDel')){
    h.type = 'hidden';
    h.name = '{$this->parentWidget->name}[_{$this->elementName}_del]';
    h.value = '1';
    h.id = '{$this->htmlName}_hiddenDel';
    getObj('{$this->parentWidget->htmlName}').appendChild(h);
  }
}
</script>
_EOS_;
      }

      print "<input type='file' ";
      print "id='{$this->htmlName}' ";
      print "name='{$this->name}'>";
      if ($this->value){
          $savedName = $this->inputName('saved');
          $savedName = $this->name.'[saved]';
          print "<input type='hidden' id='{$this->htmlName}_saved' name='{$savedName}' value='{$this->value}'>";
          if ($this->config['fileUrl']){
              $url = $this->config['fileUrl'];
          }
          else{
              $url = URL_WEBDATA.'/';
              if ($this->config['filePath']) $url .= $this->config['filePath'].'/';
              $url .= $this->value;
          }
          if($this->form->error && isset($this->config['allowedFileExt'])) {
              print "<br>Invia un file nel formato consentito: ( ";
              foreach($this->config['allowedFileExt'] as $extension) 
                  print $extension." ";
              print ")";
          }
          else {
              print "<br>Salvato: <a id='{$this->htmlName}_fileLink' href='{$url}' target='_blank'>".$this->value."</a>";
              if($this->config["allowFileDelete"]) { print " (<a href='javascript:{$this->htmlName}_delFile()' id='{$this->htmlName}_delLink'>Cancella</a>)";}
              if($this->config["storico"]!="") {
                print " (<a href='javascript:;' class='storicizza' title='Sposta in altra documentazione'>Storicizza</a>)";
              }
          }
      }
    }
  }

  function prepare($file){
      global $IMP;
      if (!is_array($file)) return;
    $saved = $file['saved'];
    $file['name'] = trim($file['name']);
    $ext = get_estensione($file['name']);
    // formato estensioni consentite
   if (isset($this->config['maxSize']) && ($file['size'] > $this->config['maxSize'])){
        //print "DIMENSIONE: ".$file['size']." IMPOSTATA: ".$this->config['maxSize'];
	$this->form->addError($this->elementName, "file troppo grande");
        return '';
    }

     if (isset($this->config['soloFirmati']) && file_exists($file['tmp_name'])){
       $verified = new VerifiedFile($file['tmp_name']);
       if (!$verified->isSigned()){
         $this->form->addError($this->elementName, "Formato del file non valido. Si prega di allegare un file firmato digitalmente ovvero in formato .p7m (firma CAdES) oppure .pdf (firma PAdES).");
         $this->clearParams();
         return '';
       }

     }

    if (isset($this->config['allowedFileExt']) && in_array($ext, $this->config['allowedFileExt'])  == false ) {
        $this->form->addError($this->elementName, "formato del file non consentito");
        return '';
    }

    if (in_array($ext, array('.php', '.pl', '.cgi', '.sh'))) $file['name'] .= '.txt';
    if ($file['tmp_name']){
      $file['name'] = sanitizeFilename($file['name']);
      $name = $IMP->files->store($file['tmp_name'], $file['name'], $this->config['filePath']);
      return $name;
    }
    else if ($saved){
      return $saved;
    }
    else return '';
  }
  
  
}
  
  
  
?>

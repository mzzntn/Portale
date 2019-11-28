<?
  
class TextInput extends BasicInput{
  var $value;
  var $size;
  var $readOnly;
  
  function TextInput($name){
    parent::BasicWidget($name);
    $this->addClass('input');
    $this->addClass('text');
  }

  function setValue($value){
    $this->value = $value;
  }
  
  function fixValue($value){
    return $value;
  }

  function display($optionString=''){
    global $C; // to get style version
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
    $this->parseOptions($optionString);
    
    if(isset($C['style']) && $C['style']=="2016") {
      $this->addClass('form-control');
      print "<input type='text' ";
      print "id='{$this->htmlName}' ";
      print "class='".$this->getCSSClass()."' ";
      print "name='{$this->name}' value=\"";
      //print htmlspecialchars($this->value)."\"";
      // Codice aggiunto da Irene il 16.12.2016 per risolvere le problematiche di inserimento caratteri speciali da web e da ws
      // WARNING: per far funzionare la conversione dei caratteri speciali è necessario aggiornare /usr/local/lib/hippo_nuova_grafica/libs/String/StringUtils.php ed assicurarsi che contenga la classe StringParser      
      print StringParser::parse($this->value, false, true)."\"";
      if ($this->config['readOnly']) print " readonly";
      //if ($this->config['size']) print " size='{$this->config['size']}'";
      if ($this->config['autocomplete']) print " autocomplete='{$this->config['autocomplete']}'";
      print ">";
    } else {
      if ($this->readOnly){
	print "<div class='".$this->getCSSClass('readOnlyText')."'>";
	print $this->value;
	print "</div>";
      }
      else{
	print "<input type='text' ";
	print "id='{$this->htmlName}' ";
	print "class='".$this->getCSSClass()."' ";
	print "name='{$this->name}' value=\"";
	//print htmlspecialchars($this->value)."\"";
	// Codice aggiunto da Irene il 16.12.2016 per risolvere le problematiche di inserimento caratteri speciali da web e da ws
	// WARNING: per far funzionare la conversione dei caratteri speciali è necessario aggiornare /usr/local/lib/hippo_nuova_grafica/libs/String/StringUtils.php ed assicurarsi che contenga la classe StringParser      
	print StringParser::parse($this->value,false,true)."\"";
	if ($this->config['size']) print " size='{$this->config['size']}'";
	if ($this->config['autocomplete']) print " autocomplete='{$this->config['autocomplete']}'";
	print ">";
      }
    }
  }

}


?>

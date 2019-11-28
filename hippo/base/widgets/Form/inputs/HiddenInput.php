<?
  
class HiddenInput extends BasicInput{
  var $value;
  var $readOnly;
  
  function HiddenInput($name){
    parent::BasicWidget($name);
    $this->class = 'input.text';
  }

  function setValue($value){
    $this->value = $value;
  }

  function display(){
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
    if ($this->readOnly){
    }
    else{
        //$value = str_replace("'", "\'", $this->value);
        $value = htmlspecialchars($this->value);
      print "<input type=\"hidden\" ";
      print "id='{$this->htmlName}' ";
      print "name='{$this->name}' value=\"{$value}\">";
    }
  }
  
}


?>

<?
  
class PasswordInput extends BasicInput{
  var $value;
  var $size;
  var $readOnly;
  
  function PasswordInput($name){
    parent::BasicWidget($name);
    $this->addClass('input');
  }

  function setValue($value){
    $this->value = $value;
  }
  
  function prepare($value){
    if ($value == '000000') return;
    return $value;
  }

  function display(){
    global $IMP;
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
    if ($this->readOnly){
      print "<div class='".$this->getCSSClass('readOnlyText')."'>";
      print $this->value;
      print "</div>";
    }
    else{
      $IMP->loadJs('passwordGenerator');
      print "<input id='{$this->htmlName}' type='password' ";
      print "class='".$this->getCSSClass()."' ";
      if ($this->value) $value = '000000';
      else $value = '';
      print "name='{$this->name}' value='$value' size='{$this->size}'>";
      print " <a href=\"javascript: generatePassword('{$this->htmlName}')\">Genera</a>";
    }
  }
  
}


?>

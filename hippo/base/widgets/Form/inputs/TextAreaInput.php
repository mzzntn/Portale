<?
  
class TextAreaInput extends BasicInput{
  var $value;
  var $rows;
  var $cols;
  var $readOnly;
  
  function TextAreaInput($name){
    parent::BasicWidget($name);
    $this->class = 'input.textArea';
    $this->config['cols'] = 30;
    $this->config['rows'] = 10;
  }

  function setValue($value){
    $this->value = $value;
  }

  function display(){
    global $C; // to get style version
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
    if(isset($C['style']) && $C['style']=="2016") {
      $this->addClass('form-control');
      print "<textarea ";
      print "id='{$this->htmlName}' ";
      print "class='".$this->getCSSClass()."'";
      print "name='{$this->name}' rows='{$this->config['rows']}'>";
      print $this->value;
      print "</textarea>";
    }
    else {
      if ($this->readOnly){
	print "<div class='".$this->getCSSClass('readOnlyText')."'>";
	print $this->value;
	print "</div>";
      }
      else{
	print "<textarea ";
	print "id='{$this->htmlName}' ";
	print "class='".$this->getCSSClass()."'";
	print "name='{$this->name}' cols='{$this->config['cols']}' rows='{$this->config['rows']}'>";
	print $this->value;
	print "</textarea>";
      }
    }
  }
  
}
?>

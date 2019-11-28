<?
class CheckBoxInput extends BasicInput{
  var $readOnly;
  var $value;
  var $options;
  var $radio;
  
  function CheckBoxInput($name, $structName=''){
    parent::DataWidget($name, $structName);
    $this->options = array();
    $this->class = 'input.checkbox';
  }
  
  function setValue($value){
    $this->value = $value;
  }
  
  function build(){
    if (!$this->structName){
      return;
    }
    $loader = new DataLoader($this->structName);
    $loader->setTypeSpace($this->typeSpace);
    $loader->setBindingManager($this->bindingManager);
    $requests = new LoadOptions();
    $struct = $this->typeSpace->getStructure($this->structName);
    $isRecursive = $struct->isRecursive();
    $names = $struct->getNames();
     foreach($names as $name){
      $requests->request($name);
    }
    $loader->setRequests($requests);
    $list = $loader->load();
    $list->moveFirst();
    while ( $list->moveNext() ){
      $id = $list->get('id');
      #if ($id = $this->parentWidget->id) continue;
      $text = '';
      for ($i=0; $i < sizeof($names); $i++){
        if ($text) $text .= ' ';
        $text .= $list->get($names[$i]);
      }
      $this->options[$id] = $text;
    }
  }
  
  function display(){
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
    if ($this->readOnly){
      print "<div align='left' class='".$this->getCSSClass('readOnlyText')."'>";
    }
    else{
    }
    $this->arrayToOptions($this->options, $this->value);
    if ($this->readOnly){
      print "</div>";
    }
  }
  
   function arrayToOptions($array, $selectedValues, $depth=0){
    if ( !is_array($array) || sizeof($array) == 0){
      $array = array($this->name);
      $emptyArray = true;
    }
    if ( !is_array($selectedValues) ) $checkBoxValue = $selectedValues;
    else foreach ($selectedValues as $value){
      $selectedHash[$value] = true;
    }
    foreach($array as $optionValue => $optionText){
      if ( is_array($optionText) ) $this->arrayToOptions($depth+1, $optionText, $selectedValues);
      else{
        if ($this->readOnly){
            if ($emptyArray) print $selectedValues ? 'S&igrave;' : 'No';
            else{
          if ($checkBoxValue == 1 || $selectedHash[$optionValue]){
            print "<b>";
            if ($this->multiple) print "-";
            print " $optionText</b><br>";
          }
        }
        }
        else{
          for ($i=0; $i<$depth; $i++) print "-";
          #print "$optionText ";
          print "<input type='hidden' name='{$this->name}' value='0'>"; #:KLUDGE: to have 0 passed; will it work an all browsers?
          print "<input ";
          if ($this->radio){
            print "type='radio' name='{$this->name}' value='$optionValue' ";
          }
          else{
            print "type='checkbox' ";
            if (sizeof($array) == 1) print "name='{$this->name}' ";
            else print "name='{$this->htmlName}[$optionValue]' ";
            print "value='1' ";
          }
          if ($checkBoxValue == 1 || $selectedHash[$optionValue]) print " CHECKED";
          print ">";
        }
      }
    }
  }


}
?>

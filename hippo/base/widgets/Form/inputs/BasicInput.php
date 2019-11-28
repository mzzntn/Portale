<?

class BasicInput extends DataWidget{
  var $elementName;
  var $inputName;
  var $form;

  function setValue($value){
    $this->value = $value;
  }
  
  //OBSOLETE
  function fixValue($value){
    return $value;
  }

  function prepare($value){
    if (is_array($value) || is_object($value)) return new PHPelican($this->name, $value);
    return $value;
  }
  
  function inputName($name){
    return $this->parentWidget->htmlName.'['.$this->inputName.'_'.$name.']';
  }
  
  function inputs(){
    return $this->inputName;
  }
  
  function setReadOnly($bool){
      $this->readOnly = $bool;
  }
  

}


?>

<?
include_once(BASE.'/widgets/Tree/Tree.php');

class SelectInput extends Tree{
  var $readOnly;
  var $value;
  var $options;
  var $multiple;
  var $noBlank;
  var $elementName;
  
  function __construct($name, $structName=''){
    parent::__construct($name, $structName);
    $this->config = array();
    $this->addClass('input');
  }
  
  function setValue($value=''){
    if (!$value){
      unset($this->selectedValues);
      return;
    }
    $pelican = new PHPelican();
    if ($pelican->isPelican($value) && !$value->hasData()) $value = array();
    if (is_object($value)) $value = $value->id;
    if (!is_array($value) && $value) $this->value = array($value);
    else $this->value = $value;
    if ( is_array($this->value) ) foreach ($this->value as $id){
      $this->selectedValues[$id] = -1;    
    }
    if ( is_array($this->selectedValues) ) foreach ( array_keys($this->selectedValues) as $id ){
      if ($this->selectedValues[$id] == -1) $this->selectedValues[$id] = true;
      else unset($this->selectedValues[$id]);
    }
  }
  

  
  #FIXME: remove
  function fixValue($value){
    if (!$value[0]) return 0;
    return $value;
  }

  function prepare($value){
    if (!$value) return array();
    return $value;
  }
  
  function length(){
    return $this->data->listSize();
  }
  
  function setReadOnly($bool){
      $this->readOnly = $bool;
  }

}
?>

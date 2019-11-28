<?
include_once(LIBS.'/Data/DataTypes/dateTime.php');

class DateTimeInput extends BasicInput{
  var $value;
  var $size;
  var $readOnly;
  
  function DateTimeInput($name){
    parent::BasicWidget($name);
    $this->addClass('input');
    $this->addClass('text');
  }

  function setValue($value){
    if ($value){
      $date = & dt_DateTime($value);
      $this->value = $date->toUser();
    }
  }
  
  function fixValue($value){
    if ($value){
      $date = & dt_DateTime($value);
      #$this->value = $date->toISO();
      return $date->toISO();
    } 
  }


  
}


?>

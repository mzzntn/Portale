<?

class Container extends BasicWidget{
  var $widgets;
 

  function add(& $widget){
    if (!is_array($this->widgets)) $this->widgets = array();
    $this->widgets[] = & $widget;
  }

}


?>

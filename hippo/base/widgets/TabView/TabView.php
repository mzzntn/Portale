<?

class TabView extends BasicWidget{
  var $widgets;

  function add($label, & $widget){ 
    $this->widgets[$label] = & $widget;
  }
  
  function size(){
    return sizeof($this->widgets);
  }
  
}


?>
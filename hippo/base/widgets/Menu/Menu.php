<?
  
class Menu extends BasicWidget{
  var $tree;
  var $current;
  
  function Menu($name){
    parent::BasicWidget($name);
    //$this->addClass('menu');
    $this->tree = new MenuTree();
  }

  function setTree($tree){
    $this->tree->checkPelican($tree);
    $this->tree = $tree;
  }
  
  function setCurrent($section){
    $this->current = $section;
  }
  
}

class MenuTree extends PHPelican{
  var $_link;
  var $_label;
}


?>

<?
class Displayer_passthrough extends Displayer{
  
  //this gets called if no template exists
  function ddisplay(){
    if ( is_array($this->w->children) ) foreach ($this->w->children as $child){
      $child->display();
    }
  }

}
?>

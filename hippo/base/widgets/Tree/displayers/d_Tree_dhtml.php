<?

class d_Tree_dhtml extends Displayer_dhtml{

  
  function ddisplay(){
    $this->printTree();
  }
  
  function printTree($root=0, $level=0){
    if (!$root) $root = $this->tree->root;
    while ($current = $root->nextChild()){
      print "<div >";
      print $current->content;
      print "</div>";
      $this->printTree($current, $level+1);
    }
  }
  

}


?>
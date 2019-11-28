<?

class d_Menu_html_dhtml extends Displayer_dhtml{
  var $count;

  function printMenu(){
    $this->printTree($this->w->tree);
  }
  
  function printTree($tree){
    while ($tree->moveNext()){
      $child = $tree->getName();
      if (is_object($tree->$child)){
        $tree->checkPelican($tree->$child);
        $this->printBranch($tree->$child, $level+1);
      }
    }
  }
  
  function printBranch($branch, $level=0){
    $this->count++;
    if (!$branch->_link){
?>
    <h5>
        <?=$branch->_label?>
        <?if($level > 1&&$branch->hasChildren()){
            print "<a href=\"javascript: {$this->name}_tog('{$this->name}_{$this->count}')\">+</a>";
        }?>
    </h5> 
<?
    }else{
     $css = 'inactive';
      if ($this->w->current && $this->w->current == $branch->_name) $css = 'active';    
?>       
    <li class="<?=$css?>"><a href='<?=$branch->_link?>'><?=$branch->_label?></a></li>
<?
    }  
    if($level==1){ 
?>  
    <ul>      
<?     
    }
    while ($branch->moveNext()){
      $child = $branch->getName();
      if (is_object($branch->$child)){
        $branch->checkPelican($branch->$child);
        $this->printBranch($branch->$child, $level+1);
      }
    }
    if($level==1){ 
?>  
    </ul>      
<?     
    }
  }

}


?>

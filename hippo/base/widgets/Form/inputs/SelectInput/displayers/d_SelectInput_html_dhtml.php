<?

class d_SelectInput_html_dhtml extends displayer_dhtml{
  var $fields;
  var $depth;
  
  function start(){
    if (is_array($this->w->tree)) foreach ($this->w->tree as $branch){
      $this->traverseBranch($branch);
    }
  }

  function traverseBranch($branch, $level=0){
    $this->fields[$branch->id] = $branch->label;
    $this->depth[$branch->id] = $level;
     foreach ($branch->children as $child){
      $this->traverseBranch($child, $level+1);
    }
  }
  
  function printBranch($branch, $level=0){
    print "<option ";
    if ($this->w->selectedValues[$branch->id]) print "SELECTED ";
	$value = str_replace("'", "\'", $branch->id);
    print "value='{$value}' ";
    print ">";
    for ($i=0; $i<$level; $i++) print "-";
    print $branch->label;
    print "</option>";
    foreach ($branch->children as $child){
      $this->printBranch($child, $level+1);
    }
  }
  

}



?>

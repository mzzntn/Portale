<?

class Tree extends DataWidget{
  var $tree;
  var $items;
  var $order;
  var $levels;
  
  function __construct($name, $structName=''){
    parent::__construct($name, $structName);
    $this->order = array();
    $this->items = array();
    $this->tree = array();
  }
  
  function generateFromStructure(){
    $this->load();
  }
  
  function load($parentId=0, $childBranch=false){
      global $IMP;
      #$IMP->debugLevel = 3;
    if (!$this->structName) return;
    $loader = & $this->getLoader();
    $loader->requestNames($this->config['names']);
    $this->struct = $this->typeSpace->getStructure($this->structName);
    $recursiveElement = $this->struct->getLinkingElement($this->structName);
    if ($recursiveElement) $loader->request($recursiveElement);
    $names = $this->struct->getNames();
    $orderEls = $this->struct->getElementsByType('order');
    if ($this->config['order']) foreach ($this->config['order'] as $element => $dir){
        $loader->addOrder($element, $dir);
    }
    elseif (sizeof($orderEls) < 1) foreach ($names as $name){
  	  $loader->addOrder($name);
  	}
  	if ($parentId) $loader->addParam($recursiveElement, $parentId);
		if (is_array($this->config['params'])) foreach ($this->config['params'] as $key => $value){
      $loader->addParam($key, $value);
		}
        else{
            $loader->setParams($this->config['params']);
        }
  	$list = $loader->load();
    $this->data = $list;
    if ($this->config['names']) $names = $this->config['names'];
    else $names = $this->struct->getNames();
    while ($list->moveNext()){
      $id = $list->get('id');
      $label = '';
      foreach($names as $name){
        if ($label) $label .= ' ';
        $label .= $list->get($name);
      }
      if ($recursiveElement) $parent = $list->get($recursiveElement);
      $this->items[$id]->id = $id;
      $this->items[$id]->label = $label;
      $this->items[$id]->children = array(); #this requires the data to be sorted breadth-first
      if ($parentId) $this->load($id, true); //if the param was added we need to get the others
      if (!$parent || (!$childBranch && $parent == $parentId)) $this->tree[$id] = & $this->items[$id];
      else{
        if ($this->items[$parent]){
          $this->items[$id]->parent = $this->items[$parent];
          $this->items[$parent]->children[$id] = & $this->items[$id];
        }
        else $this->orphans[$id] = $parent;
      }
      
    }
    #Kludgeddy kludgeddy klu
    if (is_array($this->orphans)) foreach ($this->orphans as $id => $parent){
      $this->items[$id]->parent = $this->items[$parent];
      $this->items[$parent]->children[$id] = & $this->items[$id];
    }
  }
  
  function generateFromArray($array){
    foreach ($array as $key => $value){
      $this->items[$key]->id = $key;
      $this->items[$key]->label = $value;
      $this->items[$key]->children = array();
      $this->tree[$key] = & $this->items[$key];
    }
  }
  
  function sortElements(){
    foreach ($this->tree as $branch){
      $this->sortBranch($branch);
    }
  }
  
  function sortBranch($branch, $level=0){
    if (!is_array($this->order)) $this->order = array(); #why do we get here?
    array_push($this->order, $branch->id);
    $this->levels[$branch->id] = $level;
    foreach ($branch->children as $child){
      $this->sortBranch($child, $level+1);
    }
  }

  

}

class TreeNode extends PHPelican{
  var $_label;
}


?>

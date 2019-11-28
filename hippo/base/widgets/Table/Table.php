<?

#$IMP->debugLevel = 3;


class Table extends DataWidget{
  var $requests;
  var $elements;
  var $tableElements;
  var $order;
  var $orderDirection;
  var $params;
  var $context;
  
  function Table($name, $structName=''){
    global $IMP;
    parent::dataWidget($name, $structName);
    $this->params = $this->getParam('params');
  }
  
  function start(){
    parent::start();
    $this->generateFromStructure();
    $this->load();
  }

  function end(){
    $this->clearParam('start');
  }

  function init(){
    global $IMP;
    $this->struct = $this->typeSpace->getStructure($this->structName);
  }
  
  function setDefaults(){
    global $C;
    $this->config['admin'] = '';
    $this->config['adminId'] = 'id';
    $this->config['contextAdmin'] = '';
    //if(isset($C['style']) && $C['style']=="2016") {
    //  unset($this->config['maxRows']);
    //  unset($this->config['maxElements']);
    //}
    //else {
      $this->config['maxRows'] = 15;
      $this->config['maxElements'] = 10;
    //}
    $this->config['disabledTypes']['html'] = true;
    $this->config['disabledTypes']['richText'] = true;
    $this->config['disabledTypes']['img'] = true;
    $this->config['buildRequests'] = true;
    $this->config['linkLabel'] = 0; #shows the id
    $this->config['maxSubElements'] = 8;
    $this->config['sortable'] = true;
    $this->config['excludeSorting'] = array();
    $this->config['sort'] = array();
    $this->config['selectableRows'] = true;
    $this->config['linkEl'] = 'id';
    $this->config['showId'] = true;
    $this->config['showHeadings'] = true;
    $this->config['noResultString'] = 'Nessun elemento da visualizzare';
    $this->config['jsConfig'] = array('admin', 'action', 'idLinkLabel', 'selectableRows', 'linkEl', 'showId', 'tdClasses', 'actionOnClick', 'adminId');
  }
  
  function generateFromStructure(){
    global $IMP;
    $this->init();
    if ($this->config['elements']) $this->elements = $this->config['elements'];
    else{
      $this->struct->sortByRelevance();
      $this->elements = $this->struct->getElements();
    }    
    $echo = /*false && */strpos($_SERVER['HTTP_HOST'],"civilianext")!==false;
    foreach($this->struct->structure as $fieldName => $fieldData) {
      if(strpos($fieldData["type"],"::")!==false) {
// 	if($echo) { echo "excluding $fieldName from sorting<br>"; }
	$this->config['excludeSorting'][] = $fieldName;
      }
    }
//     if($echo) { echo "<pre>"; print_r($this->struct); echo "</pre>"; }
    if (!$this->requests) $this->requests = new Requests();
    $this->requests->id = 1;
    array_unshift($this->elements, 'id');
    $cnt = 0;
    foreach($this->elements as $key => $elementName){
      $cnt++;
      if (preg_match('/^(.+?)\..+/', $elementName, $matches)) $el = $matches[1];
      else $el = $elementName;
      if ( ($this->config['maxElements'] && $cnt > $this->config['maxElements']) ||
           ($this->config['disabledTypes'][$this->struct->type($el)]) ){
        unset($this->elements[$key]);
        continue;
      }
      if ($this->config['buildRequests']){
        if ($el == $elementName) $this->buildRequests($elementName);  
        elseif ($this->struct->hasElement($el)){
          $this->requests->set($elementName, 1); #:KLUDGE:
        }
      }
      if (!$this->struct->hasElement($elementName)){
        #print "NOTSORTABLE $elementName<br>";
        $this->config['notSortable'][$elementName] = true;
      }
    }
    if ($this->config['contextAdmin']){
      $struct = $this->typeSpace->getStructure($this->context['struct']);
      if (is_array($struct->extend[$this->context['el']])) foreach ( array_keys($struct->extend[$this->context['el']]) as $el){
        $this->requests->$el = 1;
        $this->contextElements[$el] = true;
        array_push($this->elements, $el);
      }
    }
    if ( ($IMP->syndacator[$this->name] || $IMP->syndacator['all']) &&
          $IMP->syndacator[$this->name] != 'no'){
      $this->config['postUrl'] = $IMP->syndacator[$this->name]?$IMP->syndacator[$this->name]:$IMP->syndacator['all'];
    }
    else $this->config['postUrl'] = $_SERVER['PHP_SELF'];
  }
  
  function buildRequests($elementName){
    $type = $this->struct->type($elementName);
    if (!$type) return;
    if ( !$this->typeSpace->isBaseType($type) ){
      $lastEl = $elementName;
      $nameParent = '';
      while (!$this->typeSpace->isBaseType($type)){
        if ($nameParent) $nameParent .= '.';
        $nameParent .= $lastEl;
        $struct = $this->typeSpace->getStructure($type);
        $names = $struct->getNames();
        $type = $struct->type($names[0]);
        $lastEl = $names[0];
      }
      foreach($names as $name){
        $this->requests->request($nameParent.'.'.$name);
      }
    }
    else $this->requests->$elementName = 1;
  }
  
  function setParams($queryParams){
    #$this->clearParam('start');
    $this->params = $queryParams;
  }
  
  function addParam($elementName, $value, $comparison=''){
    if ( !is_object($this->params) ) $this->params = new QueryParams();
    $this->params->set($elementName, $value);
    if ($comparison) $this->params->setComparison($elementName, $comparison);
  }
  
  function setRequests($requests){
    $this->requests = $requests;
    $this->config['buildRequests'] = false;
  }
  
  function setContext($struct, $el, $id){
    $this->context['struct'] = $struct;
    $this->context['el'] = $el;
    $this->context['id'] = $id;
  }
  
  function load(){
    global $IMP;
    $this->init();
    $echo = /*false && */strpos($_SERVER['HTTP_HOST'],"civilianext")!==false;
//     if($echo) { $IMP->debugLevel = 5; }

    $this->generateFromStructure();
    $loader = & $this->getLoader($this->structName);
    if (is_object($this->config['mergeRequests'])){
      $vars = get_object_vars($this->config['mergeRequests']);
      foreach (array_keys($vars) as $var){
        $this->requests->$var = $this->config['mergeRequests']->$var;
      }
    }
    $loader->setRequests($this->requests);
    if ($this->params) $loader->setParams($this->params);
//     if($echo) {echo "context <pre>".$this->context."</pre>";}
    if ($this->context){
      $loader->setContext($this->context['struct'], $this->context['el'], $this->context['id']);
      $struct = $this->typeSpace->getStructure($this->context['struct']);
//       if($echo) {echo "context el <pre>".$struct->extend[$this->context['el']]."</pre>";}
      if (is_array($struct->extend[$this->context['el']])) foreach (array_keys($struct->extend[$this->context['el']]) as $extendedEl){
        if ($struct->getExtendType($this->context['el'], $extendedEl) == 'order') $contextOrder = true;
        //wonder if there is a better way... maybe not needed if 'sort' param is not preserved
      }
    }
    $this->start = $this->getParam('start');
    // questo serve per tenere in memoria la pagina corrente quando ci si sposta da tabella a form e viceversa
    if($this->start!= "") {
      $_SESSION['table_pages'][$this->structName."_start"] = $this->start;
    }
    if($this->start=="" && isset($_SESSION['table_pages'][$this->structName."_start"])) {
      $this->start = $_SESSION['table_pages'][$this->structName."_start"];
    }
    if (!$this->start) $this->start = 0;
    if ($this->config['maxRows']) $end = (($this->start==0)?1:$this->start) + $this->config['maxRows'];
    else $end = 0;
    $sort = $this->getParam('sort');
    if (!$contextOrder && is_array($sort)) foreach ($sort as $element => $dir){
      $loader->addOrder($element, $dir);
//       echo "\n<!-- ORDERING by $element, $dir -->";
    }
    if (is_array($this->config['sort'])) foreach ($this->config['sort'] as $element => $dir){
      $loader->addOrder($element, $dir);
//       echo "\n<!-- ORDERING by $element, $dir -->";
    }
//     if($echo){echo "Loader SQL: ".$loader->generateSql()."<br>";}
    //$this->start++;
    //if ($end) $end++;
//     if(strpos($_SERVER['HTTP_HOST'],"civilianext")!==false){$IMP->debugLevel = 7;}
    $this->data = $loader->load($this->start, $end);
//     echo "\n<!-- ".$this->data->dumpToXML("test")." -->\n";
    if ($this->config['checkWritable']){
        $checkLoader = & $this->getLoader();
        $checkLoader->setParams($loader->params);
        $this->writable = array();
        $writable = $checkLoader->testMode('u', $this->start, $end);
        while ($writable->moveNext()){
            $this->writable[$writable->get('id')] = true;
        }
    }
    if (is_array($this->config['data'])) $this->loadCustomData();
    $this->resultRows = $loader->numResults();
    $IMP->debug("Loaded data for the table:", 6);
    $IMP->debug($this->data, 6);
    $this->setParam('params', $this->params);
  }

  function loadCustomData(){
    foreach($this->config['data'] as $key => $val){
      $this->data->reset();
      while ($this->data->moveNext()){
        $id = $this->data->get('id');
        $this->data->set($key, $val[$id]);
      }
    }
  }
  
  function deleteElements($paramName='del'){
    global $IMP;
    $del = $this->takeParam($paramName);     #KLUDGE: should be somewhere else
    if (is_array($del)){
      $deleter = & $IMP->getDeleter($this->structName);
      $delIds = array_keys($del);
      $deleter->addParam('id', $delIds);
      $deleter->go();
    }
    $this->clearParam('del');
    return $delIds;
  }
  
  function size(){
    return intval($this->resultRows);
  }
  
  
}



?>

<?
include(LIBS.'/ext/forceUTF8.php');

//:TODO:
//-move to a lighter structure, where nested data isn't kept in pelicans but in arrays.
//This involves (among other things) rewriting locate() to be non-recursive (it might be for the best)
//-move all import-export functions in separate files, maybe loaded dinamically from a dir
class PHPelican{
  var $_name;
  var $_content;
  var $_attributes;
  var $_order;
  var $_vars;
  var $_list;
  var $_l;
  var $_indexes;
  var $_indexing;
  var $_indexBy;
  var $_unique;

  function PHPelican($name='', $value=0){
    if ($name) $this->_name = $name;
    if ($value){
      if ( $this->isPelican($value) ){
        $vars = get_object_vars($value);
        foreach (array_keys($vars) as $var){
          $this->$var = $value->$var;
        }
      }
      elseif (is_object($value)) $this->loadObject($value);
      elseif (is_array($value)) $this->loadArray($value);
      else $this->_content = $value;
      $this->rebuildVars();
    }
    else{
      $this->_vars = array();
    }
    if (!$this->isList()) $this->_list = '_vars';
    $this->_indexBy = array();
    $this->_indexes = array();
    $this->reset();
  }

  function setName($name){
    $this->_name = $name;
  }

  function checkPelican(& $object){
    if ( !is_object($object) || (!is_a($object, get_class($this))) ){
      $className = get_class($this);
      $object = new $className('', $object);
    }
    $object->rebuildVars();
  }

  function makePelican(& $object){
    return $this->checkPelican($object);
  }

  function isPelican($obj){
    return is_a($obj, get_class($this));
  }

  function hasChildren(){
    if (sizeof($this->_vars) < 1) $this->rebuildVars();
    if (sizeof($this->_vars) < 1) return false;
    return true;
  }
  
  function hasData(){
    $this->rebuildVars();
    foreach ($this->_vars as $var){
      if ($this->$var) return true;
    }
    return false;
  }

  function clearEmpty(){
    $this->rebuildVars();
    foreach ($this->_vars as $var) if (!$this->$var) unset($this->$var);
  }
  
  function emptyList(){
    $this->_l = array();
    $this->_indexes['_l'] = array();
    $this->_indexing = array();
  }

  function becomeList(){
    global $IMP;
    $IMP->debug("Becoming list", 6);
    $this->_l = array();
    $this->_list = '_l';
  }

  function isList($elementName=''){
    if ($elementName){
      if ($this->isList() && is_array($this->_l[$this->_indexes['_l']]->$elementName)) return true;
    }
    else if ($this->_list == '_l') return true;
    return false;
  }

  function getElements(){
    //if ( !$this->_vars ) $this->rebuildVars();
  $this->rebuildVars();  //:FIXME: could be expensive, but...
    return $this->_vars;
  }

  function numElements(){
    $this->rebuildVars();
    return sizeof($this->_vars);
  }

  function getAttribute($name, $element=''){
    if (!$element) return $this->_attributes[0][$name];
    $a = & $this->locate($element);
    $pelican = & $a['pel'];
    $el = $a['el'];
    $index = $a['ind'];
    if (is_int($index)) return $pelican->_attributes[$el][$index][$name];
    elseif ($el) return $pelican->_attributes[$el][$name];
    else return $pelican->_attributes[0][$name];
  }

  function getAttr($name, $element=''){
    return $this->getAttribute($name, $element);
  }

  function getAttributes($element=''){
    if (!$element) return $this->_attributes[0];
    $a = & $this->locate($element);
    $pelican = & $a['pel'];
    $el = $a['el'];
    $index = $a['ind'];
    if (is_int($index)) return $pelican->_attributes[$el][$index];
    elseif ($el) return $pelican->_attributes[$el];
    else return $pelican->_attributes[0];
  }

  function getName($element=''){
    if (!$element){
      if ($this->_list == '_vars'){
        return $this->_vars[$this->getIndex('_vars')];
      }
      return $this->_name;
    }
    $a = & $this->locate($element);
    return $a['el'];
  }

  function callMethod($method, $element){
    $obj = & $this->get($element);
    call_user_func(array(& $obj, $method));
  }

  function setAttribute($name, $value, $element=''){
    global $IMP;
    $IMP->debug("Setting attribute '$name' to '$value' for '$element'", 6);
    if (!$element) $this->_attributes[0][$name] = $value;
    else{
      $a = & $this->locate($element);
      $pelican = & $a['pel'];
      $el = $a['el'];
      $index = $a['ind'];
      if (is_int($index)){
        $pelican->_attributes[$el][$index][$name] = $value;
      }
      elseif ($el) $pelican->_attributes[$el][$name] = $value;
      else $pelican->_attributes[0][$name] = $value;
    }
  }

  function setAttr($name, $value, $element=''){
    $this->setAttribute($name, $value, $element);
  }

  function moveFirst($element=''){
    if (!$element) $element = $this->_list;
    $this->_indexes[$element] = -1;
  }

  function reset($element=''){
    if (!$element) $element = $this->_list;
    $this->moveFirst($element);
    if ($element == '_vars'){
      $this->rebuildVars();
    }
  }

  function rebuildVars(){
    global $IMP;
    $this->_vars = array_values(array_diff(array_keys(get_object_vars($this)), array_keys(get_class_vars(get_class($this)))));
  }

  function moveNext($element=''){ #:TODO: extend to multilevel
    global $IMP;
    if (!$element) $element = $this->_list;
    if (!$this->$element && $this->isList()){
      $pelican = & $this->get();
      if (!$pelican) return false;
      return $pelican->moveNext($element);
    }
    $IMP->debug("moveNext called for $element", 7, 'pelican');
    if ( !isset($this->_indexes[$element]) ) $this->_indexes[$element] = -1;
    $this->_indexes[$element]++;
    $IMP->debug("index is currently ".$this->_indexes[$element], 7, 'pelican');
    $IMP->debug("size is ".sizeof($this->$element), 8, 'pelican');
    if ($this->_indexes[$element] > sizeof($this->$element)-1){
      $this->_indexes[$element] = -1;
      return false;
    }
    return true;
  }

  function listSize(){
    if ($this->_list == '_vars') $this->rebuildVars();
    return sizeof($this->{$this->_list});
  }

  function getList($element=''){
    if (!$element) $obj = & $this;
    elseif ($this->isList()){
        $row = $this->getRow();
        $obj = $row->{$element};
    } 
    else $obj = & $this->{$element};
    //FIXME: what if $this is a list?
    if ($obj){
      $this->makePelican($obj);
      if (!$obj->isList()){
        $pel = new PHPelican();
        $pel->addRow($obj);
      }
      else $pel = & $obj;
    }
    else{
      $pel = new PHPelican();
      $pel->becomeList();
    }
    $pel->reset();
    return $pel;
  }


  function getArray($element=''){
    if (!$element) $obj = & $this;
    else $obj = & $this->get($element);
    if ($this->isPelican($obj)) return $obj->toArray();
    else return array($obj);
  }

  function toArray(){
    global $IMP;
    $IMP->debug("Starting toArray", 6);
    $list = $this->_list;
    if ($list == '_vars') $this->rebuildVars();
    $array = array();
    foreach( array_keys($this->$list) as $key){
      if ($list == '_vars'){
        $varName = $this->{$list}[$key];
        $val = & $this->$varName;
      }
      else{
        $val = & $this->{$list}[$key];
        $varName = $key;
      }
      if ($this->isPelican($val)) $array[$varName] = $val->toArray();
      else $array[$varName] = $val;
      $IMP->debug("Processed $varName", 6);
    }
    return $array;
  }
  
  function listToArray(){
    $list = $this->_list;
    return $this->$list;
  }

  /**
   NOTE: The get function, if called on a pelican, always returns the next list item,
   NOT the pelican itself.
  **/
  function & get($element=''){
    global $IMP;
    $IMP->debug("Get called for '$element'", 7, 'pelican');
    //make it faster if it's simple
    if (strpos($element, '.') === false && !$this->isList() && isset($this->$element)){
      return $this->$element;
    }
    if (!$element){
      $pelican = & $this;
      $name = $this->_list;
    }
    else{
      if ($this->$element){
        $pelican = & $this;
        $name = $element;
      }
      else{
        $a = & $this->locate($element);
        if (!$a) return 0;
        $pelican = & $a['pel'];
        $name = $a['el'];
        $index = $a['ind'];
      }
    }
    $IMP->debug("Locate found: $name, $index ", 7, 'pelican');

    if ($name && is_array($pelican->$name)){
      if (!is_int($index)) $index = $pelican->getIndex($name);
      if ($pelican->_order[$name]) $index = $pelican->_order[$name][$index];
      $IMP->debug("Index used is ".$index, 7, 'pelican');
      $IMP->debug("Returning $name, $index, $el ", 7, 'pelican');
      if ($name == '_vars') return $this->{$this->_vars[$index]};
      elseif($name){
        if (!isset($pelican->{$name}[$index])) return 0;
        //otherwise php sets it
        return $pelican->{$name}[$index];
      }
      else return $pelican; #this shouldn't happen, so maybe trigger an error
    }
    elseif($name) return $pelican->$name;
    else return $pelican->get();
  }

  //elements is a comma separated list
  function indexBy($elements){
    array_push($this->_indexBy, $elements);
  }

  function uniqueIndexBy($elements){
    $this->indexBy($elements);
    $this->_unique[$elements] = true;
  }

  function search($element, $value){
    if (!is_array($value)) $value = array($value);
    $res = & $this->_indexing[$element];
    foreach ($value as $val){
      $res = & $res[$val];
    }
    return $res;
  }

  function set($element, $value=null){
    global $IMP;
    //make it faster if it's simple
    if (!$element) return;
    if (strpos($element, '.') === false && !$this->isList()){
      $this->$element = $value;
      return;
    }
    $IMP->debug("set called on $element", 7);
    $a = & $this->locate($element);
    $pelican = & $a['pel'];
    $el = $a['el'];
    $num = $a['ind'];
    if (is_int($num)){
      if (!is_array($pelican->$el)) $pelican->$el = array();
      unset($pelican->{$el}[$num]);
      if ($value !== null) $pelican->{$el}[$num] = $value;
    }
    else{
      unset($pelican->$el); #this fixes undue references
      if ($value !== null) $pelican->$el = $value;
    }
  }

  function add($element, $value){
    $a = & $this->locate($element);
    if (isset($a['pel']->{$a['el']})){
      if (!is_array($a['pel']->{$a['el']})) $a['pel']->{$a['el']} = array($a['pel']->{$a['el']});
      //:FIXME: not sure this will work
      if (is_array($value)) array_merge($a['pel']->{$a['el']}, $value);
      else array_push($a['pel']->{$a['el']}, $value);
      //array_push($a['pel']->{$a['el']}, $value);
    }
    else $a['pel']->{$a['el']} = array($value);
    $a['pel']->_indexes[$a['el']] = sizeof($a['pel']->{$a['el']})-1;
  }

  #identical to add, but works by reference
  function bindAdd($element, & $var){
    $a = & $this->locate($element);
    if (isset($a['pel']->{$a['el']})){
      if (!is_array($a['pel']->{$a['el']})) $a['pel']->{$a['el']} = array($a['pel']->{$a['el']});
      $a['pel']->{$a['el']}[] = & $var;
    }
    else $a['pel']->{$a['el']} = & $var;
    $a['pel']->_indexes[$a['el']] = sizeof($a['pel']->{$a['el']})-1;
  }

  function addRow(& $row){
    if (!is_object($row)) return;
    if (!$this->isList()) $this->becomeList();
    foreach($this->_indexBy as $elements){
      $aElements = explode(',', $elements);
      $index = & $this->_indexing[$elements];
      foreach($aElements as $element){
        $index = & $index["".$row->$element];
      }
      if ($this->_unique[$elements] && (is_object($index) || is_array($index))) return;
      if ($index && !is_array($index)) $index = array($index);
      elseif ($index) $index[] = & $row;
      else{
        $index = array();
        $index[0] = & $row;
      }
    }
    array_push($this->_l,  $row);
  }


  function &getRow(){
    if (!$this->isList()) return false;
    if ($this->_indexes['_l'] == -1) $this->_indexes['_l'] = 0;
    return $this->_l[$this->_indexes['_l']];
  }

  function & locate($element){
    global $IMP;
#$IMP->debug("Locate called for $element", 7, 'pelican');
    $num = '';
    list($first, $rest) = @ explode('.', $element, 2);
# $IMP->debug("Split $element in $first and $rest", 8, 'pelican');
    if (!isset($this->$first) && $this->isList() && sizeof($this->_l) > 0){
      $pelican = & $this->_l[$this->getIndex('_l')];
#$pelican = & $this->get();
      if (!$pelican) return 0;
      return $pelican->locate($element);
    }
    $num = '';
    //without the size check it sets the 0 index el
    if (is_array($this->$first) && sizeof($this->$first) > 0){
#$IMP->debug("$first is an array", 8, 'pelican');
      list($num, $rest2) = @ explode('.', $rest, 2);
      if ( !is_int($num) ) $num = $this->getIndex($first);
      else{
        if ( $num == 'i') $num = $this->getIndex($first);
        if ( $num == '++i') $num = $this->nextIndex($first);
        $rest = $rest2;
      }
      if ($this->_order[$first]) $num = $this->_order[$first][$num];
      $next = & $this->{$first}[$num];
#$IMP->debug("Using index $num for $first", 8, 'pelican');
    }
    else $next = & $this->$first;
    if (!$rest){
      $a = array('pel' => & $this,'el' => $first,'ind' => $num);
#$IMP->debug("Locate found '$first' ($num) for '$element'", 7, 'pelican');
      return $a;
    }
    $this->checkPelican($next);
    return $next->locate($rest);
  }

  function getIndex($element){ 
    if ( !isset($this->_indexes[$element]) || $this->_indexes[$element] < 0 ||
         $this->_indexes[$element] > sizeof($this->$element)-1 )
      $this->_indexes[$element] = 0;
    return $this->_indexes[$element];
  }

  function nextIndex($element){
    if ( !isset($this->_indexes[$element]) ||
         $this->_indexes[$element]+1 > sizeof($this->$element)-1 )
      $this->_indexes[$element] = -1;
    return ++$this->_indexes[$element];
  }

  function sort(){
    //TODO
  }

  function merge(& $pelican){
    //TODO
  }

  function append(& $pelican){
    if (!$pelican->isList()) return;
    if (!$this->isList()) $this->becomeList();
    while ($pelican->moveNext()){
      $row = & $pelican->getRow();
      $this->addRow($row);
    }
    $this->reset();
  }

  function loadObject(& $obj){
    global $IMP;
    $IMP->debug("Pelican loading object:", 6);
    $IMP->debug($obj, 6);
    $vars = get_object_vars($obj);
    foreach ( array_keys($vars) as $varName ){
      if (is_array($obj->$varName))
        foreach ( array_keys($obj->$varName) as $key){
          if ( isset($obj->{$varName}[0]) ) $dest = & $this->{$varName}[$key];
          else{
            if ( !is_object($this->$varName) ) $this->$varName = new PHPelican($varName);
            $dest = & $this->$varName->$key;
          }
          if (is_object($obj->{$varName}[$key]))
            $dest = new PHPelican($varName, $obj->{$varName}[$key]);
          else $dest = $obj->{$varName}[$key];
        }
      elseif (is_object($obj->$varName))
        $this->$varName = new PHPelican($varName, $obj->$varName);
      else $this->$varName = $obj->$varName;
    }
  }

  function loadArray(& $array){
    global $IMP;
    $IMP->debug("Pelican loading array:", 7);
    $IMP->debug($array, 7);
    if (!$array) return;
    if ($array[0]){
    //if (key($array) === 0){
      $this->becomeList();
      foreach ($array as $key=>$value) {
        $this->checkPelican($value);
        $this->_l[$key] = $value;
      }
      return;
    }
    foreach ($array as $name => $value){
      if (is_array($array[$name])){
        $isIdArray = true;
        $isReversed = true; #we get reversed arrays from checkboxes
        if (isset($array[$name][0])) $isReversed = false;
        foreach ($array[$name] as $key => $value){  #:KLUDGE:!!! BIG KLUDGE!!!
          if (is_int($key) && $value) $hasData = true;
          if ($key && $value && !is_int($key) && !is_int($value)){
            $isIdArray = false;
            if ($value != 1) $isReversed = false;
            if (!$isIdArray && !$isReversed) break; #just so...
          }
        }
        if (!$hasData) $isIdArray = false;
        if ($isIdArray && $isReversed){
          $idArray = array();
          foreach (array_keys($array[$name]) as $key){
            array_push($idArray, $key);
          }
          $array[$name] = $idArray;
        }
        foreach ( array_keys($array[$name]) as $key){
          if ( $isIdArray ) $dest = & $this->{$name}[$key];
          else{
            if ( !is_object($this->$name) ) $this->$name = new PHPelican($name);
            $dest = & $this->$name->$key;
          }
          if (is_object($array[$name][$key]))
            $dest = new PHPelican($key, $array[$name][$key]);
          else $dest = $array[$name][$key];
        }
      }
      elseif (is_object($array[$name]))
        $this->$key = new PHPelican($key, $array[$name]);
      else $this->$name = $array[$name];
    }
    $IMP->debug("Pelican loaded:", 7);
    $IMP->debug($this, 7);
  }

   /**
  * bool loadXML(string)
  * Load from an XML string
  **/
  function loadXML($xml){
    if (!$xml) return false;
    $dom = new DOMDocument();
    $loaded = $dom->loadXML($xml);
$loaded = $dom->loadXML(iconv('UTF-8', 'UTF-8//IGNORE',$xml));

    if (!$loaded) return false;
    $root = $dom->documentElement;
    return $this->loadDomNode($root);
  }

  function loadXMLFile($file){
    $dom = new DOMDocument();
    $loaded = $dom->load($file);
    if (!$loaded) return false;
    $root = $dom->documentElement;
    return $this->loadDomNode($root);
  }

  /**
  * bool loadDomNode(DomNode)
  * Load from a DomNode object
  * Toplevel is used for loading inline data: it assumes every row
  **/
  function loadDomNode(& $domNode){
    global $IMP;
    $this->_name = $domNode->nodeName;
    if ($this->_name == 'list' || $this->_name == 'data'){
      $this->becomeList();
      $isList = true;
    }
    //_attributes[0] stores the top level attributes
    if ($domNode->hasAttributes()) $this->_attributes[0] = $this->getDomAttributes($domNode->attributes);
    $children = $domNode->childNodes;
    foreach ($children as $child){ //:KLUDGE:
      $numChildren[$child->nodeName]++;
    }
    $childCnt = 0;
    foreach ($children as $child){
      $nodeName = $child->nodeName;
      $name = $nodeName;
      if ($child->nodeType == XML_TEXT_NODE || $child->nodeType == XML_CDATA_SECTION || $name == '#cdata-section' || $name == '#text') continue;
      if ($this->_name == 'list' || $this->_name == 'data') $this->_name  = $name;  //try to put there something more useful
      if ($isList) $name = '_l';
      $nephews = $child->childNodes;
      foreach ($nephews as $nep){
        if ($nep->nodeType != XML_TEXT_NODE && $nep->nodeType != XML_CDATA_SECTION && $nep->nodeName != '#cdata-section' && $nep->nodeName != '#text'){
          $hasChildren = true;
          break;
        }
      }
      if ( $hasChildren){  // || $numChildren[$nodeName] > 1 :breaks security (multiple ids)
        $value = new PHPelican();
        $value->loadDomNode($child);
      }
      else{
        $value = $child->textContent;
        #$value = strtr($value, "Ã", "à");
        if($IMP->config['charsetDB'])
        {
          // se il charset da' problemi nell'importazione, specificare charsetDB
          $value = html_entity_decode($value, ENT_QUOTES,$IMP->config['charsetDB']);
        }
        else
        {
          $value = html_entity_decode($value, ENT_QUOTES, 'ISO-8859-1');
        }
      }


      $domAttributes = $this->getDomAttributes($child->attributes);
      //NOTE: these attributes are stored in both the child pelican and $this
      if ( isset($this->$name) ){
        if ( !is_array($this->$name) ){
          $this->$name = array($this->$name);
        }
        if ($child->hasAttributes()) $this->_attributes[$name][sizeof($this->$name)] = $attributes;
        array_push($this->$name, $value);
      }
      else{
        $this->$name = $value;
        if ($child->hasAttributes()) $this->_attributes[$name] = $attributes;
        array_push($this->_vars, $name);
      }
      $childCnt++;
    }
    if (!$childCnt){
      $content = $domNode->textContent;
      if ($content) $this->{$this->_name} = $content;
    }
    return true;
  }

  //helper function to put an array of domattribute objects into an associative array
  function getDomAttributes($domAttributes){
    foreach($domAttributes as $domAttribute){
      $attributes[$domAttribute->name] = $domAttribute->value;
    }
    return $attributes;
  }

  function dumpToXML($rootName=''){
    $doc = $this->dumpToDomDoc($rootName);
    $xml = $doc->saveXML();
    $xml = str_replace('<?xml version="1.0"?>', '', $xml);
    return $xml;
  }

  function dumpToDomDoc($rootName=''){
    if ($rootName) $name = $rootName;
    else{
      if ($this->isList()) $name = 'list';
      else $name = $this->_name;
      if (!$name) $name = 'pelican';
    }
    $name = str_replace('::', '_', $name);
    $xmlDoc = new DOMDocument();
    $xmlDoc->loadXML("<{$name}></{$name}>");
    $xmlRoot = $xmlDoc->documentElement;
    $node = $this->dumpToDomNode($xmlDoc, $xmlRoot);
    return $xmlDoc;
  }

  function dumpToDomNode(&$xmlDoc, &$xmlRoot){
    global $IMP;
    if (is_array($this->attributes[0])) foreach ($this->attributes[0] as $key => $value){
      $xmlRoot->setAttribute($key, $value);
    }
    $this->reset();
    while ($this->moveNext()){
      $name = $this->getName();
      $name = str_replace('::', '_', $name); #until I understand xml namespaces
      if (!$name) $name='row';
      $elValue = $this->get();
      if (!is_array($elValue)) $elValue = array($elValue);
      foreach ($elValue as $value){
        $node = $xmlDoc->createElement($name);
        if (is_object($value) || is_array($value)){
          $this->checkPelican($value);
          if (!$value->_name) $value->setName($name);
          $subNode = $value->dumpToDomNode($xmlDoc, $node);
          $node = $subNode;
        }
        else{
          if($IMP->config['charsetDB']){
          //Mettere htmlentities in caso di problemi di decoding XML
            $cdata = $xmlDoc->createCDATASection(htmlentities($value,ENT_COMPAT | ENT_HTML401,$IMP->config['charsetDB']));
          }
          else{
            $cdata = $xmlDoc->createCDATASection(forceUTF8(htmlentities($value)));  
          }
          $node->appendChild($cdata);
        }
        $attributes = $this->getAttributes($name);
        if (is_array($attributes)) foreach ($attributes as $key => $value){
          $node->setAttribute($key, $value);
        }
        $xmlRoot->appendChild($node);
      }
    }
    return $xmlRoot;
  }
  
  function dumpToJSON(){

      $strSearches = array("\\", "'", "\b", "\t", "\n", "\f", "\r");
      $strReplaces = array("\\\\", "\'", '\b', '\t', '\n', '\f', '\r');
     $this->reset();
     if ($this->isList()){
       $json = '[';
       $cnt = 0;
       while ($this->moveNext()){
         if ($cnt) $json .= ', ';
         $cnt++;
         $pel = & $this->getRow();
         $json .= $pel->dumpToJSON();
       }
       $json .= ']';
     }
     else{
       $json = '{';
       $cnt = 0;
        
       while ($this->moveNext()){
         if ($cnt) $json .= ', ';
         $cnt++;
         $name = $this->getName();
         $name = str_replace('::', '_', $name);
         $json .= "'$name': ";
         $elValue = $this->get();
         if ($this->isPelican($elValue)){
           $json .= $elValue->dumpToJSON();
         }
         elseif (is_array($elValue)){
           $json .= '[';
           $cnt2 = 0;
           foreach ($elValue as $arrayValue){
               if ($this->isPelican($arrayValue)){
                  $val = $arrayValue->dumpToJSON(); 
               } 
               else $val = $arrayValue;
             if ($cnt2) $json .= ', ';
             $cnt2++;
             $json .= $val;
           }
           $json .= ']';
           
         }
         elseif (is_numeric($elValue)){
           $json .= $elValue;
         }
         else{
             $json .= "'".str_replace($strSearches, $strReplaces, $elValue)."'";
         }
       }
       $json .= '}';
     }
     return $json;
  }
  

  function _debug($mode=''){
    $debug = $this->dumpToXML();
    if ($mode == 'html'){
      $debug = htmlspecialchars($debug);
    }
    return $debug;
  }
  
//  function __sleep(){    
//      return array('_name', '_content', '_attributes', '_order', '_list', '_l', '_indexBy', '_unique');
//      # PHP5: , '_indexes', '_indexing' (can't handle references with PHP4)
//  }
  
  function __wakeup(){
      $this->rebuildVars();
  }


}

?>

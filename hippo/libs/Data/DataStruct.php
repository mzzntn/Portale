<?

/**
  This is the class that describes your data. Reads from an xml file, as required by
  the *TypeSpace*
  
  XML Syntax:
  
  <struct 
    name='...'            #REQUIRED
  >
    <extends>...</extends>  #name of struct which elements will be part of this
    ...
    <label>...</label>      #something descriptive
    <plural>...</plural>    #plural of the label
    <element 
      name='...'            #REQUIRED
      type='...'            #REQUIRED: can be any of the basic types 
                            #(which depend on the current handler's implementation: must include
                            #int, bool, text, longText
                            #) or another structure, which MUST be available to the TypeSpace
                            #you are using
      meaning='...'         #gives the system some hints about what it is about
                            'name' is understood by this object
      store='...'           #enables saving the content of the field in another structure to keep
                            #an edit history, can be used only on file fields and must include 
                            #another structure, which MUST be available to the TypeSpace
                            #you are using
      required='true|false' #
    >                       #
      <label>...</label>    #somewhat short
    </element>
    ...
  </struct>
                            
  
*/
class DataStruct{
  var $name;            #-(string) what's in a name?
  var $localName;       #-(string) name without nameSpace
  var $nameSpace;
  #refs             
  var $typeSpace;       #-(&TypeSpace) : a ref to a *TypeSpace*
  #description
  var $structure;       #-(array[(string)elementName]=(string)type : array representing xml
  var $parameters;      #-(array[(string)elementName]=(array)parameters :  extra data for each element
  var $internals;       #-(array[(string)elementName]=(string)type  : *elements* used by system
  var $extends;         #-(array[i]=(string)structName :  structures this one incorporates. 
                        # Rescursely loaded. Keep them in your typespace, and DON'T make loops!
                        # NOTE: parent struct elements overwrite if same name
  #utils                                                                      
  var $index;           #-(int) for list-like operation
  var $order;           #-(array[i]=(string)elementName current ordering for list
  var $definitionOrder; #-(array[i]=(string)elementName xml element order: write it to convey
                        # information!
  var $parentElements;  #holds info about which extended structure provides an element
  var $extend;
  var $data;            #holds any inline data
  var $inline;          #elements with inline type
  var $inlineTypes;     #reverse of the previous
  var $keys;
  var $keysByName;
  var $languageTypes;
  
  
  function DataStruct($name=''){
    if ($name){
      list($accessMode, $nameSpace, $localName) = parseClassName($name);
      $this->name = $name;
      $this->localName = $localName;
      $this->nameSpace = $nameSpace;
    }
    $this->definitionOrder = array();
    $this->order = array();
    $this->extends = array();
    $this->parentElements = array();
    $this->keys = array('id');
    $this->keysByName = array();
    $this->index = -1;
    #elements all structs must have
    #:TODO: maybe use something more extensible...
    $this->internals['cr_date']['type'] = 'dateTime';
    $this->internals['cr_date']['label'] = 'Data creazione';
    $this->internals['mod_date']['type'] = 'dateTime';
    $this->internals['mod_date']['label'] = 'Data modifica';
    #$this->internals['cr_user']['type'] = 'security.user';
    #$this->internals['mod_user']['type'] = 'security.user';
    $this->internals['cr_user']['type'] = 'text';
    $this->internals['cr_user']['label'] = 'Utente creazione';
    $this->internals['mod_user']['type'] = 'text';
    $this->internals['mod_user']['label'] = 'Utente modifica';
    $this->internals['perms']['type'] = 'text';
    $this->internals['perms']['label'] = 'Utente admin';
    $this->languageTypes = array('text', 'longText', 'html');
  }

#  
# /* flags */ #
#
  
  # for elements: #
  
  /**
  * void disable(string)
  * Disabled elements should be actively ignored by anything accessing the struct 
  **/
  function disable($elementName){
    $this->parameters[$elementName]['disabled'] = true;
  }
  
  /**
  * void enable(string)
  * Re-enable a previously disabled element
  **/
  function enable($elementName){
    $this->parameters[$elementName]['disabled'] = false;
  }

  function getInternals(){
    return $this->internals;
  }
  
#
# /*  questions  */ #
#

  # to elements: #
  /**
  * bool isMultiple(string)
  * Can the element contain more than one piece of data?
  **/
  function isMultiple($elementName){
    $elementName = $this->getLanguageNeutral($elementName);
    $maxOccurs = $this->getParameter($elementName, 'maxOccurs');
    $minOccurs = $this->getParameter($elementName, 'minOccurs');
    if ( (is_numeric($maxOccurs) && $maxOccurs > 1) || $maxOccurs == 'unbounded') return true;
    if ( $minOccurs > 1 ) return true;
    return false;
  }
  
  /**
  * bool isRequired(string)
  * Is the element required?
  **/
  function isRequired($elementName){
    $elementName = $this->getLanguageNeutral($elementName);
    $minOccurs = $this->getParameter($elementName, 'minOccurs');
    if ($minOccurs > 0) return true;
    $required = $this->getParameter($elementName, 'required');
    if ($required == 'true') return true;
    return false;
  }
  
  /**
  * string label([string])
  * Get the label of the struct or of an element
  *
  * If $elementName is supplied, returns the label associated to the element; if called with no parameter,
  * returns the label associated to the struct itself.
  * The label can be explicitly assigned in the struct definition; otherwise, the name of the structure or element
  * is taken, with first word letters capitalized.
  **/
  function label($elementName=''){
    global $IMP;
    if (!$elementName){
      if ($this->label) return $this->label;
      else return ucwords( strtolower($this->name) );
    }
    if ($elementName == 'id') return 'ID';
    if ($this->internals[$elementName]['label']) return $this->internals[$elementName]['label'];
    $original = $elementName;
    $elementName = $this->getLanguageNeutral($elementName);
    if ($this->structure[$elementName]['parameters']['label']){
      $label = $this->structure[$elementName]['parameters']['label'];
    }
    else $label = ucwords( strtolower($elementName) );
    if ($this->isMultiLanguage($elementName) && 
        preg_match('/_(\w+)?/', $original, $matches)){
      $lang = $matches[1];
      $langLabel = $IMP->config['languages'][$lang]['label'];
      if ($langLabel) $label .= ' '.$langLabel;
    }
    return $label;
  }
  
  # to the structure: #

  /**
  * bool hasType(string)
  * Is there any element of type $structName?
  */
  function hasType($structName){
    foreach($this->structure as $elementName => $elementDetails){
      if ($elementDetails['type'] == $structName) return $elementName;
    }
    return false;
  }
  
  function hasElement($elementName){
    if ($elementName == 'id') return true;
    if ($this->structure[$elementName]) return true;
    if ($this->languageElements[$elementName]) return true;
    if ($this->internals[$elementName]) return true;
    if (substr($elementName, 0, 3) == 'id_' && in_array(substr($elementName, 3), $this->extends) ) return 'true';
    return false;
  }
  
  function extendsType($type){
    if (is_array($this->extend)) foreach (array_keys($this->extend) as $element){
      if ($this->type($element) == $type) return true;
    }
    return false;
  }

  /**
    bool isChildOf(string);
    Does this struct has a many-to-one relationship to $structName?
  **/
  function isChildOf($structName, $through=''){
    foreach($this->structure as $elementName => $elementDetails){
      if ( (!$through || $elementName == $through) &&
            $elementDetails['type'] == $structName && !$this->isMultiple($elementName) ){
        return true;
      }
    }
    if ($this->inlineTypes[$structName]) return true;
    #if (!$examineOther) return false;
#?
#   $otherStruct = $this->typeSpace->getStructure($structName);
#   foreach ($otherStruct->structure as $elementName => $elementDetails){
#     if ($elementDetails['type'] == $this->name && !$otherStruct->isMultiple($elementName) ){
#       return true;
#     }
#   }
    return false;
  }
  
  /**
  * bool isRecursive()
  * Is there any element that has this struct as a type?
  * 
  * Beware of infinite recursion!
  **/
  function isRecursive(){
    return $this->hasType($this->name);
  }

  
# /* description requests */ #  

  
  /**
  * string[] linkElements(string)
  * Get all elements of type $structName inside this struct
  **/
  function linkElements($structName){
    foreach($this->structure as $elementName => $elementDetails){
      if ($elementDetails['type'] == $structName) $arrayLinks[] = $elementName;
    }
    return $arrayLinks;
  }
  
  /**
  * string getLinkingElement(string);
  * Get the element that represents this structure in $otherStruct
  *
  * This function looks if $otherStruct has one, and only one, element that has this struct as its type.
  * If it is so, it returns the name of the element; otherwise, it returns false.
  **/
  function getLinkingElement($structName, $elementName=''){
    $links = $this->linkElements($structName);
    if ($elementName){
      foreach ($links as $element){
        if ($this->structure[$element]['linkFor'] == $elementName) return $element;
      }
    }
    if ( sizeof($links) > 1) return 0;
    else return $links[0];
  }
  
  /**
  * string type(string)
  * Get the type of $elementName
  **/
  function type($elementName){
    #id is of type id: it's special!
    if ($elementName == 'id' && !$this->structure[$elementName]['type']) return 'id'; 
    #but other internals too deserve a special treatment
    if ($this->internals[$elementName]) return $this->internals[$elementName]['type'];
    #otherwise, just have a look
    if ($this->structure[$elementName]['type'] == 'inline'){
      return $this->name.'.'.$elementName;
    }
    $elementName = $this->getLanguageNeutral($elementName);
    return $this->structure[$elementName]['type'];
  }
  
  function isInline($elementName){
    if ($this->structure[$elementName]['type'] == 'inline'){
      return true;
    }
    return false;
  }
  
  function getExtendType($element, $elementName){
    $elementName = $this->getLanguageNeutral($elementName);
    return $this->extend[$element][$elementName]['type'];
  }
  
#
# /* set&get methods */ #
#
  
  /**
  * void setTypeSpace(TypeSpace)
  * Assign a TypeSpace to the structure
  **/
  function setTypeSpace(& $typeSpace){
    $this->typeSpace = & $typeSpace;
  }
  
  /**
  * string getParameter(string, string)
  * Get the value of an element parameter
  **/
  function getParameter($elementName, $parameterName){
    $elementName = $this->getLanguageNeutral($elementName);
    return $this->structure[$elementName]['parameters'][$parameterName];
  }
  
  /**
  * string[string] getParameters(string, string)
  * Get an associative array of the structurs's parameters
  **/
  function getParameters($elementName){
    $elementName = $this->getLanguageNeutral($elementName);
    return $this->structure[$elementName]['parameters'];
  }
  
  /**
  * string[] getElements()
  * Get the names of the elements
  *
  * Returns an array, indexed starting from 0, containing the names of all the elements in the structure,
  * in the order they were defined in.
  **/
  function getElements(){
    $elements = array();
    if ( is_array($this->structure) ) foreach( array_keys($this->structure) as $elementName){
      //if ($elementName != 'id') 
      array_push($elements, $elementName);
    }
    return $elements;
  }

  function getElementsWithLocalized(){
    $elements = array();
    while ($this->moveNext()){
      $elementName = $this->currentElement();
      if ($elementName == 'id') continue;
      if (sizeof($this->languages) > 0 
          && in_array($this->type($elementName), $this->languageTypes)){
        foreach ($this->languages as $lang){
          $elements[] = $elementName.'_'.$lang;
        }
      }
      else $elements[] = $elementName;
    }
    return $elements;
  }

  function getSimpleElements(){
    $allElements = $this->getElements();
    $elements = array();
    foreach ($allElements as $element){
      if ($this->typeSpace->isBaseType($this->type($element))) array_push($elements, $element);
    }
    return $elements;
  }

  function & getAncestorStruct($element){
    $element = $this->getLanguageNeutral($element);
    if ($this->parentElements[$element]){
      $struct = & $this->typeSpace->getStructure($this->parentElements[$element]);
      return $struct->getAncestorStruct($element);
    }
    return $this;
  }
  
  function getAncestorsTree($element){
    $element = $this->getLanguageNeutral($element);
    if ($this->parentElements[$element]){
      $struct = & $this->typeSpace->getStructure($this->parentElements[$element]);
      return array_push($struct->getAncestorsTree($element), $this->name);
    }
    return array($this->name);
  }

  function getElementsByType($type){
    $elements = array();
    if ( is_array($this->structure) ) foreach( array_keys($this->structure) as $elementName){
      if ($this->type($elementName) == $type) array_push($elements, $elementName);
    }
    return $elements;
  }
  
  function getElementsByAttribute($name, $value){
    $elements = array();
    foreach($this->structure as $elementName => $elementDetails){
      if ($elementDetails['parameters'][$name] == $value){
        array_push($elements, $elementName);
      }
    }
    return $elements;
  }
  
  /**
  * string getElementMeaning(string)
  * Get the first element found having $meaning as its 'meaning' parameter
  *
  * @return the name of the first element found, in no particular order, which has 'meaning' $meaning.
  **/
  function getElementByMeaning($meaning){
    foreach($this->structure as $elementName => $elementDetails){
      $elMeaning = $elementDetails['parameters']['meaning'];
      if ($elMeaning == $meaning) return $elementName;
    }
  }  
  
  /**
  * string[] getNames()
  * Get the elements serving as the 'title' of the data contained in the struct
  *
  * This function returns an array, indexed starting at 0, of the elements in the struct which can be
  * used to assign a meaningful 'title', to be used everywhere only some elements will be describing
  * the data, as in lists, tables, etc.
  * An element can be explicitly defined to be a name using the 
  * <element [...] meaning='name'>[...]</element> syntax, or <element [...] meaning='name.i'>[...]</element> for
  * multiple elements, where i is the position where the element appears in the title, starting at 1.
  * If no element is defined to be a name, the struct is sorted by relevance (using other defined parameters, or
  * definition order if none) and the first element is returned.
  **/
  function getNames(){
    $names = array();
    foreach($this->structure as $elementName => $elementDetails){
      $meaning = $elementDetails['parameters']['meaning'];
      if ($meaning){
        if ($meaning == 'name') array_push($names, $elementName);
        #multiple names are assigned as name.position
        elseif ( preg_match('/(.+?)\((\d+)\)$/', $meaning, $matches) ){
          #$matches[2] is the name position; subtract 1 to start from 0
          if ($matches[1] == 'name') $names[$matches[2]-1] = $elementName;
        }
      }
    }
    if ( sizeof($names) == 0){
      $this->sortByRelevance();
      $cnt = -1;
      while ($cnt < sizeof($this->order)){
        $cnt++;
        $name = $this->order[$cnt];
        if ($name != 'id' && !$this->internals[$name] && $this->type($name) == 'text') break;
      }
      if ($name) $names[0] = $name;
      else $names[0] = 'id';
    }
    return $names;
  }
  

  
# /* ----------------------------- */ #

#
# /* list-mode (inc. sorting) functions */ #
#
  
  /**
  * bool moveNext()
  * Move the list index to the next item
  *
  * @return false if the index is out of bounds (in which case the list is resetted), true otherwise.
  **/
  function moveNext(){
    if ( sizeof($this->order) == 0 ) $this->sortByDefinition();
    $this->index++;
    if ($this->index > sizeof($this->order) - 1){
      $this->reset();
      return 0;
    }
    return 1;
  }
  
  
  /**
  * void reset()
  * Resets the index to the first item
  **/
  function reset(){
    $this->index = -1;
  }
  
  /**
  * void moveFirst()
  * Alias for reset()
  **/
  function moveFirst(){
    $this->reset();
  }
  
  /**
  * void sortByDefinition()
  * Sort the elements in the order they were defined in.
  **/
  function sortByDefinition(){
    $this->order = $this->definitionOrder;
  }

  /**
  * void sortByRelevance()
  * Sort the elements by relevance
  *
  * Currently, the way to defined a custom relevance is to use the 'relevance' parameter on an element.
  * Otherwise, the definition order is used.
  **/
  function sortByRelevance(){
    if ( is_array($this->structure) ) foreach($this->structure as $elementName => $elementDetails){
      $relevances[$elementName] = $elementDetails['parameters']['relevance'];
      if ($relevances[$elementName]) $relevanceFound = true;
    }
    if (!$relevanceFound){
      return $this->sortByDefinition();
    }
    asort($relevances);
    $this->order = array();
    foreach( array_keys($relevances) as $elementName){
      array_push($this->order, $elementName);
    }
  }

  /**
  * string currentElement()
  * Get the element the list pointer is currently on.
  **/
  function currentElement(){
    return $this->order[$this->index];
  }
  
  function getKeys(){
    return $this->keys;
  }

  function isKey($elementName){
    return $this->keysByName[$elementName];
  }
  
# /* ------------------ */ #
  
  
  
  # /* other utility functions */ #
  
    
  /**
    @bool
    Do we handle the (string)$type directly or interpret it as a struct?
  */
  function isBaseType($type){
    return $this->typeSpace->isBaseType($type);
  }
  
  /**
    @void
    Add element (string)$name, (string)$type, (array[(string)param]=(string)value)
  */
  function add($elementName, $type, $parameters=0){
    $this->structure[$elementName]['type'] = $type;
    $this->structure[$elementName]['parameters'] = $parameters;
    array_push($this->definitionOrder, $elementName);
  }
  
  function parseElementNode($child){
    $parameters['encodedName'] = $child->getAttribute('name');
    $name = utf8_decode($parameters['encodedName']);
    $type = $child->getAttribute('type');
    $parameters['meaning'] = $child->getAttribute('meaning');
    $parameters['maxOccurs'] = $child->getAttribute('maxOccurs');
    $parameters['minOccurs'] = $child->getAttribute('minOccurs');
    $parameters['required'] = $child->getAttribute('required');
    $parameters['label'] = $child->getAttribute('label');
    $parameters['size'] = $child->getAttribute('size');
    $parameters['cascade'] = $child->getAttribute('cascade');
    $parameters['case_insensitive'] = $child->getAttribute('case_insensitive');
    $parameters['storico'] = $child->getAttribute('storico');
    $sub = $child->childNodes;
    foreach($sub as $subNode){
      $subNodeName = $subNode->nodeName;
      if ($subNodeName == 'label'){
        $parameters['label'] = utf8_decode($subNode->textContent);
      }  
    }
    $el['name'] = $name;
    $el['type'] = $type;
    $el['parameters'] = $parameters;
    if ($child->getAttribute('key') == 'true'){
      $this->keys[] = $name;
    }
    $this->keysByName[$name] = true;
    return $el;
  }

  function parseNode($root, $name=''){
    global $IMP;
    if (!$this->name){
      if ($name) $this->name = $name;
      else $this->name = $root->getAttribute('name');
      if ($this->external){
          $this->internals = array();
      }
      list($accessMode, $nameSpace, $localName) = parseClassName($this->name);
      $this->localName = $localName;
      $this->nameSpace = $nameSpace;
    }
    $this->external = $root->getAttribute('external');
    if ($this->external) $this->keys = array();
    $children = $root->childNodes;
    foreach ($children as $child){
      $entity = $child->nodeName;
      if ($entity == 'element'){
        $el = $this->parseElementNode($child);
        $name = $el['name'];
        $type = $el['type'];
        if ($el['type'] == 'inline'){
          $type = $this->name.'.'.$name;
          $nodes = $child->childNodes;
          unset($struct);
          $struct = new DataStruct();
          //:KLUDGE:
          foreach ($nodes as $tmpNode){
            if ($tmpNode->nodeName == 'struct') $node = $tmpNode;
            if ($tmpNode->nodeName == 'element') $hasElements = true;
            if ($tmpNode->nodeName == 'data') $dataNode = $tmpNode;
          }
          if (!$node || $node->nodeName != 'struct'){
            if ($hasElements) $node = $child;
            else{
              //defaults to a simple element
              $inlineType = $child->getAttribute('inlineType');
              if (!$inlineType) $inlineType = 'text';
              $struct->structure[$name]['type'] = $inlineType;
              $struct->structure[$name]['parameters']['meaning'] = 'name';
            }
          }
          if ($node) $struct->parseNode($node, $type);
          $struct->structure['_inline']['type'] = $this->name;
          if ($dataNode){
            $struct->data = new PHPelican();
            $struct->data->loadDomNode($dataNode);
          }
          $this->typeSpace->addStruct($type, $struct);
          $IMP->bindingManager->set($type, 'inline');  //:FIXME: should we have a bindingManager instance ready?
          $this->inline[$name] = $type;
          $this->inlineTypes[$type] = $name;
        }
        $this->structure[$name]['type'] = $el['type'];
        $this->structure[$name]['parameters'] = $el['parameters'];
        array_push($this->definitionOrder, $name);
      }
      elseif ($entity == 'extends'){
        $extends = $child->textContent;
        if ($extends) array_push($this->extends, $extends);
        #we relay on the typespace to get other structures
        $extendedStruct = $this->typeSpace->getStructure($extends);
        if (!$this->structure) $this->structure = array();
        $this->structure = array_merge($this->structure, $extendedStruct->structure);
        foreach (array_keys($extendedStruct->structure) as $el){
          $this->parentElements[$el] = $extends;
        }
	$this->parentElements['id_'.$extends] = $extends;
        foreach ($extendedStruct->definitionOrder as $element){
          array_push($this->definitionOrder, $element);
        }
	      if (is_array($extendedStruct->inline)) foreach ($extendedStruct->inline as $name => $extType){
      		$type = $this->name.'.'.$name;
      		$this->inline[$name] = $type;
      		unset($inlineStruct);
      		$inlineStruct = $this->typeSpace->getStruct($extType);
      		$this->typeSpace->addStruct($type, $inlineStruct);
      		$this->inlineTypes[$type] = $name;
      		$IMP->bindingManager->set($type, 'inline');
      	}
      }
      elseif ($entity == 'extend'){
        $el = $child->getAttribute('element');
        $this->extend[$el] = array();
        $sub = $child->childNodes;
        foreach($sub as $subNode){
          $subNodeName = $subNode->nodeName;
          if ($subNodeName == 'element'){
            $subEl = $this->parseElementNode($subNode);
            $name = $subEl['name'];
            $this->extend[$el][$name]['type'] = $subEl['type'];
            $this->extend[$el][$name]['parameters'] = $subEl['parameters'];
          }  
        }
      }
      elseif ($entity == 'data'){
        $this->data = new PHPelican($this->name);
        $this->data->loadDomNode($child);
        $IMP->bindingManager->set($this->name, 'inline');
        //:TODO: maybe we could want to get db data together with inline data...
        //think about it
      }
      elseif ($entity == 'languages'){
        $this->languages = explode(',', $child->textContent);
      }
    }
  }

  /**
  * void loadDefinition(string)
  * Parse an xml definition of the struct
  *
  *
    
    The typespace takes care of feeding it the filename 
    Nested structs are NOT loaded: we can get info about them querying the struct in question.
    But: extended structs are loaded recursively and their fields become this struct's fields
    (overwriting if same name) 
    (:TODO: avoid load at this time? There might data replication. Use some kind of
    namespace extended elements?) 
  **/
  function loadDefinition($file){
    global $IMP;
    if (!$file) error("Null filename given for struct '{$this->name}'");
    $dom = new DOMDocument();
    $loaded = $dom->load($file);
    if (!$loaded) error("Unable to open struct definition file $file");
    $root = $dom->documentElement;
    if ($root->nodeName != 'struct') error("File $file does not start with <struct> tag");
    $this->parseNode($root);
    if (!$this->languages && is_array($IMP->config['languages'][$this->nameSpace])){
      foreach (array_keys($IMP->config['languages'][$this->nameSpace]) as $lang) $this->languages[] = $lang;
    }
    $this->processLanguages();
    $this->sortByDefinition();
  }

  function processLanguages(){
    //is this really needed?
    if (!is_array($this->languages)) return;
    foreach (array_keys($this->structure) as $elementName){
      if (in_array($this->structure[$elementName]['type'], $this->languageTypes)){
        foreach ($this->languages as $lang){
          $this->languageElements[$elementName.'_'.$lang] = $elementName;
        }
      }
    }
  }


  function getLanguageNeutral($elementName){
    if ($this->languageElements[$elementName]) return $this->languageElements[$elementName];
    return $elementName;
  }

  function isMultiLanguage($elementName){
    if (sizeof($this->languages) > 0 &&
        in_array($this->type($elementName), $this->languageTypes)) return true;
    return false;
  }
  
  function getElementInAllLanguages($elementName){
    if (sizeof($this->languages) < 1) return array($elementName);
    foreach($this->languages as $lang){
      $res[] = $elementName.'_'.$lang;
    }
    return $res;
  }

}


?>

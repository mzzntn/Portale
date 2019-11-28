<?
/**
  The class responsible for getting the *DataStruct* object associated to a name.
  The name of a struct should be something like
  [accessMode://][address/][nameSpace::]name   where
  -accessMode tells how to get the structure; if not present, 'xml' will be assumed
    access modes understood are:
      xml: the structure is locally available as an xml file
      net:  contact a remote typesace to get the strucutre, using whatever protocol is most
            convenient at the moment
  -address is the ip of the machine providing the struct; as for now,
    it is used only for 'net' accessMode
  -nameSpace is a way for structures from different applications not to interfere with
    eachother. If no nameSpace is specified, it will be assumed to be 'base', the namespace
    of system-wide structs. @seeDoc dir_structure and @see TypeSpace->getStructure to know
    where files are looked for.
*/

class TypeSpace{
  var $structures;    #-(array[(string)structName]=(&DataStruct)structure
                      # a cache for already loaded structures.
  var $proxyTo;      #array of other typeSpaces we are a proxy for
  
  /**
    Constructor. //Makes sure the cached paths of precedently found structs are known.
  */
  function TypeSpace(){
    $this->loadBaseStructs();
  }

  function addTypeSpace($nameSpace, & $typeSpace){
    if (!is_array($this->proxyTo[$nameSpace])) $this->proxyTo[$nameSpace] = array();
    $this->proxyTo[$nameSpace][] = & $typeSpace;
    $typeSpace->parent = $this;
  }
  
  /**
    @&DataStruct
    Figure out where to look for (string)$structName , create a *DataStruct* object,
    load it and return a reference to it.
    Structures are loaded only the first time they are requested, and then 
    returned from cache.
    NOTE: you should always call this function with $v = *&* $typeSpace->getStructure(...)
    to get a reference to the returned object, at least until the new Zend engine comes in.
    Xml files are looked for in any subdirectory of the nameSpace's base dir (@see TypeSpace).
    
  */
  function & getStructure($structFullName, $proxied=false){
    global $IMP;
    if ($this->structures[$structFullName]) return $this->structures[$structFullName];
    //for inline structures, load the parent
    if ( preg_match('/(.+)\.(.+)/', $structFullName, $matches) ){
      #$name = $matches[1];
      $container = $this->getStructure($matches[1]);
      $ancestor = $container->getAncestorStruct($matches[2]);
      if ($ancestor->name != $matches[1]){
        $struct = & $this->getStructure($ancestor->name.'.'.$matches[2]);
	$this->structures[$structFullName] = & $struct;
	return $struct;
      }
      return $this->structures[$structFullName];
    }
    $name = $structFullName;
    $struct = & $this->getStructureFromProxies($name);
    if ($struct){
      $this->structures[$structFullName] = & $struct;
      return $struct;
    }
    $IMP->debug("Attempting to load structure $structFullName", 5);
    $path = findClass($name, 'structs');
    if (!$path){
      if ($proxied) return 0;
      error("XML description for $name not found");
    }
    $this->structures[$name] = new DataStruct($name);
    $this->structures[$name]->setTypeSpace($this);
    $this->structures[$name]->loadDefinition($path);
    $this->structures[$name]->name = $name;
    if ($this->structures[$name]->data) $IMP->bindingManager->set($structFullName, 'inline');
    return $this->structures[$name];
  }

  function & getStructureFromProxies($structFullName){
    list ($accessMode, $nameSpace, $localName, $dir) = parseClassName($structFullName);
    if (is_array($this->proxyTo[$nameSpace])) for($i=0; $i<sizeof($this->proxyTo[$nameSpace]); $i++){
      $struct = $this->proxyTo[$nameSpace][$i]->getStructure($structFullName,true);
      if ($struct) return $struct;
    }
  }

  
  function addStruct($name, & $struct){
    $this->structures[$name] = & $struct;
  }
  
  function & getStruct($structName){
    return $this->getStructure($structName);
  }

  function structName($n){
    return $this->structNames[$n];
  }
  
  function structId($name){
    return $this->structIds[$name];
  }
  
  /**(d)
    @void
    Load structures used by the system.
  */
  function loadBaseStructs(){
    #$this->parseDir(BASE.'/structs');
  }
  
  function loadStructNames(){
    global $IMP;
    _catch('data.def.file.bindingNotFound');
    $loader = $IMP->getLoader('_struct');
    if (_catched()) return; //db not yet initialized
    $loader->requestAll();
    $structs = $loader->load();
    while ($structs->moveNext()){
      $name = $structs->get('name');
      $id = $structs->get('id');
      $this->structNames[$id] = $name;
      $this->structIds[$name] = $id;
    }
  }
  
  /**(d)
    @void
    Load all structures in directory (string)$directory
  */
  function parseDir($directory){
    $d = dir($directory);
    while (false !== ($entry = $d->read())) {
      $fullPath = $directory.'/'.$entry;
      if ($entry != '.' && $entry != '..' && $entry != 'bindings' && is_dir($fullPath) ) $this->parseDir($fullPath);
      else{
        $pathParts = pathinfo($entry);
        if ($pathParts['extension'] == 'xml'){
          $baseName = basename($pathParts['basename'], '.xml');
          $this->getStructure($baseName, $fullPath);
        }
      }
    }
  }
  
  function parseNameSpace($nameSpace){
    global $IMP;
    $IMP->debug("Parsing $nameSpace", 4);
    if ($nameSpace == 'base') $path = BASE;
    else $path = APPS.'/'.$nameSpace;
    if ( !file_exists($path) ) return 0;
    $IMP->debug("Path: $path", 5);
    if ( file_exists($path.'/structs') ){
      $bindingsPath = $path.'/structs/bindings';
      if ( !file_exists($bindingsPath) ) mkdir($bindingsPath);
    }
    $structs = array();
    $structPaths = find_file($path, '*.xml');
    if ( is_array($structPaths) ) foreach ($structPaths as $structPath){
      #:ERROR: will discard anything if the site is under a folder called 'bindings'
      if ( strpos($structPath, 'bindings') !== false) continue;
      $structLocalName = basename($structPath, '.xml');
      if ($nameSpace != 'base') $structName = $nameSpace.'::'.$structLocalName;
      else $structName = $structLocalName;
      $IMP->debug("Adding $structName", 6);
      array_push($structs, $structName);
    }
    if (is_array($this->proxyTo[$nameSpace])) foreach ($this->proxyTo[$nameSpace] as $typeSpace){
      $structs = array_merge($typeSpace->parseNameSpace($nameSpace), $structs);
    }
    return $structs;
  }
  
  /**
  * object getObject(string)
  * Get a DataType object
  *
  * Returns a new instance of the DataType object representing type $type 
  **/
  function getObject($type){
    $className = 'DataT_'.$type;
    if ( !class_exists($className) ) search_include($type, 'DataTypes');
    if ( class_exists($className) ) return new $className();
    return new DataT();
  }
  
  function getObj($type){
    return $this->getObject($type);
  }
  
  /**
  * bool isBaseType(string)
  * Determine if a type is a base type
  *
  * @return true if type $type is one of the internal types, or is handled by some DataType;
  * false otherwise.
  **/
  function isBaseType($type){
    $baseTypes['id'] = true;
    $baseTypes['int'] = true;
    $baseTypes['real'] = true;
    $baseTypes['text'] = true;
    $baseTypes['longText'] = true;
    $baseTypes['richText'] = true;
    $baseTypes['password'] = true;
    $baseTypes['html'] = true;
    $baseTypes['dateTime'] = true;
    $baseTypes['time'] = true;
    $baseTypes['ip'] = true;
    $baseTypes['email'] = true;
    $baseTypes['img'] = true;
    $baseTypes['bool'] = true;
    $baseTypes['file'] = true;
    $baseTypes['phpelican'] = true;
    $baseTypes['order'] = true;
    $baseTypes['money'] = true;
    $baseTypes['url'] = true;
    return $baseTypes[$type];
  }
  


}



?>

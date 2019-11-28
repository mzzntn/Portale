<?
  
class DataStorer extends DataManager{
  var $structName;
  var $bindingManager;
  var $securityManager;
  var $typeSpace;
  var $binding;
  var $params;
  var $options;
  var $idArray;
  var $data;
  var $elements;
  var $dataObjects;
  var $extendData;
  var $multiple;
  var $children;
  var $parents;
  var $ancestorIds;
  var $previousData;

  
  function DataStorer($structName){
    parent::DataManager($structName);
    $this->dataObjects = array();
    $this->parents = array();
  }
  
  function add($type, $name, $value=''){
    global $IMP;
    $IMP->debug("Adding element '$name' of type '$type' and value '$value'", 5);
    if ($name == 'id'){
      if (!$this->config['copyMode']){
        $this->params = new QueryParams();
        $this->params->id = $value;
	return;
      }
    }
    if (!$this->isBaseType($type)){
      $struct = $this->typeSpace->getStructure($type);
      $type = $struct->type('id');
    }
    $value = $this->prepare($type, $value);
    $this->elements[$name]['value'] = $value;
    $this->elements[$name]['type'] = $type;
  }
  
  function addInternals(){
    $now = $this->typeSpace->getObj('dateTime');
    $now->now();
    if ($_SESSION['userId']) $userId = $_SESSION['userId'];
    else $userId = $this->security->userId();
#    if ($this->security->userId == '-1')  $this->add('text', 'perms', $this->security->login);
    $nowIso = $now->toIso();
    $userId = $this->security->userId();
    if (!$userId) $userId = $_SESSION['userId'];
    if ($this->binding->dbField('mod_date')) $this->add('dateTime', 'mod_date', $nowIso);
    if (!$this->binding->isExternal()) $this->add('int', 'mod_user', $userId);
    if ($this->mode == 'insert'){
     if ($this->binding->dbField('cr_date')) $this->add('dateTime', 'cr_date', $nowIso);
      if (!$this->binding->isExternal()) $this->add('int', 'cr_user', $userId);
    }
  }
  
  function setData(& $pelican){
    $this->data = & $pelican;
  }
  
  function set($name, $value){
    if (!$this->data) $this->data = new PHPelican();
    $this->data->set($name, $value);
  }
  
  function addId($id){
    $this->idArray[$id] = true;
    $this->lastSeenId = $id;
  }
  
  function getId(){
    return reset(array_keys($this->idArray)); #returns the first entry
  }
  
  function getIdArray(){
    return $this->idArray;
  }
  
  function store($data=0, $params=0){
    global $IMP;
    $IMP->debug("Starting store on data, params:", 5);
    $IMP->debug($data, 5);
    $IMP->debug($params, 5);
    $this->init();
    if ($IMP->version[$this->structName]){
      $this->watchAll();
    }
    $this->processData($data, $params); #NOTICE: at this step any parent data gets stored
    $this->loadWatches();
    if ($this->execute()){
      if ($IMP->version[$this->structName]){
        while ($this->previousData->moveNext()){
          $this->versioner->put($this->structName, $this->previousData->get(), $data);
        }
      }
      $this->processOne2N();
      $this->processN2N();
      $this->processParents();
      $this->finalize();
      $this->invalidateCaches();
    }
    return $this->lastSeenId;
  }
  
  function checkMode($field='id'){
    $this->checkModeFields[$field] = true;
  }
  
  
  function processData($data=0, $params=0){
    global $IMP;
    if ($data) $this->data = $data;
    if ($params) $this->params = $params;
    $IMP->debug("Processing data and params:", 5);
    $IMP->debug($this->data, 5);
    $IMP->debug($this->params, 5);
    if (!$this->data) error("No data to store was supplied to the datastorer");
    $this->init();
    if (is_array($IMP->alwaysSet[$this->structName])) foreach($IMP->alwaysSet[$this->structName] as $key => $value){
      $this->data->$key = $value;
    }
    $elements = $this->data->getElements();
    $keys = $this->struct->getKeys();
    if (sizeof($keys) < 1 && $this->binding->id) $keys = array('id');
    if (sizeof($keys) > 0){
      foreach ($keys as $key){
        $this->checkMode($key);
      }
    }
    foreach ($elements as $elementName){
      if ($this->data->getAttribute('checkMode', $elementName)){
        $this->checkMode($elementName);
      }
    }
    
    if (sizeof($this->checkModeFields) > 0 ){
      $loader = & $this->getLoader();
      foreach ($keys as $key){
        $loader->request($key);
      }
      foreach (array_keys($this->checkModeFields) as $field){
        $key = $this->data->get($field);
        if ($key){
          $loader->addParam($field, $key);
          $hasParams = true;
        }
      }
      if ($hasParams){
        $list = $loader->load();
        if ($list->moveNext()){
          foreach ($keys as $key){
            $val = $list->get($key);
            if (!$val) $val = 'NULL';
            $this->addParam($key, $val);
          }
          $listId = $list->get('id');
          if ($listId) $this->lastSeenId = $listId;
          $this->addId($listId);
        }
        
      }  
    }
    if (sizeof($this->checkModeFields) < 1 || ($this->checkModeFields['id'] && !$this->config['copyMode'])){
      $id = $this->data->id;
      if ($id){
        $this->add('id', 'id', $id); #this generates new params
        $this->addId($id);
      }
    }
    $this->processParams($this->params);
    if ($this->condition) $this->mode = 'update';
    else $this->mode = 'insert';
    $globallyAllowed = false;
    $this->security->setMode($this->mode);
    $this->security->setStruct($this->structName);
    $userOnly = false;
    foreach($elements as $elementName){ #main loop
      $IMP->debug("Processing $elementName", 5);
      if ($elementName == 'id') continue; #already done with that
      if (!$this->security->checkEl($elementName) ){
        if ( !$this->security->checkCreatorUpd($elementName) ) continue;
        else $this->creatorOnly = true;
      }
      if ($this->struct->parentElements[$elementName]){
        $parentStruct = $this->struct->parentElements[$elementName];
        if (!is_object($this->parents[$parentStruct])) $this->parents[$parentStruct] = new PHPelican();
        $this->parents[$parentStruct]->$elementName = $this->data->$elementName;
        unset($this->data->$elementName);
        continue;
      }
      $type = $this->struct->type($elementName);
      if (!$type){
        //for N2N context data
        if ($elementName{0} == '_' && $this->struct->type(substr($elementName, 1))){
          $elementName = substr($elementName, 1);
          $type = $this->struct->type($elementName);
        }
        else continue;
      }
      if  ( $this->typeSpace->isBaseType($type) ){
        $IMP->debug("Is baseType($type)", 5);
        $this->add($type, $elementName, $this->data->$elementName);
      }
      else{
        $subStruct = $this->typeSpace->getStructure($type);
        if ( $this->binding->dbField($elementName) ){ #we are a child
          $IMP->debug("It is a parent($type)", 5);
          $elementValue = $this->data->$elementName;
          $subId = $this->processN2One($type, $elementValue);
          $this->add($type, $elementName, $subId);
        }
        elseif ($this->binding->isN2N($elementName)){
          $IMP->debug("It is n2n($type)", 5);
          $this->multiple[$elementName] = $type;          
        }
        else{ #we assume the substruct is a child and hope for the best; a check could be
          $IMP->debug("It is a child($type)", 5); #done using the subbinding, but if 
          $this->children[$elementName] = $type;  #it is remote it could not be available
        }
        
      }
    }
    //:FIXME: wrong for external, multiple key tables
    if ( is_array($this->children) || is_array($this->multiple) || is_array($this->parents)) $this->addWatch('id');
    // || is_array($this->checkModeFields)
  }
  
  function processN2One($type, $value){
    global $IMP;
    if (is_array($value)) $value = $value[0];
    $subStruct = $this->typeSpace->getStruct($type);
    $textId = ($subStruct->type('id')=='text');
    //:KLUDGE: quickfix
    if ($type == 'delibere_b::anno' || $type == 'delibere_b::annoProvv' || ($value && !is_numeric($value) && is_string($value) && !$textId)){
    #if ($value && !is_numeric($value) && is_string($value) && !$textId){
      $subStruct = $this->typeSpace->getStruct($type);
      $names = $subStruct->getNames();
      $name = $names[0];
      if ($subStruct->type($name) != 'text') error("Could not interpret text data ($value) for $type");
      $pelican = new PHPelican();
      $pelican->set($name, $value);
      if (is_array($IMP->alwaysSet[$type])) foreach ($IMP->alwaysSet[$type] as $key => $value){
        $pelican->$key = $value;
      }
      $value = $pelican;
    }
    if ( is_object($value) ){
      $elements = $value->getElements();
      if (sizeof($elements) == 1 && $value->id) $subId = $value->id;
      else{
        #:TODO: extend this check to other joins
        $loader = $this->getLoader($type);
        #$params = new QueryParams($value);
        #$loader->setParams($params); this should work but it doesn't
        foreach ($elements as $element){
          $loader->addParam($element, $value->get($element));
        }
        $loader->request('id');
        $list = $loader->load();
        if ($list->moveNext()) $subId = $list->get('id');
        else{
          $subStorer = $this->getStorer($type);
          $subStorer->addWatch('id');
          $subStorer->store($value);
          $subId = $subStorer->getId(); #note: this returns just the first stored id
        }
      }
    }
    else $subId = $value;
    return $subId;
  }
  
  function processOne2N(){
    global $IMP;
    if ( !is_array($this->children) ) return;
    foreach($this->children as $elementName => $elementType){
      $elementValue = $this->data->get($elementName);
      if (!$elementValue) continue;
      $subStruct = $this->typeSpace->getStructure($elementType);
      $linkElement = $subStruct->getLinkingElement($this->struct->name, $elementName);
      $orderEl = $subStruct->getElementsByType('order');
      $orderEl = $orderEl[0];
      $rows = new PHPelican();
      $rows->becomeList();
      if ( is_object($elementValue) ) $subData = $elementValue;
      else{
        if ( !is_array($elementValue) ) $elementValue = array( $elementValue );
        $subData = new PHPelican();
        foreach($elementValue as $subId) $subData->id = $subId; #get the last? why?
        $rows->addRow($subData);
      }
      if ($subData->isList()) $rows = $subData;
      else $rows->addRow($subData);
      $rows->reset();
      $hasData = false;
      while ($rows->moveNext()){
        $row = $rows->getRow();
        if ($row->hasData()){
          $hasData = true;
          break;
        }
      }
      //if (!$hasData) return; ???NEEDED??? conflicts with multiform total deletion
      $rows->reset();
      $subDeleter = & $this->getDeleter($elementType);
      $subDeleter->addParam($linkElement, $this->getId());
      $subDeleter->go();
      $rowCnt = 0;
      while ($rows->moveNext()){
        $row = $rows->getRow();
        $subStorer = $this->getStorer($elementType);
        $row->set($linkElement, $this->getId() );
        if ($orderEl) $row->set($orderEl, $rowCnt);
        $subStorer->store($row);
        $rowCnt++;
      }
    }
  }
  
  function processN2N(){
    if ( !is_array($this->multiple) ) return;
    foreach($this->multiple as $elementName => $elementType){
      if (isset($this->data->$elementName)){
        $elementValue = $this->data->$elementName;
      //if (!$elementValue) continue;
        if ( is_object($elementValue) || (is_array($elementValue) && is_object($elementValue[0])) ){
          $subIdArray = array();
          if (is_object($elementValue) && method_exists($elementValue, 'isList') && $elementValue->isList()){
            $elementValue = $elementValue->listToArray();
          }
          if (!is_array($elementValue)) $elementValue = array($elementValue);
          foreach ($elementValue as $data){
            $subId = $data->id;
            if (is_array($this->struct->extend[$elementName])){
              foreach (array_keys($this->struct->extend[$elementName]) as $extEl){
                $extendInfo[$subId][$extEl] = $data->$extEl;
              }
              array_push($subIdArray, $subId);
            }
          }
        }
        elseif (!is_array($elementValue)) $subIdArray = array($elementValue);
        else $subIdArray = $elementValue;
      }
      else if ($this->data->{'_'.$elementName}) $subIdArray = array_keys($this->data->{'_'.$elementName});
      if ($this->data->{'_'.$elementName}) $extendInfo = $this->data->{'_'.$elementName}; #kludge
      $this->linkN2N($elementName, $subIdArray, $extendInfo);
    }
  }
  
  function processParents(){
    if (!is_array($this->parents)) return;
    foreach (array_keys($this->parents) as $parentStruct){
      $parentIds = $this->getParentIds($parentStruct, $this->idArray);
      $storer = & $this->getStorer($parentStruct);
      foreach (array_keys($this->idArray) as $childId){
        $parentId = $parentIds[$childId];
        if ($parentId) $this->parents[$parentStruct]->set('id', $parentId);
        $parentId = $storer->store($this->parents[$parentStruct]);
        $newParentIds[$childId] = $parentId;
		$this->ancestorIds[$parentStruct][$childId] = $parentId;
      }
      $this->setParentIds($parentStruct, $newParentIds);
    }
  }
  
  function linkN2N($element, $idArray){
    #virtual
  }
  
  function addWatch($element){
    if (!$this->watches) $this->watches = new Requests();
    $this->watches->$element = 3;
  }
  
  function watchAll(){
    $elements = $this->struct->getElements();
    foreach ($elements as $element){
      $this->addWatch($element);
    }
  }
  
  function loadWatches(){
    global $IMP;
    if ($this->mode == 'update' && is_object($this->watches) ){
      $IMP->debug("Loading watches:", 5);
      $IMP->debug($this->watches);
      $loader = & $this->getLoader($this->structName);
      $params = $this->params;
      if ($this->data->id) $params->id = $this->data->id;
      //$loader->setCondition($this->conditionBuilder->getCondition()); #condition format is binding-dependent; however,
      $loader->setParams($params);
      $loader->setRequests($this->watches);    #the loader will be of the same binding as this,
      $list = $loader->load();                 #so it's safe to pass it as it is
      $this->idArray = array();
      while ( $list->moveNext() ) $this->addId($list->get('id'));
      $this->previousData = $list;
      //if (is_array($this->checkModeFields) && sizeof($this->idArray) < 1){
      //  $this->condition = '';
      //  $this->mode = 'insert';
      //}
    }
  }
  
  function finalize(){
    foreach ($this->dataObjects as $obj){
      $obj->store($this->binding->type);
    }
  }
  
  function loadDefaultBindings(){
    #:TODO:
  }
  
  function prepare($type, $value){
    global $IMP;
    if ($type=='id' || !$this->typeSpace->isBaseType($type)) $type='int';
    $obj = $this->typeSpace->getObj($type);
    $obj->set($value);
    $this->dataObjects[] = & $obj;
    $res = $obj->get($this->binding->type, $this->binding->dbType);
    $IMP->debug("The prepared value is $res", 5);
    return $res;
  }
  
  function invalidateCaches(){
    global $IMP;
    if (is_array($IMP->config['cache_invalidate_by_structure'][$this->structName])) foreach ($IMP->config['cache_invalidate_by_structure'][$this->structName] as $nameSpace => $widgets){
      foreach ($widgets as $widgetType => $instances){
        if (is_array($instances)){
          foreach ($instances as $name => $bool){
            deleteDirectory(VARPATH.'/widgets_cache/'.$nameSpace.'/'.$widgetType.'/'.$name);
          }
        }
        else{
          deleteDirectory(VARPATH.'/widgets_cache/'.$nameSpace.'/'.$widgetType);
        }
      }
    }
  }
  
  function removeN2N($id){
      
  }
  
  function updateN2N($element, $contextData){
      
  }

}

?>

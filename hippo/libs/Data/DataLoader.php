<?
include_once(LIBS.'/Data/TypeSpace.php');
include_once(LIBS.'/Data/DataManager.php');

/**
* Base class for structured data loading
*
* @author Ivan Pirlik
* @version 0.8
*
*/

class DataLoader extends DataManager{
  var $order;
  var $ordered;
  var $requests;
  var $fetchWindow;
  var $elements;
  var $selectStructs;
  var $foreign;
  var $loadAll;
  var $parentKeys;
  var $hasMore;
  var $contextElements;
  var $parentElements;
  

  /**
  * Il costruttore
  *
  * @param string $structName	the full name of the structure the object has to work on
  */
  function DataLoader($structName){
    parent::DataManager($structName);
  }

  /**
  * Carica oggetti helper
  *
  * @access private
  * @see DataManager::init()
  */
  function init(){
    parent::init();
    $this->conditionBuilder = & $this->getConditionBuilder();
  }

  /**
  * Esegue la query; restituisce una lista
  *
  * @param int $start	      first row to fetch (starting from 0)
  * @param int $end	      last row to fetch; if 0 or not specified, the rows are fetched until
  *                           the stream's end, unless DataLoader::config['limit'] is set
  * @return object PHPelican  a list containing one PHPelican object for each row
  */
  function load($start=0, $end=0){
    global $IMP;
    $IMP->debug("Starting load", 5);
    $this->init();
    $this->processParams($this->params);
    $this->processRequests($this->requests);
    $this->start = $start;
    $this->end = $end;
    $this->execute();
    #$this->data->indexBy('id');
    if (!$this->fetchWindow) $this->fetch($start, $end);
    //$this->data->sort($this->order, $this->ordered);
    return $this->data;
  }
  
  
  /**
   * Esegue la query in modalità test mode, per verificare su quali elementi può essere effettuata
   * l'operazione $mode
   * 
   * @param string $mode    Il modo da testare
   * @param int $start      Prima riga da caricare
   * @param int $end        Ultima riga da caricare
   */
  function testMode($mode, $start=0, $end=0){
      $this->testMode = $mode;
      $this->reset();
      unset($this->requests);
      $this->request('id');
      return $this->load($start, $end);
  }

  /**
   * Resetta il loader
   */
  function reset(){
      $this->fetched = array();
      if ($this->data) $this->data->emptyList();
   }

  /**
  * Richiede il caricamento di un singolo elemento
  * @param string $elementName
  * @access private
  */
  function add($elementName){
    global $IMP;
    if ( !$this->security->checkEl($elementName) ) return;
    $IMP->debug("Adding $elementName to be loaded", 5);
    $this->elements[$elementName] = true;
    if ($this->struct->isMultiLanguage($elementName) && $IMP->config['fallback_to_default_lang']){
      $languageNeutral = $this->struct->getLanguageNeutral($elementName);
      $defaultLangEl = $languageNeutral.'_'.$IMP->config['default_lang'];
      $this->elements[$defaultLangEl] = true;
    }
  }

  /**
  * Richiede il caricamento di un elemento context
  * @param string $elementName
  * @access private
  */
  function addContextElement($elementName){
    $this->contextElements[$elementName] = true;
  }


  /**
  * Set to load a field from a different struct; it's expected that
  * the struct will have a uniquely defined relationship to the current
  * 
  * @access private
  * @param string $structName
  * @param string $elementName
  */
  function addForeign($structName, $elementName){
    if ( !$this->security->checkEl($elementName, $structName) ) return;
    $this->foreign[$structName] = $elementName;
  }


  /**
  * Richiede il caricamento di un elemento che è una struttura
  *
  * @access private
  * @param string $elementName
  */  
  function addSubStruct($elementName){
    global $IMP;
    if ( !$this->security->checkEl($elementName) ) return;
    $IMP->debug("Adding substruct $elementName to be loaded", 5);
    $this->selectStructs[$elementName] = true;
  }

  /**
  * Richiede il caricamento di tutti gli elementi
  * @access private
  */
  function addAll(){
    $this->loadAll = true;
  }

  /**
   * Richiede il caricamento di tutti gli elementi semplici; per le strutture verranno caricati gli id
   */
  function requestAll(){
    if (!$this->requests) $this->requests = new Requests();
    while ($this->struct->moveNext()){
      $element = $this->struct->currentElement();
      if (!$this->requests->$element) $this->requests->$element = 1;
    }
    $this->loadAll = true;
  }

  /**
   * Aggiunge una condizione alla query
   * 
   * @param string $elementName     nome dell'elemento
   * @param mixed $value            valore della condizione
   * @param string $comparison      il confronto da effettuare (di default è '=')
   */
  function addParam($elementName, $value, $comparison=''){
    if ( !is_object($this->params) ) $this->params = new QueryParams();
    $this->params->set($elementName, $value);
    if ($comparison) $this->params->setComparison($elementName, $comparison);
  }
  
  function addRange($elementName, $value1, $value2){
      if (!is_object($this->params)) $this->params = new QueryParams();
      $this->params->addRange($elementName, $value1, $value2);
  }


  /**
   * Imposta quanti elementi scaricare alla volta
   *
   * @param int $size
   */
  function setFetchWindow($size){
    $this->fetchWindow = $size;
  }


  /**
   * Ritorna true se ci sono altre righe oltre a quelle caricate in base all'$end o alla fetchWindow
   */
  function hasMore(){
    return $this->hasMore;
  }

  /**
   * @access private
   */
  function & getLoader($bindType){
    $obj = $this->bindingManager->getLoader($bindType);
    $obj->setTypeSpace($this->typeSpace);
    $obj->setBindingManager($this->bindingManager);
    $obj->setSecurity($this->security);
    return $obj;
  }

  /**
   * Imposta l'ordinamento
   * 
   * @param string $element     L'elemento per cui ordinare
   * @param string $type        La direzione (di default è 'ASC', accetta 'DESC')
   */
  function setOrder($element, $type='ASC'){
    $this->order = array();
    $this->addOrder($element, $type);
  }

  /**
    * Aggiunge l'ordinamento per un elemento
    * 
    * @param string $element     L'elemento per cui ordinare
    * @param string $type        La direzione (di default è 'ASC', accetta 'DESC')
    */
  function addOrder($element, $type='ASC'){
    $this->order[$element] = $type;
    $this->request($element);
  }

  /**
   * Effettua il caricamento nel contesto di un'altra struttura (in modo da caricarne i dati aggiuntivi)
   * 
   * @param string $struct      La struttura che contiene questa
   * @param string $el          L'elemento della struttura che punta a questa
   * @param int $id             L'id dell'oggetto nel cui contesto ci si trova
   */
  function setContext($struct, $el, $id){
    $this->context['struct'] = $struct;
    $this->context['el'] = $el;
    $this->context['id'] = $id;
  }

  /**
   * Aggiunge l'ordinamento per un elemento (alias di addOrder)
   *
   * @param string $elementName 
   * @param string $orderType 
   * @return void
   */
  
  function sortBy($elementName, $orderType=''){
    $this->addOrder($element, $orderType);
  }

  /**
   * Setta le request all'oggetto passato alla funzione
   *
   * @param Requests $requests 
   * @return void
   */
  
  function setRequests($requests){
    if (is_string($requests) && $requests != ''){
      $obj = new Requests();
      $obj->loadFromXml($requests);
      $requests = $obj;
    }
    $this->requests = $requests;
  }

  
  /**
   * Richiede il caricamento di un elemento
   * Può essere chiamata anche con la sintassi request($element1, $element2, $element3...);
   *
   * @param string $element 
   * @param int $level          Fino a che livello caricare l'albero delle strutture
   */
  
  function request($element, $level=1){
    if (is_string($level)){
      $level = 1;
      $elements = func_get_args();
    }
    else $elements = array($element);
    if (!is_object($this->requests)) $this->requests = new Requests($this->structName);
    foreach ($elements as $element){
      $this->requests->request($element, $level);
    }
  }


  /**
   * Richiede il caricamento dei nomi
   *
   */
  
  function requestNames($names=0){
    if (!is_object($this->requests)) $this->requests = new Requests($this->structName);
    if (!$names) $names = $this->struct->getNames();
    foreach ($names as $name){
      $this->requests->request($name);
    }
  }


  /**
   * @access private
   */
  function processRequests($requests){
    global $IMP;
    $IMP->debug("Starting to process requests for $this->structName", 5);
    $IMP->debug($requests, 5);
    if (!$requests || $requests == 'all') $this->addAll();
    if($requests !== 1){ #1 stands for 'load just id', which is done later
    $elements = $this->struct->getElements();
    foreach ($elements as $element){
      $type = $this->struct->type($element);
      if ($type == 'order') $this->addOrder($element);
      if ($this->struct->isMultiLanguage($element)){
        $langElements = $this->struct->getElementInAllLanguages($element);
        $langElements[] = $element;
      }
      else $langElements = array($element);
      foreach ($langElements as $elementName){
        //in-depth requests
        //if (is_int($requests) || $requests == 'inf'){
          //  if (is_int($requests)) $newRequests = $requests - 1;
          //  else $newRequests = $requests;
          //  $requests->$elementName = $newRequests;
          //}
          $doLoad = false;
          if ($this->loadAll || $requests->$elementName || $this->struct->isKey($elementName)) $doLoad = true;
          if ($this->isBaseType($type) && $IMP->config['always_load_default_lang'] && $IMP->config['default_lang']){
            $languageNeutral = $this->struct->getLanguageNeutral($elementName);
            $defaultLangEl = $languageNeutral.'_'.$IMP->config['default_lang'];
            if ($elementName == $defaultLangEl) $doLoad = true;
          }
          if ($doLoad){
            $this->add($elementName);
            if ($this->struct->parentElements[$elementName]) $this->parentElements[$elementName] = true;
            if (!$this->typeSpace->isBaseType($type) && $requests->$elementName !== 1){
              $this->addSubStruct($elementName);
            }
          }
        }
      }
    }
    if ($this->context){
      $contextStructName = $this->context['struct'];
      $contextEl = $this->context['el'];
      $contextStruct = $this->typeSpace->getStructure($contextStructName);
      $this->contextStruct = $contextStruct;
      $this->contextBinding = $this->bindingManager->getBinding($contextStructName);
      if (is_array($contextStruct->extend[$contextEl])) foreach ( array_keys($contextStruct->extend[$contextEl]) as $elementName){
        $added = false;
        $type = $contextStruct->getExtendType($contextEl, $elementName);
        if ($requests->$elementName || $type == 'order'){
          $this->add($elementName);
          $this->addContextElement($elementName);
          if ($type == 'order') $this->addOrder($elementName);
          $IMP->debug("Added context element $elementName", 6);
          $added = true;
        }
      }
    }

    foreach( array_keys( $this->struct->getInternals() ) as $internalElement){
      if ( (($requests == 'info' || $this->loadAll) && !$this->binding->isExternal()) ||
      $this->requests->$internalElement){
        $this->add($internalElement);
      }
    }
    #else $this->add('id');
  }

  /**
   * @access private
   */
  function processParams(& $params){
    global $IMP;
    if ($this->context){
      if (!$params) $params = new QueryParams($this->structName);
      if (!$params->{$this->context['struct']}->id){
        $params->{$this->context['struct']}->id = $this->context['id'];
      }
    }
    $IMP->debug("Starting to process params for $this->structName", 5);
    if (!is_object($params)){
      if ($IMP->config['alwaysParams'][$this->structName]) $params = new QueryParams();	    
      else return;
    }
    $this->conditionBuilder->processParams($params);
    $this->getForeign($params);
    $IMP->debug("Params processed", 5);
  }

  /**
   * @access private
   */
  function getForeign($params){
    global $IMP;
    $IMP->debug("getForeign for $this->structName called", 6);
    if (!is_object($params)) return;
    $IMP->debug($params, 6);
    $params->reset();
    while ($params->moveNext()){
      $param = $params->get();
      $name = $params->getName();
      if ($params->getAttribute('custom', $name)) continue;
      $IMP->debug("getForeign for $this->structName processing param: ", 6);
      $IMP->debug($param, 6);
      if ($params->isConjunction() || $params->isList() ){
        $this->getForeign($param); #$param should be an object
      }
      else{ #$param is a string
        $param = $name;
        if (!$this->struct->hasElement($param)){
          $this->addForeign($param, 'id');
          $IMP->debug("Added foreign param $param", 6);
        }
      }
    }
  }

  /**
   * @access private
   */
  function loadSubStructs($idArray){
    global $IMP;
    $IMP->debug("Starting to load subStructs for $this->structName", 6);
    if ( !is_array($this->selectStructs) || sizeof($idArray) < 1 || !$this->requests) return;
    foreach( array_keys($this->selectStructs) as $elementName ){
      if ( !$this->security->checkEl($elementName) ) continue;
      $IMP->debug("Substruct element: $elementName", 6);
      $type = $this->struct->type($elementName);
      $struct = $this->typeSpace->getStruct($type);
      $loader = $this->getLoader($type);
      $subRequests = $this->requests->get($elementName);
      $IMP->debug("Subrequests:", 6);
      $IMP->debug($subRequests, 6);
      if ( !$this->isValidRequest($subRequests)) continue;
      if (is_object($subRequests) && !$this->requests->isPelican($subRequests)) 
        $subRequests = new Requests($type, $subRequests);
      #:TODO: extend to handle different types of recursion
      if ( (is_int($subRequests) && $subRequests > 1) || $subRequests == 'inf'){
        if (is_int($subRequests)) $level = $subRequests - 1;
        else $level = 'inf';
        $subRequests = new Requests();
        $elements = $struct->getElements();
        if ($this->struct->extend[$elementName]){
            foreach (array_keys($this->struct->extend[$elementName]) as $contextElement){
                $elements[] = $contextElement;
            }
        }
        foreach($elements as $element){ 
          $type = $struct->type($element);
          if ($type != $this->struct->name) $subRequests->request($element, $level);
        }
      }
      $IMP->debug("Subrequests:", 6);
      $IMP->debug($subRequests, 6);
      $subParams = new QueryParams();
      if (is_array($this->parentKeys[$elementName])){
        $parentKeys = array_values($this->parentKeys[$elementName]);
        foreach ($parentKeys as $key => $value) if (!$value) unset($parentKeys[$key]);
        //go on with the next struct if no parentKeys are found; this is only needed
        //for legacy databases
        if (sizeof($parentKeys) < 1) continue;
        $subParams->id = $parentKeys;
        $indexElement = 'id';
      }
      else{
        if ($this->struct->parentElements[$elementName]){
          $struct = $this->struct->getAncestorStruct($elementName);
          $structName = $struct->name;
          $ids = array_values($this->ancestorIds[$structName]);
        }
        else{
          $ids = & $idArray;
          $structName = $this->structName;
        }
        $subParams->{$structName}->id = $ids;
        $indexElement = '_id_'.$structName;
        $subList = new PHPelican();
        $subList->indexBy($indexElement);
        $loader->data = & $subList; #:ERROR: not net safe? is needed for indexing
      }
      $loader->setRequests($subRequests);
      $loader->setParams($subParams);
      $IMP->debug("Subparams:", 6);
      $IMP->debug($subParams, 6);
      //:FIXME: make it work with substruct loading for lists?
      if ($this->struct->extend[$elementName]){ 
        $loader->setContext($this->structName, $elementName, $idArray[0]);
      }
      $subList = $loader->load();
      $IMP->debug("SubList for $elementName:", 6);
      $IMP->debug($subList);
      if ( is_array($this->rows) ) foreach( array_keys($this->rows) as $rowId){
        if (is_array($this->parentKeys[$elementName])){
          $elementList = $subList->search($indexElement, $this->parentKeys[$elementName][$rowId]);
        }
        else{
          if ($this->struct->parentElements[$elementName]){
            $struct = $this->struct->getAncestorStruct($elementName);
            $structName = $struct->name;
            $searchId = $this->ancestorIds[$structName][$rowId];
          }
          else $searchId = $rowId;
          $elementList = $subList->search($indexElement, $searchId);
        }
        $this->rows[$rowId]->set($elementName, $elementList);
        $IMP->debug("Set element list on row $rowId for $elementName", 6);
      }
    }
  }

  /**
   * @access private
   */
  function isValidRequest($request){
    if (is_object($request)) return true;
    if (is_int($request)) return true;
    if ($request == 'inf') return true;
    return false;
  }

  /**
   * @access private
   */
  function numResults(){
    #virtual
  }

  /**
   * @access private
   */
  function decode($type, $value){
    // $value = preg_replace("/'+/", "'", $value);
    //     $value = str_replace("/sitowebnew", "", $value);
    if ($type == 'id') return $value;
    if ($this->isBaseType($type)){
      $obj = $this->typeSpace->getObj($type);
      $obj->set($value, $this->binding->type, $this->binding->dbType);
      return $obj->get();
    }
    return $value;
  }

}



?>

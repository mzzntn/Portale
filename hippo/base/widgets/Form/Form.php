<?
  
class Form extends BasicInput{
  var $id;
  var $info;
  var $error;
  var $errors;
  var $inputTypes;
  var $inputs;
  var $inputsOrder;
  var $inputsPos;
  var $hidden;
  var $disabled;
  var $invisible;
  var $required;
  var $elements;
  var $tables;
  var $subForms;
  var $fixed;
  var $rawData;
  var $inlineStructs;
  var $isMulti;
  var $stored;
  var $readOnly;
  var $readOnlyElements;
  
  
  function Form($name, $structName='', $id=0){
    parent::DataWidget($name, $structName);
    $this->inputsOrder = array();
    if ($id) $this->setParam('id', $id);
    $this->rawData = $this->widgetParams->getPelican($this->name);
    //print_r($this->rawData);
    $this->id = $this->rawData->id;
    if (!$this->id) $this->new = true;
    $this->inputs = new PHPelican();
    $this->update = false;
    $this->config['insertText'] = _('Aggiungi');
    $this->config['modifyText'] = _('Salva');
    $this->disabled['id'] = true;
    $this->config['keepData'] = true;
    $this->config['method'] = 'POST';
    $this->config['allowFileDelete'] = true;
  }
  
  function setId($id){
    if (!$id && !$_POST[$this->name])$this->clearParams();
    $this->id = $id;
    $this->setParam('id', $id);
  }
  
  //function choose($element){
  //  if (!is_object($this->elements)) $this->elements = new PHPelican();
  //  $this->elements->$element = true;
  //}
  
  function get($name){
 //   if ($this->inputs->$name)
 //     return $this->inputs->$name->fixValue($this->data->$name);
    return $this->data->$name;
  }

  
  function takeData($element){
    if ($this->data){
      $data = $this->data->get($element);
      $this->data->set($element);
    }
    $data = $this->widgetParams->take($element);
    return $data;
  }
  
  function getElements(){
    if ($this->config['elements']) $elements = $this->config['elements'];
    else $elements = $this->struct->getElementsWithLocalized();    
    return $elements;
  }
  
  function set($name, $value){
    $ok = $this->data->set($name, $value);
    if ($ok && $this->inputs->$name){
      $this->inputs->$name->setValue($value);
    }
  }
  
  function setFixed($name, $value){
    $this->fixed[$name] = $value;
    $this->disabled[$name] = true;
  }
  
  function & createInput($widgetType, $name, $structName=''){
    global $IMP;
    $this->inputTypes[$name] = $widgetType;
    if ($widgetType == 'Form') $widget = 'Form';
    elseif ($widgetType == 'SearchForm') $widget = 'Form/SearchForm';
    else $widget = 'Form/inputs/'.$widgetType;
    $input =  & $this->createWidget($widget, "{$this->name}[$name]");
    $IMP->debug("Created input ($widgetType, $name, $structName):", 9);
    $IMP->debug($input, 9);
    if ($structName) $input->setStruct($structName);
    if ($structName){
      #recursion breaking
      $input->disabledTypes = $this->disabledTypes;
      $input->disabledTypes[$this->structName] = true;
    }
    if (!$this->labels[$name]) $this->labels[$name] = $name;
    #$this->inputs->set($name, & $input);
    if (is_array($this->config['inputs'][$name])) foreach ($this->config['inputs'][$name] as $key => $value){
      $input->config[$key] = $value;
    }
    if($widgetType == 'FileInput') {
      $input->config["allowFileDelete"] = $this->config["allowFileDelete"];
      $input->config["storico"] = $this->struct->getParameter($name,"storico");
      $this->config['storicizza'] = true;
    }
    if ($this->config[$name]['template']) $input->setTemplate($this->config[$name]['template']);
    $input->form = & $this;
    $this->inputs->$name = & $input;
    $this->inputs->$name->inputName = $name;
    return $input;
  }
  
  function addToOrder($name, $pos=0){
    if ($this->invisible[$name]) return;
    if (!$pos) array_push($this->inputsOrder, $name);
    else array_insert($this->inputsOrder, $name, $pos-1);
    if ($pos) $this->inputsPos[$name] = $pos;
    else $this->inputsPos[$name] = sizeof($this->inputsPos);
  }
  
  
/**
Call before generateFromStructure() !
*/  
  function hide($elementName){
      $args = func_get_args();
      foreach ($args as $elementName){
          $this->hiddenElements[$elementName] = true;
      }
  }
  
  function disable($elementName){
    $args = func_get_args();
    foreach ($args as $elementName){
      $this->disabled[$elementName] = true;
    }
  }
  
  function invisible($elementName){
    $args = func_get_args();
    foreach ($args as $elementName){
      $this->invisible[$elementName] = true;
    }
  }
  
  function readOnly($elementName, $val=true){
      $this->readOnlyElements[$elementName] = $val;
  }
  
  function removeOnInsert($elementName){
    $this->onlyUpdate[$elementName] = true;
  }
  
  function removeOnUpdate($elementName){
    $this->onlyInsert[$elementName] = true;
  }
  
  
  function setOption($elementName, $option, $value=1){
    $this->config[$elementName][$option] = $value;
  }

  function generateFromStructure(){
    global $IMP;
    global $C;
    $this->generatedFromStructure = true;
    $this->id = $this->widgetParams->get($this->name, 'id');
#    if ($this->id) $this->getItemInfo();
    if ( !$this->security->checkStruct($this->structName) ) return;
    $this->struct = $this->typeSpace->getStructure($this->structName);
    if (!$this->checkWritable()){
        $this->readOnly = true;
    }
    $elements = $this->getElements();
    foreach($elements as $elementName){
      if ($this->config[$elementName]['fixed']){
          $this->setFixed($elementName, $this->config[$elementName]['fixed']);
      }
      if ($this->disabled[$elementName]) continue;
      $readOnly = true;
      if ( !$this->security->checkEl($elementName, $this->structName, 'read') ) continue;
      $canWrite = false;
      if (!$this->readOnly && !$this->readOnlyElements[$elementName]){
        if ( $this->id )
          $canWrite = $this->security->checkEl($elementName, $this->structName, 'update');
        else
          $canWrite = $this->security->checkEl($elementName, $this->structName, 'insert');
      }
      if ($canWrite){
        $readOnly = false;
        $this->tmp['canWrite'] = true;
      }
      $languageNeutralName = $this->struct->getLanguageNeutral($elementName);
      $type = $this->struct->type($elementName);
      if ($this->disabledTypes[$type]) continue;
      if ($this->struct->isRequired($elementName)){
        $this->required[$elementName] = true;
      }
      $value = $this->data->$elementName;
      $IMP->debug("Form field $elementName, $type, $value", 6);
      if ( $this->typeSpace->isBaseType($type) || $this->hiddenElements[$elementName]){
        $widgetTypes['text'] = 'TextInput';
        $widgetTypes['longText'] = 'TextAreaInput';
        $widgetTypes['dateTime'] = 'DateTimeInput';
        $widgetTypes['time'] = 'TextInput';
        $widgetTypes['int'] = 'TextInput';
        $widgetTypes['real'] = 'TextInput';
        $widgetTypes['html'] = 'RichTextInput';
        $widgetTypes['richText'] = 'RichTextInput';
        $widgetTypes['password'] = 'PasswordInput';
        $widgetTypes['file'] = 'FileInput';
        $widgetTypes['img'] = 'ImageInput';
        $widgetTypes['email'] = 'TextInput';
        $widgetTypes['order'] = 'TextInput';
        $widgetTypes['bool'] = 'CheckBoxInput';
        $widgetTypes['money'] = 'TextInput';
        $widgetTypes['picture'] = 'PictureInput';
        if ($this->config[$elementName]['inputWidget']) $widgetType = $this->config[$elementName]['inputWidget'];
        else $widgetType = $widgetTypes[$type];
        if ($this->hiddenElements[$elementName]) $widgetType = 'HiddenInput';
        if (!$widgetType) continue;
        $this->createInput($widgetType, $elementName);
        if (!$addedToOrder[$languageNeutralName]){
          $addedToOrder[$languageNeutralName] = true;
          $this->addToOrder($languageNeutralName);
        }
        $this->inputs->$elementName->elementName = $elementName;
        $this->inputs->$elementName->setValue($value);
        $this->inputs->$elementName->setReadOnly($readOnly);

        if ($this->required[$elementName]) $this->inputs->$elementName->addClass('required');
        if ($type == 'int' || $type == 'order'){
          $this->inputs->$elementName->config['size'] = 5;
        }
        if ($type == 'real'){
          $this->inputs->$elementName->config['size'] = 8;
        }
        if ($type == 'file' || $type == 'picture'){
          $this->config['enctype'] = 'multipart/form-data';
        }
      }
      else{ #structure
        $otherStruct = $this->typeSpace->getStructure($type);
        if (!$this->config[$elementName]['noSelect'] && !$this->config['multiForm'][$elementName] && ($this->config['showSelect'][$type] || $this->struct->isChildOf($type) || ($otherStruct && !$otherStruct->isChildOf($this->structName) && !$this->struct->extendsType($type)))){
          #if ($this->security->checkStruct($type, 'insert')) $widgetType = 'ComboInput';
          #else $widgetType = 'SelectInput';
          if ($this->config[$elementName]['inputWidget']) $widgetType = $this->config[$elementName]['inputWidget'];
          else $widgetType = 'SelectInput';
          $this->createInput($widgetType, $elementName, $type);
          if ($this->config[$elementName]['combo']) $this->inputs->$elementName->setTemplate('combobox');
          if ($this->config[$elementName]['onlyRelated']){
              $linkingElement = $otherStruct->getLinkingElement($this->structName);
              $this->inputs->$elementName->config['params'][$linkingElement] = $this->id;
          }
          $IMP->debug("Form generating $elementName", 4);
          $this->inputs->$elementName->generateFromStructure();
          $this->addToOrder($elementName);
          if ($this->struct->isMultiple($elementName)) 
            $this->inputs->$elementName->config['multiple'] = true;
          $this->inputs->$elementName->setValue($value);
          $this->inputs->$elementName->setReadOnly($readOnly);
          $this->inputs->$elementName->elementName = $elementName;
          if ($this->required[$elementName]) $this->inputs->$elementName->addClass('required');
        }
        if ($this->config['multiForm'][$elementName]){
          $form = & $this->createInput('Form', $elementName, $type);
          $form->isMulti = true;
          $form->disabledTypes['order'] = true;
          //$form->setTemplate('multi');
          $form->setValue($value);
          $form->setReadOnly($readOnly);
          $form->generateFromStructure();
          $this->config['enctype'] = $form->config['enctype'];
          $this->addToOrder($elementName);
          $this->inlineStructs[$elementName] = true;
          #$this->subForms[$elementName] = & $form;
        }
        if ( $this->config[$elementName]['showTable'] || !( $this->struct->isChildOf($type) || ($otherStruct && !$otherStruct->isChildOf($this->structName) && !$this->struct->extendsType($type) ) )  && !$this->config['multiForm'][$elementName]){
          $table = & $this->createWidget('Table', $this->name.'_tab'.$elementName);
          $table->setStruct($type);
          $table->addClass('Administrator'); //KLUDGE
          $table->setContext($this->structName, $elementName, $this->id);
          $table->config['allowDelete'] = true;
          $table->config['maxElements'] = 3;
          $table->config['maxRows'] = 0;
          $table->config['contextAdmin'] = $this->name;
          $table->config['admin'] = $_SERVER['PHP_SELF'].'?'.$this->parent->name.'[widget]='.$otherStruct->name.'&form_'.$otherStruct->name.'[id]=';
	  #$table->config['selectableRows'] = false;
          if ($this->config[$elementName]['tableParams']){
            $table->setParams($this->config[$elementName]['tableParams']);
          }
          #$table->generateFromStructure();
          $this->tables[$elementName] = & $table;
          $subStruct = $this->typeSpace->getStructure($type);
          $subElements = $subStruct->getElements();
          //:TODO: miniform could be bigger; have to count number of "requested" elements instead of all
          if ( ($this->config[$elementName]['showMiniForm'] || sizeof($subElements) < 3) || $this->config[$elementName]['showSubForm']){
            $form = & $this->createWidget('Form', $this->name.'_subform'.$elementName);
            $form->config['keepData'] = 0;
            $linkingElement = $subStruct->getLinkingElement($this->structName);
            if ($linkingElement){
              $form->setFixed($linkingElement, $this->id);
            }
            if ($this->config[$elementName]['showSubForm']){
                $form->label = "Aggiungi nuovo:";
            }
            else $form->setTemplate('micro');
            $form->setStruct($type);
            $form->generateFromStructure();
            $this->subForms[$elementName] = & $form;
          }
        }
      }
      $this->readOnlyElements[$elementName] = $readOnly;
      $this->labels[$languageNeutralName] = $this->struct->label($languageNeutralName);
    }
    if ($this->id)
    {
      $this->saveActions['save'] = 'Salva';
      $this->saveActions['save_and_stay'] = 'Salva e rimani';
      $this->saveActions['save_and_new'] = 'Salva e nuovo';
    }
    else
    {
      $this->saveActions['save'] = 'Inserisci';
      $this->saveActions['save_and_stay'] = 'Inserisci e rimani';
      $this->saveActions['save_and_new'] = 'Inserisci e nuovo';
    }
    $IMP->debug("Created inputs:", 9);
    $IMP->debug($this->inputs, 9);
  }
  
  function removeInput($name){
    unset($this->inputs->$name);
    unset($this->labels[$name]);
    unset($this->inputsOrder[$this->inputsPos[$name]-1]);
  }
  
  function removeAllInputs(){
      foreach ($this->inputsOrder as $inputName){
          $this->removeInput($inputName);
      }
      $this->inputsOrder = array();
  }
  
  function buildRequests(){
    global $IMP;
    $requests = new Requests();
    #:KLUDGE: we have all elements in readOnly
    if (is_array($this->readOnlyElements)) foreach ( array_keys($this->readOnlyElements) as $elementName){
      if ($this->inlineStructs[$elementName]){
        $subStruct = $IMP->typeSpace->getStructure($this->struct->type($elementName));
        foreach ($subStruct->getElements() as $element){
          $requests->request($elementName.'.'.$element);
        }
      }
      elseif ($this->struct->isInline($elementName)){
      	$requests->request($elementName, 1);
      }
      else $requests->request($elementName, 2);
    }
    return $requests;
  }
  
  function loadData(){
    global $IMP;
    if (!$this->config['keepData']) $this->widgetParams->clear($this->name);
    $this->struct = $this->typeSpace->getStructure($this->structName);
    $this->id = $this->widgetParams->get($this->name, 'id');
    if ($this->id && !$this->error){
      $loader = $this->getLoader($this->structName);
      $loader->addParam('id', $this->id);
      #$loader->requestAll();
      $requests = $this->buildRequests();
      $loader->setRequests($requests);
      $list = $loader->load();
      if ($list->listSize() > 0){
        $row = $list->getRow();
        #this gets all active elements
        foreach( array_keys($this->readOnlyElements) as $elementName){ 
          $IMP->debug("Form loading $elementName", 6);
          if ($this->inlineStructs[$elementName]){
            $this->data->$elementName = $row->$elementName;
          }
          elseif ( $list->isList($elementName) ){
            $IMP->debug("Is pelican", 6);
            $idArray = array();
            #$subList = $list->get($elementName);
            while ( $list->moveNext($elementName) ){
               array_push( $idArray, $list->get($elementName.'.id') );
            }
            if (sizeof($idArray) < 2) $this->data->$elementName = $idArray[0];
            else $this->data->$elementName = $idArray;
          }
          else $this->data->$elementName = $list->get($elementName);
        }
      }
      $IMP->debug("Form loaded data: ", 7);
      $IMP->debug($this->data, 7);
    }
    elseif(!$this->data) $this->data = $this->rawData; //needed for errors
    //KLUDGE: all elements are in readOnlyElements
    if (is_array($this->readOnlyElements)) foreach ( array_keys($this->readOnlyElements) as $elementName){
      $type = $this->struct->type($elementName);
      $value = $this->data->$elementName;
      if (!isset($this->data->$elementName) &&
           $this->config['defaults'][$elementName]){
        $value = $this->config['defaults'][$elementName];
      }
      if ( is_object($this->inputs->$elementName) ){
        if ($type == 'real') $value = round($value, 20);  //:KLUDGE
        $this->inputs->$elementName->setValue($value);
	//COMMENTATO IL 01/01/2016 DA VERIFICARE
        //$IMP->debug("Set value to $elementName, $value", 7);
      }
    }
    if ($this->id && is_array($this->tables)) foreach (array_keys($this->tables) as $elementName){
      #context is already set, but the id may not
      $this->tables[$elementName]->setContext($this->structName, $elementName, $this->id);
      #FIXME: in user managed forms tables must not be loaded!
      $this->tables[$elementName]->load();
    }
  }
  
  
  function setValue($data){
    $this->data = $data;
    $this->loadData();
  }
  
  function prepare($data){
    if ($this->isMulti){
      $res = new PHPelican();
      $res->becomeList();
      if (is_array($data)) foreach ($data as $key => $value){
        $res->addRow($this->buildPelican($value));
      }
    }
    else $res = $this->buildPelican($data);
    return $res;
  }
  
  function fixValue($value){
    return $value;
  }
  
  #overloads 
  function clearParams(){
    $this->widgetParams->clear($this->name);
    unset($this->data);
    unset($this->rawData);
    foreach (array_keys($this->children) as $key){
      $this->children[$key]->clearParams();
    }
    $this->inputs->reset();
    while ($this->inputs->moveNext()){
      $name = $this->inputs->getName();
      if ($this->inputs->name) $this->inputs->$name->setValue('');
    }
  }
  
  
  function checkData($data=0){
    if (!$data) $data = $this->data;
    $elements = $this->getElements();
    foreach ($elements as $element){
      if (!isset($data->$element)) continue;
      $this->checkElement($element, $data->get($element));
    }
    return !$this->error;
  }
  
  function checkElement($elementName, $elementValue){
    if ($this->struct->isRequired($elementName) && !$elementValue){
        if ($this->struct->type($elementName) == 'password' && $this->passwordHasValue[$elementName]) return;
      $this->addError($elementName, _("\"%s\" non può essere vuoto."));
    }
  }
  
  function addError($elementName, $errorText=''){
    $a = & $this->inputs->locate($elementName);
    $pelican = & $a['pel'];
    $el = $a['el'];
    $num = $a['num'];
    $inputs = & $pelican;
    //$inputs->$el->addClass('error')r
    if (!is_array($this->errors)) $this->errors = array();
    if (!is_array($this->elementErrors[$elementName])) $this->elementErrors[$elementName] = array();
    if ($errorText){
        $errorText = sprintf($errorText, $this->struct->label($elementName));
        array_push($this->errors, $errorText);
        array_push($this->elementErrors[$elementName], $errorText);
    }
    $this->error = true;
  }
  
  function hasData(){
    $data = $this->widgetParams->get($this->name);
    if ( !is_array($data) || (sizeof($data) < 2 && $data['id']) ) return false;
    return true;
  }
  
  function storeData($pelican=''){
      global $IMP;
      if (is_array($this->tables)){
          foreach (array_keys($this->tables) as $elementName){
              $deletedIds = $this->tables[$elementName]->deleteElements();
              if (sizeof($deletedIds) > 0) $deleted = true;
          }
          //KLUDGE!! This is needed to avoid stale
          if ($deleted){
              $this->removeAllInputs();
              $this->generateFromStructure();        
          }
      }
      if (is_array($this->subForms)){
          foreach (array_keys($this->subForms) as $element){
              $storedIds = $this->subForms[$element]->storeData();
              if ($storedIds) $stored = true;
          }
          if ($stored){
              $this->removeAllInputs();
              $this->generateFromStructure();
          }
      }
      if (!$pelican) $pelican = $this->buildPelican();
      if ($pelican->id) $this->update = true;
      #if ( !is_array($data) || (sizeof($data) < 2 && $data['id']) ) return false;
      //if we just have the id, there is nothing to store
      if (!$pelican->listSize() || ($pelican->listSize() < 2 && $pelican->id) ) return false;
      //files

      $IMP->debug("Form storing data: ", 5);
      $IMP->debug($pelican);
      $this->checkData($pelican);
      if ($this->error) return false;
      $storer = & $this->getStorer($this->structName);
      if ($this->config['watch'] == 1){
          $storer->watchAll();
      }
      $storer->store($pelican);
      if ($this->config['watch']){
          $this->previousData = $storer->previousData;
      }
      #NOTE: the id in the $pelican, if set, will be interpreted as an update condition
      $this->id = $storer->getId();
      $IMP->debug("Set form id to $this->id", 5);
      $this->widgetParams->clear($this->name);
      $this->widgetParams->set($this->name, 'id', $this->id);
      if (!$this->config['keepData']) $this->widgetParams->clear($this->name);
      $this->stored = $pelican;
      return $this->id;
  }
  
  
  function buildPelican($data=0){
    $pelican = new PHPelican();
    if (!$data) $data = $this->widgetParams->get($this->name);
    if ($data['id']) $pelican->id = $data['id'];
    $this->inputs->reset();
    while ($this->inputs->moveNext()){
      $element = $this->inputs->getName();
      if ($element == 'id') continue;
      if (isset($data[$element])){
        $posted = true;
        break;
      }
    }
    $this->inputs->reset();
    if (!$posted) return $pelican;
    if (is_array($data)) foreach ($data as $key => $value){
      if ($value){
        $haveValue = true;
        break;
      }
    }
//     $echo = /*false && */strpos($_SERVER['HTTP_HOST'],"civilianext")!==false;
    while ($this->inputs->moveNext()){
      $element = $this->inputs->getName();
      $type = $this->struct->type($element);
      if ($this->readOnlyElements[$element]) continue;
      if ($type == 'password' && $data[$element] == '000000') continue;
      if ($type == 'password' && !$data[$element]) continue;
      if ($type == 'password' && $data[$element]) $this->passwordHasValue[$element] = true;
      if ($type == 'file'){
	// controllo se il file esiste già e al caso lo rinomino, PRIMA di fare prepare che lo salva
	if($data[$element]["name"]!="" && file_exists(PATH_WEBDATA.'/'.$data[$element]["name"])) {
	  $counter = 0;
	  while(file_exists(PATH_WEBDATA.'/'.$data[$element]["name"])){
	    $counter++;
	    $exploded = explode(".", $data[$element]["name"]);
	    $exploded[0] = preg_replace("/_[0-9]+$/","",$exploded[0]);
	    $exploded[0] = "{$exploded[0]}_{$counter}";
	    $data[$element]["name"] = implode(".",$exploded);
	  }
	}
      }
      $value = $this->inputs->$element->prepare($data[$element]);
      if ($type == 'file'){
// 	if($echo) {
// 		echo "input type is file<br>";
// 		echo "value is $value<br>";
//                 echo "element is $element<br>";
// 		echo "element saved is ".$data[$element]["saved"]."<br>"; 
//                 echo "element name is ".$data[$element]["name"]."<br>";
//                 echo "element size is ".$data[$element]["size"]."<br>";
//                 echo "element del is ".$data['_'.$element.'_del']."<br>"; 
// 		echo "with getparam it's ".$this->getParam('_'.$element.'_del')."<br>";
// 		echo "file exists ".file_exists(PATH_WEBDATA.'/'.$data[$element]["name"])."<br>";
// 		print_r($data[$element]);
// 		//exit();
// 	}

	$deleteSaved = false;
	if($data[$element]["saved"] != "" && $data[$element]["size"]>0) {
	  $deleteSaved = true;
// 	  if($echo) {echo"deleting saved file because new upload<br>";}
	}
        //if (!$value){
          if ($data['_'.$element.'_del']){
          #if(!$this->getParam('_'.$element.'_del')){
            //unset($value);
	    $value = "";
	    $deleteSaved = true;
// 	    if($echo) {echo "deleting saved file because requested<br>";}
          } else {
	    // delete previous file before uploading new?
	  }
          //else $value = '';
        //}
        //:TODO: delete file through $IMP->files
	if($deleteSaved) {
	  unlink(PATH_WEBDATA.'/'.$data[$element]["saved"]);
	}
      }
      if (isset($value)){
        $pelican->$element = $value;
      }
      //comboboxes
      if ($data['_'.$element.'_mode'] == 'val'){
        $type = $this->struct->type($element);
        $struct = $this->typeSpace->getStruct($type);
        $names = $struct->getNames();
        //print_r($names);
        $name = $names[0];
        $pelican->$element = new PHPelican();
        $pelican->$element->$name = $data[$element];
      }
    }
    if (is_array($this->struct->extend)) foreach (array_keys($this->struct->extend) as $extendElement){
      if (is_array($data['_'.$extendElement])){
        #let the datastorer handle it
        $pelican->{'_'.$extendElement} = $data['_'.$extendElement];
        //why did I add this? it makes deleting N2N elements impossible
        //foreach (array_keys($data['_'.$extendElement]) as $extendId){
        //  $pelican->{$extendElement}[] = $extendId;
        //}
 /*
        foreach (array_keys($data[$extendElement]) as $extendId){
          $extendObj = new PHPelican();
          #it is not an array if it comes from a select
          if (is_array($data['_'.$extendElement][$extendId])){
            $extendObj->id = $extendId;
            foreach ($data['_'.$extendElement][$extendId] as $extension => $extensionValue){
              $extendObj->$extension = $extensionValue;
            }
          }
          $pelican->add($extendElement, $extendObj);
        }*/
      }
      
    }
    if ($haveValue || $data['id']){
      if (is_array($this->fixed)) foreach ($this->fixed as $key => $value){  # array or pelican?
        $pelican->{$key} = $value;
      }
    }
    return $pelican;
  }
  
  function processData(){
    $this->checkData();
    if (!$this->error) $this->storeData();
    if (!$this->error){
      $this->loadData();
      return $this->id;
    }
    return false;
  }
  
  function serialize(){
      if (is_array($this->subForms)) foreach (array_keys($this->subForms) as $element){
        $serialized['subForms'][$element] = $this->subForms[$element]->serialize();
      }
  }
  
  function __sleep(){
      $vars =  array('name', 'structName', 'data', 'subForms', 'config');
      if (!$this->generatedFromStructure) $vars[] = 'inputs';
  }
  
  function __wakeup(){
      
  }
  
  function isNew(){
    if (!$this->update) return true;
    return false;
  }
  
  function checkWritable(){
      global $IMP;
      if ($this->id) $mode = 'u';
      else $mode = 'i';
      if ($IMP->security->checkSuperUser() || $IMP->security->checkPolicy($this->structName, $mode)){
          return true;
      }
      if ($mode == 'i'){
	      if (is_array($IMP->security->structures[$this->structName])){
		      foreach ($IMP->security->structures[$this->structName] as $grant){
			      if ( ($grant['ops']['i'] || $grant['ops']['w']) && !$grant['params'] ){
				      return true;
			      }
		      }
	      }
	}
      $loader = & $IMP->getLoader($this->structName);
      $loader->request('id');
      $loader->addParam('id', $this->id);
      $list = $loader->testMode('u');
      while ($list->moveNext()){
          if ($list->get('id') == $this->id) return true;
      }
      return false;
  }

}

?>

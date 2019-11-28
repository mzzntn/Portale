<?
include_once(BASE.'/widgets/Form/Form.php');

class SearchForm extends Form{
  var $rangeInputs;
  var $queryParams;
  
  function SearchForm($name, $structName=''){
    parent::Form($name, $structName);
    $this->addClass('Form');
    $this->disabledTypes['password'] = true;
    $this->config['hidable'] = true;
    $this->config['keepData'] = true;
    $this->data = $this->widgetParams->getPelican($this->name);

  }

  function setDefaults(){
    if (is_array($this->config['defaults'])) 
    foreach ($this->config['defaults'] as $elementName => $value){
      if (!isset($this->data->$elementName)){
        $this->data->$elementName = $value;
      }
    }
  }

  function generateFromStructure(){
    global $IMP;
    $this->setDefaults();
    $this->id = $this->widgetParams->get($this->name, 'id');
    if ( !$this->security->checkStruct($this->structName, 'read') ) return;
    $this->struct = $this->typeSpace->getStructure($this->structName);
    $params = $this->getParam();
    $elements = $this->getElements();
    if(isset($this->config['forcedElements']) && is_array($this->config['forcedElements'])) {
      $elements = array_merge($elements, $this->config['forcedElements']);
    }
    foreach ($elements as $elementName){
      if ($this->disabled[$elementName]) { $IMP->debug("TEST: element $elementName is disabled", 9); continue; }
      if (is_array($this->elements) && !in_array($elementName, $this->elements) && !is_array($this->elements[$elementName])) { $IMP->debug("TEST: element $elementName wrong format", 9); continue; }
      $readOnly = true;
      if ( !$this->security->checkEl($elementName, $this->structName, 'read') ) { $IMP->debug("TEST: element $elementName disabled by security", 9); continue; }
      $type = $this->struct->type($elementName);
      if ($this->disabledTypes[$type]) { $IMP->debug("TEST: type $type is disabled", 9); continue; }
      $value = $this->data->$elementName;
      if ( $this->typeSpace->isBaseType($type) ){
        $IMP->debug("TEST: type $type for field $elementName is a base type", 9);
        $widgetTypes['text'] = 'TextInput';
        $widgetTypes['longText'] = 'TextInput';
        $widgetTypes['dateTime'] = 'DateTimeInput';
        $widgetTypes['time'] = 'TextInput';
        $widgetTypes['file'] = 'TextInput';
        $widgetTypes['html'] = 'TextInput';
        $widgetTypes['int'] = 'TextInput';
        $widgetTypes['img'] = 'TextInput';
        $widgetTypes['bool'] = 'CheckBoxInput';
        $widgetType = $widgetTypes[$type];
        if (!$widgetType) { $IMP->debug("TEST: type $type doesn't have a widget type", 9); continue;}
        if (!$this->inputs->$elementName){
          $IMP->debug("TEST: creating input for $elementName with type $widgetType", 9);
          $this->createInput($widgetType, $elementName);
//           $test = (array)$this->inputs->$elementName;
//           $IMP->debug("TEST: now element input is ".print_r(array_keys($test),true), 1);
//           $IMP->debug("TEST: <pre>".print_r($this->inputs->$elementName,true)."</pre>", 1);
        }
        $this->addToOrder($elementName);
        if ($type == 'dateTime' || $type == 'int'){
          $this->createRangeInputs($widgetType, $elementName);
        }
        $this->inputs->$elementName->setValue($value);
      }
      else{ #structure
        $IMP->debug("TEST: type $type for field $elementName is a struct", 9);
        $otherStruct = $this->typeSpace->getStructure($type);
        $struct = explode("::", $type);
        $tableName = $struct[1];
        if(isset($this->config['enabledChildStructs']) && !in_array($tableName, $this->config['enabledChildStructs'])){
          $IMP->debug("TEST: struct $type not allowed", 9);
        } else {
          $IMP->debug("TEST: allowed struct $type", 9);
          if (!$otherStruct->isChildOf($this->structName)  && !$this->config[$elementName]['subForm']){
            $this->createInput('SelectInput', $elementName, $type);
            $this->inputs->$elementName->options['multiple'] = true;
            $this->addToOrder($elementName);
            $this->inputs->$elementName->setValue($value);
            $this->inputs->$elementName->generateFromStructure();
          }
          else{
              $this->createInput('SearchForm', $elementName, $type);
              $this->addToOrder($elementName);
              $this->inputs->$elementName->inline = true;
          $this->inputs->$elementName->config = $this->config['inputs'][$elementName];
            if (is_array($this->elements[$elementName])) $this->inputs->$elementName->elements = $this->elements[$elementName];
              $this->inputs->$elementName->setStruct($type);
              $this->inputs->$elementName->setValue($value);
              $this->inputs->$elementName->generateFromStructure();
              //$this->inputs->$elementName->config = $this->config; //FIXME ???
          }
        }
      }
      $this->labels[$elementName] = $this->struct->label($elementName);
    }

    $IMP->debug("Created inputs:", 9);
    $IMP->debug($this->inputs, 9);
  }
  
function generateQuery(){
	$params = new QueryParams($this->structName);
	if ($this->data->_simple){
		$params->setConjunction('or');
		$simple = $this->data->_simple;
		$elements = $this->getElements();
		foreach ($elements as $elementName){
			$type = $this->struct->type($elementName);
			if ($type == 'bool') continue;
			if ($this->typeSpace->isBaseType($type)){
				//$params->set($elementName, $this->data->_simple);
				$value = $this->data->_simple;
				$obj = $this->typeSpace->getObj($type);
				$obj->set($value);
				//array_push($this->dataObjects, & $obj);
				$res = $obj->get();
				if ($res){
					$params->set($elementName, $res);
				}
				if ($this->config[$elementName]['comparison']) $params->setComparison($elementName, $this->config[$elementName]['comparison']);
				elseif ($type == 'text' || $type == 'html' || $type == 'longText'){
					if ($this->config[$elementName]['fulltext']){
						$params->setComparison($elementName, 'fulltext');
					}
					elseif ($this->config[$elementName]['fulltext_boolean']){
						$params->setComparison($elementName, 'fulltext_boolean');
					}
					else $params->setComparison($elementName, 'like');
				}
			}
		}
		return $params;
	}
	foreach ($this->inputsOrder as $inputName){
		if ($this->rangeInputs[$inputName] && ($this->get($inputName.'_1') || $this->get($inputName.'_2'))){
			$params->addRange($inputName, $this->get($inputName.'_1'), $this->get($inputName.'_2'));
			$haveParams = 1;
		}
		elseif ($this->get($inputName)){
			$value = $this->get($inputName);
			if ($this->inputs->$inputName->config['multiple'] && $this->data->isPelican($value) && !$value->hasData()){
				unset($value);
			}
			#print "NAME: $inputName<br>";
			if (is_array($value) && !$this->inputs->$inputName->config['multiple']) continue; #:KLUDGE: otherwise we get empty range inputs
			if ($this->data->isPelican($value)){
				$value->clearEmpty();
				$value = $this->inputs->{$inputName}->generateQuery();
			}
			if ($value == '') continue;
			if ($this->config['prepare'][$inputName]){;
				$value = call_user_func($this->config['prepare'][$inputName], $this, $value);
			}
			$params->set($inputName, $value);
			$haveParams = 1;
			$type = $this->struct->type($inputName);
			if ($this->config[$inputName]['comparison']) $params->setComparison($inputName, $this->config[$inputName]['comparison']);
			else{
				if ($type == 'text' || $type == 'html' || $type == 'longText'){
					$params->setComparison($inputName, 'like');
				}
			}
		}
	}
	$this->queryParams = $params;
	if ($haveParams) return $params;
	return 0;
}
  
  function store(){
    if (!$this->takeParam('save') ||!$this->getParam('saveName')) return;
    $storer = $this->getStorer('_search');
    $storer->set('name', $this->getParam('saveName'));
    $storer->set('struct', $this->structName);
    $storer->set('category', $this->getParam('category'));
    $storer->set('queryParams', $this->queryParams);
    $storer->store();
  }
  
  function createRangeInputs($widgetType, $elementName){
    $this->createInput($widgetType, $elementName.'_1');
    $this->inputs->{$elementName.'_1'}->setValue($this->get($elementName.'_1'));
    $this->createInput($widgetType, $elementName.'_2');
    $this->inputs->{$elementName.'_2'}->setValue($this->get($elementName.'_2'));
    $this->rangeInputs[$elementName] = true;
  }
  
  
  function show(){
  }

  function hide(){ 
  }

}


?>

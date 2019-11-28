<?
class ConditionBuilder_db extends DataManager_db{
  var $typeSpace;
  var $structName;
  var $bindingManager;
  var $security;
  var $struct;
  var $binding;
  var $condition;
  var $newSubCondition;
  var $tables;
  var $id;

  
  /**
  * string getCondition()
  *
  **/
  function getCondition(){
    return $this->condition;
  }

  function startSubCondition(){
    $this->newSubCondition = true;
  }
  
  function endSubCondition(){
    #close, just let it not be empty
    if (!$this->newSubCondition) $this->condition .= ')';
  }

  function getSqlCond($element, $values, $comparison='='){
    global $IMP;
    $IMP->debug("getSqlCond called for $element, $values, $comparison on struct $this->structName", 6);
    $table = $this->binding->table;
    $dbField = $this->binding->dbField($element);
#    $comparison = $this->sqlComparisons[strtolower($comparison)]; :TODO:
    $condition = '';
    if (!$comparison) $comparison = '=';
    if (!$values) $condition .= "{$this->binding->table}.$dbField IS NULL";
    if ($comparison){ #no values and no comparison is condition for null
      if ( !is_array($values) ) $values = array($values);
      foreach ($values as $value){
        $value = $this->prepare($this->struct->type($element), $value, $comparison);
        if ($condition) $condition .= " OR ";
        $condition .= $table.'.'.$dbField.' '.$comparison.' '.$value;
      }
    }
    if ($condition) $condition = '('.$condition.')';
    return $condition;
  }
  
  function addSql($conjunction, $sql){
    if (!$sql) return;
    if ($this->newSubCondition){
      $this->condition .= '(';
      $this->newSubCondition = 0;
    }
    else $this->condition .= " $conjunction ";
    $this->condition .= "($sql)";
  }
  
  function getSecurityCondition($mode){
    global $IMP;
    $read = ($mode == 'r');
    $write = ($mode == 'w');
    if ($IMP->security->checkSuperUser() || 
        $IMP->security->checkPolicy($this->structName, $mode)){ #:TODO: check for 'ir' mode etc.
      return '';
    }
    $pt = $this->binding->permsTable();
    $securityCond = " {$this->binding->table}.{$this->binding->id} = {$pt}.SID AND ";
    $permsCheck = '';
    if ($read) $permsCheck = "{$pt}.R=1";
    if ($write){
      if ($permsCheck) $perms .= " AND ";
      $permsCheck .= "{$pt}.W=1";
    }
    $permsCheck .= " AND ({$pt}.USER={$IMP->security->user}";
    if (is_array($IMP->security->groups)) foreach (array_keys($IMP->security->groups) as $group){
      $permsCheck .= " OR {$pt}.GRP=$group";
    }
    $permsCheck .= ")";
    $securityCond .= $permsCheck;
    $this->tables[$pt] = true;
    $varFile = fixForFile(VARPATH.'/security/inherit/'.$this->structName);
    if (file_exists($varFile)){
      $inherit = unserialize(file_get_contents($varFile));
      foreach (array_keys($inherit) as $structName){
        #$structName = $this->typeSpace->structName($structNum);
        $struct = $this->typeSpace->getStructure($structName);
        $binding = $this->bindingManager->getBinding($structName);
        $elements = $this->struct->getElementsByType($structName);
        if (is_array($elements)) foreach ($elements as $element){
          $this->structId = $this->typeSpace->structId($this->structName);
          if (!$this->structId) continue;
          $join = $this->getJoin($element);
          $securityCond .= " OR ";
          $securityCond .= "(";
          $pt = $binding->permsTable();
          $this->tables[$pt] = true;
          $securityCond .= "{$binding->table}.{$binding->id} = {$pt}.SID AND ";
          $securityCond .= "{$pt}.STRUCT = {$this->structId} AND ";
          if ($join) $securityCond .= $join." AND ";
          $securityCond .= $permsCheck;
          $securityCond .= ")";
        }
      }
    }
    return $securityCond;
  }

  function processParams($params){
    global $IMP;
    $this->init();
    $IMP->debug("ConditionBuilder starting to process params:", 5);
    $IMP->debug($params, 5);
    if (!is_object($params)) return;
    $params->reset();
    if (!$this->structName) $this->structName = $params->_name;
    $this->struct = $this->typeSpace->getStruct($this->structName);
    $conjunction = $this->sqlConjunctions[$params->getConjunction()];
    if (!$conjunction) $conjunction = ' AND';
    while ($params->moveNext()){
      $element = $params->getName();
      $param = $params->get();
      $type = $this->struct->type($element);
      $IMP->debug("Processing element $element of type $type", 5);
      if (!$type){ #it's a foreign param
        $type = $element;
        if ($type == $this->structName){ #:FIXME: doesn't work with multiple recursing elements 
          $element = $this->struct->getLinkingElement($this->structName);
          $param = $param->id; #bleah, this won't work half the times
        }
        else $foreign = true;
      }
      $IMP->debug("ConditionBuilder processing $element, $type, {$params->$element}", 5);
      $condition = '';
      $join = '';
      if (is_object($param)) $params->checkPelican($param);
      if (is_object($param) && ($param->isConjunction() || $param->isList())){
        $param->reset();
        $conj = strtoupper($param->_name);
        while($param->moveNext()){
          $subParam = $param->get();
          if ($params->isPelican($subParam)){
            $subWhereBuilder = $this->getConditionBuilder();
            $subWhereBuilder->processParams($subParam);
            if ($condition) $condition .= " $conj ";
            $condition .= $subWhereBuilder->getCondition();
          }
          else{
            if ($condition) $condition .= " $conj ";
            $condition .= $this->getSqlCond($element, $subParam, $param->getComparison($element)); 
          }
        }
      }
      elseif ($this->binding->dbField($element) && !is_object($param)){ #param must be scalar or array
        $comparison = $params->getComparison($element);
        $condition = $this->getSqlCond($element, $param, $comparison);
      }
      else{
        $IMP->debug("Creating subConditionBuilder for element $element of type $type", 6);
        $subConditionBuilder = $this->getConditionBuilder($type);
        $IMP->debug("Param:", 6);
        $IMP->debug($param, 6);
        if ( (is_array($param) && isset($param[0])) || !is_object($param)){
          $tmpObj->id = $param;
          $param = $tmpObj;
        }
        $params->checkPelican($param);
        $IMP->debug("Param:", 6);
        $IMP->debug($param, 6);
        $subConditionBuilder->processParams($param);
        $IMP->debug("Returning to {$this->structName}", 6);
        $condition = $subConditionBuilder->getCondition();
        $join = '';
        if ($condition){
          if ($foreign){
            $subStruct = $this->typeSpace->getStructure($element);
            $element = $subStruct->getLinkingElement($this->structName);
            $join = $subConditionBuilder->getJoin($element);
          }
          elseif ($element) $join = $this->getJoin($element);
        }
        $this->tables = array_merge($this->tables, $subConditionBuilder->getTables());
      }
      $IMP->debug("Condition: $condition", 5);
      if ($condition){
        $this->tables[$this->binding->table] = true;
        if ($this->condition) $this->condition .= $conjunction;
        $this->condition .= " $condition";
        if ($join) $this->condition .= " AND $join";
      }
    }
   
    $IMP->debug("Condition: $this->condition", 5);
  }
  
  function getJoin($elementName){
    global $IMP;
    $IMP->debug("Starting to get join for $elementName to {$this->structName}", 4);
    $type = $this->struct->type($elementName);
    if (!$type) $type = $elementName;
    if ($this->typeSpace->isBaseType($type)) return ''; #:TODO: join for index
    $dbField = $this->binding->dbField($elementName);
    $struct = $this->typeSpace->getStruct($type);
    $binding = $this->bindingManager->getBinding($type);
    $childElement = $struct->getLinkingElement($this->structName);
    if ($dbField){
      #many to one
      $IMP->debug("Db field $dbField found, many to one", 4);
      if ($this->binding->table != $binding->table)
        $join = "{$this->binding->table}.{$dbField} = {$binding->table}.{$binding->id}";
      else $join = '';
      $this->tables[$this->binding->table] = true;
      $this->tables[$binding->table] = true;
    }
    elseif ( $struct->isChildOf($this->structName) && $binding->dbField($childElement) ){
      #one to many
      $IMP->debug("Childof, one to many", 4);
      $linkElement = $struct->getLinkingElement($this->structName, $elementName);
      if (!$linkElement) error("Unable to find linking element for $structName to {$this->structName} for element $elementName");
      $linkField = $binding->dbField($linkElement);
      if ($binding->table != $this->binding->table)
        $join = "{$binding->table}.{$linkField} = {$this->binding->table}.{$this->binding->id}";
      else $join = '';
      $this->tables[$this->binding->table] = true;
      $this->tables[$binding->table] = true;
    }
    elseif( $this->binding->isN2N($elementName) ){
      #many to many
      $id1 = $this->binding->n2nOwnId($elementName);
      $id2 = $this->binding->n2nForeignId($elementName);
      $n2nTable = $this->binding->n2nTable($elementName);
      $IMP->debug("N2N: $n2nTable, $id1, $id2", 4);
      $join = "{$this->binding->table}.{$this->binding->id} = $n2nTable.$id1";
      $join .= " AND {$binding->table}.{$binding->id} = $n2nTable.$id2";
      $this->tables[$n2nTable] = true;
      $this->tables[$binding->table] = true;
    }
    else error("No valid binding found for element $elementName of type $type while building condition");
    return $join;
  }
  
  function getTables(){
    return $this->tables;
  }
  
  function isBaseType($type){
    if ($type == 'longText' || $type == 'html') return false;
    return $this->typeSpace->isBaseType($type);
  }
  
  function prepare($type, $value, $comparison){
    global $IMP;
    $IMP->debug("CB prepare for $type, $value, $comparison", 7);
    $comparison = strtolower($comparison);
    if (!$this->isBaseType($type)){
      $struct = $this->typeSpace->getStructure($type);
      $type = $struct->type('id');
    }
    switch ($type){
      case 'html':
      case 'longText':
      case 'text':
        $value = str_replace("\\\\", "\\", $value);
        $value = str_replace('\"', '"', $value);
        $value = str_replace ("\\'", "''", $value);
        if ($comparison == 'like') $value = '%'.$value.'%';
        # go on
      case 'dateTime':
      case 'password':
        $value = "'$value'";
        break;
      case 'int':
        $value = intval($value);
        if (!$value) $value = 0;
        break;
      default:
        break;
      
    }
    return $value;
  }
  
}

?>

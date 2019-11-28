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
  var $joined;
  var $parentJoined;
  var $join;

  
  
  /**
  * string getCondition()
  *
  **/
  function getCondition(){
    $sql = $this->condition;
    if ($this->joins){
      if ($sql) $sql = "($sql) AND ($this->joins)";
      else $sql = $this->joins;
    }
    return $sql;
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
      if ($this->struct->parentElements[$element]){
          $struct = $this->struct->getAncestorStruct($element);
          $structName = $struct->name;
          if ($element == 'id_'.$structName) $element = 'id';
          $binding = $this->bindingManager->getBinding($struct->name);
      }
      else{
          $struct = $this->struct;
          $structName = $this->structName;
          $binding =  $this->binding;
      }
      $type = $this->struct->type($element);
      $table = $binding->table;
      $dbField = $binding->dbField($element);
      #    $comparison = $this->sqlComparisons[strtolower($comparison)]; :TODO:
      $condition = '';
      #if (!$values && !$comparison) $condition .= "{$this->binding->table}.$dbField IS NULL";
      if (!$comparison) $comparison = '=';#no values and no comparison is condition for null
      if ( !is_array($values) ) $values = array($values);
      foreach ($values as $value){
          if ($condition){
              if ($comparison == '<>') $condition .= ' AND ';
              else $condition .= " OR "; 
          } 
          if ($value === "NULL"){
              $condition .= $table.'.'.$dbField;
              if ($comparison == '<>'){
                  $nullConj = 'AND';
                  $condition .= ' IS NOT NULL';
              }
              else{
                  $comparison = '=';
                  $nullConj = 'OR';
                  $condition .= ' IS NULL';
              }
              if ($binding->typeIsRendered($type) == 'text') $condition .= " $nullConj {$table}.{$dbField} $comparison ''";
              elseif ($binding->typeIsRendered($type) == 'int') $condition .= " $nullConj {$table}.{$dbField} $comparison 0";
          }
          else{
              $value = $this->prepare($this->struct->type($element), $value, $comparison);
              $field = $table.'.'.$dbField;
              if ($comparison == 'like' || $comparison == ' like ') $field = "UPPER($field)";
              elseif ($this->struct->structure[$element]['parameters']['case_insensitive']){
                  $field = "UPPER($field)";
                  $value = strtoupper($value);
              }
              if ($comparison == 'fulltext'){
                  $condition = "MATCH($field) AGAINST($value)";
              }
	      elseif ($comparison == 'fulltext_boolean'){
	          $condition = "MATCH($field) AGAINST($value IN BOOLEAN MODE)";
	      } 
              else $condition .= $field.' '.$comparison.' '.$value;
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
  
  function getSecurityCondition($mode, $tables = 0){
    global $IMP;
    if (!$tables) $tables = array();
    $IMP->debug("Getting security condition for struct {$this->structName}, mode $mode", 5);
    if ($IMP->security->checkSuperUser() || 
        $IMP->security->checkPolicy($this->structName, $mode)){
        //:TODO: check for ir, iu
      return '';
    }
    if (is_array($IMP->security->structures[$this->structName])){
        foreach ($IMP->security->structures[$this->structName] as $grant){
            if ($mode == 'r' || $mode == 'i'){
                if ($grant['ops'][$mode] ||
                            ( ($mode == 'i'  && ($grant['ops']['w'] || $grant['ops']['iw']) )
                              || ($mode == 'u' && ($grant['ops']['w'] || $grant['ops']['iw'])) ) ){
                    if (sizeof($grant['params']) > 0) foreach($grant['params'] as $param){
                        $cb = new ConditionBuilder_db($this->structName);
                        $cb->processParams($param);
                        if (!$this->tables) $this->tables = array();
                        $this->tables = array_merge($this->tables, $cb->tables);
                        $sql = $cb->getCondition();
                        if ($securityCond) $securityCond .= ' OR ';
                        $securityCond .= "($sql)";
                    }
                    else return '';
                }
            }
            elseif ($mode == 'u'){
                if ($grant['ops']['u'] || $grant['ops']['w']){
                    if (sizeof($grant['params']) > 0) foreach($grant['params'] as $param){
                        $keys = $this->struct->getKeys();
                        $params = new QueryParams();
                        $params->becomeList();
                        $params->setConjunction('or');
                        $loader = & $this->getLoader();
                        $params->checkPelican($param);
                        $loader->setParams($param);
                        foreach ($keys as $key) $loader->request($key);
                        $list = $loader->load();
                        if ($list->listSize() < 1) return '1=0';
                        while ($list->moveNext()){
                            $paramsRow = new QueryParams();
                            foreach ($keys as $key){
                                $paramsRow->addParam($key, $list->get($key));
                            }
                            $params->addRow($paramsRow);
                        }
                        $cb = new ConditionBuilder_db($this->structName);
                        $cb->processParams($params);
                        $sql = $cb->getCondition();
                        if ($securityCond) $securityCond .= ' OR ';
                        $securityCond .= "($sql)";
                    }
                    else return '';
                }
            }
            if ( !$this->binding->isExternal() && $IMP->userId && ( ( $mode == 'r' && ($grant['ops']['ir'] || $grant['ops']['iw']) ) ||
                                   ($mode == 'u' && $grant['ops']['iw'] ) ) ){
              #foreach (array_keys($tables) as $table){
                if ($securityCond) $securityCond .= " OR ";
                $table = $this->binding->table;
                $securityCond .= "{$table}.CR_USER_ID = ".$IMP->userId;
              #}
            }
        }
        // if ($grant['ops'][$mode]
        //            ( ($mode == 'i'  && ($grant['ops']['w'] || $grant['ops']['iw']) )
        //              || ($mode == 'u' && ($grant['ops']['w'] || $grant['ops']['iw'])) ) ){
        //          if (sizeof($grant['params']) > 0) foreach($grant['params'] as $param){
        //            $cb = new ConditionBuilder_db($this->structName);
        //            $cb->processParams($param);
        //            $this->tables = array_merge($this->tables, $cb->tables);
        //            $sql = $cb->getCondition();
        //            if ($securityCond) $securityCond .= ' OR ';
        //            $securityCond .= "($sql)";
        //          }
        //          else if (!($mode == 'u' && $grant['ops']['iw'])){
        //            return '';
        //          }
        //        }
        //        if ( !$this->binding->isExternal() && $IMP->userId && ( ( $mode == 'r' && ($grant['ops']['ir'] || $grant['ops']['iw']) ) ||
        //                               ($mode == 'u' && $grant['ops']['iw'] ) ) ){
        //          #foreach (array_keys($tables) as $table){
        //            if ($securityCond) $securityCond .= " OR ";
        //            $table = $this->binding->table;
        //            $securityCond .= "{$table}.CR_USER_ID = ".$IMP->userId;
        //          #}
        //        }
        //      }
    }
    if (!$securityCond) return '1=0'; //no policy, no grants, no access
    /*:TODO: row level security, has to be debugged
    if ($securityCond) $securityCond = "($securityCond) OR ";
    $pt = $this->binding->permsTable();
    $securityCond .= " {$this->binding->table}.{$this->binding->id} = {$pt}.SID AND ";
    $permsCheck = '';
    if ($read) $permsCheck = "{$pt}.R=1";
    if ($write){
      if ($permsCheck) $perms .= " AND ";
      $permsCheck .= "{$pt}.W=1";
    }
    $permsCheck .= " AND ({$pt}.USER={$IMP->security->userId}";
    if (is_array($IMP->security->groups)) foreach (array_keys($IMP->security->groups) as $group){
      $permsCheck .= " OR {$pt}.GRP=$group";
    }
    $permsCheck .= ")";
    $securityCond .= $permsCheck;
    $this->tables[$pt] = true;
    */
    return $securityCond;
  }

  function processParams($params){
    global $IMP;
    
    $this->init();
    $IMP->debug("ConditionBuilder starting to process params:", 5);
    $IMP->debug($params, 5);
    if (!is_object($params)) return;
    $qp = new QueryParams();
    $qp->makePelican($params);
    #if ($IMP->config['alwaysParams'][$this->structName]){
	#    foreach ($IMP->config['alwaysParams'][$this->structName] as $key => $value){
	#    	$params->add($key, $value);
	#    }
    #}
    $params->reset();
    if (!$this->structName) $this->structName = $params->_name;
    $this->struct = $this->typeSpace->getStruct($this->structName);
    #$conjunction = $this->sqlConjunctions[$params->getConjunction()];
    $conjunction = strtoupper($params->getConjunction());
    if ($IMP->config['alwaysParams'][$this->structName]){
	    foreach ($IMP->config['alwaysParams'][$this->structName] as $key => $value){
	    	$params->set($key, $value);
	    }
    }
    if (!$conjunction) $conjunction = ' AND';
    $params->reset();
    if ($params->isList()){
      while ($params->moveNext()){
        $param = $params->getRow();
        $subConditionBuilder = $this->getConditionBuilder($this->structName);
        $subConditionBuilder->processParams($param);
        #$condition = $subConditionBuilder->condition;
        $condition = $subConditionBuilder->getCondition();
        if ($condition){
          if ($this->condition) $this->condition .= $conjunction;
          $this->condition .= '('.$condition.')';
        }
        if (!is_array($this->tables)) $this->tables = array();
		$this->tables = array_merge($this->tables, $subConditionBuilder->getTables());
      }
    }
    else while ($params->moveNext()){
      $element = $params->getName();
      $param = $params->get();
      $dbField = '';
      if ($params->getAttribute('custom', $element)){
        $this->processCustom($element, $conjunction);
        continue;
      }
      $type = $this->struct->type($element);
      //figure out inline ids to use instead of params
      if ($this->struct->isInline($element) && $param != 'NULL'){
        $loader = & $IMP->getLoader($type);
        $loader->request('id');
        $loader->setParams($param);
        $list = $loader->load();
        $ids = array();
        while ($list->moveNext()){
            $ids[] = $list->get('id');
        }
        $param = $ids;
      }
      if ($this->struct->parentElements[$element]){
        $struct = $this->struct->getAncestorStruct($element);
        $structName = $struct->name;
        $binding = $this->bindingManager->getBinding($struct->name);
        $this->addParentJoin($element);
	      if ($element == 'id_'.$structName){
      	  $type = $struct->type('id');
      	  $dbField = $binding->dbField('id');
      	}
      }
      else{
        $struct = $this->struct;
        $structName = $this->structName;
        $binding =  $this->binding;
      }
      $IMP->debug("Processing element $element of type $type", 5);
      if (!$type && $element != 'params'){ #it's a foreign param
        $type = $element;
        if ($type == $structName){ #:FIXME: doesn't work with multiple recursing elements 
          $element = $struct->getLinkingElement($this->structName);
          $param = $param->id; #bleah, this won't work half the times
        }
        else{
          $isForeign = true;
          $foreignStruct = $this->typeSpace->getStructure($element);
          $foreignElement = $foreignStruct->getLinkingElement($structName);
          $foreignLocal = $struct->getLinkingElement($element);
          $foreignBinding = $this->bindingManager->getBinding($element);
        }
      }
      #$IMP->debug("ConditionBuilder processing $element, $type, $param", 5);
      $condition = '';
      $join = '';
      if (!$dbField) $dbField = $binding->dbField($element);
      if (is_object($param)) $params->checkPelican($param);
      if (is_object($param) && ($param->isConjunction() || $param->isList())){
        $param->reset();
        $conj = strtoupper($param->_name);
        $subCondition = '';
        while($param->moveNext()){
          $subParam = $param->get();
          if ($params->isPelican($subParam)){
            $subWhereBuilder = $this->getConditionBuilder();
            $subWhereBuilder->processParams($subParam);
            if ($subCondition) $subCondition .= " $conj ";
            $subCondition .= '('.$subWhereBuilder->getCondition().')';
          }
          else{
            if ($subCondition) $subCondition .= " $conj ";
            $subCondition .= $this->getSqlCond($element, $subParam, $param->getComparison($element)); 
          }
        }
        if ($subCondition) $condition .= '('.$subCondition.')';
      }
      elseif ($dbField && (!is_object($param) || $param->id) ){
        if (is_object($param)) $param = $param->id;
        $comparison = $params->getComparison($element); #FIXME: WRONG FOR param->id?
        $condition = $this->getSqlCond($element, $param, $comparison);
      }
      elseif ($isForeign && $param->id && $binding->dbField($foreignLocal)){
        $comparison = $params->getComparison('id');
        $condition = $this->getSqlCond($foreignLocal, $param->id, $comparison);
      }
      else{
        if ($param == 'NULL'){
          $nullCond = $this->getNullCondition($element);
          if ($nullCond){
            if ($condition) $condition .= " AND ";
            $condition .= $nullCond;
          }
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
          $IMP->debug("Returning to {$this->structName} condition builder", 6);
          $condition = $subConditionBuilder->getCondition();
          $join = '';
          if ($condition){
            if ($isForeign) $this->addJoin($foreignElement, $type);
            $paramValue = $params->$element;
            $this->addJoin($element);
          }
          $subTables = $subConditionBuilder->getTables();
          if (!$subTables) $subTables = array();
          if (!$this->tables) $this->tables = array();
          $this->tables = array_merge($this->tables, $subTables);
        }
      }
      $IMP->debug("Condition: $condition", 5);
      if ($condition){
        $this->addTable($this->binding->table);
        if ($this->condition) $this->condition .= $conjunction;
        $this->condition .= " $condition";
      }
    }
    $IMP->debug("Condition: $this->condition", 5);
    $IMP->debug("Joins: $this->joins", 5);
  }
  
  function processCustom($condition, $conjunction){
    $processed = $condition;
    if (preg_match_all('/(?:\*(\w+))+/', $condition, $matches)){
      for ($i=0; $i<sizeof($matches[1]); $i++){
        $element = $matches[1][$i];
        $field = $this->getQueryField($element);
        $processed = str_replace('*'.$element, $field, $processed);
      }
    }
    if ($this->condition) $this->condition .= $conjunction;
    $this->condition .= " $processed";
  }
  
  function addParentJoin($elementName, $childStruct=''){
    global $IMP;
    if (!$childStruct) $childStruct = $this->structName;
    $structName = $childStruct;
    $struct = $this->typeSpace->getStruct($structName);
    $binding1 = & $this->bindingManager->getBinding($structName);
    while ($struct->parentElements[$elementName]){
      $parentStructName = $struct->parentElements[$elementName];
      $binding = $this->bindingManager->getBinding($parentStructName);
      if (!$this->parentJoined[$structName][$parentStructName]){
        $id1 = $binding1->parentRefChildId($parentStructName);
        $id2 = $binding1->parentRefParentId($parentStructName);
        $parentRefTable = $binding1->parentRefTable($parentStructName);
        if ($parentJoin) $parentJoin .= " AND ";
        $parentJoin .=  "{$binding1->table}.{$binding1->id} = $parentRefTable.$id1";
        $parentJoin .= " AND {$binding->table}.{$binding->id} = $parentRefTable.$id2";
        $this->parentJoined[$structName][$parentStructName] = true;
        $this->addTable($parentRefTable);
        $this->addTable($binding->table);
      }
      $structName = $parentStructName;
      $struct = $this->typeSpace->getStruct($structName);
      $binding1 = $binding;
      $IMP->debug("Parent join: $parentJoin", 4);
    }
    if ($parentJoin){
      if ($this->joins) $this->joins .= " AND ";
      $this->joins .= $parentJoin;
    }
    #specially crafted for getJoin()
    return array($structName, $struct, $binding1);
  }
  
  function addJoin($element, $structName=''){
    if (!$structName) $structName = $this->structName;
    $struct = $this->typeSpace->getStruct($structName);
    $parts = explode('.', $element);
    foreach($parts as $part){
      $type = $struct->type($part);
      if (!$type || $this->isBaseType($type)) break;
      $join = $this->getJoin($part, $structName);
      if ($join){
        if ($this->joins) $this->joins .= " AND ";
        $this->joins .= $join;
      }
      $structName = $type;
      $struct = $this->typeSpace->getStruct($structName);
    }
  }
  
  
  //:NOTE: naming is somewhat deceptive, getJoin DOES add something to the condition: the tables
  function getJoin($elementName, $structName=''){
    global $IMP;
    if (!$structName) $structName = $this->structName;
    $struct = $this->typeSpace->getStruct($structName);
    $binding1 = $this->bindingManager->getBinding($structName);
    $IMP->debug("Getting join for $elementName to {$structName}", 4);
    if ($struct->parentElements[$elementName]){
      $struct = $struct->getAncestorStruct($elementName);
      $structName = $struct->name;
      $binding1 = $this->bindingManager->getBinding($structName);
    }
    $IMP->debug("Now the struct is $structName", 4);
    $type = $struct->type($elementName);
    if (!$type) $type = $elementName;  //?
    //are inverse joins  (joined[$structName][$type]) the same? too tired to think of it right now
    if (!$type || $this->typeSpace->isBaseType($type) || $this->joined[$type][$structName]){
      return ''; #:TODO: join for index
    }
    $dbField = $binding1->dbField($elementName);
    $struct = $this->typeSpace->getStruct($type);
    $binding = $this->bindingManager->getBinding($type);
    $childElement = $struct->getLinkingElement($structName);
    if ($dbField){
      #many to one
      $IMP->debug("Db field $dbField found, many to one", 4);
      if ($binding1->table != $binding->table)
        $join = "{$binding1->table}.{$dbField} = {$binding->table}.{$binding->id}";
      else $join = '';
      $this->addTable($binding1->table);
      $this->addTable($binding->table);
    }
    elseif ( $struct->isChildOf($structName) && $binding->dbField($childElement) ){
      #one to many
      $IMP->debug("Childof, one to many", 4);
      $linkElement = $struct->getLinkingElement($structName, $elementName);
      if (!$linkElement) error("Unable to find linking element for $type to {$structName} for element $elementName");
      $linkField = $binding->dbField($linkElement);
      if ($binding->table != $this->binding->table)
        $join = "{$binding->table}.{$linkField} = {$binding1->table}.{$binding1->id}";
      else $join = '';
      $this->addTable($binding1->table);
      $this->addTable($binding->table);
    }
    elseif( $binding1->isN2N($elementName) ){
      #many to many
      $id1 = $binding1->n2nOwnId($elementName);
      $id2 = $binding1->n2nForeignId($elementName);
      $n2nTable = $binding1->n2nTable($elementName);
      $IMP->debug("N2N: $n2nTable, $id1, $id2", 4);
      $join = "{$binding1->table}.{$binding1->id} = $n2nTable.$id1";
      $join .= " AND {$binding->table}.{$binding->id} = $n2nTable.$id2";
      $this->addTable($n2nTable);
      $this->addTable($binding->table);
      $this->addTable($binding1->table);
    }
    else error("No valid binding found for element $elementName of type $type while building join");
    return $join;
  }
  
  #FIXME: this is cutandpasted from getCondition. btw has problems with outer joins/subqueries
  #we should move to the (outer) join syntax and eliminate the need for this
  function getNullCondition($elementName, $structName=''){
    global $IMP;
    if (!$structName) $structName = $this->structName;
    $struct = $this->typeSpace->getStruct($structName);
    $binding1 = $this->bindingManager->getBinding($structName);
    $IMP->debug("Getting null condition for $elementName to {$structName}", 4);
    
    if ($struct->parentElements[$elementName]){
      $struct = $struct->getAncestorStruct($elementName);
      $structName = $struct->name;
      $binding1 = $this->bindingManager->getBinding($structName);
    }
    $IMP->debug("Now the struct is $structName", 4);
    $type = $struct->type($elementName);
    if (!$type) $type = $elementName;  //?
    //are inverse joins  (joined[$structName][$type]) the same? too tired to think of it right now
    if (!$type || $this->typeSpace->isBaseType($type) || $this->joined[$type][$structName]){
      return $parentJoin; #:TODO: join for index
    }
    $dbField = $binding1->dbField($elementName);
    $struct = $this->typeSpace->getStruct($type);
    $binding = $this->bindingManager->getBinding($type);
    $childElement = $struct->getLinkingElement($structName);
    if ($dbField){
      #many to one
      $IMP->debug("Db field $dbField found, many to one", 4);
      $cond = "{$binding1->table}.{$dbField} IS NULL";
      if ($binding->typeIsRendered($type) == 'text') $cond .= " OR {$table}.{$dbField} = ''";
      elseif ($binding->typeIsRendered($type) == 'int') $cond .= " OR {$table}.{$dbField} = 0";
    }
    elseif ( $struct->isChildOf($structName) && $binding->dbField($childElement) ){
      #one to many
      $IMP->debug("Childof, one to many", 4);
      $linkElement = $struct->getLinkingElement($structName, $elementName);
      if (!$linkElement) error("Unable to find linking element for $type to {$structName} for element $elementName");
      $linkField = $binding->dbField($linkElement);
      if ($binding->table != $this->binding->table){
        #$cond = "NOT EXISTS( SELECT * FROM {$binding->table} WHERE ";
        #$cond .= "{$binding->table}.{$linkField} = {$binding1->table}.{$binding1->id})";
        $outerJoin = "{$binding1->table} LEFT OUTER JOIN {$binding->table} ";
        $outerJoin .= "ON {$binding->table}.{$linkField} = {$binding1->table}.{$binding1->id}";
        $cond = "{$binding->table}.{$linkField} IS NULL";
        $this->tables[$binding1->table] = false;
        $this->addTable($outerJoin);
      }
    }
    elseif( $binding1->isN2N($elementName) ){
      #many to many
      $id1 = $binding1->n2nOwnId($elementName);
      $id2 = $binding1->n2nForeignId($elementName);
      $n2nTable = $binding1->n2nTable($elementName);
      $IMP->debug("N2N: $n2nTable, $id1, $id2", 4);
      #$cond = "NOT EXISTS( SELECT * FROM $n2nTable WHERE $n2nTable.$id1 = {$binding1->table}.{$binding1->id})";
      $outerJoin = "{$binding1->table} LEFT OUTER JOIN $n2nTable ";
      $outerJoin .= "ON $n2nTable.$id1 = {$binding1->table}.{$binding1->id}";
      $cond = "$n2nTable.$id2 IS NULL";
      $this->tables[$binding1->table] = false;
      $this->addTable($outerJoin);
    }
    else error("No valid binding found for element $elementName of type $type while building null condition");
    return $cond;
  }
  
  #KLUDGE
  function addTable($tableName, $val=true){
    if (isset($this->tables[$tableName]) && $this->tables[$tableName] == false) return;
    //if ($val == true) $val == $tableName;
    $this->tables[$tableName] = $val;
  }
  
  function getTables(){
      if (is_array($this->tables)) return $this->tables;
      return array();
  }
  
  function isBaseType($type){
    #if ($type == 'longText' || $type == 'html') return false;
    return $this->typeSpace->isBaseType($type);
  }
  
  /*
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
  }*/
  
  function prepare($type, $value, $comparison){
    global $IMP;
    $comparison = strtolower($comparison);
    if (!$this->isBaseType($type)){
      $struct = $this->typeSpace->getStructure($type);
      $type = $struct->type('id');
    }
    if ($type=='id') $type='int';
    switch ($type){
      case 'html':
      case 'longText':
      case 'text':
          if ($comparison == 'like'){
		$value = str_replace(' ', '%', $value);
		$value = '%'.strtoupper($value).'%';
          }
          elseif ($comparison == ' like') $value = strtoupper($value);
        break;
      default:
        break;
      
    }
    #$tmp = $IMP->config['dataTypes']['dateTime']['noTimeISO'];
    #$IMP->config['dataTypes']['dateTime']['noTimeISO'] = true;
    $obj = $this->typeSpace->getObj($type);
    $obj->set($value);
    //array_push($this->dataObjects, & $obj);
    $res = $obj->get($this->binding->type);
    #$IMP->config['dataTypes']['dateTime']['noTimeISO'] = $tmp;
    $IMP->debug("The prepared value is $res", 5);
    return $res;
  }

  function getQueryField($element){    
    $parts = explode('.', $element);
    $structName = $this->structName;
    $struct = $this->struct;
    $cnt = 0;
    foreach ($parts as $part){
      $cnt++;
      if ($cnt >= sizeof($parts)) break;
      $structName = $struct->type($part);
      $struct = $this->typeSpace->getStruct($structName);
    }
    if ($structName != $this->structName) $this->conditionBuilder->addJoin($element);
    $binding = $this->bindingManager->getBinding($structName);
    //$part is now the last bit
    if (!$binding->dbField($part)) return '';
    $this->tables[$binding->table] = true;
    return $binding->table.'.'.$binding->dbField($part);
  }
  
}

?>

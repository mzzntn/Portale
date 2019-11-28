<?
include_once(LIBS.'/Data/DataLoader.php');
include_once(LIBS.'/Data/Db/ConditionBuilder_db.php');


/**
* The class that gets data from a database, using the descriptions given in DataStruct
* and in Binding_db
*/    
class DataLoader_db extends DataLoader{
  var $tables;          #-(array[(string)tableName]=true) : additional tables, besides
  # the one given by $this->binding, from where to 'SELECT', as needed
  # by joins
  var $ordered;         #-(array[(string)elementName]=true) : elements we were able to sort
  # ourselves using 'ORDER BY'; others will be sorted by the *ListHolder*
  var $db;
  var $resultRows;
  var $foreignKeys;
  var $ancestorBindings;
  var $ancestorKeys;


  /**
  * string generateSql()
  * Generate the sql based on processed params and requests.
  **/
  function generateSql(){
    global $IMP;
    $IMP->debug("Starting generateSql", 5);
    #    if ($this->loadAll) $sqlFields = '*';
    #    else{
      if ($this->binding->id){
        $sqlFields = $this->binding->table.'.'.$this->binding->id;
        $added[$this->binding->id] = true;
      }
      if ( is_array($this->elements) )
      foreach ( array_keys($this->elements) as $element){
        $IMP->debug("$element is selected", 7);
        $dbField = $this->binding->dbField($element);
        if (!$dbField) continue; #must be context or parent
        if (!$added[$dbField]){
          if ($sqlFields) $sqlFields .= ", ";
          $sqlFields .= "{$this->binding->table}.{$dbField}";
          $added[$dbField] = true;
        }
      }
      if ( is_array($this->foreign) )
      foreach ( $this->foreign as $foreignStruct => $foreignElement){
        $linkElement = $this->struct->getLinkingElement($foreignStruct);
        $linkField = $this->binding->dbField($linkElement);
        if ($foreignElement == 'id' && $linkField){
          if ($sqlFields) $sqlFields .= ", ";
          $sqlFields .= "{$this->binding->table}.{$linkField}";
          $this->foreignKeys['id'] = $linkField;
        }
        else{
          $binding = $this->bindingManager->getBinding($foreignStruct);
          $foreignTable = $binding->table;
          $foreignField = $binding->dbField($foreignElement);
          #$foreignAs = 'K_'.$foreignField.'_'.$foreignTable;
          $this->keyCnt++;
          $foreignAs = 'K_'.$this->keyCnt;
          if ($sqlFields) $sqlFields .= ", ";
          $sqlFields .= "{$foreignTable}.{$foreignField}";
          $sqlFields .= " AS {$foreignAs}";
          $this->tables[$foreignTable] = true;
          $this->foreignKeys[$foreignElement] = $foreignAs;
        }
      }
      $this->ancestorBindings = array();
      if ( is_array($this->parentElements) ){
        foreach (array_keys($this->parentElements) as $parentEl){
          $struct = $this->struct;
          $structName = $this->structName;
          while ($struct->parentElements[$parentEl]){
            $parentStructName = $struct->parentElements[$parentEl];
            $this->conditionBuilder->addParentJoin($parentEl, $structName);
            $struct = $this->typeSpace->getStructure($parentStructName);
            $structName  = $parentStructName;
          }
          $parentBinding =  $this->bindingManager->getBinding($parentStructName);
          $this->ancestorBindings[$parentStructName] =  $parentBinding;
          $dbField = $parentBinding->dbField($parentEl);
          if ($dbField){
            if ($sqlFields) $sqlFields .= ", ";
            $sqlFields .= "{$parentBinding->table}.{$dbField}";
          }
        }
        foreach (array_keys($this->ancestorBindings) as $parentStructName){
          $parentBinding = & $this->ancestorBindings[$parentStructName];
          $this->ancestorKeyCnt++;
          $as = 'P'.$this->ancestorKeyCnt;
          $dbField = $parentBinding->dbField('id').' AS '.$as;
          if ($sqlFields) $sqlFields .= ", ";
          $sqlFields .= "{$parentBinding->table}.{$dbField}";
          $this->ancestorKeys[$parentStructName] = $as;
        }
      }
      if ( is_array($this->contextElements) ){
        $binding = $this->bindingManager->getBinding($this->context['struct']);
        foreach ( array_keys($this->contextElements) as $element){
          $table = $binding->n2nTable($this->context['el']);
          $this->tables[$table] = true;
          $dbField = $binding->getExtendField($this->context['el'], $element);
          if ($sqlFields) $sqlFields .= ", ";
          $sqlFields .= "{$table}.{$dbField}";
        }
      }
      $order = '';
      $orderCnt = 0;
      if ( is_array($this->order) ) foreach ($this->order as $element => $orderType){
          $orderCnt++;
        if ($this->contextElements[$element]){
          $binding = $this->contextBinding;
        }
        if ($element){
          if ($order) $order .= ', ';
          if ($this->contextElements[$element]){
            $dbField = $binding->getExtendField($this->context['el'], $element);
            $table = $binding->n2nTable($this->context['el']);
            $queryField = $table.'.'.$dbField;
          }
          else if ($element == '_random'){
            $queryField = $this->binding->randomOrder();
          }
          else{
            $queryField = $this->getQueryField($element);
          }
          if ($queryField){
            if (!$orderType) $orderType = 'ASC';
            $order .= "$queryField $orderType";
            $this->ordered[$element] = true;
            if ($sqlFields) $sqlFields .= ', ';
            $sqlFields .= $queryField.' AS O'.$orderCnt;
          }
        }
      }
      $strSql = "SELECT ";
      $this->tables[$this->binding->table] = true;
      if ($this->testMode) $mode = $this->testMode;
      else $mode = 'r';
      $securityCond = $this->conditionBuilder->getSecurityCondition($mode, $this->tables);
      $conditionTables = $this->conditionBuilder->getTables();
      if (is_array($conditionTables)) $this->tables = array_merge($this->tables, $conditionTables);

      #if (sizeof($this->tables) > 0) $strSql .= "DISTINCT ";
      //$strSql .= "DISTINCT ";
      $strSql .= "$sqlFields FROM ";
      $fromTables = "";
      foreach( $this->tables as $selectTable => $add){
        if ($add){
          if ($fromTables) $fromTables .= ", ";
          $fromTables .= $selectTable;
        }
      }
      $strSql .= $fromTables;
      if ($this->condition) $condition = $this->condition;  //set with setCondition()
      else $condition = $this->conditionBuilder->getCondition();
      #if ($this->binding->external){
        #  if ($condition) $condition .= " AND ";
        #  $condition .= "{$this->binding->table}.{$this->binding->id} IS NOT NULL";
        #}

        if ($securityCond){
          if ($condition) $condition = "($condition) AND ";
          $condition .= "($securityCond)";
        }
        $this->condition = $condition;
        if ($condition) $strSql .= " WHERE {$condition}";
        if ($order) $strSql .= " ORDER BY $order";
        return $strSql;
      }

      function execute(){
        global $IMP;
        $IMP->debug("Starting dl_db execute", 5);
        $this->db = $this->binding->getDbObject();
        $sql = $this->generateSql();
        $this->db->execute($sql);
        #if ($this->db->isSeekable) 
        $this->resultRows = $this->db->numRows();
        #else{
          #  $this->resultRows = 0;
          #  while ($this->db->fetchrow() && (!$this->config['limit'] || $this->resultRows < $this->start+$this->config['limit'])) $this->resultRows++;
          #  #$this->db->execute($sql); #if the db doesn't have a caching mechanism, well I really
          #don't see why we should bother
          #  $this->db->rewind(); 
          # }                      
        }

        function numResults(){
          return $this->resultRows;
        }

        function fetch($start=1, $end=0){
          global $IMP;
          $IMP->debug("Starting fetch", 6);
          if ($start <= 0) $start = 0;
          $idArray = array();
          if ($this->db->isSeekable()){
            $this->db->moveTo($start);
            $this->dbPos = $start;
          }
          else{
            $this->dbPos = 1;
            while ($this->dbPos < $start && $this->db->fetchrow()) $this->dbPos++;
          }
          $this->data->becomeList();
          if ($this->binding->id) $this->data->uniqueIndexBy('id');
          elseif (sizeof($this->struct->getKeys()) > 0){
            $keyIndex = '';
            foreach ($this->struct->getKeys() as $key){
              if ($keyIndex) $keyIndex .= ',';
              $keyIndex .= $key;
            }
                if ($keyIndex) $this->data->uniqueIndexBy($keyIndex);
          }
          if ($this->context){
            $contextStructName = $this->context['struct'];
            $contextEl = $this->context['el'];
            $contextBinding = $this->bindingManager->getBinding($contextStructName);
            #:ERROR: binding may not be available if the struct is remote
          }
          if (!$end && $this->config['limit']) $end = $start+$this->config['limit'];
          while ( ($lastFetch = $this->db->fetchrow()) && (!$end || $this->dbPos < $end) ){
            $this->dbPos++;
            $IMP->debug("Db fetching row {$this->dbPos}", 6, 'data.loader.db');
            if ($start && $this->dbPos < $start) continue;
            if ($this->fetched[$this->dbPos]) continue;
            unset($row);
            $row = new PHPelican();
            if ($this->binding->id){
              $id = $this->db->result( $this->binding->id );
              $row->set('id', $id);
              $this->lastSeenId = $id;
            }
            else{
                    $id = '';
                    $keys = $this->struct->getKeys();
                    foreach ($keys as $key){
                        $type = $this->struct->type($key);
                        if ($this->binding->dbField($key)) $keyVal = $this->db->result($this->binding->dbField($key));
                        if ($id) $id .= '_';
                        $id .= $keyVal;
                    }
                }
            foreach (array_keys($this->ancestorBindings) as $parentStructName){
              $parentId = $this->db->result($this->ancestorKeys[$parentStructName]);
              $this->ancestorIds[$parentStructName][$id] = $parentId;
              $row->set('id_'.$parentStructName, $parentId);
            }
            if ( is_array($this->elements) ) foreach( array_keys($this->elements) as $elementName){
              $IMP->debug("Fetching $elementName", 7, 'data.loader.db');
              if (!$this->security->checkEl($elementName)) continue; #it's a double (triple?) check
              $dbField = $this->binding->dbField($elementName);
              if (!$dbField && $contextBinding) $dbField = $contextBinding->getExtendField($contextEl, $elementName);
              if (!$dbField && $this->struct->parentElements[$elementName]){
                $ancestorStruct = & $this->struct->getAncestorStruct($elementName);
                $ancestorBinding = & $this->bindingManager->getBinding($ancestorStruct->name);
                $dbField = $ancestorBinding->dbField($elementName);
              }
              if (!$dbField) continue; //uhm... the element shouldn't have been added
              $value = $this->db->result( $dbField );
              $falledBackToDefaultLang = false;
              if (!$value && $this->struct->isMultiLanguage($elementName) && $IMP->config['fallback_to_default_lang']){
                $languageNeutral = $this->struct->getLanguageNeutral($elementName);
                $defaultLangEl = $languageNeutral.'_'.$IMP->config['default_lang'];
                $value = $this->db->result($this->binding->dbField($defaultLangEl));
                $falledBackToDefaultLang = true;
              }
              $value = trim($value);  //:FIXME: temp fix for strange error, could raise problems
              $type = $this->struct->type($elementName);
              if (!$type) $type = $this->contextStruct->getExtendType($contextEl, $elementName);
              if (!$this->typeSpace->isBaseType($type) && $this->requests->$elementName !== 1){
                $this->parentKeys[$elementName][$id] = $value;
              }
              $newVal = $this->decode($type, $value);
              if ($falledBackToDefaultLang){
                $newVal = $IMP->config['lang_not_found_prefix'].$value;
                $newVal .= $IMP->config['lang_not_found_suffix'];
              }
              if ($value != $newVal){
                $row->set($elementName.'_', $value);
              }
              $value = $newVal;
              $row->set($elementName, $value);
            }
            if (is_array($this->foreign) ) foreach ( array_keys($this->foreign) as $foreignStruct ){
              $binding = $this->bindingManager->getBinding($foreignStruct);
              $foreignId = $binding->dbField('id');
              if ($this->foreignKeys['id']) $foreignId = $this->foreignKeys['id']; #MAH?!? RIVEDERE
              if ($foreignId) $row->set(
                '_id_'.$foreignStruct, $this->db->result($foreignId) 
                );
              }
              if (!$id) $id = sizeof($this->rows);
              $this->rows[$id] = $row;

              $this->fetched[$this->dbPos] = true;
              if ($this->security->checkRow($row)){
                $this->data->addRow($this->rows[$id]);
                array_push($idArray, $id);
              }
            }
            if ($lastFetch) $this->hasMore = true;
            $IMP->debug("Finished first level fetch", 7, 'data.loader.db');
            #$IMP->debug($this->data, 7, 'data.loader.db');
            if (sizeof($idArray) > 0) $this->loadSubStructs($idArray);
            $IMP->debug("Finished fetch", 4, 'data.loader.db');
            #$IMP->debug($this->data, 4, 'data.loader.db');
            $this->data->reset();
            if (!$end) $this->db->close();
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

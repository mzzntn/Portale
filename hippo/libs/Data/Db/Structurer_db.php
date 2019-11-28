<?
include_once(LIBS.'/Data/DataStruct.php');
include_once(LIBS.'/Data/TypeSpace.php');
include_once(LIBS.'/Data/BindingManager.php');
include_once(LIBS.'/Data/Db/Binding_db.php');


class Structurer_db{
  var $tableName;
  var $dbFields;
  var $refTables;
  var $dbType;
  var $basePath;
  var $bindingXML;
  var $history;

  function Structurer_db(){
  }
  
  function setPath(){
    list($accessMode, $nameSpace, $localName, $dir) = parseClassName($this->structName);
    $this->init();
    if ($nameSpace == 'base') $this->basePath = BASE;
    else $this->basePath = APPS.'/'.$nameSpace;
  }
  
  function writeBinding($file){
    print "FILE: $file<br>";
    $this->bindingXML->save($file);
  }
  
  function build($generateBinding=true){
    $bindingFile = $this->basePath.'/structs/bindings/'.$this->struct->localName.'-db.xml';
    if ($generateBinding || $bindingFile){
      $this->parseStruct();
      $this->writeBinding($bindingFile);
    }
    else{
      $this->parseBinding();
    }
    $this->fixTable($this->tableName, $this->dbFields);
    if (is_array($this->refTables)) foreach ($this->refTables as $table => $fields){
      $this->fixTable($table, $fields);
    }
  }
  
  function parseBinding(){
    #:TODO:
  }
  
  function parseStruct(){
    global $IMP;
    $structName = $this->structName;
    $IMP->debug("Parsing struct $structName", 4);
    $tableName = strtr($structName, ':', '_');
    $tableName = strtoupper($tableName);
    $this->tableName = $tableName;
    $xmlDoc = new DOMDocument();
    $xmlDoc->loadXML('<?xml version="1.0" encoding="iso-8859-1" ?><binding type="db"></binding>');
    $node = $xmlDoc->createElement('binding');
    $xmlRoot = $xmlDoc->documentElement;
    $node = $xmlDoc->createElement('table');
    $node->appendChild($xmlDoc->createTextNode($tableName));
    $xmlRoot->appendChild($node);
    $struct = & $this->typeSpace->getStructure($structName);
    $this->struct = $struct;
    $this->clearFields();
    $this->addField('ID', 'INT');
    $this->addField('CR_DATE', 'VARCHAR', 50);
    $this->addField('MOD_DATE', 'VARCHAR', 50);
    $this->addField('CR_USER_ID', 'INT');
    $this->addField('MOD_USER_ID', 'INT');
    $this->addField('PERMS', 'VARCHAR', 50);
    $elements = $struct->getElements();
    foreach ($elements as $element){
      $IMP->debug("Parsing element $element", 4);
      $encodedName = $struct->getParameter($element, 'encodedName');
      $type = $struct->type($element);
      $parameters = $struct->getParameters($element);
      if ( $this->typeSpace->isBaseType($type) ){
        $dbField = $this->mapElement($element, $type, $parameters);
        $node = $xmlDoc->createElement('bind');
        $node->setAttribute('element', $encodedName);
        $node->setAttribute('dbField', $dbField);
        $xmlRoot->appendChild($node);
      }
      else{
        $otherStruct = $this->typeSpace->getStructure($type);
        if ( $struct->isChildOf($type) ){
          print "CHILD DI $element<br>";
          $IMP->debug("{$struct->name} appears to be a child of $type", 4);
          #just add a key for the ID of the foreign structure
          $dbField = 'ID_'.strtoupper($element);
          $this->addField($dbField, 'INT');
          $node = $xmlDoc->createElement('bind');
          $node->setAttribute('element', $element);
          $node->setAttribute('dbField', $this->fixForDb($dbField));
          $xmlRoot->appendChild($node);
        }
        elseif( $otherStruct->isChildOf($structName) ){
          print "$element E'CHILD<br>";
          #do nothing on the db: we will take care of it when dealing with the substruct
          #:TODO: we could add some info about the linking field in $otherStruct to the
          #binding xml. But than again, we don't have more info now than later.
        }
        else{
          #default to a mmbind
          print "MM $element<br>";
          $IMP->debug("m2m for {$struct->name} and $type", 4);
          $localField = 'ID_'.$tableName;
          $typeDbName = strtr($type, ':', '_');
          $typeDbName = strtoupper($typeDbName);
          $remoteField = 'ID_'.$typeDbName;
          $mmTable = $tableName.'_REF_'.$typeDbName;
          $mmFields = array();
          $mmFields['ID']['type'] = 'INT';
          $mmFields[$localField]['type'] = 'INT';
          $mmFields[$remoteField]['type'] = 'INT';
          $this->refTables[$mmTable] = $mmFields;
          $node = $xmlDoc->createElement('mmbind');
          $node->setAttribute('element', $element);
          $node->setAttribute('table', $mmTable);
          $node->setAttribute('local_id', $localField);
          $node->setAttribute('remote_id', $remoteField);
          $xmlRoot->appendChild($node);
        }
      }  
    }
    $IMP->debug($xmlDoc->saveXML(), 4);
    $this->bindingXML = $xmlDoc;
  }

  function addField($name, $sqlType, $sqlSize=0){
    $this->dbFields[$name] = array('type' => $sqlType, 'size' => $sqlSize);
  }

  function clearFields(){
    $this->dbFields = array();
  }
  
  function getDbType($fieldDesc){
    $dbType = $fieldDesc['type'];
    if ($fieldDesc['size']) $dbType .= '('.$fieldDesc['size'].')';
    return $dbType;
  }

  function fixTable($tableName, $fields){
    global $IMP;
    $restoreDebug = $IMP->debugLevel;
    $IMP->debugLevel = 2;
    $binding = $this->bindingManager->getBinding($this->structName);
    $this->db = $binding->getDbObject();
    $table = $this->db->describeTable($tableName);
    $IMP->debug("TABLE $tableName:", 2);
    if ($table) $IMP->debug($table, 2);
    else $IMP->debug('Not found', 2);
    if ($table){
      foreach ($fields as $name => $fieldDesc){
        $dbType = $this->getDbType($fieldDesc);
        if (!$table[$name]){
          $sql = "ALTER TABLE $tableName ADD $name $dbType";
          $IMP->debug($sql, 2);
          $this->db->execute($sql);
        }
        elseif ($table[$name]['type'] != $fieldDesc['type'] || $table[$name]['size'] != $fieldDesc['size']){
          $sql = "ALTER TABLE $tableName MODIFY $name $dbType";
          $IMP->debug($sql, 2);
          $this->db->execute($sql);
        }
      }
      if ($this->params['deleteUnused']){
        foreach ( array_keys($table) as $fieldName ){
          if ( !$fields[$fieldName] ){
            $sql = "ALTER TABLE $tableName DROP $fieldName";
            $IMP->debug($sql, 2);
            $this->db->execute($sql);
          }
        }
      }
    }
    else{
      $sql = "CREATE TABLE $tableName ";
      foreach ($fields as $name => $fieldDesc){
        $dbType = $this->getDbType($fieldDesc);
        if ($fieldsSql) $fieldsSql .= ', ';
        $fieldsSql .= "$name $dbType";
      }
      $sql .= "($fieldsSql)";
      $IMP->debug($sql, 2);
      $this->db->execute($sql);
    }
    $IMP->debugLevel = $restoreDebug;
  }
  
  
  function mapElement($element, $type, $parameters=0){
    switch ($type){
      case 'text':
      case 'password':
      case 'email':
      case 'img':
      case 'file':
        $sqlType = 'VARCHAR';
        $sqlSize = 50;
        break;
      case 'longText':
      case 'html':
        $sqlType = 'TEXT';
        break;
      case 'int':
      case 'bool':
        $sqlType = 'INT';
        break;
      case 'dateTime':
        $sqlType = 'CHAR';
        $sqlSize = 19;
        break;
    }
    $dbField = $this->fixForDb($element);
    $this->addField($dbField, $sqlType, $sqlSize);
    return $dbField;
  }
  
  function fixForDb($string){
    $string =  strtoupper(remove_accents($string));
    return $string;
  }
  
}



?>
<?
include_once(LIBS.'/Data/Db/Db.php');
include_once(LIBS.'/Data/Db/Db_mysql.php');
include_once(LIBS.'/Data/Db/Db_oracle.php');
include_once(LIBS.'/Data/Db/Db_pgsql.php');

/**
  The class describing the binding of a structure to a database.
  Loads from an xml file provided by the *BindingManager*; see the virtual base class
  *Binding* for discussion.
  
  XML Syntax:
  
  <binding type='db'>
  <engine src='C:\internet\apache\htdocs\nbt\configuration\db.xml' />     # external info
                                          #about the database: see below for its syntax
  <table>...</table>                      #the table associated to the structure
  <bind element='...' dbField='...' />    #associate an element to a db field in the table
  ...
  <mmbind element='...' table='...' local_id='...' remote_id='...' />     # a "many-to-many"
                                          #binding: cross-references between the struct and
                                          #another are stored in the table specified, having
                                          #the db field 'local_id' as index of this struct
                                          #and 'remote_id' for the other.
  </binding>
  
*/
class Binding_db{
  var $type;
  var $structName;
# info about the physical world around us
  var $dbType;              #-(string) : the brand of db being used
  var $dbConnection;        #-(string) : the way of accessing the db: depending on
                            # the system and $this->dbType, can be odbc or direct connect
  var $dbEngineParams;      #-(array[(string)param]=(string)value) : extra params to use
                            # while dealing with that particular db
  var $dbConnectionParams;  #-(array[(string)param]=(string)value) : extra params to use
                            # while connecting
# about the db table
  var $id;                  #-(string) : the field storing the 'id' element
  var $table;               #-(string) : name of the db table
  var $fields;              #-(array[(string)elementName]=(string)dbFieldName
  var $mmbind;              #-(array[(string)elementName]=(array[(string)param]=(string)value)mmBindInfo)
                            # stores info about a mmbind
	var $parentRef;
  var $mmExt;
  var $internalFields;      #-(array[(string)elementName]=(string)dbFieldName :
                            # db representation of elements needed by the system
                            # (they are internals in *DataStruct*)
 	var $external;
  var $localized;           #elements that do not have a binding on their own, but
                            #rather for the different language versions
  
  
  /**
    Constructor
  */
  function Binding_db(){
    $this->type = 'db';
    $this->internalFields['cr_date'] = 'CR_DATE';
    $this->internalFields['mod_date'] = 'MOD_DATE';
    $this->internalFields['cr_user'] = 'CR_USER_ID';
    $this->internalFields['mod_user'] = 'MOD_USER_ID';
    $this->internalFileds['perms'] = 'PERMS';
  }

  
#
# /* Queries to the obj */
#

  /**
    @string
    Returns the dbField of element (string)$name
  */  
  function dbField($name){
    if ($name == 'id') return $this->id;
    if ($this->internalFields[$name] && !$this->isExternal()) return $this->internalFields[$name];
    if ($this->localized[$name]) $name = $this->getLocalized($name);
    return $this->fields[$name];
  }
  
  function hasField($name){
    return $this->dbField($name);
  }
  
  function getExtendField($element, $extElement){
    if ($this->localized[$extElement]) $extElement = $this->getLocalized($extElement);
    return $this->mmExt[$element][$extElement];
  }
  
  function isExternal(){
    return $this->external;
  }
  
  /**
    @bool
    Has (string)$elementName got a many-to-many binding?
  */
  function isN2N($elementName){
    if (is_array($this->mmbind[$elementName])) return 1;
    return 0;
  }
  
  /**
    @string
    The cross-reference db table used in the mm binding, if any
  */
  function n2nTable($elementName){
    return $this->mmbind[$elementName]['table'];
  }
  
  function permsTable(){
    return 'P_'.$this->table;
  }
  
   /**
    @string
    The db field storing id of this struct in the cross-reference mm binding table, if any
  */
  function n2nOwnId($elementName){
    return $this->mmbind[$elementName]['local_id'];
  }
  
  /**
    @string
    The db field storing id of the other struct, the one given by the element type
    in the cross-reference mm binding table (if any)
  */
  function n2nForeignId($elementName){
    return $this->mmbind[$elementName]['remote_id'];
  }
	
	function parentRefTable($elementName){
		return $this->parentRef[$elementName]['table'];
	}
	
	function parentRefChildId($elementName){
		return $this->parentRef[$elementName]['child_id'];
	}
	
	function parentRefParentId($elementName){
		return $this->parentRef[$elementName]['parent_id'];
	}
  
  /**
    @void
    Try to figure out what db to use and how to connect to it.
    If a $this->dbType was not specified, get one from $CONFIGURATION.
    If no $this->dbConnection was specified, get a default based on the db used and the
    system.
    Then, if no $this->dbConnectionParams were given (in the xml or by code), get them
    from $CONFIGURATION
  */
  function assignDb(){
    global $IMP;
    
    list($accessMode, $nameSpace, $localName, $dir) = parseClassName($this->structName);
    if ($IMP->bindingManager->overrideEngine[$this->structName]){
        $override = $IMP->bindingManager->overrideEngine[$this->structName];
    } 
    elseif ($IMP->bindingManager->overrideEngine[$nameSpace]){
        $override = $IMP->bindingManager->overrideEngine[$nameSpace];
    } 
    if ($override && !is_array($override)){
        $node = $this->loadExternalXmlNode('engine', CONFIG.'/db/'.$override);
        $this->loadEngine($node);
    }
    if (is_array($override)){
        $this->dbType = $override['dbType'];
    }
    if (!$this->dbType) $this->dbType = $IMP->defaults['dbType'];
    if (!$this->dbType) $this->dbType = 'mysql';
    if (!$this->dbConnection){
      if ($this->dbType == 'msaccess'){
        $this->dbConnection = 'odbc';
      }
      elseif ($this->dbType == 'sqlite'){
        $this->dbConnection = 'sqlite';
      }
      elseif ($this->dbType == 'mysql'){
        $this->dbConnection = 'mysql';
      }
      elseif ($this->dbType == 'oracle'){
      	$this->dbConnection = 'oracle';
      }
      elseif ($this->dbType == 'mssql'){
        $this->dbConnection = 'mssql';
      }
      elseif ($this->dbType == 'pgsql'){
        $this->dbConnection = 'pgsql';
      }
    }
    if (!$this->dbConnectionParams){
      $this->dbConnectionParams = $IMP->defaults['db'];
 #print_r( $this->dbConnectionParams);
    }
  }   
  

 
#  
# /* Creators and tools */ #
#
  
  /**
    @int
    Get an incremental number for $tableName table, or for this table if none given
    @(string)$tableName
  */
  function assignId($tableName=''){
    global $IMP;
    if (!$tableName) $tableName = $this->table;
    return $IMP->getIndex($tableName,$this->structName);
  }
 
  /**
    @Db
    Get an appropriate object able to talk via SQL to the database
  */
  function getDbObject(){
    global $IMP;
    if ($IMP->config['fakeDb']) return new Db_fake();
    $this->assignDb();
    if ($this->dbConnection == 'odbc'){
      $db = new Db_odbc(0, $this->dbConnectionParams['dsn'], $this->dbConnectionParams['user'], $this->dbConnectionParams['pass']);
    }
    elseif ($this->dbConnection == 'sqlite'){
      $db = new Db_sqlite(0, $this->dbConnectionParams['dbFile']);
    }
    elseif ($this->dbConnection == 'mysql'){
      $db = new Db_mysql(0, $this->dbConnectionParams['name'], $this->dbConnectionParams['user'], $this->dbConnectionParams['pass'], $this->dbConnectionParams['server']);
    }
    elseif ($this->dbConnection == 'oracle'){
    	$db = new Db_oracle(0, $this->dbConnectionParams['name'], $this->dbConnectionParams['user'], $this->dbConnectionParams['pass'], $this->dbConnectionParams['charset']);
    }
    elseif ($this->dbConnection == 'mssql'){
      $db = new Db_mssql(0, $this->dbConnectionParams['name'], $this->dbConnectionParams['user'], $this->dbConnectionParams['pass']);
    }
    elseif ($this->dbConnection == 'pgsql'){
      $db = new Db_pgsql(0, $this->dbConnectionParams['name'], $this->dbConnectionParams['user'], $this->dbConnectionParams['pass'], $this->dbConnectionParams['server'], $this->dbConnectionParams['charset']);
    }
    return $db;
  }
  
  function & getLoader(){
    $loader = new DataLoader_db($this->structName);
    $loader->binding = & $this;
    return $loader;
  }
  
  function & getStorer(){
    $storer = new DataStorer_db($this->structName);
    $storer->binding = & $this;
    return $storer;
  }
  
  function & getConditionBuilder(){
    return new ConditionBuilder_db($this->structName);
  }
  
  function & getDeleter(){
    return new DataDeleter_db($this->structName);
  }
	
#
# /* Dealing with xml */ #
#

  /**
  *  DomNode loadExternalXmlNode(string, string);
  *  Get the DomXML representation of an xml file
  *
  *  Returns a DomNode object of an xml file $file
  *  $nodeName provides the desired root node
  **/
  function loadExternalXmlNode($nodeName, $file){
    #:TODO: have full tree in the external file?
    $dom = new DOMDocument();
    $loaded = $dom->load($file);
    if (!$loaded) error("unable to open binding file $file for $this->structName");
    $root = $dom->documentElement;
    if ($root->nodeName != $nodeName){
      $children = $root->childNodes;
      foreach($children as $child){
        if ($child->nodeName == $nodeName){
          $node = $child;
          break;
        }
      }
    }
    else{
      $node = $root;
    }
    return $node;
  }
  
  /**
    @DomNode
    if (DomNode)$node has a src attribute, find a node by this name in the file specified
    and load it calling $this->loadExternalXmlNode(...)
  */
  function loadXmlNode($node){
    $src = $node->getAttribute('src');
    $nodeName = $node->nodeName;
    if ($src){
      //if ($src[0] == '/' || $src[0] == '\\') $src = HIPPO.$src;
      $src = str_replace('CONFIG', CONFIG.'/db', $src);
      $node = $this->loadExternalXmlNode($nodeName, $src);
    }
    return $node;
  }
  
  function loadEngine($node){
      $node = $this->loadXmlNode($node);
      $this->dbType = $node->get_attribute('dbType');
      $childrenL2 = $node->childNodes;
      foreach ($childrenL2 as $childL2){
          $childL2Name = $childL2->node_name();
          if ($childL2Name == 'connection'){
              $childL2 = $this->loadXmlNode($childL2);
              $this->dbConnection = $childL2->get_attribute('type');
              //deprecated
              if (!$this->dbConnection) $this->dbConnection = $childL2->get_attribute('name');
              $childrenL3 = $childL2->childNodes;
              foreach ($childrenL3 as $childL3){
                  $this->dbConnectionParams[$childL3->node_name()] = $childL3->get_content();
              }
          }
      }
  }

  /**
  @void
  Parses (string)$file and puts info in arrays
  **/
  function load($file){
  	$dom = new DOMDocument();
  	$loaded = $dom->load($file);
  	if (!$loaded) error("unable to open binding file $file for {$this->structName}");
  	$root = $dom->documentElement;
  	if ($root->nodeName != 'binding') error("File $file does not start with <binding> tag");
  	$bindType = $root->getAttribute('type');
  	if ($bindType != 'db') 
  		error("File $file is not of the correct type ($bindType instead of db");
  	$this->name = $root->getAttribute('name');
  	$external = $root->getAttribute('external');
  	if ($external) $this->external = 1;
  	$children = $root->childNodes;
  	foreach ($children as $child){
  		$childName = $child->nodeName;
  		if ($childName == 'table'){
  			$this->table = $child->textContent;
  		}
  		elseif ($childName == 'engine'){
  			$child = $this->loadXmlNode($child);
  			$this->dbType = $child->getAttribute('dbType');
  			$childrenL2 = $child->childNodes;
  			foreach ($childrenL2 as $childL2){
  				$childL2Name = $childL2->nodeName;
  				if ($childL2Name == 'connection'){
  					$childL2 = $this->loadXmlNode($childL2);
  					$this->dbConnection = $childL2->getAttribute('type');
  					//deprecated
  					if (!$this->dbConnection) $this->dbConnection = $childL2->getAttribute('name');
  					$childrenL3 = $childL2->childNodes;
  					foreach ($childrenL3 as $childL3){
  						$this->dbConnectionParams[$childL3->nodeName] = $childL3->textContent;
  					}
  				}
  			}
  		}
  		elseif ($childName == 'bind'){
  			$name = utf8_decode($child->getAttribute('element'));
  			$language = $child->getAttribute('language');
  			if ($language) $name = $this->getLocalized($name, $language);
  			$dbField = $child->getAttribute('dbField');
  			if (strtolower($name) == 'id') $this->id = $dbField;
  			elseif ($name && $dbField){
  				$this->fields[$name] = $dbField;
  			}
  		}
  		elseif ($childName == 'mmbind'){
  			$name = $child->getAttribute('element');
  			$table = $child->getAttribute('table');
  			$local_id = $child->getAttribute('local_id');
  			$remote_id = $child->getAttribute('remote_id');
  			$this->mmbind[$name]['table'] = $table;
  			$this->mmbind[$name]['local_id'] = $local_id;
  			$this->mmbind[$name]['remote_id'] = $remote_id;
  			$childrenL2 = $child->childNodes;
  			foreach ($childrenL2 as $childL2){
  				$childL2Name = $childL2->nodeName;
  				if ($childL2Name == 'bind'){
  					$childName = utf8_decode($childL2->getAttribute('element'));
  					$childLanguage = $childL2->getAttribute('language');
  					//is it correct to put localized in this obj's array?
  					if ($childLanguage) $childName = $this->getLocalized($childName, $childLanguage);
  					$dbField = $childL2->getAttribute('dbField');
  					if ($childName && $dbField){
  						$this->mmExt[$name][$childName] = $dbField;
  					}
  				}
  			}
  		}
  		elseif ($childName == 'parentRef'){
  			$struct = $child->getAttribute('struct');
  			$this->parentRef[$struct]['table'] = $child->getAttribute('table');
  			$this->parentRef[$struct]['parent_id'] = $child->getAttribute('parent_id');
  			$this->parentRef[$struct]['child_id'] = $child->getAttribute('child_id');
  		}
  	}
  	if (!$this->id && !$this->isExternal()) $this->id = 'ID';
  	if ($this->dbType == 'oracle') $this->table = $this->dbConnectionParams['user'].'.'.$this->table; 
  	$this->assignDb();
  }
  
  function typeIsRendered($type){
    switch ($type){
      case 'text':
      case 'longText':
      case 'dateTime':
      case 'time':
      case 'password':
        return 'text';
        break;
      case 'int':
      case 'bool':
        return 'int';
      default:
        return 'int';
    }
      //:FIXME: fill others OR change datatype handling
  }
  
  function randomOrder(){
    if ($this->dbType == 'mysql') return 'RAND()';
    else return '';
  }

  function currentLanguage(){
    //:FIXME: configuration should probably be passed to the binding, so that it
    //can be changed on the instance; and the default should be moved to $IMP
    global $IMP;
    if ($IMP->language) return $IMP->language;
    return 'it';
  }

  function getLocalized($elementName, $language=''){
    if ($language) $this->localized[$elementName] = true;
    if (!$language) $language = $this->currentLanguage();
    return $elementName.'_'.$language;
  }
  

}
?>

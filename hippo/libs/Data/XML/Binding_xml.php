<?
include_once(LIBS.'/Data/Db/Db.php');
include_once(LIBS.'/Data/Db/Db_mysql.php');
include_once(LIBS.'/Data/Db/Db_oracle.php');

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
class Binding_xml{
  var $type;
  var $structName;
  var $fileName;
  
  
  /**
    Constructor
  */
  function Binding_xml(){
    $this->type = 'xml';
  }
  
  function hasField($name){
    #:TODO:
    return true;
  }

  
#
# /* Queries to the obj */
#
  function getTag($name){
    if ($this->tags[$name]) return $this->tags[$name];
    else return $name;
  }
  
  function getFileName(){
    return $this->fileName;
  }
  
  function getArray(){
  }

  function storeArray(){
  }
  
 

 
#  
# /* Creators and tools */ #
#
  
  /**
    @int
    Get an incremental number for $tableName table, or for this table if none given
    @(string)$tableName
  */
  function assignId($name=''){
    global $IMP;
    if (!$name) $name = $this->fileName;
    return $IMP->getIndex($name);
  }
 
  
  function & getLoader(){
    return new DataLoader_xml($this->structName);
  }
  
  function & getStorer(){
    $storer = new DataStorer_xml($this->structName);
    $storer->binding = $this;
    return $storer;
  }
  
  function & getConditionBuilder(){
    return new ConditionBuilder_xml($this->structName);
  }
  
  function & getDeleter(){
    return new DataDeleter_xml($this->structName);
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
      $node = $this->loadExternalXmlNode($nodeName, $src);
    }
    return $node;
  }

  /**
    @void
    Parses (string)$file and puts info in arrays
  */
  function load($file){
    $dom = new DOMDocument();
    $loaded = $dom->load($file);
    if (!$loaded) error("unable to open binding file $file for {$this->structName}");
    $root = $dom->documentElement;
    if ($root->nodeName != 'binding') error("File $file does not start with <binding> tag");
    $bindType = $root->getAttribute('type');
    if ($bindType != 'xml') 
      error("File $file is not of the correct type ($bindType instead of xml");
    $this->name = $root->getAttribute('name');
    $external = $root->getAttribute('external');
    if ($external) $this->external = 1;
    $children = $root->childNodes;
    foreach ($children as $child){
      $childName = $child->nodeName;
      if ($childName == 'file'){
        $this->fileName = $child->textContent;
      }
      elseif ($childName == 'bind'){
        $name = utf8_decode($child->getAttribute('element'));
        $tag = $child->getAttribute('tag');
        if (strtolower($name) == 'id') $this->id = $tag;
        elseif ($name && $tag){
          $this->tags[$name] = $tag;
        }
      }
    }
    if (!$this->id) $this->id = 'ID';
  }
  
  function isExternal(){ #called in dataLoader. no good.
  }
  

}


?>

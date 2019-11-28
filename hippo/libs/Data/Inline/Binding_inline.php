<?

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
class Binding_inline{
  var $type;
  var $structName;
  
  /**
    Constructor
  */
  function Binding_inline(){
    $this->type = 'inline';
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
    return $IMP->getIndex($tableName);
  }
 
  
  function & getLoader(){
    return new DataLoader_inline($this->structName);
  }
  
  function & getStorer(){
    $storer = new DataStorer_inline($this->structName);
    $storer->binding = $this;
    return $storer;
  }
  
  function & getConditionBuilder(){
    return new ConditionBuilder_inline($this->structName);
  }
  
  function & getDeleter(){
    return new DataDeleter_inline($this->structName);
  }
  
  function isExternal(){
      return true;
  }

}
?>

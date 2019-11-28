<?

class Cache{

  /**
  * mixed load(string)
  * Get an object from the cache
  *
  * @return the object
  **/
  function load($name){
    $s = 0;
    $s = @implode("", @file(VARPATH.'/'.$name));
    return unserialize($s);
  }
  
  /**
  * void store(string, $mixed)
  * Store an object in the cache
  **/
  function store($name, & $object){
    $s = serialize($object);
    #:TODO: lock
    $fp = fopen(VARPATH.'/'.$name, 'w');
    fputs($fp, $s);
    fclose($fp);
  }


}


?>
<?

class IMPTest{
  var $name;
  var $group;
  var $depends;

  function IMPTest($name){
    $this->name = $name;
    $this->depends = array();
  }
  
  function addDependency($name){
    array_push($this->depends, $name);
  }


}


?>
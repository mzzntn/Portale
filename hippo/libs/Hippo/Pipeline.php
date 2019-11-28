<?

class Pipeline{
  var $name;
  var $step;
  var $steps;
  var $stepNames;
  var $finished;

  function Pipeline($name){
    $this->name = $name;
    $this->step = 0; #first step will be 1
    $this->steps = array();
  }
  
  function move(){
    $this->step++;
    $this->steps[$this->step] = array();
  }
  
  function step($name){
    if ($this->stepNames[$this->step] == $name){
      $this->resetStep();
    }
    else{
      $this->move();
      $this->stepNames[$this->step] = $name;
    }
  }
  
  function addFile($file){
    if ($this->steps[$this->step]) $this->undo($this->steps[$this->step]);
    $action['type'] = 'file';
    $action['file'] = $file;
    array_push($this->steps[$this->step], $action);
  }
  
  function rollback($to=0){
    foreach (array_reverse($this->steps) as $step => $actions){
      if ($step <= $to) break;
      foreach ($actions as $action) $this->undo($action);
    }
  }
  
  function resetStep(){
    foreach ($this->steps[$this->step] as $action){
      $this->undo($action);
    }
    $this->steps[$this->step] = array();
  }
  
  function undo($action){
    if ($action['type'] == 'file'){
      @ unlink($action['file']);
    }
  }
  
  function finish(){
    $this->steps = array();
    $this->finished = true;
  }
  


}


?>
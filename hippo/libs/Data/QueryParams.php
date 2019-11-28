<?
  
class QueryParams extends PHPelican{

  function isConjunction(){
    $name = strtolower($this->_name);
    if ($name == 'and' || $name == 'or') return true;
    return false;
  }
  
  function getConjunction(){
    $conj = $this->getAttribute('conj');
    if (!$conj) return 'and';
    return $conj;
  }
  
  function addParam($element, $value, $comparison=''){
      $this->set($element, $value);
      if ($comparison) $this->setComparison($element, $comparison);
  }
  
  function getComparison($element){
    return $this->getAttribute('comp', $element);
  }
  
  function setComparison($element, $comparison){
    $this->setAttribute('comp', $comparison, $element);
  }
  
  function setConjunction($conj){
    $this->setAttribute('conj', $conj);
  }
  
  function addRange($element, $rangeStart, $rangeEnd, $conjunction='and'){
    $param = new QueryParams($conjunction);
    $param->_list = $element;
    if ($rangeStart){
      $param->add($element, $rangeStart);
      $param->setComparison($element, '>=');
    }
    if ($rangeEnd){
      $param->add($element, $rangeEnd);
      $param->setComparison($element, '<=');
    }
    $this->$element = $param;
  }
  
  function addCustom($query){
    $this->set($query, 'custom');
    $this->setAttribute('custom', 'true', $query);
  }
 

}



?>

<?
include_once(LIBS.'/Data/TypeSpace.php');
include_once(LIBS.'/Data/DataManager.php');

  
class DataDeleter extends DataManager{
  var $keyArray;
  var $cascadeElements;
  var $cascadeIds;
  var $parentIds;
  
  function DataDeleter($structName){
    parent::DataManager($structName);
    $this->init();
    $cascadeEls = $this->config['cascade'];
    if (!is_array($cascadeEls)) $cascadeEls = array();
    $cascadeEls = array_merge($cascadeEls, $this->struct->getElementsByAttribute('cascade', true));;
    $this->cascadeElements = $cascadeEls;
  }
  

  //:FIXME: BROKEN: doesn't work with structures without or with multiple keys
  function go(){
    $this->init();
    $this->processParams($this->params);
    //old mysql does not support multiple table conditions in delete...
    //if (sizeof($this->struct->extends) > 0 || sizeof($this->cascadeElements) > 0){
      $this->getIds();
    //}
    if (sizeof($this->keyArray) < 1) return;
    $success = $this->execute();
    if (!$success) return 0;
    foreach ($this->struct->extends as $parentStruct){
      $refIds = $this->getParentIds($parentStruct, $this->keyArray);
      $this->parentIds[$parentStruct] = $refIds;
      $deleter = & $this->getDeleter($parentStruct);
      if (sizeof($refIds) < 1) continue;
      foreach ($refIds as $refId){
        $deleter->addParam('id', $refId);
      }
      if ($deleter->go()){
        $this->deleteParentReferences($parentStruct);
      }
    }
    foreach ($this->cascadeElements as $element){
      if (sizeof($this->cascadeIds[$element]) < 1) continue;
      $type = $this->struct->type($element);
      $deleter = & $this->getDeleter($type);
      foreach ($this->cascadeIds[$element] as $cascadeId){
        $deleter->addParam('id', $cascadeId);
      }
      $deleter->go();
    }
    $elements = $this->struct->getElements();
    foreach ($elements as $element){
      if (!$this->typeSpace->isBaseType($this->struct->type($element))){
        $this->deleteReferences($element);
      }
    }
  }

  function truncate(){
    $this->init();
    $this->config['allowTruncate'] = true;
    $this->execute();
  }
  
  function getIds(){
    $loader = & $this->getLoader();
    $loader->setParams($this->params);
    foreach ($this->cascadeElements as $element){
      $loader->request($element.'.id');
    }
    $keys = $this->struct->getKeys();
    foreach ($keys as $key){
      $loader->request($key);
    }
    $list = $loader->load();
    while ($list->moveNext()){
      $keyValues = array();
      foreach($keys as $key){
        $keyValues[$key] = $list->get($key);
      }
      $this->keyArray[] = $keyValues;
      foreach ($this->cascadeElements as $element){
        while ($list->moveNext($element)){
          $this->cascadeIds[$element][] = $list->get($element.'.id');
        }
      }
    }
  }

}



?>

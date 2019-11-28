<?
include_once(LIBS.'/Data/DataLoader.php');
#include_once(LIBS.'/Data/XML/ConditionBuilder_xml.php');

class DataLoader_xml extends DataLoader{
 
  


  function fetch(){
    #QUICK HACK
    global $IMP;
    $IMP->debug("Starting dl_xml execute", 5);
    $file = $this->binding->getFileName();
    list($accessMode, $nameSpace, $localName, $dir) = parseClassName($this->structName);
    $dom = new DOMDocument();
    if (file_exists(DATA.'/'.$nameSpace.'/xml/'.$file)) $loaded = $dom->load(DATA.'/'.$nameSpace.'/xml/'.$file);
    else $loaded = $dom->load(DATA.'/xml/'.$file);
    if (!$loaded) return false;
    $root = $dom->documentElement;
    $items = $root->childNodes;
    $pelican = new PHPelican();
    foreach ($items as $item){
      if ($item->nodeType != XML_ELEMENT_NODE) continue;
      $name = $item->nodeName;
      if ($row) unset($row);
      $row = new PHPelican($name);
      $elements = $item->childNodes;
      foreach ($elements as $element){
        if ($element->nodeType != XML_ELEMENT_NODE) continue;
        $elementName = $element->nodeName;
        $value = utf8_decode($element->textContent);
        $row->set($elementName, $value);
      }
      $this->resultRows++;
      $pelican->addRow($row);
    }
    $this->data = $pelican;
    $this->data->reset();
  }
  
  function execute(){
  }
  
  
}


?>

<?
include_once(PEAR.'/Text/Diff.php');
include_once(PEAR.'/Text/Diff/Renderer.php');
include_once(PEAR.'/Text/Diff/Renderer/unified.php');
include_once(PEAR.'/Text/Diff/Renderer/inline.php');

class Versioner{
  var $typeSpace;
  var $bindingManager;
  var $useBranch;
  var $struct;
  
  function Versioner(){
    global $IMP;
    $this->typeSpace = & $IMP->typeSpace;
    $this->bindingManager = & $IMP->bindingManager;
  }

  function getDir($structName, $id, $branch){
    list($accessMode, $nameSpace, $localName, $structDir) = parseClassName($structName);
    $dir = PATH_VERSIONING;
    $dir .= '/'.$branch;
    if (!$nameSpace) $nameSpace = 'base';
    $dir .= '/'.$nameSpace;
    if ($structDir) $dir .= '/'.$structDir;
    $dir .= '/'.$localName;
    $dir .= '/'.$id;
    return $dir;
  }
  
  function currentBranch($structName){
    if ($this->useBranch[$structName]) return $this->useBranch[$structName];
    return 'main';
  }

  function get($structName, $id, $branch, $revision=0, $xml=''){
    $dir = $this->getDir($structName, $id, $branch);
    $structDir = $nameSpace.'/'.$structName;
    $path = $dir.'/'.$revision;
    if (file_exists($dir.'/current')) $current = file_get_contents($dir.'/current');
    if (!$revision) $revision = $current;
    if ($revision != 0 && !file_exists($path)) error("Revision {$branch}.{$revision} for $structName does not exist!");
    $bindingType = $this->bindingManager->bindingType($structName, $branch);
    list($major, $minor) = explode('.', $revision);
    if ($bindingType){ //reverse mode
      if (!$xml){
        $loader = & $this->bindingManager->getLoader($structName);
        $loader->versioner = & $this;
        $this->useBranch[$structName] = $branch;
        $loader->addParam('id', $id);
        $list = $loader->load();
        $xml = $list->get();
      }
      list($currentMajor, $currentMinor) = explode('.', $current);
      $currentRev = $currentMajor;
      if ($currentMinor) $currentRev .= '.'.$currentMinor;
      while ($currentRev != $revision){
        if (!$currentMinor){
          $currentMajor--;
          $currentMinor = file_get_contents($dir.'/'.$currentMajor.'.last');
        }
        else{
          $currentMinor--;
        }
        $currentRev = $currentMajor;
        if ($currentMinor) $currentRev .= '.'.$currentMinor;
        $xml = $this->patch($xml, $branch, $currentRev);
      } 
    }
    else{
      if (!$xml) $xml = $this->get($structName, $id, 'main', $major);
      $currentMinor = 1;
      $currentRev = $major.'.'.$currentMinor;
      do{
        $xml = $this->patch($xml, $branch, $currentRev);
        $currentRev = $major.'.'.(++$currentMinor);
      } while ($currentRev != $revision);
    }
    return $xml;
  }

  function put($structName, $previousXml, $currentXml=null, $branch='', $id=0){
    $this->struct = & $this->typeSpace->getStructure($structName);
    if (!$branch) $branch = $this->currentBranch($structName);
    if (!$id) $id = $previousXml->get('id');
    $dir = $this->getDir($structName, $id, $branch);
    if (file_exists($dir.'/current')){
      $current = file_get_contents($dir.'/current');
      list($currentMajor, $currentMinor) = explode('.', $current);
    }
    $bindingType = $this->bindingManager->bindingType($structName, $branch);
    if ($bindingType) $loadBranch = $branch;
    else{
      $loadBranch = 'main';
    }
    if ($currentXml ===  null) $currentXml = $this->get($structName, $id, $loadBranch);
    if ($bindingType) $diff = $this->diff($currentXml, $previousXml);
    else $diff = $this->diff($previousXml, $currentXml);
    //for testing: $diff = $this->diff($previousXml, $currentXml);
    if (!file_exists($dir)) createPath($dir);
    if ($bindingType) $current = ++$currentMajor;
    else $current = $currentMajor.'.'.(++$currentMinor);
    $fp = fopen($dir.'/current', 'w');
    fwrite($fp, $current);
    fclose($fp);
    $patchFile = $dir.'/'.$current;
    if ($this->config['compress']) $patchFile .= 'compress.'.$this->config['compress'].'://';
    $fp = fopen($patchFile, 'w');
    fwrite($fp, $diff->dumpToXML());
    fclose($fp);
  }

  function patch($xml, $diff){
    $diff->rebuildVars();
    $res = new PHPelican();
    while ($diff->moveNext()){
      $element = $diff->getName();
      $val = $xml1->$element;
      $valDiff = $diff->$element;
      $elRes = "";
      if (!$valDiff){
        $res->set($element, $val);
        continue;
      }
      for ($i=0; $i<strlen($valDiff); $i++){
        $diffLine .= $valDiff{$i};
        if ($valDiff{$i} == "\n"){
          if (preg_match('/@@ -(\d+),(\d+) +(\d+),(\d+) @@/', $diffLine, $m)){
            $xStart = $m[1];
            $xLen = $m[2];
            $yStart = $m[3];
            $yLen = $m[4];
            for ($valChar; $valLines<$xStart; $valChar++){
              $valLine .= $val{$valChar};
              if ($val{$valChar} == "\n"){
                $valLines++;
                if ($valLines < $xStart) $elRes .= $valLine;
                $valLine = '';
              }
            }
          }
          else{
            $cnt++;
            if ($diffLine{0} != '-'){
              if ($diffLine{0} == '+') $diffLine = substr($diffLine, 3);
              $elRes .= $diffLine;
            }
            $diffLine = '';
          }
        }
      }
      $res->set($element, $elRes);
    }
    return $res;
  }


  function diff($xml1, $xml2){
    //$xml1Obj = new PHPelican($xml1);
    //$xml2Obj = new PHPelican($xml2);
    
    $xml1Obj = $xml1;
    $xml2Obj = $xml2;
    $xml1Obj->rebuildVars();
    $xml2Obj->rebuildVars();
    $diff = new PHPelican('patch');
    while ($xml1Obj->moveNext()){
      $element = $xml1Obj->getName();
      if (in_array($element, array('cr_user', 'mod_user', 'cr_date', 'mod_date'))){
        continue;
       }
      $type = $this->struct->type($element);
      if (!$this->typeSpace->isBaseType($type)) continue;
      $val1 = $xml1Obj->$element;
      $val2 = $xml2Obj->$element;
      $val1 = $this->prepare($type, $val1);
      $val2 = $this->prepare($type, $val2);
      $td = new Text_Diff(explode("\n", $val1), explode("\n", $val2));
      $renderer = new Text_Diff_Renderer_unified();
      $sDiff = $renderer->render($td);
      if ($sDiff) $diff->set($element, $sDiff);
    }
    return $diff;
  }
  
  function prepare($type, $value){
    switch ($type){
      case "html":
      case "richText":
        $value = str_replace("<br>", "<br>\n", $value);
      case "longText":
        $value = str_replace("\r", "", $value);
      case "text":
        break;
    }
    return $value;
  }

}


?>
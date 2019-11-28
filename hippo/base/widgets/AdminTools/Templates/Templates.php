<?

class Templates extends BasicWidget{
  var $structName;
  var $widgets;
  var $templates;
  var $widget;
  var $id;
  
  function setStruct($structName){
    $this->structName = $structName;
  }

  function load(){
    global $IMP;
    $this->widgets = $IMP->widgetsForStruct($this->structName);
    $this->widgets = array('Form/inputs/SelectInput');
    if (is_array($this->widgets)) foreach ($this->widgets as $widget){
      list($accessMode, $nameSpace, $localName, $dir) = parseClassName($widget);
      if ($nameSpace) $base = APPS.'/'.$nameSpace;
      else $base = BASE;
      $base .= '/widgets';
      if ($dir) $base .= '/'.$dir;
      $base .= '/'.$localName;
      $base .= '/templates';
      $paths = search_dir($base, '*.php');
      if (is_array($paths)) foreach ($paths as $path){
        $template = str_replace($base, '', $path);
        $templateDir = dirname($template);
        $path_parts = pathinfo($template);
        $templateDir = $path_parts["dirname"];
        $templateFile = $path_parts["basename"];
        $extension = $path_parts["extension"];
        $templateName = str_replace('.'.$extension, '', $templateFile); #potentially WRONG
        if (!isset($setTemplates[$templateDir])){
          $varFile = VARPATH."/templates/$widget/".$templateDir.'/'.$id;
          if (file_exists($varFile)){
            $set = file_get_contents($varFile);
            $setTemplates[$templateDir] = $set;
          }
        }
        $set = $setTemplates[$templateDir];
        if ($set == $templateName) $templates[$widget][$templateDir][$templateName] = 1;
        else $templates[$widget][$templateDir][$templateName] = 0;
      }
    }
    if (is_array($templates)) ksort($templates);
    $this->templates = $templates;
  }

  function save(){
    if (!$this->$id) return;
    $templates = $this->takeParam('templates');
    if (!is_array($templates)) return;
    foreach ( array_keys($templates) as $widget){
      foreach ( $templates[$widget] as $dir => $name){
        if ($name){
          $varDir = fixForFile(VARPATH."/templates/$widget/".$dir);
          createPath($varDir);
          $varFile = $varDir.'/'.$id;
          $fp = fopen($varFile, 'w');
          fwrite($fp, $name);
          fclose($fp);
        }
      }
    }
  }
  
}

?>
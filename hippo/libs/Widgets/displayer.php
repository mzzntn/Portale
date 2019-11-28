<?
class Displayer{
  var $w;
  var $mode;
  var $template;
  var $templateFolder;
  var $baseDir;
  var $style;
  
  function Displayer(& $parentWidget){
    $this->w = & $parentWidget;
    $this->name = $parentWidget->htmlName;
    $this->shortName = $this->name; #:TODO: make short
  }
  
  function setTemplate($template){
    $this->template = $template;
  }
  
  function setStyle($style){
    $this->style = $style;
  }
  
  function getClass($class=''){
    #virtual
  }
  
  function setMode($mode){
    $this->mode = $mode;
  }
  
  function start(){
    #virtual
  }
  
  function end(){
    #virtual
  }
  
  function prepare(){
    #:todo? needed? Should call w->start & start
  }
  
  function display($optionString=''){
    global $IMP;
    $IMP->debug("Starting display for {$this->w->name}", 6);
#    if ($this->adminMode()) $this->startAdmin();
    $this->w->parseOptions($optionString);
    if (!$this->w->started) $this->w->start();
    $this->start();
    if ($this->displayTemplate()){
      $displayed = true;
    }
    elseif ( method_exists($this, 'ddisplay') ){
      $this->ddisplay();
      $displayed = true;
    }
    elseif ( method_exists($this->w, 'wdisplay') ){
      $this->w->wdisplay();
      $displayed = true;
    }
    $this->end();
    $this->w->end();
#    if ($this->adminMode()) $this->endAdmin();
    return $displayed;
  }
  
  
  function displayTemplate(){
    $templateFile = $this->getTemplateFile();
    if (!$templateFile) return false;
    global $W;
    global $D;
    $W = & $this->w;
    $D = & $this;
    global $C;
    include($templateFile);
    return true;
  }
  
  function getTemplateFile(){
    if (!$this->templateFolder) return false;
    if ($_REQUEST['target']){
      if ((is_string($_REQUEST['target']) && $_REQUEST['target'] != $this->w->name) || (is_array($_REQUEST['target']) && !$_REQUEST['target'][$this->w->name])){
        return false;
      }
    }
    $id = $this->w->getParam('id');
    if ($id){
      $varFolder = VARPATH."/templates/".$this->w->fullType."/".str_replace($this->baseDir.'/templates', '', $this->templateFolder);
      $varFile = fixForFile($varFolder.'/'.$id);
      if (file_exists($varFile)) $this->template = file_get_contents($varFile);
    }
    if (!$this->template) $this->template = 'default';
    if (!$this->templateFolder) return false;
    $templateFile = find_file($this->templateFolder, $this->template.'.php');
    return $templateFile;
  }
  
  function getVisualizator($widget, $mode, $id){
    global $IMP;
    $key = 0;
    $varDir = fixForFile(VARPATH."/vis/".$widget."/".$mode);
    $varFile = $varDir.'/'.$id;
    if ($id && file_exists($varFile)) $key = file_get_contents($varFile);
    return $IMP->vis[$mode][$widget][$key];
  }

}
?>

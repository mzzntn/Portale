<?

class d_Administrator_html_dhtml extends Displayer_dhtml{

  function start(){
    foreach ($this->w->structures as $i => $structName){
      $link = $_SERVER['PHP_SELF']."?{$this->name}[action]=table&{$this->name}[widget]=".urlencode($structName);
      foreach ($this->w->sections as $section){ //:KLUDGE:
        if ($this->w->menu->{$section}->{$structName}) $this->w->menu->{$section}->{$structName}->_link = $link;
      }
    }
    if (is_array($this->w->customPages)) foreach ($this->w->customPages as $name => $page){
      $link = $_SERVER['PHP_SELF']."?{$this->name}[action]=customPage&{$this->name}[widget]=".urlencode($name);
      foreach ($this->w->sections as $section){ //:KLUDGE:
        if ($this->w->menu->{$section}->{$name}) $this->w->menu->{$section}->{$name}->_link = $link;
      }
    }
    $this->w->widgets['menu']->setTree($this->w->menu);
    if ($this->w->widgets['table']){
      $adm = $_SERVER['PHP_SELF'].'?'.$this->w->name.'[action]=form&';
      $adm .= '&form_'.$this->w->currentStructName.'[id]=';
      $this->w->widgets['table']->config['admin'] = $adm;
    } 
  }
  
  function displayTemplateChoice(){
    if (is_array($this->w->widgetTemplates)){
      foreach (array_keys($this->w->widgetTemplates) as $widget){
        foreach (array_keys($this->w->widgetTemplates[$widget]) as $dir){
          print "$dir:";
          print "<select name='{$this->w->name}[templates][$widget][$dir]'>";
          foreach ($this->w->widgetTemplates[$widget][$dir] as $name => $set){
            print "<option";
            if ($set) print " SELECTED";
            print " value='$name' ";
            print ">$name</option>";
          }
          print "</select>";
          print "<br>";
        }
      }
    }
  }
  
  function displayVisualizationChoice($currentId){
    global $IMP;
    if (!$currentId) return;
    if (is_array($this->w->visWidgets)) foreach ($this->w->visWidgets as $widget){
      if (is_array($IMP->vis)) foreach ($IMP->vis as $mode => $vis){
        if (is_array($vis[$widget])){
          print "$mode:";
          print "<select name='{$this->w->name}[vis][$widget][$mode]'>";
          foreach ($vis[$widget] as $key => $details){
            print "<option ";
            print "value='$key' ";
            $varDir = fixForFile(VARPATH."/vis/$widget/".$mode);
            $varFile = $varDir.'/'.$currentId;
            if (file_exists($varFile) && file_get_contents($varFile) == $key) print "SELECTED";
            print ">";
            if ($details['name']) print $details['name'];
            elseif ($details['url']) print $details['url'];
            print "</option>";
          }
          print "</select>";
          print "<br>";
        }
      }
    }
  }
  
}


?>

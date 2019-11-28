<?
if ($W->readOnly){
      print "<div align='left' class='".$this->getCSSClass('readOnlyText')."'>";
    }
    else{
      print "<select ";
      print "id='{$this->name}' ";
      print "class='".$this->getCSSClass()."' ";
      if ($this->w->config['multiple']) print "multiple ";
      print "name='{$this->w->name}";
      if ($this->w->config['multiple']) print "[]";
      print "'>";
      if (!$this->w->config['noBlank']) print "<option value=''></option>";
    }
    if (is_array($this->w->tree)) foreach ($this->w->tree as $branch){
      $this->printBranch($branch);
    }
    if ($this->readOnly){
      print "</div>";
    }
    else print "</select>";

?>

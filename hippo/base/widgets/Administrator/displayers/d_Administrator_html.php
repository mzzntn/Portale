<?

class d_Administrator_html extends Displayer_html{

  function ddisplay(){
    $w = & $this->w;
    print "<table width='100%' border='0'>";
    print "<tr>";
    print "<td class='";
    print $this->w->getCSSClass('heading');
    print "' valign='top' align='center' width='15%'>";
    print "</td>";
    print "<td>";
    if ($this->w->activeWidget) $this->linksRow();    
    print "</td>";
    print "</tr>";
    print "<tr>";
    print "<td valign='top'>";
    $this->menu();
    print "</td>";
    print "<td>";
    if ($this->w->activeWidget){
      $w->activeWidget->display();
    }
    print "</td>";
    print "</tr>";
    print "</table>";
  }
  
  function menu(){
    print "<table width='100%'>";
    print "<tr>";
    print "<td>";
    $currentMenu = $this->w->getParam('widget');
    for ($i=0; $i<sizeof($this->w->structures); $i++){
      $structName = $this->w->structures[$i];
      $menuName = $this->w->menu[$structName];
      print "<tr><td class='";
      if ($i == $currentMenu) print $this->w->getCSSClass('activeCell');
      else print $this->w->getCSSClass('menuCell');
      print "'>";
      print "<a class='";
#     print $this->w->getCSSClass('menuLink');
      print "' href='{$_SERVER['PHP_SELF']}?{$this->w->name}[widget]=$i&{$this->w->name}[action]=table'>$menuName</a>";
      print "</td>";
      print "</tr>";
    }
    foreach($this->w->customPages as $name => $page){
      print "<tr><td class='";
      if ($name == $currentMenu) print $this->w->getCSSClass('activeCell');
      else print $this->w->getCSSClass('menuCell');
      print "'>";
      print "<a class='";
#     print $this->w->getCSSClass('menuLink');
      print "' href='{$_SERVER['PHP_SELF']}?{$this->w->name}[widget]=$name&{$this->w->name}[action]=custom'>$menuName</a>";
      print "</td>";
      print "</tr>";
    }
    print "</table>";
      
  }
  
  function linksRow(){
    print "<table width='100%'>";
    print "<tr>";
    print "<td align='center' class='";
    if ($this->w->getParam('action') == 'search') print $this->w->getCSSClass('activeCell');
    else print $this->w->getCSSClass('menuCell');
    print "'><a href='{$_SERVER['PHP_SELF']}?{$this->w->name}[action]=table'>Cerca</a></td>";
    print "<td align='center' class='";
    if ($this->w->getParam('action') == 'form') print $this->w->getCSSClass('activeCell');
    else print $this->w->getCSSClass('menuCell');
    print "'><a href='{$_SERVER['PHP_SELF']}?{$this->w->name}[action]=form&_clear[form_{$this->w->currentStructName}]=1'>Nuovo</a></td>";
    print "</tr>";
    print "</table>";
  }

}

?>

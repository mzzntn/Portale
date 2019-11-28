<?
if (is_array($W->templates)){
  foreach (array_keys($W->templates) as $widget){
    foreach (array_keys($W->templates[$widget]) as $dir){
      print "$dir:";
      print "<select name='{$W->name}[templates][$widget][$dir]'>";
      foreach ($W->templates[$widget][$dir] as $name => $set){
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
?>
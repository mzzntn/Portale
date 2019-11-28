<?
  print $W->data->dumpToXML($D->name);
  print "<{$D->name}_queryInfo>";
  print "<totalRows>".$W->resultRows."</totalRows>";
  print "</{$D->name}_queryInfo>";
?>

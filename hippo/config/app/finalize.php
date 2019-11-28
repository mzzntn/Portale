<?

if ($IMP->defaults['display'] == 'xml'){
  print "</response>";
}
$IMP->widgetParams->dumpToSession();
$IMP->savePipelines();

?>
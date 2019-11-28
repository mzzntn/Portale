<?
foreach (array_keys($W->widgets) as $key){
?>
<?
  $W->widgets[$key]->display();
?>
<br>
<?
}
?>
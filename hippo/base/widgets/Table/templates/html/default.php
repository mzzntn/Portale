<table class='<?= $D->getCSSClass() ?>' border='1'>
<tbody id='<?=$D->name?>'>
<tr>
<?
   foreach ($W->elements as $elementName){
?>
  <th class='<?= $D->getClass('th')?>'><?= $D->label($elementName) ?>
  </th>
<? } ?>
</tr>
<?
  while ($W->data->moveNext()){
    print "<tr>";
    $id = $W->data->get('id');
    print "<td>";
    if ($W->config['admin']) print "<a href='{$W->config['admin']}$id'>";
    print $id;
   	if ($W->config['admin']) print "</a>";
    foreach ($W->elements as $elementName){
      if ($elementName == 'id') continue;
      print "<td>";
      print $W->data->get($elementName);
      print "</td>";
    } 
    print "</tr>\n";
  }
?>
</tbody>
</table>
<?
if ($W->config['maxElements'] && $W->resultRows){
  if ($W->getParam('start') > 1){
    $prev = $W->getParam('start') - $W->config['maxRows'];
    if ($prev < 1) $prev = 1;
?><span class='<?= $D->getCSSClass('tasti_li') ?>'>
<a href='<?=$_SERVER['PHP_SELF']?>?<?=$W->name?>[start]=<?=$prev?>'>Indietro</a></span>
<?
  }
  $next = $W->getParam('start') + $W->config['maxRows'];
  if ($next < $W->resultRows){
?><span class='<?= $D->getCSSClass('tasti_li') ?>'>
<a href='<?=$_SERVER['PHP_SELF']?>?<?=$W->name?>[start]=<?=$next?>'>Avanti</a></span>
<?
  }
}
?>

<?
  if (!$D->manualOpen){ #Yeah, it's a kludge
?>
<form action='<?=$_SERVER['PHP_SELF']?>' method='POST' id='<?=$D->name?>'>
<?
  }
?>
<?
foreach ($W->inputsOrder as $inputName){
?>
<?=$W->inputs->$inputName->display()?>
<?
  break;
}
?>
<input type='submit' class='<?=$D->getCSSClass('button')?>' name='submit_<?=$D->name?>' value='<?=$D->submitText?>'>
<?
  if (!$D->manualOpen){
?>
</form>
<?
  }
$D->printEndScripts();
?>
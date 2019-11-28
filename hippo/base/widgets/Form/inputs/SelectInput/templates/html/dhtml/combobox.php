<?
if ($W->readOnly){
    print "<div align='left' class='".$this->getCSSClass('readOnlyText')."'>";
    $values = array();
    if ( is_array($D->fields) ) foreach ($D->fields as $id => $name){
        if ($W->selectedValues[$id]) $values[] = $name;
    }
    print implode(', ', $values);
    print "</div>";
    return;
}
?>
<?
$D->loadJs('comboBox');
?>
<div class="<?=$this->getCSSClass()?>" id="<?=$D->name?>_div">
<input type="hidden" name="<?=$W->parentWidget->name?>[_<?=$W->elementName?>_mode]" id="<?=$D->name?>_mode">
<input type="hidden" name="<?=$W->name?>" id="<?=$D->name?>_hidden">
<input type="text" name="<?=$D->name?>_text" id="<?=$D->name?>_text">
<a href="javascript: noa()" id="<?=$D->name?>_button">+</a>
</div>
<script language="Javascript">
  var combo_<?=$D->name?> = new ComboBox("<?=$D->name?>_text", "<?=$D->name?>_button");
  combo_<?=$D->name?>.setHiddenInput("<?=$D->name?>_hidden");
  combo_<?=$D->name?>.setModeInput("<?=$D->name?>_mode");
<?
if (is_array($D->fields)) foreach ($D->fields as $value => $label){
  for ($i=0; $i<$D->depth[$value]; $i++){
    $label = '-'.$label;
  }
?>
  combo_<?=$D->name?>.v["<?=$value?>"] = "<?=$label?>";
<?
}
$setValue = $W->value;
if (is_array($setValue)) $setValue = array_shift($setValue);
?>
  combo_<?=$D->name?>.setValue("<?=$setValue?>");
  //combo_<?=$D->name?>.init();
  //document.body.onload = function(){combo_<?=$D->name?>.init();};
  addEvent(document.body, 'load', function(){combo_<?=$D->name?>.init();});
</script>

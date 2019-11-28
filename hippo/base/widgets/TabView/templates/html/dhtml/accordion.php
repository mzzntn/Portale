<script>
var <?=$this->name?>_activeTab = '';

function <?=$this->name?>_switchTab(tab){
  var div = getObj(tab);
  div.style.display='';
  if (<?=$this->name?>_activeTab){
    var active = getObj(<?=$this->name?>_activeTab);
    active.style.display='none';
  }
  if (<?=$this->name?>_activeTab == tab) <?=$this->name?>_activeTab = '';
  else <?=$this->name?>_activeTab = tab;
}
</script>
<?
foreach (array_keys($W->widgets) as $label){
  $jsLabel = fixForJs($label);
?>
<div>
<a href="javascript: <?=$this->name?>_switchTab('<?=$jsLabel?>')"><?=$label?></a>
</div>
<div id='<?=$jsLabel?>' style='display: none'>
<?
  $W->widgets[$label]->display();
?>
</div>
<?
}
?>
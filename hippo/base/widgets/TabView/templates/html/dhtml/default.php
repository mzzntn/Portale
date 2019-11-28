<div class='<?=$W->getCSSClass()?>'>
<script>
var <?=$this->name?>_activeTab = '';

function <?=$this->name?>_switchTab(tab){
  //alert(tab);
  var div = $('#'+tab);
  var tab = $('#'+tab+'_but').parent();
  $('.nav-tabs').find('li.active').each(function(){$(this).removeClass('active');});
  $('.TabView').find('div.tab').each(function(){$(this).hide();});
  div.show();
  tab.addClass('active');
  /*var div = getObj(tab);
  div.style.display='';
  var but = getObj(tab+'_but');
  makeCool(but);
  but.addClass('active');
  if (<?=$this->name?>_activeTab){
    var active = getObj(<?=$this->name?>_activeTab);
    active.style.display='none';
    var activeBut = getObj(<?=$this->name?>_activeTab+'_but');
    makeCool(activeBut);
    activeBut.removeClass('active');
  }
  if (<?=$this->name?>_activeTab == tab) <?=$this->name?>_activeTab = '';
  else <?=$this->name?>_activeTab = tab;*/
}
</script>
<div class='nav nav-tabs'>
<?
foreach (array_keys($W->widgets) as $label){
  $jsLabel = fixForJs($label);
?>
    <li role="presentation">
        <a href="javascript: <?=$this->name?>_switchTab('<?=preg_replace("/[^a-zA-Z\-_:\.]/","",$jsLabel)?>')" id='<?=preg_replace("/[^a-zA-Z\-_:\.]/","",$jsLabel)?>_but'><?=$label?></a>
    </li>
<?
}
?>
</div>
<?
foreach (array_keys($W->widgets) as $label){
  $jsLabel = fixForJs($label);
?>
<div id='<?=preg_replace("/[^a-zA-Z\-_:\.]/","",$jsLabel)?>' style='display: none' class='<?=$W->getCSSClass('tab')?>'>
<?
  $W->widgets[$label]->display();
?>
</div>
<?
}
?>
</div>

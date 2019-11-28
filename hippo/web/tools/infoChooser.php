<?
include_once('../init.php');
include_once('tools_top.php');
$search = & $IMP->widgetFactory->getWidget('Form/SearchForm', 'search_info');
$search->setStruct('cms::pagina');
$search->config['table'] = 'table_info';
$search->generateFromStructure();
$query = $search->generateQuery();
$table = & $IMP->widgetFactory->getWidget('Table', 'table_info');
$table->setStruct('cms::pagina');
$table->config['maxElements'] = 4;
$table->setParams($query);
$table->load();
$table->config['action'] = 'pass';
?>
<?
 if ($IMP->canDisplay('html')){
?>
<script>
function pass(id){
  params = new Array();
  params['href'] = '<?=HOME?>/pagina.php?id='+id;
  if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  if (master){
    if (master.insertLink) master.insertLink(params);
  }
  //iPopupClose();
  return false;
}
</script>
<?
}
$search->display();
$table->display();
include_once('tools_bottom.php');
?>

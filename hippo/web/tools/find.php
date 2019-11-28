<?
include_once('tools_top.php');
?>
<script>
function doFind(){
  var text = document.forms[0].find.value;
  var master = getMaster();
  if (!master) return;
  master.findText(text);
  return false;
}
</script>
<form onsubmit='doFind();return false;'>
Trova: <input type='text' name='find' />
<input type='submit' name='submit' value='Vai' />
</form>
<?
include_once('tools_bottom.php');
?>
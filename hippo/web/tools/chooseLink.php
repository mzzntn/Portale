<?
include_once('../init.php');
?>
<html>

<head>
  <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
  <title>Inserisci/Modifica link</title>
<?
  $IMP->loadJs('divControls');
?>  
<script>
function pass(){
  var params = new Array();
  params["href"] = document.linkForm.href.value;
  params["target"] = document.linkForm.target.value;
  if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  if (master){
    if (master.insertLink) master.insertLink(params);
  }
  iPopupClose();
  return false;
}
</script>
</head>

<body class='tools'>
<form name='linkForm' onsubmit='pass(); return false;'>
<table border="0" style="width: 100%;">
  <tr>
    <td>URL:</td>
    <td><input type="text" name='href' style="width: 100%" value='<?=$_REQUEST['url']?>' /></td>
  </tr>
  <tr>
    <td>Titolo (tooltip):</td>
    <td><input type="text" name="title" style="width: 100%" /></td>
  </tr>
  <tr>
    <td>Target:</td>
    <td><select name="target">
      <option value="" >Nessuno (usa implicito)</option>
      <option value="_blank" <? if ($_REQUEST['target'] == '_blank') print "SELECTED"?> >
      Nuova finestra (_blank)</option>
      <option value="_self" <? if ($_REQUEST['target'] == '_self') print "SELECTED"?> >
      Stesso frame (_self)</option>
      <option value="_top" <? if ($_REQUEST['target'] == '_top') print "SELECTED"?> >
      Frame principale (_top)</option>
    </select>
    </td>
  </tr>
</table>

  <input type="submit" name="ok" value='Ok'>
</form>
</body>
</html>

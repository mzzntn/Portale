<?
include_once('../init.php');

?>
<html>
<head>
<title>Salva template</title>
</head>
<body bgcolor='#FFFFFF'>
<?
if ($_REQUEST['template'] && $_REQUEST['name']){
  $dir = PATH_WEBDATA.'/myHTML/templates';
  createPath($dir);
  $file = fixForFile($dir.'/'.$_REQUEST['name']);
  if (!preg_match('/\.(html|htm)$/', $file)) $file .= '.html';
  $fp = fopen($file, 'w');
  $template = "<html><body>".$_REQUEST['template']."</body></html>";
  fwrite($fp, $template);
  fclose($fp);
  print "<script>if (iPopupClose) iPopupClose();\n else window.close();</script>";
}
?>
  
<form action='' method='POST'>
<input type='hidden' name='template'>
Nome: <input type='text' name='name'><br>
<input type='submit' name='submit' value='Ok'>
</form>
<script>
hidden = document.forms[0].template;
if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
if (master && master.hidden) hidden.value = master.hidden.value;
</script>
</body>
</html>

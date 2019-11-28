<?
include_once('../init.php');
if (!$IMP->canDisplay('html')) return;
?>  
<html>

<head>
  <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
<?
  $IMP->loadJs('divControls');
?>
<script>
function getMaster(){
  var master;
  if (window.master) master = window.master;
  else if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  return master;
}
</script>
</head>
<body class='tools'>

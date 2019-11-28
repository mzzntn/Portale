<?

include_once('../init.php');
set_time_limit(300);
#$pipeline = & $IMP->getPipeline();
#$pipeline->step('fileUpload');
if ($_FILES['userfile']['tmp_name']){
  $userFile_path = $_FILES['userfile']['tmp_name'];
  $userFile_name = $_FILES['userfile']['name'];
  $files = new Files();
  $fileName = $files->store($userFile_path, $userFile_name, '/files');
  $url = URL_WEBDATA.'/files/'.$fileName;
}
if (!$url) $url = $_REQUEST['url'];
  
  
  
?>
<html>

<head>
  <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
  <title>Inserisci/Modifica file</title>
<?
  $IMP->loadJs('divControls');
?>  
<script>
function pass(){
  if (document.fileForm.userfile.value) return true;
  var params = new Array();
  params["href"] = document.fileForm.href.value;
  if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  if (master){
    if (master.insertLink) master.insertLink(params);
  }
  window.parent.closeIPopup(window.parent.currentIPopup);
  return false;
}
function changeButton(){
  if (document.fileForm.userfile.value) document.fileForm.ok.value = 'Invia';
  else document.fileForm.ok.value = 'Ok';
}
function sendToTop(){
  if (window != window.parent && window.name == 'hiddenIframe') window.parent.document.body.innerHTML = window.document.body.innerHTML;
}
</script>
</head>

<body class='tools' onload='sendToTop()'>
<iframe name="hiddenIframe" style="display: none"></iframe>
<form name='fileForm' enctype='multipart/form-data' target='hiddenIframe' onsubmit='pass()' action="<?$_SERVER['PHP_SELF']?>" method='POST'>
<table border="0" style="width: 100%;">
  <tr>
    <td>File:</td>
    <td><input type='file' name='userfile' onchange='changeButton()'></td> 
  <tr>
    <td>URL:</td>
    <td><input type="text" name='href' style="width: 100%" value='<?=$url?>'/></td>
  </tr>
  <tr>
    <td>Titolo (tooltip):</td>
    <td><input type="text" name="title" style="width: 100%" value='<?=$_REQUEST['title']?>'/></td>
  </tr>
</table>

  <input type="submit" name="ok" value='Ok' onclick="pass();">
</form>
</body>
</html>

<?
set_time_limit(120);
include_once('../init.php');

#$pipeline = & $IMP->getPipeline();
#$pipeline->step('imageUpload');

if ($_FILES['userfile']['tmp_name']){
  $userFile_path = $_FILES['userfile']['tmp_name'];
  $userFile_name = $_FILES['userfile']['name'];
  $fileName = $userFile_name;
  $tmpDir = sys_get_temp_dir();
  $tmpName = uniqid('tmp_');
  $tmpImage = $tmpDir.'/'.$tmpName;
  move_uploaded_file($userFile_path, $tmpImage);
  if ($_REQUEST['resize'] && $_REQUEST['resize'] != 'no'){
    if ($_REQUEST['resize'] == 'micro'){
      $rWidth = 80;
      $rHeight = 80;
    }
    elseif ($_REQUEST['resize'] == 'thumb'){
      $rWidth = 150;
      $rHeight = 150;
    }
    elseif ($_REQUEST['resize'] == 'web'){
      $rWidth = 500;
      $rHeight = 500;
    }
    elseif ($_REQUEST['resize'] == 'big'){
      $rWidth = 800;
      $rHeight = 600;
    }
    $image = new Image($tmpImage);
    $image->copySmaller($tmpDir, $rWidth, $rHeight);
  }
  $images = new Images();
  $uploadedImage = $images->store($tmpImage, $userFile_name);
  unlink($tmpImage);
  
}
  
  
  
?>
<html>
<head>
<style>
.tagButton{
 border: 1px solid blue;
 spacing: 0px;
 padding: 0px;
}
.tagButton.selected{
  background-color: #DDDDFF;
}
</style>
<link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
<title>Scegli un'immagine...</title>
<?
  $IMP->loadJs('divControls');
?>
<script language="JavaScript"><!--
function pass(val, params){
  if (!val){
    alert("Devi prima inviare un'immagine!");
    return;
  }
  params = new Array();
  params['border'] = getObj('img_border').value;
  params['align'] = getObj('img_align').value;
  params['alt'] = getObj('alt').value;
  if (!params['alt']){
    alert("Devi specificare un testo alternativo!");
    return;
  }
  else if (params['alt'].indexOf('"') != -1){
    alert('Il testo alternativo non può contenere doppi apici (")');
    return;
  }
  if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  if (master){
    if (master.insertImage) master.insertImage(val, params);
  }
  else if (top.opener.callFunc){
    top.opener.callFunc(val);
  }
  closeWindow();
}

function closeWindow(){
  window.parent.closeIPopup(window.parent.currentIPopup);
}

function fileChanged(){
//  a = getObj('sendLink');
//  a.href = '';
  submitImage();
}
function submitImage(){
  val = document.forms[0].userfile.value;
  if (!val.match(/\.(jpg|jpeg|png)\s*$/i)){
    alert("E' possibile inviare solo immagini in formato JPEG o PNG.");
    return;
  } 
  document.getElementById('imageDiv')['innerHTML'] = "<b>Sto inviando l'immagine, attendi...</b>";
  document.forms[0].submit();
}

function updateFromUrl(){
  val = document.forms[0].fileUrl.value;
  var imageDiv = getObj('imageDiv');
  var okDiv = getObj('okDiv');
  if (val.match(/\.(jpg|jpeg|png|gif)\s*$/i)){
    imageDiv.innerHTML = "<img src='"+val+"'>";
    okDiv.style.display = '';
  }
  else{
    imageDiv.innerHTML = "<b>Scegli un'immagine!</b>";
  }
}

function switchView(tag){
  var tagImm = getObj('tagImm');
  var tagProps = getObj('tagProps');
  var butTagImm = getObj('butTagImm');
  var butTagProp = getObj('butTagProp');
  makeCool(butTagImm);
  makeCool(butTagProp);
  if (tag == 'tagImm'){
    tagProps.style.display = 'none';
    tagImm.style.display = '';
    butTagImm.addClass('selected');
    butTagProp.removeClass('selected');
  }
  else{
    tagProps.style.display = '';
    tagImm.style.display = 'none';
    butTagProp.addClass('selected');
    butTagImm.removeClass('selected');
  }
}

function sendToTop(){
  if (window != window.parent && window.name == 'hiddenIframe') window.parent.document.body.innerHTML = window.document.body.innerHTML;
}
//--></script>
</head>
<body class='tools' onload='sendToTop()'>
<iframe name="hiddenIframe" style="display: none" src='<?=HOME?>/tools/empty.html'></iframe>
<div class='tags'>
<span id='butTagImm' class='tagButton' onclick="switchView('tagImm')">Immagine</span>
<span id='butTagProp' class='tagButton' onclick="switchView('tagProp')">Proprietà</span>
</div>
<div id='tagImm'>
<table>
<tr>
<td width='50%' valign='top' align='center'>
Anteprima:
<br><br>
<div id='imageDiv' style='min-width: 80px;'>
<?
  if ($uploadedImage){
    print "<img src='".URL_WEBDATA."/img/thumb/$uploadedImage'>";
    #$url = URL_WEBDATA."/img/orig/$uploadedImage";
    $url = $uploadedImage;
  }
  else{
    $url = $_REQUEST['fileUrl'];
    //$url = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $url);
    $url = preg_replace('/http:\/\/.+?'.preg_quote(URL_WEBDATA, '/').'/', URL_WEBDATA, $url);
    $urlImg = str_replace(URL_WEBDATA."/img/orig", URL_WEBDATA."/img/thumb", $url);
    if ($url)  print "<img src='".$urlImg."'>";
    else print "<b>Scegli un'immagine!</b>";
  }
  
  $resize = $_REQUEST['resize'];
  $preferredResize = $_REQUEST['preferredResize'];
  if (!$preferredResize) $preferredResize = 'big';
  if (!$resize) $resize = $preferredResize;
  
?>
</div>
</td>
<td valign='top'>
<form enctype='multipart/form-data' action="<?$_SERVER['PHP_SELF']?>" method='POST' target='hiddenIframe' onsubmit='pass()'>
<input type='hidden' name='preferredResize' value='<?=$preferredResize?>'>
<input type='hidden' name='uploadedFile' value='<?=$uploadedImage?>'>
Ridimensiona: <select name='resize'>
<option value='no' <? if ($resize == 'no') print "SELECTED"?>>Non ridimensionare</option>
<option value='micro' <? if ($resize == 'micro') print "SELECTED"?> >Piccolissima</option>
<option value='thumb' <? if ($resize == 'thumb') print "SELECTED"?> >Piccola</option>
<option value='web' <? if ($request == 'web') print "SELECTED"?>>Normale</option>
<option value='big' <? if ($request == 'big') print "SELECTED"?>>Grande</option>
</select><br>
<input type='file' name='userfile' onChange='fileChanged()'> 
<br><br>
Url:<br> <input type='text' id='fileUrl' name='fileUrl' size='40' value='<?=$url?>' onChange='updateFromUrl()'><br>
Testo alternativo:<br> <input type='text' id='alt' name='alt' size='40' value="<?= $_REQUEST['alt']?>"><br>
<br><center>
<div id='okDiv' <? if (!$uploadedImage && !$url){ print "style='display: none'"; }?>>
<a href='#' onclick='pass(document.forms[0].fileUrl.value); return false;'>Ok! Questa!</a>
</div>
</center>
</td>
</tr>
</table>
</div>
<div id='tagProps'>
<table width='100%'>
<tr>
<td>Bordo:</td>
<td><input type='text' id='img_border' name='img_border' value='<?=$_REQUEST['img_border']?>' size='3'></td>
<td>Allineamento:</td>
<td><select name='img_align' id='img_align'>
<option value=''></option>
<option value='left' <? if ($_REQUEST['img_align']=='left') print "SELECTED";?> >Sinistra</option>
<option value='center' <? if ($_REQUEST['img_align']=='center') print "SELECTED";?> >Centro</option>
<option value='right' <? if ($_REQUEST['img_align']=='right') print "SELECTED";?> >Destra</option>
</select>
</form>
</div>
<script>
switchView('tagImm');
</script>
</body>
</html>
<?
#print "CWD: ".getCwd()."<br>";
#include_once('../../config/web/app/finalize.php');
?>

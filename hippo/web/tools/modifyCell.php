<?
include_once('../init.php');
?>  
<html>

<head>
  <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
  <title>Proprietà cella</title>
<?
  $IMP->loadJs('divControls');
?>  
<script>
function pass(){
  var params = new Array();
  params["width"] = document.cellForm.width.value; // + document.tableForm.unit.value;
  params["align"] = document.cellForm.align.value;
  params["valign"] = document.cellForm.valign.value;
  params["colspan"] = document.cellForm.colspan.value;
  params["rowspan"] = document.cellForm.rowspan.value;
  params["bgcolor"] = document.cellForm.bgcolor.value;
  var master = null;
  if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  if (master){
    if (master.modifyTable){
      master.modifyCell(params);
      //closePopup();
    }
    //if (master.closePopup) master.closePopup();
  }
  return false;
}

function openColorChoice(){
  window.colorIPopup = openIPopup('<?=HOME?>/tools/palette.php', 'palette', 'Scegli un colore');
}
function setColor(color){
  getObj('input_color').value=color;
  //closeIPopup(window.colorIPopup.iPopupNum);
}

function closePopup(){
  //window.parent.closeIPopup(window.iPopup.iPopupNum);
  return false;
}
</script>

</head>

<body class='tools'>

<form action="" method="get" name="cellForm" onsubmit='pass(); return false;'>

<div>Colore:</div>
<input id='input_color' type="text" name="bgcolor" size="12" value="<?=$_REQUEST['bgcolor']?>"
    title="Larghezza" onclick="openColorChoice()"/> <span id='span_color' style='background-color: <?=$_REQUEST['bgcolor']?>; width: 8px;'></span>
<div>Larghezza:</div>
<input type="text" name="width" size="8" value="<?=$_REQUEST['width']?>"
title="Larghezza" />

<div>Posizionamento orizzontale:</div>
<select size="1" name="align"
  title="Posizionamento orizzontale">
  <option value="" selected="1"                >Not impostato</option>
  <option value="middle" <? if($_REQUEST['valign']=='middle')print "SELECTED";?> >Centro</option>
  <option value="left" <? if($_REQUEST['align']=='left')print "SELECTED";?> >Sinistra</option>
  <option value="right" <? if($_REQUEST['align']=='right')print "SELECTED";?> >Destra</option>
  <option value="absmiddle" <? if($_REQUEST['align']=='absmiddle')print "SELECTED";?> >Centro assoluto</option>
</select>

<div>Posizionamento verticale:</div>
<select size="1" name="valign"
  title="Posizionamento verticale">
  <option value="" selected="1"                >Not impostato</option>
  <option value="top" <? if($_REQUEST['align']=='top')print "SELECTED";?> >Sopra</option>
  <option value="middle" <? if($_REQUEST['valign']=='middle')print "SELECTED";?> >Centro</option>
  <option value="bottom" <? if($_REQUEST['valign']=='bottom')print "SELECTED";?> >Sotto</option>
  <option value="baseline" <? if($_REQUEST['valign']=='baseline')print "SELECTED";?> >Baseline</option>
  <option value="absbottom" <? if($_REQUEST['valign']=='absbottom')print "SELECTED";?> >Absbottom</option>
</select>


<div >Colspan:</div>
<input type="text" name="colspan" size="5" value="<?=$_REQUEST['colspan']?>"
title="Larghezza in colonne" />

<div>Rowspan:</div>
<input type="text" name="rowspan" size="5" value="<?=$_REQUEST['rowspan']?>"
title="Altezza in righe" />
<br>



<input type="submit" name="ok" value='Ok'/>
<input type="button" name="cancel" value='Annulla' onclick='closePopup()'/>

</form>

</body>
</html>

<?
include_once('../init.php');
?>  
<html>

<head>
  <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
  <title>Modifica tabella</title>
<?
  $IMP->loadJs('divControls');
?>  
<script>
function pass(){
  var params = new Array();
  params["border"] = document.tableForm.border.value;
  params["width"] = document.tableForm.width.value; // + document.tableForm.unit.value;
  params["align"] = document.tableForm.align.value;
  params["spacing"] = document.tableForm.spacing.value;
  params["padding"] = document.tableForm.padding.value;
  var master = null;
  if (window.parent.iPopupInfo) master = window.parent.iPopupInfo.master;
  else if (top.opener && top.opener.openerInfo) master = top.opener.openerInfo.master;
  else if(window.parent.dialogArguments.master) master = window.parent.dialogArguments.master;
  if (master){
    if (master.modifyTable) master.modifyTable(params);
    //if (master.closePopup) master.closePopup();
  }
  return false;
}

function closePopup(){
  //window.parent.closeIPopup();
  return false;
}
</script>

</head>

<body class='tools'>
<form action="" method="get" name="tableForm" onsubmit='pass(); return false;'>


Layout:

<div>Larghezza:</div>
<input type="text" name="width" size="8" value="<?=$_REQUEST['width']?>"
title="Larghezza" />

<div>Posizionamento:</div>
<select size="1" name="align"
  title="Posizionamento">
  <option value="" selected="1"                >Not impostato</option>
  <option value="left" <? if($_REQUEST['align']=='left')print "SELECTED";?> >Sinistra</option>
  <option value="right" <? if($_REQUEST['align']=='right')print "SELECTED";?> >Destra</option>
  <option value="texttop" <? if($_REQUEST['align']=='texttop')print "SELECTED";?> >Sopra il testo</option>
  <option value="absmiddle" <? if($_REQUEST['align']=='absmiddle')print "SELECTED";?> >Centro assoluto</option>
  <option value="baseline" <? if($_REQUEST['align']=='baseline')print "SELECTED";?> >Baseline</option>
  <option value="absbottom" <? if($_REQUEST['align']=='absbottom')print "SELECTED";?> >Absbottom</option>
  <option value="bottom" <? if($_REQUEST['align']=='bottom')print "SELECTED";?> >Bottom</option>
  <option value="middle" <? if($_REQUEST['align']=='middle')print "SELECTED";?> >Centro</option>
  <option value="top" <? if($_REQUEST['align']=='top')print "SELECTED";?> >Sopra</option>
</select>


<div >Spessore bordo:</div>
<input type="text" name="border" size="5" value="<?=$_REQUEST['border']?>"
title="Spessore bordo" />

<div>Spaziatura celle:</div>
<input type="text" name="spacing" size="5" value="<?=$_REQUEST['spacing']?>"
title="Spazio tra celle adiacenti" />


<div class="fr">Padding:</div>
<input type="text" name="padding" size="5" value="<?=$_REQUEST['padding']?>"
title="Spazio tra bordo e contenuto nella cella" />



<input type="submit" name="ok" value='Ok'/>
<input type="button" name="cancel" value='Annulla' onclick='closePopup()'/>

</form>

</body>
</html>

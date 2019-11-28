<?
include_once('tools_top.php');
?>  
<script>
var undo;
function saveUndo(){
  if (!master) return;
  undo = master.doc.body.innerHTML;
}
function pass(){
  var params = new Array();
  params["rows"] = document.tableForm.rows.value;
  params["cols"] = document.tableForm.cols.value;
  params["border"] = document.tableForm.border.value;
  params["width"] = document.tableForm.width.value + document.tableForm.unit.value;
  params["align"] = document.tableForm.align.value;
  params["spacing"] = document.tableForm.spacing.value;
  params["padding"] = document.tableForm.padding.value;
  master = getMaster();
  if (master){
    if (master.insertTable) master.insertTable(params);
    //closePopup();
  }
  
  return false;
}

function closePopup(){
  window.parent.closeIPopup(window.iPopup.iPopupNum);
  return false;
}

function closeUndo(){
  if (master) master.setValue(undo);
  //closePopup;
}
</script>

<form action="" method="get" name="tableForm" onsubmit='pass(); return false;'>
<table border="0">
  <tbody>
  <div

  <tr>
    <td>Righe:</td>
    <td><input type="text" name="rows" size="5" title="Numero righe" value="2" /></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>Colonne:</td>
    <td><input type="text" id="cols" name="cols" size="5" title="Numero colonne" value="4" /></td>
    <td>Larghezza:</td>
    <td><input type="text" name="width" size="5" title="Larghezza tabella" value="100" /></td>
    <td><select size="1" name="unit" title="Unità di misura">
      <option value="%" selected="1"  >Percento</option>
      <option value="px"              >Pixels</option>
      <option value="em"              >Em</option>
    </select></td>
  </tr>

  </tbody>
</table>


Layout:

<div>Posizionamento:</div>
<select size="1" name="align"
  title="Posizionamento">
  <option value="" selected="1"                >Not impostato</option>
  <option value="left"                         >Sinistra</option>
  <option value="right"                        >Destra</option>
  <option value="texttop"                      >Sopra il testo</option>
  <option value="absmiddle"                    >Centro assoluto</option>
  <option value="baseline"                     >Baseline</option>
  <option value="absbottom"                    >Absbottom</option>
  <option value="bottom"                       >Bottom</option>
  <option value="middle"                       >Centro</option>
  <option value="top"                          >Sopra</option>
</select>


<div >Spessore bordo:</div>
<input type="text" name="border" size="5" value="1"
title="Spessore bordo" />

<div>Spaziatura celle:</div>
<input type="text" name="spacing" size="5" value="1"
title="Spazio tra celle adiacenti" />


<div class="fr">Padding:</div>
<input type="text" name="padding" size="5" value="1"
title="Spazio tra bordo e contenuto nella cella" />



<input type="submit" name="ok" value='Ok' />
<input type="button" name="cancel" value='Annulla' onclick='closeUndo()'/>

</form>
<?
include_once('tools_bottom.php');
?>

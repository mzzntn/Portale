<?
$D->loadJs('divControls');
?>
<script>
var <?=$D->name?>_divs = new Array();
function <?=$D->name?>_switchDivs(button, inputName){
  div1 = <?=$D->name?>_divs[inputName+'_i'];
  div2 = <?=$D->name?>_divs[inputName+'_r'];
  if (button.innerHTML == '+'){
    button.innerHTML = '-';
    div2.replace(div1);
  }
  else{
    button.innerHTML = '+';
    div1.replace(div2);
  }
  return false;
}
<?
if ($W->config['hidable']){
?>

function <?=$D->name?>_toggle(){
  table = getObj('<?=$D->name?>_table');
  a = getObj('<?=$D->name?>_toggleLink');
  if (table.style.display == 'none'){
    table.style.display = 'inline';
    a.innerHTML = 'Nascondi maschera di ricerca';
  }
  else{
    table.style.display = 'none';
    a.innerHTML = 'Mostra maschera di ricerca';
  }
}


<?
$display = 'none';
}
else $display = '';
?>
</script>
<form action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form' >
<table id='<?=$D->name?>_table' class='<?=$this->getCSSClass('table')?>'>
  <tr>
    <td class='txt_standard'>Tipo provvedimento</td>
    <td><? $W->inputs->provvedimenti->inputs->tipo->display() ?></td>
    <td class='txt_standard'>Anno</td>
    <td><? $W->inputs->provvedimenti->inputs->num3->display() ?></td>

  </tr>

  <tr>
    <td class='txt_standard'>Numero provvedimento</td>
    <td colspan='3'>
<?
  $W->inputs->provvedimenti->prepareDisplayer();
  $W->inputs->provvedimenti->displayer->printScript();
  $W->inputs->provvedimenti->displayer->displayInput('num2');
?>


</td>
  </tr>

  <tr>
    <td class='txt_standard'>Oggetto</td>
    <td colspan='3'><? $W->inputs->descrizione->display("size=40") ?></td>
  </tr>

  <tr>
    <td class='txt_standard'>Data Provvedimento</td>
    <td colspan='3'>
<?
  $W->inputs->provvedimenti->displayer->displayInput('data');
?>


</td>
  </tr>
  <tr>
    <td class='txt_standard'>Ufficio</td>
    <td colspan='3'><? $W->inputs->ufficio->display(); ?></td>
  </tr>
  <tr>
    <td></td>
  </tr>
  <tr>
    <td></td>
    <td colspan='2'>
    <input type='submit' name='submit' value='Cerca'>
    <input type='submit' name='clear' value = 'Nuova Ricerca'>
    <input type='submit' name='oggi' value = 'Oggi'>
    </td>
  </tr>
  <tr>
    <td></td>
  </tr>
</table>
</form>


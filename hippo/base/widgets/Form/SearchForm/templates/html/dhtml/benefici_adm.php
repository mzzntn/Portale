<?
$D->loadJs('divControls');
?>
<script type="text/javascript">
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
$i = & $W->inputs;
?>
</script>
        <form action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form'>
      <p class='multi'>
            <p><label for='<?=$i->tipo->htmlName?>'>Tipo</label><? $i->tipo->display(); ?></p>
            <p><label for='<?=$i->descrizione->htmlName?>'>Descrizione</label><? $i->descrizione->display(); ?></p>
            <p><label for='<?=$i->modalita->htmlName?>'>Modalit&agrave;</label><? $i->modalita->display(); ?></p>
            <p><label for='<?=$i->cig->htmlName?>'>CIG</label><? $i->cig->display(); ?></p>
            <p><label for='<?=$i->ufficio->htmlName?>'>Ufficio</label><? $i->ufficio->display(); ?></p>
            <p><label for='<?=$i->data_1->htmlName?>'>Data</label><? $W->inputs->data_1->display(); ?>
        <label for='<?=$i->data_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->data_2->display(); ?></p>
            <p><label for='<?=$i->beneficiario->htmlName?>'>Beneficiario</label><? $i->beneficiario->display(); ?></p>
            <div style='clear: both'></div>
            <div class="rightalign">
                <input type='submit' name='submit' value='Cerca' class="button">
                <input type='submit' name='clear' value='Nuova Ricerca' class="button">            
            </div>
        </form>    

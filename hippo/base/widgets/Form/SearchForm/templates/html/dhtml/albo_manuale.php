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
  if (!$W->showForm) $display = 'none';
}
else $display = '';
$i = & $W->inputs;
?>
</script>

<div class='contenitore form'>
<form action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form' >

    <p> 
	<label for='<?=$i->TIPOATTO->htmlName?>'>Atto</label>

<? if ($W->config['tipoAtto_definiti']) {
?>
    <select name="search_albo[TIPOATTO]" class="" id="search_albo_TIPOATTO">
    <option value=""/>
<?
    $tipoAttoMostrato = $W->config['tipoAtto_definiti'];
    while($tipoAttoMostrato->moveNext()) {
?>
        <option value="<?=$tipoAttoMostrato->get('id')?>"><?=$tipoAttoMostrato->get('DESC_ATTO')?></option>
<?
    } print "</select>";
} else {
    $i->TIPOATTO->display();
}?>

    <p>  
        <label for='<?=$i->NRATTO->htmlName?>'>Numero atto</label>
         <? $i->NRATTO->display() ?>
    </p>
    <p class='multi'> 
		<label for='<?=$i->DATA_ATTO_1->htmlName?>'>Data atto</label><? $W->inputs->DATA_ATTO_1->display(); ?>
        <label for='<?=$i->DATA_ATTO_2->htmlName?>'class='inline'>fino a</label> <? $W->inputs->DATA_ATTO_2->display(); ?>
	</p>

  <div style='clear: both'></div>
            <div class="rightalign">
    			<input type='submit' name='submit' value='Cerca' class="button">
    			<input type='submit' name='clear' value='Nuova Ricerca' class="button">     
            </div>
</form>
</div>



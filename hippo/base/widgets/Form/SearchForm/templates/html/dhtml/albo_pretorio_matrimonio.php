<?
$i = & $W->inputs;
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
  <form class="form-ricerca form-horizontal col-lg-12 col-md-12 col-sm-12 col-xs-12" action='<?=str_replace("\\","",$_SERVER['PHP_SELF'])?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form'>
    <input type="hidden" name="<?=str_replace("search","tabella",$D->name)?>[page]" value="1">
    <input type="hidden" name="<?=str_replace("search","tabella",$D->name)?>[start]" value="0">

      <div class="row">
	  <div class="col-lg-6 col-md-6 col-sm-10 col-xs-12">
	      <!--<div class="alert alert-danger">Errore</div>-->
	  </div>
      </div>
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->OGGETTO->htmlName?>" class="sr-only">Oggetto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Oggetto</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $W->inputs->OGGETTO->display(); ?>
	      </div>

	      <label for="<?=$i->NRATTO->htmlName?>" class="sr-only">numero atto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">numero atto</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $W->inputs->NRATTO->display(); ?>
	      </div>
	  </div>
      </div>
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->DATA_ATTO_1->htmlName?>" class="sr-only">Data atto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data atto</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $W->inputs->DATA_ATTO_1->display(); ?>
	      </div>

	      <label for="<?=$i->DATA_ATTO_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $W->inputs->DATA_ATTO_2->display(); ?>
	      </div>
	  </div>
      </div>

      <div class="form-group">
	  <div class="row">
	      <div class="col-lg-12">  
		  <input type="submit" class="btn btn-primary mt10" name='submit' value='Cerca'>
		  <input type="submit" class="btn btn-default mt10"  name='clear' value='Nuova Ricerca'>
	      </div>
	  </div>
      </div>
  </form>
<?
}
else {
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
	    <label for='<?=$i->OGGETTO->htmlName?>'>Oggetto</label>
        <? $i->OGGETTO->display() ?>
    </p>
    <p>  
	    <label for='<?=$i->NRATTO->htmlName?>'>Numero atto</label>
         <? $i->NRATTO->display() ?>
	</p>
	<p class='multi'> 
        <label for='<?=$i->DATA_ATTO_1->htmlName?>'>Data atto</label><? $W->inputs->DATA_ATTO_1->display(); ?>
        <label for='<?=$i->DATA_ATTO_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->DATA_ATTO_2->display(); ?>
	</p>

  <div style='clear: both'></div>
            <div class="rightalign">
    			<input type='submit' name='submit' value='Cerca' class="button">
    			<input type='submit' name='clear' value='Nuova Ricerca' class="button">     
            </div>
</form>
</div>
<?
}
?>

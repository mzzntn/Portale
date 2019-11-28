<?
$i = & $W->inputs;
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
  <form class="form-ricerca form-horizontal<?=$W->config['hidable']?" form-hidable":""?> col-lg-12 col-md-12 col-sm-12 col-xs-12" action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form'>
    <input type="hidden" name="<?=str_replace("search","table",$D->name)?>[page]" value="1">
    <input type="hidden" name="<?=str_replace("search","table",$D->name)?>[start]" value="0">

      <div class="row">
	  <div class="col-lg-6 col-md-6 col-sm-10 col-xs-12">
	      <!--<div class="alert alert-danger">Errore</div>-->
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      
	      <label for="<?=$i->settore->htmlName?>" class="sr-only">Settore</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Settore</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->settore->display(); ?>
	      </div>
	      
	      <label for="<?=$i->nome->htmlName?>" class="sr-only">Descrizione</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Descrizione</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->nome->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      
	      <label for="<?=$i->rilevazioni->inputs->periodo->htmlName?>" class="sr-only">Periodo</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Periodo</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->rilevazioni->inputs->periodo->display() ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <div class="col-lg-12 buttons-row">  
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
    <label for="<?=$i->settore->htmlName?>" >Settore: </label>
     <? $i->settore->display() ?>
  </p>

  <p>
    <label for="<?=$i->nome->htmlName?>">Descrizione: </label>
     <? $i->nome->display() ?>
  </p>

  <p>
    <label for="<?=$i->rilevazioni->inputs->periodo->htmlName?>" >Periodo: </label>
     <? $i->rilevazioni->inputs->periodo->display() ?>
  </p>

  <div style='clear: both'></div>
            <div class="rightalign">
    		        <input type='submit' name='submit' value='Cerca' class="button">
    			      <input type='submit' name='clear' value='Nuova Ricerca' class="button">
            </div>
</form>
</div>
<? } ?>

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
	      <label for="<?=$i->tipo->htmlName?>" class="sr-only">Tipo</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipo</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->tipo->display(); ?>
	      </div>
	      
	      <label for="<?=$i->cod2->htmlName?>" class="sr-only">Numero</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Numero</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->cod2->display(); ?>
	      </div>
	      
	      <label for="<?=$i->cod3->htmlName?>" class="sr-only">Anno</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Anno</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->cod3->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->descrizione->htmlName?>" class="sr-only">Oggetto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Oggetto</label>
	      <div class="col-lg-10 col-md-10 col-sm-10">
		  <? $i->descrizione->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      
	      <label for="<?=$i->inizio_1->htmlName?>" class="sr-only">Pubblicazione</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Pubblicazione</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->inizio_1->display(); ?>
	      </div>
	      
	      <label for="<?=$i->inizio_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->inizio_2->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      
	      <label for="<?=$i->iter->inputs->data_1->htmlName?>" class="sr-only">Scadenza</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Scadenza</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->iter->inputs->data_1->display(); ?>
	      </div>
	      
	      <label for="<?=$i->iter->inputs->data_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->iter->inputs->data_2->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->ufficio->htmlName?>" class="sr-only">Ufficio</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Ufficio</label>
	      <div class="col-lg-10 col-md-10 col-sm-10">
		  <? $i->ufficio->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <div class="col-lg-12 buttons-row">  
		  <input type="submit" class="btn btn-primary mt10" name='submit' value='Cerca'>
		  <input type="submit" class="btn btn-default mt10"  name='clear' value='Nuova Ricerca'>
		  <input type="submit" class="btn btn-default mt10"  name='oggi' value='Pubblicate Oggi'>
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
   <p class='multi'>
    <label>Pratica:</label>
    <label for='<?=$i->tipo->htmlName?>' class='inline'>Tipo:</label>
    <? $i->tipo->display() ?>
    <label for='<?=$i->cod2->htmlName?>' class='inline'>Numero:</label>
    <? $i->cod2->display('size=3') ?>
    <label for='<?=$i->cod3->htmlName?>' class='inline'>Anno:</label>
    <? $i->cod3->display() ?>
  </p>
  <p><label for='<?=$i->descrizione->htmlName?>'>Oggetto</label><? $i->descrizione->display() ?></p>
  <p class='multi'>  
    <label for='<?=$i->inizio_1->htmlName?>'>Data Pubblicazione</label><? $W->inputs->inizio_1->display(); ?>
    <label for='<?=$i->inizio_2->htmlName?>' class='inline'>fino a</label><? $W->inputs->inizio_2->display(); ?>
  </p>
    <p class='multi'>  
    <label for='<?=$i->inizio->htmlName?>'>Data Scadenza</label><? $W->inputs->iter->inputs->data_1->display(); ?>
    <label for='<?=$i->inizio->htmlName?>' class='inline'>fino a</label> <? $W->inputs->iter->inputs->data_2->display(); ?>
  </p>
  <p>
    <label for='<?=$i->ufficio->htmlName?>'>Ufficio</label>
    <label class='inline'><? $i->ufficio->display(); ?></label>
  </p>

  <div style='clear: both'></div>
     <div class="rightalign">
        <input type='submit' name='submit' value='Cerca' class="button">
    	<input type='submit' name='clear' value='Nuova Ricerca' class="button">
        <input type='submit' name='oggi' value='Pubblicate Oggi' class="button">           
      </div>
</form>
</div>
<?}?>

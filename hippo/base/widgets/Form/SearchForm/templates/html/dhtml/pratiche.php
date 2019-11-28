<?
$i = & $W->inputs;
if(isset($C['style']) && $C['style']=="2016") { // nuova graficas
?>
  <form class="form-ricerca form-horizontal <?=$W->config['hidable']?" form-hidable":""?> col-lg-12 col-md-12 col-sm-12 col-xs-12" action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form'>
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
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->tipo->display(); ?>
	      </div>
	      
	      <label for="<?=$i->cod2->htmlName?>" class="sr-only">Numero</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Numero</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->cod2->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->cod3->htmlName?>" class="sr-only">Anno</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Anno</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->cod3->display(); ?>
	      </div>
	      
	      <label for="<?=$i->inizio_1->htmlName?>" class="sr-only">Data</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->inizio_1->display(); ?>
	      </div>
	      
	      <label for="<?=$i->inizio_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->inizio_2->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->descrizione->htmlName?>" class="sr-only">Oggetto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Oggetto</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->descrizione->display(); ?>
	      </div>
	      
	      <label for="<?=$i->referenti->inputs->nome->htmlName?>" class="sr-only">Referente</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Referente</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->referenti->inputs->nome->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->indirizzi->inputs->via->htmlName?>" class="sr-only">Indirizzo</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Indirizzo</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->indirizzi->inputs->via->display(); ?>
	      </div>
	      
	      <label for="<?=$i->catasto->inputs->tipo->htmlName?>" class="sr-only">Catasto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Catasto</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->catasto->inputs->tipo->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->catasto->inputs->sez->htmlName?>" class="sr-only">Sezione</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Sezione</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->catasto->inputs->sez->display(); ?>
	      </div>
	      
	      <label for="<?=$i->catasto->inputs->fg->htmlName?>" class="sr-only">Foglio</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Foglio</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->catasto->inputs->fg->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">	      
	      <label for="<?=$i->catasto->inputs->part->htmlName?>" class="sr-only">Mappale</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Mappale</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->catasto->inputs->part->display(); ?>
	      </div>
	      
	      <label for="<?=$i->catasto->inputs->sub->htmlName?>" class="sr-only">Sub</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Sub</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->catasto->inputs->sub->display(); ?>
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
  form = getObj('<?=$D->name?>_form');
  a = getObj('<?=$D->name?>_toggleLink');
  if (form.style.display == 'none'){
    form.style.display = 'inline';
    a.innerHTML = 'Nascondi maschera di ricerca';
  }
  else{
    form.style.display = 'none';
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
   <p class='multi'>
    <label>Pratica:</label> 
    <label for='<?=$i->tipo->htmlName?>' class='inline'>Tipo:</label>
    <? $i->tipo->display() ?>
    <label for='<?=$i->cod2->htmlName?>' class='inline'>Numero:</label>
    <? $i->cod2->display('size=3') ?>
    <label for='<?=$i->cod3->htmlName?>' class='inline'>Anno:</label>
    <? $i->cod3->display() ?>
  </p>

  <p class='multi'>  
    <label for='<?=$i->inizio_1->htmlName?>'>Data</label><? $W->inputs->inizio_1->display(); ?>
    <label for='<?=$i->inizio_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->inizio_2->display(); ?>
  </p>
  <p>
    <label for="<?=$i->descrizione->htmlName?>">Oggetto</label>
     <? $i->descrizione->display() ?>
  </p>

  <p>
	  <label for='<?=$i->referenti->inputs->nome->htmlName?>'>Referente</label>
	  <? $i->referenti->inputs->nome->display() ?>
  </p>
	<p>
    <label for='<?=$i->indirizzi->inputs->via->htmlName?>'>Indirizzo</label>
	  <? $i->indirizzi->inputs->via->display() ?>
  </p>
  <p class='multi'>
	      <label >Catasto:</label><br>
	      <label for='<?=$i->catasto->inputs->tipo>htmlName?>' class='inline'>Tipo:</label>
		    <? $i->catasto->inputs->tipo->display('size=1') ?>
	      <label for='<?=$i->catasto->inputs->sez>htmlName?>' class='inline'>Sezione:</label>
		    <? $i->catasto->inputs->sez->display('size=9') ?>
		    <label for='<?=$i->catasto->inputs->fg->htmlName?>' class='inline'>Foglio:</label>
		    <? $i->catasto->inputs->fg->display('size=3') ?>
		    <label for='<?=$i->catasto->inputs->part>htmlName?>' class='inline'>Mappale:</label>
		    <? $i->catasto->inputs->part->display('size=3') ?>
		    <label for='<?=$i->catasto->inputs->sub->htmlName?>' class='inline'>Sub:</label>
		    <? $i->catasto->inputs->sub->display('size=1') ?>
  </p>
  
    <div style='clear: both'></div>
    <div class="buttons">
        <input class="button" type='submit' name='submit' value='Cerca'>
        <input class="button" type='submit' name='clear' value = 'Nuova Ricerca'>   
    </div>
</form>
<?
}
?>

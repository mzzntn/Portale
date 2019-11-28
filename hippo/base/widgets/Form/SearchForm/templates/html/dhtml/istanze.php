<?
$i = & $W->inputs;
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
// echo "<pre>".print_r($W->disabled,true)."</pre>";
$stati = array(
  'incompilazione' => 'In compilazione', 
  'attesafirma' => 'Attesa firma', 
  'dascaricare' => 'Inviate ma non scaricate', 
  'scaricate' => 'Scaricata ma non acquisite', 
  'acquisite' => 'Acquisite'
);
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
	      <label for="<?=$i->tipologia->htmlName?>" class="sr-only">Tipologia Pratica</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipologia Pratica</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? if($i->tipologia) $i->tipologia->display(); ?>
	      </div>
	      
	      <label for="<?=$i->id->htmlName?>" class="sr-only">Numero</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Numero</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? if($i->id) $i->id->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->dataInizio_1->htmlName?>" class="sr-only">Data</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? if($i->dataInizio_1) $i->dataInizio_1->display(); ?>
	      </div>
	      
	      <label for="<?=$i->dataInizio_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? if($i->dataInizio_2) $i->dataInizio_2->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->descrizione->htmlName?>" class="sr-only">Descrizione</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Descrizione</label>
	      <div class="col-lg-10 col-md-10 col-sm-10">
		  <? if($i->descrizione) $i->descrizione->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">	      
	      <label for="<?=$i->utenteDescrizione->htmlName?>" class="sr-only">Presentatore</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Presentatore</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? if($i->utenteDescrizione) $i->utenteDescrizione->display(); ?>
	      </div>  
	      
	      <label for="search_istanze_stato" class="sr-only">Stato</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Stato</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
            <select name="stato" id='search_istanze_stato' name='search_istanze[stato]' class='form-control'>
              <option value=""></option>
              <? 
              foreach($stati as $cod => $desc){ 
              ?>
              <option value="<?=$cod?>"<?=$cod == $_SESSION['widgets']['search_istanze[stato]']?" selected":""?>><?=$desc?></option>
              <? } ?>
            </select>
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
    <label>Tipologia:</label> 
    <label for='<?=$i->tipologia->htmlName?>' class='inline'>Tipologia:</label>
    <? $i->tipologia->display() ?>
    <label for='<?=$i->id->htmlName?>' class='inline'>Numero:</label>
    <? $i->id->display() ?>
  </p>

  <p class='multi'>  
    <label for='<?=$i->dataInizio_1->htmlName?>'>Data</label><? $W->inputs->dataInizio_1->display(); ?>
    <label for='<?=$i->dataInizio_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->dataInizio_2->display(); ?>
  </p>
  <p>
    <label for="<?=$i->descrizione->htmlName?>">Descrizione</label>
     <? $i->descrizione->display() ?>
  </p>
  
  <p class='multi'>  
	  <label for='<?=$i->utenteDescrizione->htmlName?>'>Presentatore</label>
	  <? $i->utenteDescrizione->display() ?>
	  <label for='<?=$i->utenteDescrizione->htmlName?>'>Stato</label>
        <select name="stato" id='search_istanze_stato' name='search_istanze[stato]' class='form-control'>
          <option value=""></option>
          <? 
          foreach($stati as $cod => $desc){ 
          ?>
          <option value="<?=$cod?>"<?=$cod == $_SESSION['widgets']['search_istanze[stato]']?" selected":""?>><?=$desc?></option>
          <? } ?>
        </select>
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

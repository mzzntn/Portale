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
	      <label for="<?=$i->provvedimenti->inputs->tipo->htmlName?>" class="sr-only">Tipo</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipo</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->provvedimenti->inputs->tipo->display(); ?>
	      </div>
	      
	      <label for="<?=$i->provvedimenti->inputs->num3->htmlName?>" class="sr-only">Anno</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Anno</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->provvedimenti->inputs->num3->display(); ?>
	      </div>
	      
	      <label for="<?=$i->provvedimenti->inputs->num2->htmlName?>" class="sr-only">Numero</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Numero</label>
	      <div class="col-lg-2 col-md-2 col-sm-2">
		  <? $i->provvedimenti->inputs->num2->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <label for="<?=$i->descrizione->htmlName?>" class="sr-only">Descrizione</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Descrizione</label>
	      <div class="col-lg-10 col-md-10 col-sm-10">
		  <? $i->descrizione->display(); ?>
	      </div>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      
	      <label for="<?=$i->provvedimenti->inputs->data->htmlName?>" class="sr-only">Data</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->provvedimenti->inputs->data->display(); ?>
	      </div>
	      
	      <!--<label for="<?=$i->inizio_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->inizio_2->display(); ?>
	      </div>-->
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
              <label for="<?=$i->ufficio->htmlName?>" class="sr-only">Ufficio</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Ufficio</label>
              <div class="col-lg-10 col-md-10 col-sm-10">

<?
		if($W->config['uffici_definiti']){
                ?>
                    <select name="search_delibere[ufficio]" class="form-control" id="search_delibere_ufficio">
                    <option value=""></option>
                <?
                    $uffici = $W->config['uffici_definiti'];
                    while($uffici->moveNext()) {
                        $idUfficio = $uffici->get('id');
                        $selected = $i->ufficio->selectedValues[$idUfficio] ? ' SELECTED' : '';
                ?>
                        <option value="<?=$uffici->get('id')?>"<?=$selected?>><?=$uffici->get('nome')?></option>
                <?
                    }
		?></select><?
                  }
		else{
		   $i->ufficio->display();
		}?>
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
             <label for='<?=$i->provvedimenti->inputs->tipo->htmlName?>'>Tipo provvedimento</label><? $i->provvedimenti->inputs->tipo->display() ?>
	         <label for='<?=$i->provvedimenti->inputs->num3->htmlName?>' class='inline'>Anno</label><? $i->provvedimenti->inputs->num3->display() ?>
        </p>
        <p>
            <label for='<?=$i->provvedimenti->inputs->num2->htmlName?>'>Numero provvedimento</label>
<?
$i->provvedimenti->prepareDisplayer();
$i->provvedimenti->displayer->printScript();
$i->provvedimenti->displayer->displayInput('num2');
?>                
            </p>
            <p><label for='<?=$i->descrizione->htmlName?>'>Oggetto</label><? $i->descrizione->display() ?></p>
            <p><label for='<?=$i->provvedimenti->inputs->data->htmlName?>'>Data Provvedimento</label><?$i->provvedimenti->displayer->displayInput('data');?></p>
            <p><label for='<?=$i->ufficio->htmlName?>'>Ufficio</label><? $i->ufficio->display(); ?></p>
            <div style='clear: both'></div>
            <div class="rightalign">
    		        <input type='submit' name='submit' value='Cerca' class="button">
    			      <input type='submit' name='clear' value='Nuova Ricerca' class="button">            
            </div>
        </form>    
<? } ?>

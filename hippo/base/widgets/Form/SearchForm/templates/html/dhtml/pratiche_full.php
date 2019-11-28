<?
$i = & $W->inputs;
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica

?>
<form class="form-ricerca form-horizontal col-lg-12 col-md-12 col-sm-12 col-xs-12" action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form' <?
    if ($W->config['enctype']) print "enctype='".$W->config['enctype']."' ";?>>
<h4><?=$titolo?></h4>
    <input type='hidden' name='<?=$W->name?>[id]' value='<?=$W->id?>'>
      <div class="row">
          <div class="col-lg-6 col-md-6 col-sm-10 col-xs-12">
              <!--<div class="alert alert-danger">Errore</div>-->
          </div>
      </div>

      <div class="form-group">
        <div class="row">
     
     
<?
if ($_SESSION['trasparenza']){

?>

          <label for="search_pratiche_provvedimenti_tipo" class="sr-only">Tipo</label>
          <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipo</label>
          <div class="col-lg-4 col-md-4 col-sm-4">
<?
		      if($W->config['provv_definiti']){
                $provv = $W->config['provv_definiti'];
                ?>
                    <select name="search_pratiche_provvedimenti_tipo" class="form-control" id="search_pratiche_provvedimenti_tipo">
                    <option value=""></option>
                <?
                    
                    foreach($provv as $id => $valore) {
                       $selected = ($id == $_SESSION[search_pratiche_provvedimenti_tipo]) ? ' SELECTED ' : '';
                ?>
                        <option value="<?=$id?>"<?=$selected?>><?=$valore?></option>
                <?
                    }
		?></select><?
                  }
		else{
		   $i->provvedimenti->inputs->tipo->display();
		}?>
	      </div>          

          <label for="<?=$i->provvedimenti->inputs->num2->htmlName?>" class="sr-only">Numero</label>
          <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Numero</label>
          <div class="col-lg-4 col-md-4 col-sm-4">
            <?=$i->provvedimenti->inputs->num2->display()?>  
          </div>
        </div>
      </div>

      <div class="form-group">
        <div class="row">
          <label for="<?=$i->provvedimenti->inputs->num3->  htmlName?>" class="sr-only">Anno</label>
          <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Anno</label>
          <div class="col-lg-4 col-md-4 col-sm-4">
            <?
          if($W->config['anni_definiti']){
               $anni = $W->config['anni_definiti'];
          ?>
                  <select name="search_pratiche[provvedimenti][num3]" class="form-control" id="search_pratiche[provvedimenti][num3]">
                  <option value=""></option>
          <?
                  foreach ($anni as $id => $valore) {
                    $selected = $i->provvedimenti->inputs->num3->selectedValues[$id] ? ' SELECTED' : '';
          ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$valore?></option>
                <?
                    }
		  ?></select><?
         } else{
   ?>   
              <?=$i->provvedimenti->inputs->num3->display()?>
<? } ?>
     </div>
     <label for="<?=$i->sezioni->inputs->sezione->htmlName?>" class="sr-only">Sezione Trasparenza</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Sezione Trasparenza</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
    <?
    if($W->config['sezioni_definite']){
               $sezioni = $W->config['sezioni_definite'];
          ?>
                  <select name="search_pratiche_sezioni_sezione" class="form-control" id="search_pratiche_sezioni_sezione">
                  <option value=""></option>
          <?  
                  foreach ($sezioni as $id => $valore) {
                    $selected = ($id == $_SESSION[search_pratiche_sezioni_sezione]) ? ' SELECTED ' : '';
          ?>
                    <option value="<?=$id?>"<?=$selected?>><?=$valore?></option>
                <?
                    }
		  ?></select><?
       }
       else $i->sezioni->inputs->sezione->display();
     ?>
	  </div>
	</div>
      </div>
<? 
}
else{
?>
	  <label for="<?=$i->tipo->htmlName?>" class="sr-only">Tipo</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipo</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
	      <?=$i->tipo->display()?>
	  </div>
	  <label for="<?=$i->cod2->htmlName?>" class="sr-only">Numero</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Numero</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
	      <?=$i->cod2->display()?>
	  </div>
	</div>
      </div>
      
      <div class="form-group">
	<div class="row">  
	  <label for="<?=$i->cod3->htmlName?>" class="sr-only">Anno</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Anno</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
	      <?=$i->cod3->display()?>
	  </div>
     	  <label for="<?=$i->codEstr->htmlName?>" class="sr-only">Codice estrazione</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Codice estrazione</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
	      <?=$i->codEstr->display()?>
	  </div>
	</div>
</div>
<?
}
?>

      
      <div class="form-group">
	<div class="row">  
	  <label for="<?=$i->inizio_1->htmlName?>" class="sr-only">Data</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
	      <?=$i->inizio_1->display()?>
	  </div>
	  <label for="<?=$i->inizio_2->htmlName?>" class="sr-only">fino a</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	  <div class="col-lg-4 col-md-4 col-sm-4">
	      <?=$i->inizio_2->display()?>
	  </div>
	</div>
      </div>
      <div class="form-group">
	<div class="row">  
	  <label for="<?=$i->descrizione->htmlName?>" class="sr-only">Oggetto</label>
	  <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Oggetto</label>
	  <div class="col-lg-10 col-md-10 col-sm-10">
	      <?=$i->descrizione->display()?>
	  </div>
	</div>
      </div>

      <div class="form-group">
	  <div class="row">
	      <div class="col-lg-12">  
		  <input type="submit" class="btn btn-primary mt10" name='submit' value='Cerca'>
		  <input type='submit' class='btn btn-default mt10' name='clear' value='Nuova Ricerca' >
	      </div>
	  </div>
      </div>
  </form>     
	
<?
} else {
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
    <label for='<?=$i->tipo->htmlName?>' class='inline'>Codice Estrazione:</label>
       <? $i->codEstr->display() ?>
  </p>

  <p class='multi'>  
    <label for='<?=$i->inizio_1->htmlName?>'>Data</label><? $W->inputs->inizio_1->display(); ?>
    <label for='<?=$i->inizio_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->inizio_2->display(); ?>
  </p>
  <p>
    <label for="<?=$i->descrizione->htmlName?>">Oggetto</label>
     <? $i->descrizione->display() ?>
  </p>

    <div style='clear: both'></div>
    <div class="buttons">
        <input class="button" type='submit' name='submit' value='Cerca'>
        <input class="button" type='submit' name='clear' value = 'Nuova Ricerca'>   
    </div>
</form>
<? } ?>

<?
$i = & $W->inputs;
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
  <form class="form-ricerca form-horizontal<?=$W->config['hidable']?" form-hidable":""?> col-lg-12 col-md-12 col-sm-12 col-xs-12" action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form'>
      <div class="row">
	  <div class="col-lg-6 col-md-6 col-sm-10 col-xs-12">
	      <!--<div class="alert alert-danger">Errore</div>-->
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">	      
	      <label for="<?=$i->oggetto->htmlName?>" class="sr-only">Oggetto</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Oggetto</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->oggetto->display(); ?>
	      </div>
	      
	      <label for="<?=$i->richiedente->htmlName?>" class="sr-only">Richiedente</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Richiedente</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->richiedente->display(); ?>
	      </div>
	  </div>
      </div>
      
      <? 
      if (!$_SESSION['settore']){
      ?>
      <div class="form-group">
	  <div class="row">	      
	      <label for="<?=$i->settore->htmlName?>" class="sr-only">Settore</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Settore</label>
	      <div class="col-lg-10 col-md-10 col-sm-10">
		  <? $i->settore->display(); ?>
	      </div>     
	  </div>
      </div>
      <? } ?>
      
      <div class="form-group">
	  <div class="row">
	      
	      <label for="<?=$i->dtPrDomanda_1->htmlName?>" class="sr-only">Data Richiesta</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data Richiesta</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->dtPrDomanda_1->display(); ?>
	      </div>
	      
	      <label for="<?=$i->dtPrDomanda_2->htmlName?>" class="sr-only">fino a</label>
	      <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">fino a</label>
	      <div class="col-lg-4 col-md-4 col-sm-4">
		  <? $i->dtPrDomanda_2->display(); ?>
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
global $IMP;
if ($_SESSION['settore']){
	$loader = & $IMP->getLoader('benefici::settore');
	$loader->addParam('id', $_SESSION['settore']);
	$tb = $loader->load();
	$nomeSettore = $tb->get('nome');
	echo "<p><br>Settore: <strong>".$nomeSettore."</strong></p>";
}
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
            <p><label for='<?=$i->oggetto->htmlName?>'>Oggetto</label><? $i->oggetto->display(); ?></p>
            <p><label for='<?=$i->richiedente->htmlName?>'>Richiedente</label><? $i->richiedente->display(); ?></p>
<? 
if (!$_SESSION['settore']){
?>
            <p><label for='<?=$i->settore->htmlName?>'>Settore</label><? $i->settore->display(); ?></p>
<? } ?>
            <p><label for='<?=$i->dtPrDomanda_1->htmlName?>'>Data Richiesta</label><? $W->inputs->dtPrDomanda_1->display(); ?>
        <label for='<?=$i->dtPrDomanda_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->dtPrDomanda_2->display(); ?></p>
            <div style='clear: both'></div>
            <div class="rightalign">
                <input type='submit' name='submit' value='Cerca' class="button">
                <input type='submit' name='clear' value='Nuova Ricerca' class="button">            
            </div>
        </form>    
<?
}
?> 


<?
$D->loadJs('divControls');
if(isset($C['style']) && $C['style']=="2016") {
$i = & $W->inputs;
?>
<form class="form-ricerca form-horizontal col-lg-12 col-md-12 col-sm-12 col-xs-12" action='<?=$_SERVER['PHP_SELF']?>' method='POST' id='<?=$D->name?>_form'>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-10 col-xs-12">
                    <!--<div class="alert alert-danger">Errore</div>-->
                </div>
            </div>

             <div class="form-group">

              <label for="<?=$i->operatore->htmlName?>" class="sr-only">Operatore</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Operatore</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
		<? $i->operatore->display(); ?>
              </div>


             <label for="<?=$i->stato->htmlName?>" class="sr-only">Stato</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Stato</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
		<? $i->stato->display(); ?>
              </div>
            </div>
            <div class="form-group">

              <label for="<?=$i->inizio_1->htmlName?>" class="sr-only">Data da</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Data da</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
		<? $i->inizio_1->display(); ?>
              </div>

              <label for="<?=$i->inizio_2->htmlName?>" class="sr-only">a</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">a</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
		<? $i->inizio_2->display(); ?>
              </div>

            </div>

           <div class="form-group">

              <label for="<?=$i->tipo->htmlName?>" class="sr-only">Tipologia</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipologia</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
		<? $i->tipo->display(); ?>
              </div>

              <label for="<?=$i->persona->htmlName?>" class="sr-only">Persona</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Persona</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
		<? $i->persona->display(); ?>
              </div>

           </div>

           <div class="form-group">

              <label for="<?=$i->note->htmlName?>" class="sr-only">Note</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Note</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
                <? $i->note->display(); ?>
              </div>

              <label for="<?=$i->noteUfficio->htmlName?>" class="sr-only">Note ufficio</label>
              <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Note ufficio</label>
              <div class="col-lg-4 col-md-4 col-sm-4">
                <? $i->noteUfficio->display(); ?>
              </div>

           </div>

           <div class="bottoni_pagina">
              <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <input type='submit' class='btn btn-default' name='submit' value='Cerca'>
		  <input type='submit' name='clear' value='Nuova Ricerca' class="btn btn-default">
                </div>
              </div>
            </div>

	</form>
<?
} else {
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
            <p><label for='<?=$i->operatore->htmlName?>'>Operatore</label><? $i->operatore->display(); ?></p>
  <p><label for='<?=$i->stato->htmlName?>'>Stato</label><? $i->stato->display(); ?></p>

            <p><label for='<?=$i->inizio->htmlName?>'>Data</label><? $W->inputs->inizio_1->display(); ?>
        <label for='<?=$i->inizio_2->htmlName?>' class='inline'>fino a</label> <? $W->inputs->inizio_2->display(); ?></p>
            <p><label for='<?=$i->tipo->htmlName?>'>Tipologia</label><? $i->tipo->display(); ?></p>
            <p><label for='<?=$i->persona->htmlName?>'>Persona</label><? $i->persona->display(); ?></p>
            <div style='clear: both'></div>
            <div class="rightalign">
                <input type='submit' name='submit' value='Cerca' class="button">
                <input type='submit' name='clear' value='Nuova Ricerca' class="button">            
            </div>
        </form>    
<? } ?>

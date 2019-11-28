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
	  <?
	  $counter = 0;
	  foreach ($W->inputsOrder as $inputName){
	    $counter++;
	    ?>
	    <label for="<?=$D->name.'_'.$inputName?>" class="sr-only"><?=$W->labels[$inputName]?></label>
	    <label class="col-lg-2 col-md-2 col-sm-2 control-label-left"><?=$W->labels[$inputName]?></label>
	    <div class="col-lg-4 col-md-4 col-sm-4">
		<?$W->inputs->$inputName->display();?>
	    </div>
	    <?
	    if($counter%2==0) {
	      ?>
	  </div>
      </div>
      
      <div class="form-group">
	  <div class="row">
	      <?
	    }
	  }
	  ?>
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
var $simpleSearch = false;
var simpleSearchText = "";
$(document).ready(function(){
  if($("input[name*='_simple']").length>0) {
    $simpleSearch = $("input[name*='_simple']");
  }
});

function <?=$D->name?>_submit(){
  var form = getObj('<?=$D->name?>_form');
  var query = buildQueryString(form);
  if (query) query += "&";
  query += "target=<?=$W->config['table']?>&<?=$W->name?>[start]=1";
  xmlHttpQuery('<?=$_SERVER['PHP_SELF']?>', query, 'POST');
  <?
  if ($W->config['table']){
    $dTable = $W->fixForHtml($W->config['table']);
  ?>
    try{
      if (<?=$dTable?>_searchFormQuery) <?=$dTable?>_searchFormQuery();
      if (<?=$dTable?>_startQuery) <?=$dTable?>_startQuery();
    }
    catch (exc){
    }
  <?
  }
  ?>
}

function <?=$D->name?>_clear(){
  var form = getObj('<?=$D->name?>_form');
  for(i=0;i<form.elements.length;i++){
    var el = form.elements[i];
    if(el.type == 'text'  || el.type == 'textarea') el.value = '';
    else if (el.type == 'checkbox' || el.type == 'radio') el.checked = false;
    else if(el.selectedIndex != undefined && el.selectedIndex != -1){
       for(j=0;j<el.options.length;j++) el.options[j].selected = false;
    }
  } 
}

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
  var a = getObj('<?=$D->name?>_toggleLink');
  var i = getObj('<?=$D->name?>_simpleInput');
  if (table.style.display == 'none'){
    table.style.display = 'inline';
    a.innerHTML = 'Ricerca semplice';
    i.style.display = 'none';
    if($simpleSearch) { simpleSearchText = $simpleSearch.val(); $simpleSearch.val(""); }
  }
  else{
    table.style.display = 'none';
    i.style.display = '';
    a.innerHTML = 'Ricerca avanzata';
    if($simpleSearch) { $simpleSearch.val(simpleSearchText); }
  }
}


<?
$display = 'none';
}
else $display = 'inline';
?>
</script>
<?
if (!$W->inline){
?>
<!--
non faccio la ricerca ajax perchè devo aggiornare il paginatore
<form id='<?=$D->name?>_form' action='<?=$_SERVER['PHP_SELF']?>' onsubmit='<?=$D->name?>_submit(); return false;' method='POST'>-->
<form id='<?=$D->name?>_form' action='<?=$_SERVER['PHP_SELF']?>' method='POST'>
  <!-- resetto il paginatore -->
  <input type='hidden' name='<?=str_replace("search_","table_",$W->name)?>[start]' value='1'>
<?
}
?>
<div id="<?=$D->name?>_table" style='display: <?=$display?>'>
<?
foreach ($W->inputsOrder as $inputName){
?>
    <p>
        <label for="<?=$D->name.'_'.$inputName?>"><?=$W->labels[$inputName]?></label>
        <span id="<?=$D->name?>_<?=$inputName?>_i"><?$W->inputs->$inputName->display();?></span>
        <script type="text/javascript">
        var div = getObj('<?=$D->name?>_<?=$inputName?>_i');
        makeCool(div);
        <?=$D->name?>_divs['<?=$inputName?>_i'] = div;
        <?
        if (!$W->inputs->$inputName->value && ($W->inputs->{$inputName.'_1'}->value || $W->inputs->{$inputName.'_2'}->value) ){
        ?>
        div.remove();
        <?
        }
        ?>
        </script>
        <?
        if ($W->rangeInputs[$inputName]){
        ?>
        <a href='javascript: return false' onClick="<?=$D->name?>_switchDivs(this, '<?=$inputName?>')">+</a>
        <span id='<?=$D->name?>_<?=$inputName?>_r'>
            da: <? $W->inputs->{$inputName.'_1'}->display(); ?> a: <? $W->inputs->{$inputName.'_2'}->display(); ?>
        </span>
        <script type="text/javascript">
        var div = getObj('<?=$D->name?>_<?=$inputName?>_r');
        makeCool(div);
        <?=$D->name?>_divs['<?=$inputName?>_r'] = div;
        <?
        if ($W->inputs->$inputName->value || (!$W->inputs->{$inputName.'_1'}->value && !$W->inputs->{$inputName.'_2'}->value) ){
        ?>
        div.remove();
        <?
        }
        ?>
        </script>
        <?
        }
        ?>
    </p>

<?
}
if (!$W->inline){
?>
    <div class="buttons">
            <input type='submit' class="button" name='<?=$W->name?>[find]' value='Cerca'>
            <input type='submit' onclick='<?=$D->name?>_clear()' class="button" name='azzera' value='Azzera'>
    </div>
<?
}
?>
</div>
<?
if ($W->config['hidable']){
?>
    <div id='<?=$D->name?>_simpleInput'>
            <!--<p>
                <label>Cerca</label>-->
                <input type='text' name='<?=$W->name?>[_simple]' value='<?=$W->getParam('_simple')?>'>
                <?
                if (!$W->inline){
                ?>
                <input type='submit' name='vai' value='Trova' class="button">
                <input type='submit' onclick='<?=$D->name?>_clear()' name='azzera' value='Pulisci' class="button">
                <?
                }
                ?>                
            <!--</p>-->
    </div>
<?
}
?>
<?if(!$W->inline){?>
</form>
<?}?>

<?
if ($W->config['hidable']){
?>   
<a href='javascript: <?=$D->name?>_toggle()' id='<?=$D->name?>_toggleLink' class='highlight'>Ricerca avanzata</a><br><br>
<?
}

}
?>

<?

class d_SearchForm_html_dhtml extends Displayer_dhtml{

  function printScript(){
    $W = & $this->w;
    $D = & $this;
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
$display = 'none';
}
else $display = '';
?>
</script>    
<?
  }


  function displayInput($inputName){
    $W = & $this->w;
    $D = & $this;
?>
<span id='<?=$D->name?>_<?=$inputName?>_i'>
<?
    $W->inputs->$inputName->display();
?>
</span>
<script>
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
<a href='#' onClick="<?=$D->name?>_switchDivs(this, '<?=$inputName?>');return false;">+</a>
<span id='<?=$D->name?>_<?=$inputName?>_r'>
da: 
<? $W->inputs->{$inputName.'_1'}->display(); ?>
a: 
<? $W->inputs->{$inputName.'_2'}->display(); ?>
</span>
<script>
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
  }
  
  
}

?>

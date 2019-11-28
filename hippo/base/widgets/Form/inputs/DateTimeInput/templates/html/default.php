<?
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
  // type="date" non si può usare perchè chrome converte il campo in un datepicker, ma se il formato data del sistema è diverso da quello usato da php, non funziona il valore di default
  echo '<input name="'.$W->name.'"  value="'.htmlspecialchars($W->value).'" size="10" class="form-control datepicker" id="'.$D->name.'">';
} 
else { // vecchia grafica
  if ($W->readOnly){
    print "<div class='".$D->getCSSClass('readOnlyText')."'>";
    print $W->value;
    print "</div>";
    return;
  }
  $D->loadJs('calendar');
  ?>
  <input type='text' id='<?=$D->name?>' class='<?=$D->getCSSClass()?>' name='<?=$W->name?>' value='<?=htmlspecialchars($W->value)?>' size='<?=$W->config['size']?>'><img src='<?=URL_IMG?>/calendar.gif' onclick='<?=$D->name?>_showCal(this)' style='cursor: pointer' alt="Mostra calendario">
  <script type="text/javascript">
  var <?=$D->name?>_calendarDiv;

  function <?=$D->name?>_showCal(srcObj){
    normalizeDiv(srcObj);
    if (<?=$D->name?>_calendarDiv && <?=$D->name?>_calendarDiv.parentNode){
      <?=$D->name?>_calendarDiv.remove();
      <?=$D->name?>_calendarDiv = null;
      return;
    }
    else if (calendarDiv && calendarDiv.parentNode) calendarDiv.remove();
    <?=$D->name?>_calendarDiv = document.createElement('div');
    calendarDiv = <?=$D->name?>_calendarDiv;
    document.body.appendChild(<?=$D->name?>_calendarDiv);
    makeCool(<?=$D->name?>_calendarDiv);
    cal = new Cal('Calendar_<?=$D->name?>', <?=$D->name?>_calendarDiv);
    cal.build();
    cal.config.action = <?=$D->name?>_dayClick;
    getWindowSize();
    var destX, destY;
    <?=$D->name?>_calendarDiv.moveTo(srcObj.x, srcObj.y);
    var width = <?=$D->name?>_calendarDiv.offsetWidth;
    var height = <?=$D->name?>_calendarDiv.offsetHeight;
    var destX = srcObj.x + 20;
    var destY = srcObj.y + 20;
    if (destX+calendarDiv.offsetWidth > window.width) destX -= calendarDiv.width;
    if (destY+calendarDiv.offsetHeight > window.height) destY -= calendarDiv.height;
    <?=$D->name?>_calendarDiv.moveTo(destX, destY);
    return false;
  }

  function <?=$D->name?>_dayClick(day){
    getObj('<?=$D->name?>').value = day;
    <?=$D->name?>_calendarDiv.remove();
    <?=$D->name?>_calendarDiv = null;
  }
  </script>
<?
}
?>

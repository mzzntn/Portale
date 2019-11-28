<?
  $D->printMenu();
?>
<script type="text/javascript">
function <?=$D->name?>_tog(id){
  div = getObj(id);
  if (div.menuShown){
    div.menuShown = 0;
    newDisplay = 'none';
  }
  else{
    div.menuShown = 1;
    newDisplay = '';
  }
  for (var i=0; i<div.childNodes.length; i++){
    if (div.childNodes[i].tagName && div.childNodes[i].tagName.toLowerCase() == 'div'){
      div.childNodes[i].style.display = newDisplay;
    }
  }
}
</script>

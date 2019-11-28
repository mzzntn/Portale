<?
$D->loadJs('divControls');
$data = $W->data;
if (!is_array($data)) $data = array($data);
?>
<span id='<?=$D->name?>_container'>
<?
ob_start();
foreach ($W->inputsOrder as $inputName){
  $W->inputs->$inputName->display();
}
$proto = ob_get_clean();
print "<script type='text/javascript'>var ".$D->name."_protoHTML='".str_replace("'", "\\'", $proto)."';</script>";
foreach ($data as $i => $row){
  $fieldCnt=0;
  $inputs = '';
  print "<span id='{$D->name}_{$i}' style='white-space: nowrap'>";
  foreach ($W->inputsOrder as $inputName){
    $W->inputs->$inputName->setValue($row->$inputName);
?>
<?=$W->inputs->$inputName->display()?>
<?
    #if ($fieldCnt == 0) 
    #else print "<br>";
    $fieldCnt++;
}
  print "<a href='javascript: {$D->name}_removeField($i)'>-</a>";
  print "</span>";
}
?>
</span><br>
<a href='javascript: <?=$D->name?>_addField()'>Aggiungi</a>
<script type="text_javascript">
var <?=$D->name?>_numRows = <?=$i?>;
function <?=$D->name?>_addField(){
  <?=$D->name?>_numRows++;
  var div = document.createElement('span');
  div.style.whiteSpace = 'nowrap';
  div.id = "<?=$D->name?>_"+<?=$D->name?>_numRows;
  div.innerHTML = ''+<?=$D->name?>_protoHTML;
  <?=$D->name?>_renameFields(div, <?=$D->name?>_numRows, true);
  var a = document.createElement('a');
  a.innerHTML = '[-]';
  a.href = 'javascript: <?=$D->name?>_removeField('+<?=$D->name?>_numRows+')';
  div.appendChild(a);
  getObj('<?=$D->name?>_container').appendChild(div);
}
function <?=$D->name?>_removeField(n){
  getObj('<?=$D->name?>_container').removeChild(getObj('<?=$D->name?>_'+n));
  for (var i=n+1; i<=<?=$D->name?>_numRows; i++){
    var cur = getObj('<?=$D->name?>_'+i);
    <?=$D->name?>_renameFields(cur, i-1);
    cur.id = '<?=$D->name?>_'+(i-1);
  }
  <?=$D->name?>_numRows--;
  //if (<?=$D->name?>_numRows == 0) addField();
}
function <?=$D->name?>_renameFields(div, n, clear){
  for (var i=0; i<div.childNodes.length; i++){
    var child = div.childNodes[i];
    if (child.nodeType == 1 && (child.tagName.toLowerCase() == 'input' || child.tagName.toLowerCase() == 'select')){
      var wName = '<?=$W->name?>';
      wName = wName.replace(/\[/, '\\[');
      wName = wName.replace(/\]/, '\\]');
      idRegExp = /(.+?)(_\d+)?$/;
      nameRegExp = new RegExp(wName+"(?:\\[\\d+\\])?(.+)$");
      var matches;
      matches = idRegExp.exec(child.id);
      child.id = matches[1]+'_'+n;
      matches = nameRegExp.exec(child.name);
      child.name = '<?=$W->name?>['+n+']'+matches[1];
      if (clear){
        if (child.tagName.toLowerCase() == 'input') child.value = '';
      }
    }
  }
}
for (var i=0; i<=<?=$D->name?>_numRows; i++){
  <?=$D->name?>_renameFields(getObj('<?=$D->name?>_'+i), i);
}
</script>

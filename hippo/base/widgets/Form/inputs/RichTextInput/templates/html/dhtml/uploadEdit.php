<?
if ($this->w->readOnly){
  print "<div class='".$this->getCSSClass('readOnlyText')."'>";
  print $this->w->value;
  print "</div>";
}
else{
   $this->loadJs('divControls');
   $this->loadJs('myHTML/myHTML');
   $this->loadJs('comboBox');
   $jsVal = fixForJs($this->w->value);
   if ($this->w->value) $editString = 'modifica';
   else $editString = 'costruisci';
   $fileInputName = $this->w->parentWidget->htmlName.'['.$this->w->inputName.'_file]';
?>
<input type='hidden' id='<?=$this->name?>' name='<?=$this->w->name?>[content]' value=''>
Upload zip:<br> <input type='file' name='<?=$fileInputName?>'><br>
oppure <?=$editString?>:
<div id='<?=$this->name?>_div' class='<?=$this->getCSSClass('myHTMLDiv')?>'>
<div id='<?=$this->name?>_toolbar'></div>
<iframe src='' id='<?=$this->name?>_iframe'></iframe>
</div>
<script>
function <?=$this->name?>_edit(){
   getObj('<?=$this->name?>_div').style.display='inline';
}
var myHTML = new MyHTML('<?=$this->name?>_div', '<?=$this->name?>');
myHTML.setToolbar('<?=$this->name?>_toolbar');
myHTML.setIframe('<?=$this->name?>_iframe');
myHTML.baseUrl = '<?=URL_JS?>/myHTML';
myHTML.toolsUrl = '<?=HOME?>/tools';
myHTML.init();
myHTML.setValue('<?=$jsVal?>');
myHTML.className = '<?=$this->getCSSClass('myHTML')?>';
</script>
<?
   }
?>

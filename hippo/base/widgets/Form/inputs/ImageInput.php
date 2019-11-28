<?
/**
Options:
-imageDiv: <div> to use to display image (will display in place if none)
-

*/
class ImageInput extends BasicInput{
  var $options;
  var $value;
  var $fileName;
  var $div;
  var $url;
  

  function ImageInput($name){
    parent::BasicWidget($name);
    $this->options = array();
    $this->class = 'input.image';
    $this->fileName = & $this->value;
    $this->config['displayUrl'] = URL_WEBDATA.'/img/thumb';
  } 
  
  function display(){
    global $IMP;
    if ($this->displayMode != 'html' && $this->displayMode != 'html.dhtml') return;
    if ($this->options['size']){
      if ($this->options['size'] == 'micro') $this->url = URL_USR_IMG_MICRO;
      elseif ($this->options['size'] == 'thumb') $this->url = URL_USR_IMG_THUMB;
      elseif ($this->options['size'] == 'web') $this->url = URL_USR_IMG_WEB;
      elseif ($this->options['size'] == 'orig') $this->url = URL_USR_IMG_ORIG;
    }
    else $this->url = URL_WEBDATA.'/img/thumb';
    $IMP->loadJs('divControls');
    if ($this->options['imageDiv']) $this->div = $this->options['imageDiv'];
    else $this->div = $this->htmlName.'_img';
    if ($this->fileName) $text = "Cambia";
    else $text = "Scegli un'immagine...";
    $this->script();
    if (!$this->options['imageDiv']){
      print "<div id='{$this->div}'>";
      if ($this->fileName) print "<img src='{$this->url}/{$this->fileName}'>";
      print "</div>";
    }
    print " ";
    print "<input type='hidden' id='{$this->htmlName}' name='{$this->name}' value='{$this->value}'>";
    print "<a href='Javascript: popup_{$this->htmlName}()'>";
    print $text;
    print "</a>";
    print "&nbsp;&nbsp;&nbsp;&nbsp;";
    if ($this->fileName) print "<a id='{$this->htmlName}_clear' href='javascript: clear_{$this->htmlName}()'>Cancella</a>";
  }
  
  function script(){
    # begin javascript #
?>
<script type="text/javascript">
function clear_<?=$this->htmlName?>(){
  var div = getObj('<?=$this->div?>');
  var input = getObj('<?=$this->htmlName?>');
  input.value = '';
  div.innerHTML = '';
  var link = getObj('<?=$this->htmlName?>_clear');
  link.innerHTML = '';
}
  
function popup_<?=$this->htmlName?>(){
  window.callFunc = getImg_<?=$this->htmlName?>;
  url = "<?=TOOLS?>/imageUploader.php?callFunc=getImg_<?=$this->htmlName?>";
  msgWindow = window.open(url,"",'width=400,height=200');
  if (msgWindow.opener == null) msgWindow.opener = self;
}

function getImg_<?=$this->htmlName?>(val){
  var div = getObj('<?=$this->div?>');
  var input = getObj('<?=$this->htmlName?>');
  input.value = val;
  if (val.substr(0,1) != '/' && val.substr(0, 4) != 'http'){
    val = '<?=$this->config['displayUrl']?>/'+val;
  }
  div.innerHTML = "<img src='"+val+"' alt='.'/>";
}

</script>
<?
    # end js #
  }

  
}
  
  
  
?>

<?
  
class d_FormRegistrazione_html extends Displayer_html{
  var $submitText;
  var $closed;

  function start(){
    if ($this->w->id) $this->submitText = $this->w->config['modifyText'];
    else $this->submitText = $this->w->config['insertText']; #:leftoffs:
  } 
  
  function printCheckScript(){
?>
<script type="text/javascript">
function check_<?=$this->w->htmlName?>(){
  var emailRegExp = /\b(^(\S+@).+((\.com)|(\.net)|(\.edu)|(\.mil)|(\.gov)|(\.org)|(\..{2,2}))$)\b/gi;
  var dateRegExp = /(\d{1,2})\/(\d{1,2})\/(\d{4})\s*,?(?:\s+(\d+):(\d+)(?:\:(\d+))?)?/gi;
<?
    if (is_array($this->w->required)) foreach (array_keys($this->w->required) as $elementName){
      if (!$this->w->inputs->$elementName->readOnly){
        $fieldId = $this->w->inputs->$elementName->htmlName;
?>
  if (! document.getElementById("<?=$fieldId?>").value ){  
    alert("\"<?=$this->w->labels[$elementName]?>\" non può essere vuoto!");
    return false;
  }
<?
      }
    }
    $this->w->inputs->reset();
    while ($this->w->inputs->moveNext()){
      $elementName = $this->w->inputs->getName();
      $type = $this->w->struct->type($elementName);
      $fieldId = $this->w->inputs->$elementName->htmlName;
      if ($type == 'email'){
?>
  val = document.getElementById("<?=$fieldId?>").value;
  if (val && !val.match(emailRegExp) ){
    alert("\"<?=$this->w->labels[$elementName]?>\" deve contenere un indirizzo email valido");
    return false;
  }
<?
      }
      elseif ($type == 'dateTime'){
?>
  val = document.getElementById("<?=$fieldId?>").value;
  if (val && !val.match(dateRegExp) ){
    alert("\"<?=$this->w->labels[$elementName]?>\" deve contenere una data nel formato gg/mm/aaaa");
    return false;
  }
<?
      }       
    }
?>
  return true;
}
</script>
<?
  }

  function formStart($noManual=false){
    if (!$noManual) $this->manualOpen = true; //:KLUDGE!
    print "<form action='{$_SERVER['PHP_SELF']}' id='{$this->name}' method='POST' ";
    if ($this->w->config['enctype']) print "enctype='".$this->w->config['enctype']."' ";
    print "onSubmit='return check_{$this->w->htmlName}()'>";
    print "<div>";
  }

  function formEnd(){
  	if ($this->closed) return;
  	$this->closed = true;
  	print "</div>";
    print "</form>";
  }

}


?>

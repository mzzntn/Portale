<?
  
class d_UserForm_html extends Displayer_html{
  var $submitText;

  function start(){
    if ($this->w->id) $this->submitText = $this->w->config['modifyText'];
    else $this->submitText = $this->w->config['insertText']; #:leftoffs:
  } 
  
  function printCheckScript(){
?>
<script>
function check_<?=$this->w->htmlName?>(){
  var emailRegExp = /\b(^(\S+@).+((\.com)|(\.net)|(\.edu)|(\.mil)|(\.gov)|(\.org)|(\..{2,2}))$)\b/gi;
  var dateRegExp = /(\d+)\/(\d+)\/(\d+)\s*,?(?:\s+(\d+):(\d+)(?:\:(\d+))?)?/gi;
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
  val = document.getElementById("<?=fieldId?>").value;
  if (val && !val.match(dateRegExp) ){
    alert("\"<?=$this->w->labels[$elementName]?>\" deve contenere una data valida");
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

  function formStart(){
    $this->manualOpen = true;
    print "<form action='{$_SERVER['PHP_SELF']}' id='{$this->name}' method='POST' onSubmit='return check_{$this->w->htmlName}()'>";
  }

  function formEnd(){
    print "</form>";
  }

}


?>
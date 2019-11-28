<?
  
class d_Form_html extends Displayer_html{
  var $submitText;
  var $closed;

  function start(){
    if ($this->w->id) $this->submitText = $this->w->config['modifyText'];
    else $this->submitText = $this->w->config['insertText']; #:leftoffs:
  }
  
  function printScripts(){
      $this->printCheckScript(); 
  }
  
  function printEndScripts(){
      # no end scripts
  }
  
  function printCheckScript(){
?>
<script type='text/javascript'>
$( document ).ready(function() {
  $('#success-message').removeClass('hide');
  $('#success-message').hide();
  $('#<?=$this->w->htmlName?>').submit(function(event)
  {
      $('#success-message').hide();
      $('#success-message').html('');
      var emailRegExp = /\b(^(\S+@).+((\.com)|(\.net)|(\.edu)|(\.mil)|(\.gov)|(\.biz)|(\.org)|(\..{2,2}))$)\b/gi;
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
    //var action = event.originalEvent.explicitOriginalTarget.value;
    var action = document.activeElement.value;
    if((action=='Inserisci' || action == "Salva") && typeof FormData !== 'undefined') {
      <?
        $formAction = $_SERVER['PHP_SELF'];
        if(isset($_GET['context'])){
  	  foreach($_GET['context'] as $struct => $id) {
  	    $formAction .= "?administrator[action]=form&administrator[widget]={$struct}&form_{$struct}[id]=$id";
	  }      
      ?>
      $.ajax({
	url: $(this).attr('action'),
        type: "POST",             
        data: new FormData(document.querySelector('#<?=$this->w->htmlName?>')),
        contentType: false,       
        cache: false,             
        processData:false,   
	success: function(data)
	{
	  //window.location = "<?=$formAction?>";
          window.history.back(1);
	}
      });
      return false;
      <? } else {
        echo "return true;";
      }?>
    } else if(action == "Salva e rimani"||action == "Inserisci e rimani") {
	console.log("salva e rimani")
	var formData = new FormData($(this)[0]);
      $.ajax({
	url: $(this).attr('action'),
	type: 'POST',
        //data: $(this).serialize(),
        data: formData,
	processData: false,
	contentType: false,
	success: function(data) {
	  //callback methods go right here
	  if(action == "Inserisci e rimani") {
	    $('input.btn[value="Inserisci e rimani"]').val("Salva");
	    $('input.btn[value="Inserisci"]').val("Salva e rimani");
	    $('input.btn[value="Inserisci e nuovo"]').val("Salva e nuovo");
	    $('#success-message').html(" Inserimento effettuato ");
	  } else {
	    $('#success-message').html(" Salvataggio effettuato ");
	  }
	  $('#success-message').show();
	}
      });
      return false;
    } else if(action == "Salva e nuovo"||action == "Inserisci e nuovo") {
	var formData = new FormData($(this)[0]);
      $.ajax({
	url: $(this).attr('action'),
	type: 'POST',
	//data: $(this).serialize(),
        data: formData,
        processData: false,
        contentType: false,
	success: function(data) {
	  //callback methods go right here
	  $('#success-message').html(" Salvataggio effettuato ");
	  $('#success-message').show();
	  window.location = $('.add').attr('href');
	}
      });
      return false;
    } else {
      return true;
    }
    return false;
  });
});

function check_<?=$this->w->htmlName?>(action){
  /*var emailRegExp = /\b(^(\S+@).+((\.com)|(\.net)|(\.edu)|(\.mil)|(\.gov)|(\.org)|(\..{2,2}))$)\b/gi;
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
  if(action=='save_and_new')
  {
    return false;
  }
  else if(action=='save_and_stay')
  {
    return false;
  }
  else if(action=='save')
  {
    return false;
  }
  return true;*/
}
</script>
<?
  }

  function formStart($noManual=false){
    if (!$noManual) $this->manualOpen = true; //:KLUDGE!
    $formAction = $_SERVER['PHP_SELF'];
    /*if(isset($_GET['context'])) {
      foreach($_GET['context'] as $struct => $id) {
	$formAction .= "?administrator[action]=form&administrator[widget]={$struct}&form_{$struct}[id]=$id";
      }
    }*/
    //print "<form class=\"form-horizontal\" action='{$formAction}' id='{$this->name}' method='POST' ";
    print "<form class=\"form-horizontal\" action='{$_SERVER['PHP_SELF']}' id='{$this->name}' method='POST' ";
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

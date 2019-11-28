<?
global $IMP;
$D->printScripts();

if($W->config['storicizza']) {
?>
<style type="text/javascript">
.ui-state-hover, .ui-widget-content .ui-state-hover, .ui-state-focus, .ui-widget-content .ui-state-focus {
	border: 1px solid #ccc;
	background: #e6e6e6;
	font-weight: normal;
	color: #333;
}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.js" type="text/javascript"></script>
<div class="fields">
  <div class="control-group politico">
    <label class="control-label" for="form_benefici_documentazione_politico">Politico</label>
    <div class="controls">
      
	  <div style="clear: both">
    </div>
  </div>
</div>

<div id="dialog-form" title="Storicizza documento"> 
  <p>Inserisci una descrizione per il documento da storicizzare</p>
  <div class="form-horizontal">
    <div class="fields">
      <div class="control-group storicizza_descrizione">
        <input id="storicizza_campo" name="storicizza_campo" type="hidden">
        <label class="control-label" for="storicizza_descrizione">Descrizione</label>
        <div class="controls">
          <input id="storicizza_descrizione" name="storicizza_descrizione" type="text" value="Documento storicizzato">
          <div style="clear: both"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?
}
?><script type='text/javascript'>
<?if($W->config['storicizza']) {?>
var dialog;
function storicizza() {
  var fieldName = $("#storicizza_campo").val().replace("form_<?=$W->struct->name?>[", "").replace("]", "");
//   var url = 'storicizza.php?struct=<?=$W->struct->name?>&field='+fieldName+'&descrizione='+$("#storicizza_descrizione").val();
//   alert(url);
  $.post( "storicizza.php", { struct: "<?=$W->struct->name?>", field: fieldName, descrizione: $("#storicizza_descrizione").val(), id: "<?=$W->id?>" }, function() {
//     alert( "waiting..." );
  })
  .done(function( data ) {
//     alert( "success" );
    if(data!="") {
      console.log(data);
      dialog.dialog( "close" );
      alert( "si e' verificato un errore" );
    } else {
      dialog.dialog( "close" );
      location.reload(); 
    }
  })
  .fail(function() {
    dialog.dialog( "close" );
    alert( "si e' verificato un errore" );
  });
}
<?}?>

$( document ).ready(function() {
<?
if(isset($_GET['context'])) {
  foreach($_GET['context'] as $struct => $id) {
    $struct = explode("::", $struct);
    $inputName = $D->name."_{$struct[1]}";
    $selectId = "#".str_replace("::","_",$inputName);
    ?>
      $('<?=$selectId?>').val(<?=$id?>);
      $('<?=$selectId?>').after('<input type="text" value="'+$("<?=$selectId?> option[value='<?=$id?>']").html()+'" disabled="true">');
      $('<?=$selectId?>').hide();
    <?
  }
}
if($W->config['storicizza']) {
  ?>  
  dialog = $( "#dialog-form" ).dialog({
    autoOpen: false,
    height: 200,
    width: 405,
    modal: true,
    buttons: {
      "Conferma":{
        click: storicizza,
        text:'Conferma',
        'class':'btn btn-primary'
      },
      "Annulla":{
        click: function() {
          dialog.dialog( "close" );
        },
        text:'Annulla',
        'class':'btn btn-default'
      },
    },
    open: function(event) {
      console.log(event);
      var $buttonPane =  $(this).parent().find(".ui-dialog-buttonpane");
      $buttonPane.addClass("form-actions").removeClass("ui-dialog-buttonpane").removeClass("ui-widget-content").removeClass("ui-helper-clearfix").css("margin-bottom","0px").css("padding-bottom", "44px");
      $buttonPane.find(".btn-primary").removeClass("ui-button").removeClass("ui-widget").removeClass("ui-state-default").removeClass("ui-corner-all").removeClass("ui-button-text-only").css("float","right").css("margin-left","10px");
      $buttonPane.find(".btn-default").removeClass("ui-button").removeClass("ui-widget").removeClass("ui-state-default").removeClass("ui-corner-all").removeClass("ui-button-text-only").css("float","right");
    }
  });
  
  $(".storicizza").click(function(){
//     var filename = $(this).parent().find("input:hidden").val();
    $("#storicizza_campo").val($(this).parent().find("input:file").attr("name"));
    $("#storicizza_descrizione").val($(this).parent().parent().find(".control-label").text()+" "+((new Date()).getFullYear()-1))
    dialog.dialog( "open" );
  });
  <?
}
?>
});</script><?
?>
<div class="fields">
  <?
    foreach ($W->inputsOrder as $inputName)
    {
      $W->labels[$inputName] = str_replace('\n', "<br>", $W->labels[$inputName]);
      if ($W->hiddenElements[$inputName]) $W->inputs->$inputName->display();
      else
      {
	print '<div class="control-group '.$inputName.'">';
	print '<label class="control-label" for="'.$D->name.'_'.$inputName.'">'.$W->labels[$inputName].'</label>';
	print '<div class="elementNotes" id="'.$D->name.'_'.$inputName.'_notes"><!-- --></div>';
	print '<div class="controls">';
	if ($W->struct->isMultiLanguage($inputName)){
	  print "<table cellpadding='0' cellspacing='0'><tr>";
	  $langElements = $W->struct->getElementInAllLanguages($inputName);
	  foreach($langElements as $langElement){
	    preg_match('/_(\w+)$/', $langElement, $matches);
	    $lang = $matches[1];
	    print "<td>";
	    print "<span class='".$D->getCSSClass('label')."'>";
	    print ucwords($IMP->config['languages'][$W->struct->nameSpace][$lang]['label']);
	    print "</span>";
	    print "<br>";
	    if ($W->inputs->$langElement) $W->inputs->$langElement->display();
	    print "</td>";
	  }
	  print "</tr></table>";
	}
	else
	{
	  $W->inputs->$inputName->display();
	  print '<span class="rightNotes" id="'.$D->name.'_'.$inputName.'_rightNotes"><!-- --></span>
	  <div style="clear: both"><!-- --></div>';
	}
	print '</div>';
	print '</div>';
      }
    }
  ?>
</div>
<div class="form-actions">
  <?
  $first = true;
  if (!$W->readOnly && is_array($W->saveActions)) foreach ($W->saveActions as $action => $label)
  {
    $class = "btn-primary";
    if(!$first){$class = "btn-default";}
  ?>        
	  <input type='submit' class='btn <?=$class?>' name='<?=$W->name?>[<?=$action?>]' value='<?=$label?>'>
  <?
    $first = false;
  }
  ?>
</div>
<?
  //force closing if there are subforms (:FIXME:  administrator expects the form to be open for visualization etc.; make administrator add subforms to the form instead)
  if (!$D->manualOpen || is_array($W->subForms)){
    $D->formEnd();
  }
?>
<?
if (is_array($W->tables) && $W->id){
  $tabView = & $W->createWidget('TabView', $W->name.'_tabView');
  foreach (array_keys($W->tables) as $elementName){
    $container = & $W->createWidget('Container', $W->name.'_cont');
    if ($W->subForms[$elementName]){
      $W->widgetParams->clear($W->subForms[$elementName]->name); #QUICK FIX
      $container->add($W->subForms[$elementName]);
    }
    $container->add($W->tables[$elementName]);
    $tabView->add($W->struct->label($elementName), $container);
    if(isset($_GET['administrator']) && $IMP->security->checkAdmin() && $W->config['contextAdmin'])
    {
      // :FIXME: this should be managed in a better way, check if we're on the administrator widget
      $adminUrl = $W->tables[$elementName]->config["admin"];
      if(strpos($adminUrl, "[widget]")!==false && strpos($adminUrl, "administrator[widget]")===false)
      {
	$adminUrl = str_replace("[widget]","administrator[widget]", $adminUrl);
      }
      $W->tables[$elementName]->config["admin"] = $adminUrl;
    }
  }
  $tabView->display();
}

?>
</div>
<div class='formSideBar'>
<?
if (is_array($W->sideBarActions)) foreach ($W->sideBarActions as $name => $action){
    if ($action){
?>
<a href='<?=$action?>'><?=$name?></a><br>
<?
    }
    else{
?>
<b><?=$name?></b><br>
<?        
    }
}
?>
<?
$D->printEndScripts();
?>

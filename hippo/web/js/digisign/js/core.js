var readyToSign = false;
var recursive = false;
var originalButtonText = "";
  
var signatureAppletHtml = "";
var signatureHelperHtml = "";

function loadApplet()
{
  $("#dialog-modal").html(msg_primo_avviso);
  
  if ($.browser.webkit)
  {
    alert(msg_primo_avviso_chrome);
  }
  else
  {
    $( "#dialog-modal" ).dialog("open");
  }

  var _app = navigator.appName;
  if (_app == 'Microsoft Internet Explorer') 
  {
    signatureAppletHtml = '<OBJECT classid="clsid:8AD9C840-044E-11D1-B3E9-00805F499D93" '+
    'id="signatureService" '+
    'name="signatureService" '+
    'codebase=" http://java.sun.com/update/1.5.0/jinstall-1_5_0_22-windows-i586.cab#Version=1,5,0,22" '+
    'WIDTH="0" HEIGHT="0" ALIGN="middle" >'+
    '<PARAM NAME="CODE" VALUE="it.dedagroup.pa.kernel.ds.applet.DS_SignatureApplet" >'+
    '<PARAM NAME="CODEBASE" VALUE="'+pathSignApplet+'applet/ds_applet/jar" >'+
    '<PARAM NAME="ARCHIVE" VALUE="Kernel_DS_framework_signed.jar, base-core.jar, base-opt.jar, bcmail-jdk15-146.jar, bcprov-jdk15-146.jar, bctsp-jdk15-146.jar, commons-codec.jar, commons-io-1.3.2.jar, iaikPkcs11Wrapper_signed.jar, iText.jar, ldapsdk-4.1.jar, mailapi.jar, pcsc-wrapper-signed.jar, SignatureHelper_signed.jar">'+
    '<PARAM NAME="NAME" VALUE="signatureService" >'+
    '<PARAM NAME="type" VALUE="application/x-java-applet;version=1.5">'+
    '<PARAM NAME="scriptable" VALUE="true">'+
    '<PARAM NAME="locale" VALUE="it">'+
    '<PARAM NAME="sessionid" VALUE="55383DB33FF68F499B2034817B874C06">'+
    '<PARAM NAME="context" VALUE="'+pathSignApplet+'">'+
    '</OBJECT>';
  }
  else  
  {
    signatureAppletHtml = '<embed code="it.dedagroup.pa.kernel.ds.applet.DS_SignatureApplet"'+
    'codebase="'+pathSignApplet+'applet/ds_applet/jar/"'+
    'id="signatureService"'+ 
    'name="signatureService"'+ 
    'archive="Kernel_DS_framework_signed.jar, base-core.jar, base-opt.jar, bcmail-jdk15-146.jar, bcprov-jdk15-146.jar, bctsp-jdk15-146.jar, commons-codec.jar, commons-io-1.3.2.jar, iaikPkcs11Wrapper_signed.jar, iText.jar, ldapsdk-4.1.jar, mailapi.jar, pcsc-wrapper-signed.jar, SignatureHelper_signed.jar"'+
    'width="0"'+
    'height="0"'+
    'scriptable="true"'+
    'type="application/x-java-applet;version=1.5"'+
    'locale="it"'+
    'sessionid="55383DB33FF68F499B2034817B874C06"'+
    'context="'+pathSignApplet+'"'+
    '>'+
    '<noembed>No Java Support.</noembed>'+
    '</embed>';
  }

  signatureHelperHtml = '<applet code="org.soluzionipa.signaturehelper.SignatureHelper" codebase="'+pathSignApplet+'applet/ds_applet/jar" archive="SignatureHelper_signed.jar" type="application/x-java-applet;version=1.5" context="'+pathSignApplet+'" name="SignatureHelper" id="SignatureHelper" width="0" height="0"></applet>';
  
  var appletbox = document.getElementById('applet-box');
  appletbox.innerHTML = signatureAppletHtml+'\n'+signatureHelperHtml;
}

// Check if applet is active
function isAppletActive(app) 
{
  var active=false;

  try 
  {
    active=app.isActive(); // IE check
  } 
  catch(Ex)
  {
    try 
    {
      active=app.isActive; // Firefox check
    } 
    catch(Ex)
    {
      try 
      {
	active=app.activated; // Firefox check
      } 
      catch(Ex)
      {
	// Is there a problem?!?
      }
    }
  }

  return active;
}

function sign(button, filename, filepath, uploadurl, id, downloadLinkId)
{
  /* Questa funzione avvia la procedura di firma e va usata in questo modo:
   * 
   * <script  type="text/javascript" src="digisign/js/core.js"></script>
   * <a href='#' onclick='sign(this, filename, filepath, uploadurl, id, downloadLinkId)'>Firma file</a> 
   * 
   * Parametri:
   * button 	-> 	dev'essere impostato a "this"
   * filename 	-> 	nome del file da firmare (senza URL, es. pippo.pdf)
   * filepath 	-> 	url del percorso dove si trova il file sul server 
   * 			(senza nome file, es. http://www.dominio.com/data/documenti/)
   * uploadurl 	-> 	url dello script di caricamento 
   * 			(es. http://www.dominio.com/upload_signed.php)
   * id 	-> 	-opzionale- id che verrà passato allo script di caricamento per 
   * 			l'eventuale inserimento/modifica di record in db
   * downloadLinkId -> 	-opzionale- id del link al file originale non firmato, al termine
   * 			della procedura verrà modificato in modo da puntare al file firmato
   */
  debugger;
  $( "#dialog-modal" ).dialog({
    autoOpen: false,
    dialogClass: "no-close",
    position: "center",
    width: 550,
    height: 350,
    modal: true  
  });
  
  var signaturehelper = document.getElementById('SignatureHelper');
  var signatureService = document.getElementById('signatureService');
  
  if(!readyToSign)
  {
    loadApplet();
    
    signaturehelper = document.getElementById('SignatureHelper');
    signatureService = document.getElementById('signatureService');

    $.when(isAppletActive(signatureService)).then(function(){
      $( "#dialog-modal" ).removeClass("ui-loading");
      $( "#dialog-modal" ).dialog("close");
      readyToSign = true;
    });
  }
  
  var okButton = [{text: "OK",click: function() {$( this ).dialog( "close" );}}];
  var closeButton = [{text: "Chiudi",click: function() {$( this ).dialog( "close" );}}];
  
  $( "#dialog-modal" ).html(msg_avvio);
  $("#dialog-modal").dialog('option', 'width', 500);
  $("#dialog-modal").dialog('option', 'height', 250);
  $("#dialog-modal").dialog('option', 'buttons', null);
  $( "#dialog-modal" ).dialog("open");
    
  if(!isAppletActive(signatureService))
  {
    $("#dialog-modal").dialog('option', 'width', 600);
    $("#dialog-modal").dialog('option', 'height', 400);
    $("#dialog-modal").dialog('option', 'buttons', okButton);
    $( "#dialog-modal" ).html(msg_errore_avvio);
  }
  else
  {
    signaturehelper.setFileToSign(filename);
    signaturehelper.setServerFilePath(filepath);
    signaturehelper.setUploadURL(uploadurl);
    
    id = (typeof id === "undefined") ? null : id;
    if(id)
    {
      signaturehelper.setRecordToUpdate(id);
    }
    
    var downloaded = false;
    
    var alreadySigned = signaturehelper.isFileSigned();
    var signAgain = false;
    
    if(!recursive && alreadySigned)
    {
      signAgain = confirm(msg_gia_firmato);
    }
    
    if(!recursive){originalButtonText = button.innerHTML;}

    if(!recursive && alreadySigned && !signAgain)
    {
      $("#dialog-modal").dialog('option', 'buttons', okButton);
      $( "#dialog-modal" ).html("Procedura completata.");
    }
    else
    {  
      $( "#dialog-modal" ).dialog("close");
      button.innerHTML = "Firma in corso...";
      $( "#dialog-modal" ).html(msg_download);
      $( "#dialog-modal" ).dialog("open");
      downloaded = signaturehelper.doDownload();

      if(downloaded)
      {
	$( "#dialog-modal" ).dialog("close");
	$( "#dialog-modal" ).html(msg_firma);
	$( "#dialog-modal" ).dialog("open");
	
	var theEnvelope = signatureService.signDocument(signaturehelper.getFileToSignPath());
	var errorMessage = "";
	
	var signatureError = signatureService.isOnError();
	
	if(signatureError == true)
	{
	  errorMessage = signatureService.getErrorMessage();	 
	  if(errorMessage=="Pin non corretto. Volete riprovare?")
	  {
	    var ok = confirm("Errore: "+errorMessage);     
	    if(ok) 
	    {
	      recursive = true;
	      errorMessage = sign();
	    }
	  }
	  else
	  {
	    recursive = false;
	    $( "#dialog-modal" ).dialog("close");
	    alert("Errore: "+errorMessage);
	  }
	}
	else
	{
	  if((theEnvelope == null) || (theEnvelope == ""))
	  {
	      recursive = false;
	      $("#dialog-modal").dialog('option', 'buttons', okButton);
	      $( "#dialog-modal" ).html(msg_annullo);	      
	  }
	  else
	  {
	    signaturehelper.fileHasBeenSigned();
	    $( "#dialog-modal" ).html(msg_upload);
	    var uploaded = signaturehelper.doUpload();
	    if(!uploaded)
	    {
	      recursive = false;
	      $("#dialog-modal").dialog('option', 'buttons', closeButton);
	      $( "#dialog-modal" ).html(msg_upload_error);
	    }
	    else
	    {
	      recursive = false;
	      $("#dialog-modal").dialog('option', 'buttons', closeButton);
	      $( "#dialog-modal" ).html(msg_completato);
	      downloadLinkId = (typeof downloadLinkId === "undefined") ? null : downloadLinkId;
	      if(downloadLinkId)
	      {
		var downloadLink = document.getElementById(downloadLinkId);
		downloadLink.href = downloadLink.href+".p7m";
	      }
	      var workDiv = document.getElementById("divFirma");
	      var messageDiv = document.getElementById("divMessaggio");
	      if(workDiv && messageDiv)
	      {
		workDiv.style.visibility = "hidden";
		workDiv.style.display = "none";
		messageDiv.style.visibility = "visible";
		messageDiv.style.display = "block";
	      }
	    }
	  }
	}
      }
      else
      {
	recursive = false;
	$("#dialog-modal").dialog('option', 'buttons', closeButton);
	$( "#dialog-modal" ).html(msg_download_error);
      }
      button.innerHTML = originalButtonText;
    }
  }
}

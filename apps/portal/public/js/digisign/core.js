var test = false;

if(test)
{
  alert("WARNING: running in test mode. No signature.");
}

var readyToSign = false;
var recursive = false;
var originalButtonText = "";
  
var signatureAppletHtml = "";
var signatureHelperHtml = "";

var postExecScript = false;
var postExecResponse = "";

var pathSignApplet = "/portal/public/";

function setPostExecScript(script)
{
  
  postExecScript = script;
}
function setPostExecResponse(response)
{
  
  postExecResponse = response;
}

function loadApplet()
{
  if ($.browser.webkit)
  {
    alert($( "#dialog-modal" ).text());
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
    //'<PARAM NAME="ARCHIVE" VALUE="Kernel_DS_framework_signed.jar, base-core.jar, base-opt.jar, bcmail-jdk15-146.jar, bcprov-jdk15-146.jar, bctsp-jdk15-146.jar, commons-codec.jar, commons-io-1.3.2.jar, iaikPkcs11Wrapper_signed.jar, iText.jar, ldapsdk-4.1.jar, mailapi.jar, pcsc-wrapper-signed.jar, SignatureHelper_signed.jar">'+
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
    //'archive="Kernel_DS_framework_signed.jar, base-core.jar, base-opt.jar, iaikPkcs11Wrapper_signed.jar, iaik_cms.jar, bcmail-jdk15-146.jar, bcprov-jdk15-146.jar, bctsp-jdk15-146.jar, commons-codec.jar, commons-io-1.3.2.jar, iaikPkcs11Wrapper_signed.jar, iText.jar, ldapsdk-4.1.jar, mailapi.jar, pcsc-wrapper-signed.jar, SignatureHelper_signed.jar"'+
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
  
  if(!test && !readyToSign)
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
// divAlert
  var okButton = [{text: "OK",click: function() {$( this ).dialog( "close" );}}];
  var closeButton = [{text: "Chiudi",click: function() {$( this ).dialog( "close" );}}];
  
  $( "#dialog-modal" ).html("<p class='ui-loading'>La procedura di firma &egrave; in esecuzione, si prega di attendere...</p>");
  $("#dialog-modal").dialog('option', 'width', 500);
  $("#dialog-modal").dialog('option', 'height', 250);
  $( "#dialog-modal" ).dialog("open");
    
//   alert("Avvio della procedura di firma.\n\nLa prima volta viene scaricata la componente di firma e quindi ci vuole un po' di pazienza.\n\nConfermare eventuali richieste.");
  if(!test && !isAppletActive(signatureService))
  {
    $("#dialog-modal").dialog('option', 'width', 600);
    $("#dialog-modal").dialog('option', 'height', 400);
    $("#dialog-modal").dialog('option', 'buttons', okButton);
    $( "#dialog-modal" ).html("<p>Errore: l'operazione di firma non e' possibile.</p><p>Per per utilizzare il servizio di firma digitale &egrave; necessario disporre di Java aggiornato all'ultima versione disponibile.<p></p>Si ricorda, infine, che il sistema di firma funziona solo in ambiente Windows (no Linux e Mac) e su uno dei seguenti browser: Internet Explorer, FireFox, Chrome. &Egrave; inoltre necessario che l'utente abbia i diritti di scrittura nella propria home.</p><p style='text-align: center;'><a href='http://www.java.com/it/download/installed.jsp?detect=jre&try=1' target='_blank' style='color: blue; font-weight:bold;'>Controlla la versione di Java</a></p>");
//     var ok = confirm("Errore: l'operazione di firma non e' possibile.\n\nPer per utilizzare il servizio di firma digitale e' necessario disporre di Java aggiornato all'ultima versione disponibile.\n\nSi ricorda, infine, che il sistema di firma funziona solo in ambiente Windows (no Linux e Mac) e su uno dei seguenti browser: Internet Explorer, FireFox, Chrome. E' inoltre necessario che l'utente abbia i diritti di scrittura nella propria home.\n\nControllare ora la versione di Java?");
//     if(ok)
//     {
//        window.open("http://www.java.com/it/download/installed.jsp?detect=jre&try=1");
//     }
  }
  else
  {
    if(!test)
    {
      signaturehelper.setFileToSign(filename);
      signaturehelper.setServerFilePath(filepath);
      signaturehelper.setUploadURL(uploadurl);
    }
    
    id = (typeof id === "undefined") ? null : id;
    if(!test && id)
    {
      signaturehelper.setRecordToUpdate(id);
    }
    
    var downloaded = false;
    
    var alreadySigned = false;
    if(!test)
    {
      alreadySigned = signaturehelper.isFileSigned();
    }
    var signAgain = false;
    
    if(!recursive && alreadySigned)
    {
      signAgain = confirm("Questo documento e' gia' stato firmato. Ripetere l'operazione?");
    }
    
    if(!recursive && alreadySigned && !signAgain)
    {
//       alert("Procedura completata.");
      $("#dialog-modal").dialog('option', 'buttons', okButton);
      $( "#dialog-modal" ).html("Procedura completata.");
    }
    else
    {  
      if(!recursive){originalButtonText = button.innerHTML;}
      $( "#dialog-modal" ).dialog("close");
      button.innerHTML = "Firma in corso...";
//       alert("Procedo con la preparazione del documento per la firma, ci vuole solo qualche secondo ...");
      $( "#dialog-modal" ).html("<p class='ui-loading'>Preparazione del documento per la firma in corso, ci vuole solo qualche secondo ...</p>");
      $( "#dialog-modal" ).dialog("open");
//       alert("test");
      
      if(!test)
      {
	downloaded = signaturehelper.doDownload();
      }
      else
      {
	downloaded = true;
      }

      if(downloaded)
      {
	$( "#dialog-modal" ).dialog("close");
	$( "#dialog-modal" ).html("<p class='ui-loading'>In attesa della firma...</p>");
	$( "#dialog-modal" ).dialog("open");
	
	if(!test)
	{
	  var theEnvelope = signatureService.signDocument(signaturehelper.getFileToSignPath());
	}
	var errorMessage = "";
	
	var signatureError = false;
	if(!test)
	{
	  signatureError = signatureService.isOnError();
	}
	
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
	  if(!test && ((theEnvelope == null) || (theEnvelope == "")))
	  {
	      recursive = false;
// 	      divAlert.innerHTML = "Procedura annullata.";
	      $("#dialog-modal").dialog('option', 'buttons', okButton);
	      $( "#dialog-modal" ).html("<p>Procedura annullata.</p>");	      
// 	      $( "#dialog-modal" ).dialog("close");
// 	      alert("Procedura annullata.");
	  }
	  else
	  {
	    if(!test)
	    {
	      signaturehelper.fileHasBeenSigned();
	    }
// 	    alert("Procedo con il salvataggio documento firmato in corso, ci vuole solo qualche secondo ...");
	    $( "#dialog-modal" ).html("<p class='ui-loading'>Procedo con il salvataggio documento firmato in corso, ci vuole solo qualche secondo ...</p>");
	    var uploaded = false;
	    if(!test)
	    {
	      uploaded = signaturehelper.doUpload();
	    }
	    if(!test && !uploaded)
	    {
	      recursive = false;
	      $("#dialog-modal").dialog('option', 'buttons', closeButton);
	      $( "#dialog-modal" ).html("<p>Errore: impossibile salvare il documento.</p>");
// 	      $( "#dialog-modal" ).dialog("close");
// 	      alert("Errore: impossibile salvare il documento.");
	    }
	    else
	    {
	      recursive = false;
// 	      $( "#dialog-modal" ).dialog("close");
	      $("#dialog-modal").dialog('option', 'buttons', closeButton);
	      $( "#dialog-modal" ).html("<p>La firma del documento &egrave; andata a buon fine.</p>");
        /* ricarico la pagina quando clicco sul chiudi del dialog dopo che viene uploadato il file firmato */
        if($("span.ui-button-text:contains('Chiudi')").length > 0){
          $("span.ui-button-text:contains('Chiudi')").parent().click(function(){
            location.reload();
          });
        }
// 	      $( "#dialog-modal" ).dialog({
// 	      position: "center",
// 	      width: 500,
// 	      height: 250,
// 	      modal: true,
// 	      buttons: [
// 		{
// 		  text: "Chiudi",
// 		  click: function() {
// 		    $( this ).dialog( "close" );
// 		  }
// 		}
// 	      ]
// 	      });
// 	      alert("La firma del documento e' andata a buon fine.");
	      downloadLinkId = (typeof downloadLinkId === "undefined") ? null : downloadLinkId;
	      if(downloadLinkId)
	      {
		var downloadLink = document.getElementById(downloadLinkId);
		downloadLink.href = downloadLink.href+".p7m";
	      }
	      if(postExecScript)
	      {
		
		$.ajax({
		    url: postExecScript,
		    type: "GET",
		    async: false, // set to false so order of operations is correct
		    success: function(data){
		      setPostExecResponse(data);
		  }
		});
		
		if(postExecResponse.indexOf("true") === 0) 
		{
		  var newLink = postExecResponse.substr(4, postExecResponse.lenght);
		  if(downloadLinkId)
		  {
		    var downloadLink = document.getElementById(downloadLinkId);
		    downloadLink.href = newLink;
		  }
		}
		else
		{
		  $("#dialog-modal").dialog('option', 'buttons', closeButton);
		  $( "#dialog-modal" ).html("<p>Si &egrave; verificato un errore durante il salvataggio del file firmato.</p>");
		}
	      }
	    }
	  }
	}
      }
      else
      {
	recursive = false;
	$("#dialog-modal").dialog('option', 'buttons', closeButton);
	$( "#dialog-modal" ).html("<p>Errore: impossibile preparare il documento per la firma.</p>");
// 	$( "#dialog-modal" ).dialog("close");
// 	alert("Errore: impossibile preparare il documento per la firma.");
      }
      button.innerHTML = originalButtonText;
    }
  }
}

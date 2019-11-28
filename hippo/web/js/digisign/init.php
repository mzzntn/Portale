<?  
function mostraLinkFirma($nomeFile, $urlFile, $urlUploadScript, $idPratica, $testoLink, $downloadLinkId, $linkClass)
{
  global $C;
  /* Funzione per la generazione del link per la firma di un file
   * 
   * $nomeFile		- nome del file da firmare (senza URL, es. pippo.pdf)
   * $urlFile		- url del percorso dove si trova il file sul server 
   * 			  (senza nome file, es. http://www.dominio.com/data/documenti/
   * 			  CON slash / finale)
   * $urlUploadScript	- url dello script di upload 
   * 			  (es. http://www.dominio.com/upload_signed.php)
   * $idPratica		- opzionale - id che verrà passato allo script di caricamento per 
   * 			  l'eventuale inserimento/modifica di record in db (da utilizzare
   * 			  solo se la funzione e' stata predisposta nello script di 
   * 			  upload
   * $linkClass		- opzionale - nome della classe CSS da assegnare al link di firma 
   */
  
  //<div id='applet-box' style='visibility: hidden; height: 0px; margin: 0px;'></div>
  $html = "<p style='text-align: justify; height: 0px; margin: 0px;'>
	  <div id='dialog-modal' title='Avviso' style='display: none; height: 0px; margin: 0px;'></div>
	  <div id='applet-box' style='height: 0px; margin: 0px;'></div>
	  <link rel='stylesheet' href='".URL_JS."/digisign/css/jquery-ui-1.9.2.custom.css' />
	  <script src='".URL_JS."/digisign/js/jquery-1.8.3.js'></script>
	  <script src='".URL_JS."/digisign/js/jquery-ui-1.9.2.custom.js'></script>
	  <script type='text/javascript'>";
  
  foreach($C['portal']['messaggi_firma'] as $msgName => $msgText)
  {
    $html .= "var msg_{$msgName} = \"{$msgText}\";\r\n";
  }
  $html .= "var pathSignApplet = '".URL_JS."/digisign/';";
  
  if($linkClass != "")
  {
    $linkClass = " class='{$linkClass}'";
  }
  
  if($downloadLinkId != "")
  {
    $downloadLinkId = ", \"{$downloadLinkId}\"";
  }
  
  $html .= "</script>
	<script  type='text/javascript' src='".URL_JS."/digisign/js/core.js'></script>
	  <a {$linkClass} href='#' onclick='sign(this, \"{$nomeFile}\", \"{$urlFile}\", \"{$urlUploadScript}\", {$idPratica}{$downloadLinkId})'>{$testoLink}</a>
	</p>";
	
  print $html;
}
?>
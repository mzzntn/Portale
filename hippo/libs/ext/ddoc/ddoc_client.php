<?php  
/*
 * *** DDOCClient ***
 * 
 * Libreria di comunicazione con server DDOC per l'invio e la ricezione di 
 * documenti tramite PHP SOAP o nusoap.
 * 
 * Il metodo di default per l'esecuzione delle richieste e' tramite nusoap.
 * Per utilizzare PHP SOAP sara' necessario richiamare la funzione
 * DDOCClient::usePHPSOAP() subito dopo l'inizializzazione.
 * 
 * *** Esempi di utilizzo ***
 * 
 * Invio di un file
 * --------------------------------------------------------------------------
 * require_once("nusoap/nusoap.php");
 * $sc = new DDOCClient($url);
 * $sc->setApplication("AP");
 * $sc->setContext("CONTES");
 * $response = $sc->upload("filename.ext", "path/to/filename/",
 * "file description");
 * if($response){//invio completato con successo}
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 * Recupero delle informazioni di un file
 * --------------------------------------------------------------------------
 * require_once("nusoap/nusoap.php");
 * $sc = new DDOCClient($url);
 * $response = $sc->get("20130509185056_OW_AFFIS_276871");
 * if($response){print_r($response);//file recuperato, stampo le informazioni}
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 * Recupero delle informazioni di un file tramite PHP SOAP
 * --------------------------------------------------------------------------
 * $sc = new DDOCClient($url);
 * $sc->usePHPSOAP();
 * $response = $sc->get("20130509185056_OW_AFFIS_276871");
 * if($response){print_r($response);//file recuperato, stampo le informazioni}
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 * Recupero e salvataggio di un file
 * --------------------------------------------------------------------------
 * $sc = new DDOCClient($url);
 * $response = $sc->getAndSave("20130510140853_OW_AFFIS_276874","docs/");
 * if($response){
 * echo "<a href='{$response['path']}'>{$response['filename']}</a>";
 * //file recuperato e salvato, mostro un link al file
 * }
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 */
class DDOCClient
{
  const NUSOAP = 0;
  const PHP_SOAP = 1;
  private $debug;
  var $application;
  var $context;
  var $method;
  var $wsdl;
    
  public function __construct($wsdl) 
  { 
    $this->method = self::NUSOAP;
    $this->wsdl = $wsdl;
    $this->debug = array();
  } 
  
  /*
   * Imposta l'applicazione (obbligatoria per l'invio documenti)
   * 
   */
  public function setApplication($application)
  {
    $this->application = $application;
  }
  
  /*
   * Imposta il contesto (obbligatorio per l'invio documenti)
   * 
   */
  public function setContext($context)
  {
    $this->context = $context;
  }
  
  /*
   * Utilizza nusoap per effettuare le richieste (default)
   * 
   */
  public function useNusoap()
  {
    $this->method = self::NUSOAP;
  }
  
  /*
   * Utilizza PHP SOAP per effettuare le richieste
   * 
   */
  public function usePHPSOAP()
  {
    $this->method = self::PHP_SOAP;
  }
  
  private function logException($e)
  {
      $this->debug['Exception']['Message'] = $e->getMessage();
      $this->debug['Exception']['Code'] = $e->getCode();
      $this->debug['Exception']['File'] = $e->getFile();
      $this->debug['Exception']['Line'] = $e->getLine();
  }
  
  private function process($params)
  {
    $response = false;
    try
    {
      if($this->method == self::NUSOAP)
      {
	// connect using nusoap_client
	// WARNING: nusoap library MUST be included before calling process()
	if(!class_exists("nusoap_client"))
	{
	  $this->debug['ClientError'] = "Fatal error: can't find class nusoap_client. Please check nusoap library installation, version (>=0.9.5) and correct loading before calling DDOCClient methods.";
	}
	else
	{
	  $client = new nusoap_client(str_replace("?wsdl","",$this->wsdl));
	  $client->soap_defencoding = 'UTF-8';
	  $client->decode_utf8 = false;
	  $client->useHTTPPersistentConnection();
	  $response = $client->call('DDocRequest', $params, $this->wsdl, "urn:process"); 
	  
	  $reqData = explode("\r\n\r\n",$client->request);
	  $resData = explode("\r\n\r\n",$client->response);
	  
	  #$this->debug['RequestRaw'] = $reqData[1];
	  $this->debug['Request'] = "<pre>".htmlspecialchars($reqData[1], ENT_QUOTES)."</pre>";
	  $this->debug['RequestHeader'] = "<pre>".htmlspecialchars($reqData[0], ENT_QUOTES)."</pre>";
	  #$this->debug['ResponseRaw'] = $resData[1];
	  #$this->debug['Response'] = "<pre>".htmlspecialchars($resData[1], ENT_QUOTES)."</pre>";
	  $this->debug['ResponseHeader'] = "<pre>".htmlspecialchars($resData[0], ENT_QUOTES)."</pre>";
	  #$this->debug['debug'] = "<pre>".htmlspecialchars($client->debug_str, ENT_QUOTES)."</pre>";      
	}
      }
      else if($this->method == self::PHP_SOAP)
      {
	if(!class_exists("SoapClient"))
	{
	  $this->debug['ClientError'] = "Fatal error: can't find class SoapClient. Please check PHP version (>=5.0.1) and if PHP SOAP extension is correctly enabled.";
	}
	else
	{
	  // connect using SoapClient
	  // WARNING: requires PHP 5 >= 5.0.1
	  $client = new SoapClient($this->wsdl, array("trace"=>1));
	  $response = $client->__soapCall("process", array($params));      
	  
	  #$this->debug['RequestRaw'] = $client->__getLastRequest();
	  #$this->debug['Request'] = "<pre>".htmlspecialchars($client->__getLastRequest(), ENT_QUOTES)."</pre>";
	  $this->debug['RequestHeader'] = "<pre>".htmlspecialchars($client->__getLastRequestHeaders(), ENT_QUOTES)."</pre>";
	  #$this->debug['ResponseRaw'] = $client->__getLastResponse();
	  #$this->debug['Response'] = "<pre>".htmlspecialchars($client->__getLastResponse(), ENT_QUOTES)."</pre>";
	  #$this->debug['ResponseHeader'] = "<pre>".htmlspecialchars($client->__getLastResponseHeaders(), ENT_QUOTES)."</pre>";
	}
      } 
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $response = false;
    }
    return $response;
  }
  
  private function sendData($fileContents, $filename, $description)
  {
    $response = false;
    try
    {
      if($this->method == self::NUSOAP)
      {
	$BinaryData = new soapval('BinaryData', 'BinaryData', base64_encode($fileContents), false, false, array('Filename' => $filename));
      }
      else if($this->method == self::PHP_SOAP)
      {
	$BinaryData = array('_' => $fileContents, 'Filename' => $filename);  
      }
      $params = array
      (
	"DDOC" => array
	(
	  "Application" => $this->application,
	  "Context" => $this->context,
	  "Action" => "SendData",
	  "SendData" => array
	  (
	    "Document" => array
	    (
	      "Encrypt" => "N",
	      "PdfConversion" => "N",
	      "MetaData" => array
	      (
		"Key" => "DescDocumento",
		"Value" => "$description",
	      ),
	      "BinaryData" => $BinaryData,
	    ),
	  ),
	),
      );  
      $response = $this->process($params);
    }
    catch(Exception $e)
    {
      $this->logException($e);
    }
    
    return $response;
  }
  
  private function getData($uid)
  {
    $response = false;
    try
    {
      $params = array
      (
	"DDOC" => array
	(
	  "Application" => $this->application,
	  "Context" => $this->context,
	  "Action" => "GetData",
	  "GetData" => array
	  (
	    "Document" => array("UID" => $uid),
	  ),
	),
      );
      $response = $this->process($params);
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $response = false;
    }
    return $response;
  }
  
  /*
   * Invia una richiesta al server DDOC e recupera i dati di un file, 
   * dato un UID.
   * 
   * Restituisce false se non riesce a recuperare il file (utilizzare la
   * funzione debug() o getError() per ulteriori dettagli) altrimenti
   * restitituisce un array associativo contenente i seguenti campi:
   *  filename -> il nome del file recuperato
   *  content -> il contenuto del file
   *  altre chiavi -> i vari metadati del file
   * 
   */
  function getNusoap($uid)
  {
    $doc = false;
    try
    {
      $docInfo = explode("_", $uid);
      $this->application = $docInfo[1];
      $this->context = $docInfo[2];
      
      $response = $this->getData($uid);
      if($response)
      {
	if($this->method == self::NUSOAP)
	{
	  if($response['DDOC']['Action'] == "ErrorSVR")
	  {
	    $this->debug['ErrorSVR'] = $response['DDOC']['ErrorSVR'];
	  }
	  else
	  {	      
	    $doc = array();
	    $doc["filename"] = $response['DDOC']['GetData']['Document']['BinaryData']['!Filename'];
	    foreach($response['DDOC']['GetData']['Document'] as $key => $value)
	    {
	      if(is_array($value) && $key == "MetaData")
	      {
		  $arraykey = "";
		  $arravalue = "";
		  /*foreach($value as $mdKey => $mdValue) 
		  {
		    if($mdKey == "Key")
		    {
		      $arraykey = $mdValue;
		    }
		    else if($mdKey == "Value")
		    {
		      $arravalue = $mdValue;
		    }
		  }*/
		  $doc[$arraykey] = $arravalue;
	      }
	      else if($key != "BinaryData")
	      {
		$doc[$key] = $value;
	      }
	    }					
	    $doc["content"] = base64_decode($response['DDOC']['GetData']['Document']['BinaryData']['!']);
	  }
	}
	else if($this->method == self::PHP_SOAP)
	{
	  if($response->DDOC->Action == "ErrorSVR")
	  {
	    $this->debug['ErrorSVR'] = $response->DDOC->ErrorSVR;
	  }
	  else
	  {	
	    $doc = array();
	    $doc["filename"] = $response->DDOC->GetData->Document->BinaryData->Filename;
	    
	    foreach($response->DDOC->GetData->Document as $key => $value) 
	    {
	      if(is_object($value) && $key == "MetaData")
	      {
		  $arraykey = "";
		  $arravalue = "";
		  foreach($value as $mdKey => $mdValue) 
		  {
		    if($mdKey == "Key")
		    {
		      $arraykey = $mdValue;
		    }
		    else if($mdKey == "Value")
		    {
		      $arravalue = $mdValue;
		    }
		  }
		  $doc[$arraykey] = $arravalue;
	      }
	      else if($key != "BinaryData")
	      {
		$doc[$key] = $value;
	      }
	    }
	    $doc["content"] = $response->DDOC->GetData->Document->BinaryData->_;	
	  }
	}
      }
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $doc = false;
    }
    
    return $doc;
  }

  
  /*
   * Invia una richiesta al server SOAP e recupera i dati di un file, 
   * dato un UID.
   * 
   * Utilizza CURL per recuperare file di grandi dimensioni, da utilizzare
   * se la versione di libxml è >= 2.7.3 e ci sono problemi nel recupero dei file
   * 
   */
  function get($uid)
  {
    $doc = false;
      $docInfo = explode("_", $uid);
      $this->application = $docInfo[1];
      $this->context = $docInfo[2];

    try
    {  
      $ch = curl_init();
      
      $rawRequest = "<?xml version='1.0' encoding='UTF-8'?><SOAP-ENV:Envelope SOAP-ENV:encodingStyle='http://schemas.xmlsoap.org/soap/encoding/' xmlns:SOAP-ENV='http://schemas.xmlsoap.org/soap/envelope/' xmlns:xsd='http://www.w3.org/2001/XMLSchema' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns:SOAP-ENC='http://schemas.xmlsoap.org/soap/encoding/'>
	<SOAP-ENV:Body>
	  <ns8674:DDocRequest xmlns:ns8674='".str_replace("?wsdl","",$this->wsdl)."'>
	    <DDOC>
	    <Application xsi:type='xsd:string'>{$this->application}</Application>
	    <Context xsi:type='xsd:string'>{$this->context}</Context>
	    <Action xsi:type='xsd:string'>GetData</Action>
	    <GetData>
	      <Document>
		<UID xsi:type='xsd:string'>{$uid}</UID>
	      </Document>
	    </GetData>
	    </DDOC>
	  </ns8674:DDocRequest>
	</SOAP-ENV:Body>
      </SOAP-ENV:Envelope>";

      curl_setopt($ch, CURLOPT_URL,            str_replace("?wsdl","",$this->wsdl));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt($ch, CURLOPT_VERBOSE, true );
      curl_setopt($ch, CURLOPT_POST,           true );
      curl_setopt($ch, CURLOPT_POSTFIELDS,     $rawRequest ); 
      curl_setopt($ch, CURLOPT_HTTPHEADER,     
	array
	(
		'POST /ddoc/ddoc.dll HTTP/1.1',
		//'Host: 192.168.11.2',
		'Content-Type: text/xml; charset=UTF-8',
		'Connection: Keep-Alive', 
		'SOAPAction: "urn:process"',
	)); 
      curl_setopt($ch, CURLINFO_HEADER_OUT, 	       true);
      //curl_setopt($ch, CURLOPT_HEADER, 	       true);
      #curl_setopt($ch, CURLOPT_HTTP_VERSION,   CURL_HTTP_VERSION_1_0 );
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false );
      //curl_setopt($ch, CURLOPT_BINARYTRANSFER, true );
      //curl_setopt($ch, CURLOPT_NOBODY, true );
      #curl_setopt($ch, CURLOPT_LOCALPORT, 80 );
      curl_setopt($ch, CURLOPT_USERAGENT, "NuSOAP/0.9.5 (1.123)" );
      #curl_setopt($ch, CURLOPT_NOBODY, true );


      $doc = array();
      $result = curl_exec($ch);
      if ($result === false) 
      {
  	$doc["error"] = curl_error($ch);
	#$doc = false;
	curl_close($ch);
      }
      else if(strpos($result,"ErrorSVR")!==false) { 
	#file_put_contents("debug.log", preg_replace("/<BinaryData>.+<\/BinaryData>/s", "<BinaryData>TEST</BinaryData>", $result)."\n\n", FILE_APPEND);     
	 $delimiter1 = "<ErrorSVR";
	 $delimiter2 = "</ErrorSVR>";
	 $pos1 = stripos($result, $delimiter1)+strlen($delimiter1);
	 $pos2 = stripos($result, $delimiter2);
         $result1 = substr($result, $pos1, ($pos2-$pos1));
         $pos3 = stripos($result1, '>')+1;
	$doc["error"] = substr($result1, $pos3);
  	curl_close($ch);
      }	
      else
      {
      //$doc['info']=curl_getinfo($ch);
      //$doc["request"]=htmlentities($rawRequest);
      //$doc["result"] = htmlentities($result);
      curl_close($ch);

      $delimiter1 = "<BinaryData";
      $delimiter2 = "</BinaryData>";
      $pos1 = stripos($result, $delimiter1)+strlen($delimiter1);
      $pos2 = stripos($result, $delimiter2);

      $result1 = substr($result, $pos1, ($pos2-$pos1));

      $pos3 = stripos($result1, '>')+1;
      $doc['content'] = base64_decode(substr($result1, $pos3));


      $delimiter1 = 'Filename="';
      $delimiter2 = '">';
      $pos1 = stripos($result1, $delimiter1)+strlen($delimiter1);
      $pos2 = stripos($result1, $delimiter2);

      $doc['filename'] = substr($result1, $pos1, ($pos2-$pos1)); 
      }
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $doc = false;
    }
    
    return $doc;
  }
  
  /*
   * Invia una richiesta al server DDOC, recupera i dati di un file, 
   * dato un UID, e salva il file nel percorso specificato.
   * 
   * Restituisce false se non riesce a recuperare il file (utilizzare la
   * funzione debug() o getError() per ulteriori dettagli) altrimenti
   * restitituisce un array associativo contenente i seguenti campi:
   *  filename -> il nome del file recuperato
   *  path -> il percorso completo del file salvato
   *  altre chiavi -> i vari metadati del file
   */
  function getAndSave($uid, $savePath)
  {
    $doc = false;    
    try
    {
      $doc = $this->get($uid);
      if($doc)
      {
	$doc['path'] = $savePath.$doc['filename'];
	$fp = fopen($doc['path'], 'w');
	if($fp)
	{
	  fwrite($fp, $doc['content']);
	  fclose($fp);
	  unset($doc['content']);
	}
	else
	{
	  $this->debug['ClientError'] = "Cannot create file \"{$doc['path']}\".";
	  $doc = false;
	}
      }
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $doc = false;
    }
    return $doc;
  }
  
  /*
   * Invia una richiesta al server DDOC, recupera i dati di un file, 
   * dato un UID, e restituisce il contenuto del file.
   * 
   */
  function getContent($uid)
  {
    $doc = $this->get($uid);
    return $doc['content'];
  }
  
  /*
   * Invia un file al server DDOC.
   * 
   * Restituisce false se non riesce ad inviare il file (utilizzare la
   * funzione debug() per ulteriori dettagli) altrimenti restitituisce l'UID
   * del documento salvato.
   * 
   */
  function upload($filename, $filePath, $description)
  {
    $UID = false;
    try
    {
      $fullpath = $filePath.$filename;
      if(!file_exists($fullpath))
      {
	$this->debug['ClientError'] = "File $fullpath does not exists.";
      }
      elseif(is_dir($fullpath))
      {
	$this->debug['ClientError'] = "$fullpath is a directory.";
      }
      else
      {
	$handle = fopen($fullpath, "r");
	$contents = fread($handle, filesize($fullpath));
	fclose($handle);
	$response = $this->sendData($contents, $filename, $description);
	if($this->method == self::NUSOAP)
	{
	  if($response['DDOC']['Action'] == "ErrorSVR")
	  {
	    $this->debug['ErrorSVR'] = $response['DDOC']['ErrorSVR'];
	  }
	  else
	  {
	    $UID = $response['DDOC']['SendData']['Document']['UID'];
	  }
	}
	else if($this->method == self::PHP_SOAP)
	{
	  if($response->DDOC->Action == "ErrorSVR")
	  {
	    $this->debug['ErrorSVR'] = $response->DDOC->ErrorSVR;
	  }
	  else
	  {
	    $UID = $response->DDOC->SendData->Document->UID;
	  }
	}
      }
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $UID = false;
    }
    return $UID;
  }
  
  /*
   * Restituisce l'array contenente le informazioni per il debug.
   * 
   */
  function getDebug()
  {
    return $this->debug;
  }
  
  
  /*
   * Stampa le informazioni di debug.
   * 
   */
  function debug()
  {
    if(isset($this->debug['Exception']))
    {
      print "<b>Exception</b><br>";
      print "<pre>".$this->debug['Exception']."</pre>";
    }
    if(isset($this->debug['ClientError']))
    {
      print "<b>ClientError</b><br>";
      print "<pre>".$this->debug['ClientError']."</pre>";
    }
    if(isset($this->debug['ErrorSVR']))
    {
      print "<b>ErrorSVR</b><br>";
      print "<pre>".$this->debug['ErrorSVR']."</pre>";
    }
    if(isset($this->debug['Request']))
    {
      print "<b>Request</b><br>";
      print $this->debug['Request'];
    }
    if(isset($this->debug['RequestHeader']))
    {
      print "<b>RequestHeader</b><br>";
      print $this->debug['RequestHeader'];
    }
    if(isset($this->debug['Response']))
    {
      print "<b>Response</b><br>";
      print $this->debug['Response'];
    }
    if(isset($this->debug['ResponseHeader']))
    {
      print "<b>ResponseHeader</b><br>";
      print $this->debug['ResponseHeader'];
    }
    if(isset($this->debug['debug']))
    {
      print "<b>Debug</b><br>";
      print "<pre>".$this->debug['debug']."</pre>";
    }
  }
  
  /*
   * Restituisce il primo messaggio di errore ricevuto.
   * 
   */
  function getError()
  {
    $error = "";
    if(isset($this->debug['ClientError']))
    {
      $error = $this->debug['ClientError'];
    }
    else if(isset($this->debug['ErrorSVR']))
    {
      $error = $this->debug['ErrorSVR'];
    }
    else if(isset($this->debug['Exception']))
    {
      $error = $this->debug['Exception']['Message'];
    }
    return $error;
  }
}
?>

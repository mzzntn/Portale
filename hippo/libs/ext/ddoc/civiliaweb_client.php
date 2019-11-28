<?php  
/*
 * *** SUAPClient ***
 * 
 * Libreria di comunicazione con server SUAP per la ricezione di 
 * documenti tramite PHP SOAP o nusoap.
 * 
 * Il metodo di default per l'esecuzione delle richieste e' tramite nusoap.
 * Per utilizzare PHP SOAP sara' necessario richiamare la funzione
 * SUAPClient::usePHPSOAP() subito dopo l'inizializzazione.
 * 
 * *** Esempi di utilizzo ***
 
 * Recupero delle informazioni di un file
 * --------------------------------------------------------------------------
 * require_once("nusoap/nusoap.php");
 * $sc = new SUAPClient($url, $username, $password, $codiceEnte);
 * $response = $sc->get("20130509185056_OW_AFFIS_276871");
 * if($response){print_r($response);//file recuperato, stampo le informazioni}
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 * Recupero delle informazioni di un file tramite PHP SOAP
 * --------------------------------------------------------------------------
 * $sc = new SUAPClient($url, $username, $password, $codiceEnte);
 * $sc->usePHPSOAP();
 * $response = $sc->get("20130509185056_OW_AFFIS_276871");
 * if($response){print_r($response);//file recuperato, stampo le informazioni}
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 * Recupero e salvataggio di un file
 * --------------------------------------------------------------------------
 * $sc = new SUAPClient($url, $username, $password, $codiceEnte);
 * $response = $sc->getAndSave("20130510140853_OW_AFFIS_276874","docs/", "filename.ext");
 * if($response){
 * echo "<a href='{$response['path']}'>{$response['filename']}</a>";
 * //file recuperato e salvato, mostro un link al file
 * }
 * else{$sc->debug();//stampo le informazioni di debug}
 * 
 */
class SUAPClient
{
  const NUSOAP = 0;
  const PHP_SOAP = 1;
  private $debug;
  var $username;
  var $password;
  var $codiceEnte;
  var $method;
  var $wsdl;
    
  public function __construct($wsdl, $username, $password, $codiceEnte) 
  { 
    $this->method = self::NUSOAP;
    $this->wsdl = $wsdl;
    $this->username = $username;
    $this->password = $password;
    $this->codiceEnte = $codiceEnte;
    $this->debug = array();
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
  
  private function process($params, $raw=false)
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
	  $this->debug['ClientError'] = "Fatal error: can't find class nusoap_client. Please check nusoap library installation, version (>=0.9.5) and correct loading before calling SUAPClient methods.";
	}
	else
	{
	  $client = new nusoap_client(str_replace("?wsdl","",$this->wsdl));
	  if($raw)
	  {
	    #$msg = $client->serializeEnvelope($params);
	    $response = $client->send($params, str_replace("?wsdl","",$this->wsdl));
	  }
	  else
	  {
	    $client->soap_defencoding = 'UTF-8';
	    $client->decode_utf8 = false;
	    $client->useHTTPPersistentConnection();
	    $response = $client->call('allegatoPratica', $params, $this->wsdl/*, "allegatoPratica"*/); // togliere commento a urn:process se non funziona
	  }

	  $reqData = explode("\r\n\r\n",$client->request);
	  $resData = explode("\r\n\r\n",$client->response);
	  
	  $this->debug['RequestRaw'] = $reqData[1];
	  $this->debug['Request'] = "<pre>".htmlspecialchars($reqData[1], ENT_QUOTES)."</pre>";
	  $this->debug['RequestHeader'] = "<pre>".htmlspecialchars($reqData[0], ENT_QUOTES)."</pre>";
	  $this->debug['ResponseRaw'] = $resData[1];
	  $this->debug['Response'] = "<pre>".htmlspecialchars($resData[1], ENT_QUOTES)."</pre>";
	  $this->debug['ResponseHeader'] = "<pre>".htmlspecialchars($resData[0], ENT_QUOTES)."</pre>";
  	  $this->debug['debug'] = "<pre>".htmlspecialchars($client->debug_str, ENT_QUOTES)."</pre>";      
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
    
  private function getData($uid)
  {
    $response = false;
    try
    {
	/*$info = new StdClass();
	$info->username = $this->username;
	$info->password = $this->password;
	$info->codiceEnte = $this->codiceEnte;
	$info->pkAllegato = $uid;
      $params = array
      (
	#$info
	#"allegatoPratica" => array
	#(
	  "username" => $this->username,
	  "password" => $this->password,
	  "codiceEnte" => $this->codiceEnte,
	  "pkAllegato" => $uid,
	#),
      );
      $response = $this->process($params);*/
      
      $xml = "<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:ser='http://servizi.suap.ws.pa.dedagroup.it'>
	<soapenv:Header/>
	<soapenv:Body>
	    <ser:allegatoPratica>
	      <ser:username>{$this->username}</ser:username>
	      <ser:password>{$this->password}</ser:password>
	      <ser:codiceEnte>{$this->codiceEnte}</ser:codiceEnte>
	      <ser:pkAllegato>{$uid}</ser:pkAllegato>
	    </ser:allegatoPratica>
	</soapenv:Body>
      </soapenv:Envelope>";
      $response = $this->process($xml, true);
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $response = false;
    }
    return $response;
  }
  
  /*
   * Invia una richiesta al server SUAP e recupera i dati di un file, 
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
  function getSoap($uid)
  {
    $doc = false;
    try
    {      
      $response = $this->getData($uid);
      if($response)
      {
	if($this->method == self::NUSOAP)
	{	      
	  $doc = array();
// 	    $doc["filename"] = $response['allegatoPraticaResponse']['return'];
	  /* inutile se non ci sono altri campi variabili
	  foreach($response['DDOC']['GetData']['Document'] as $key => $value)
	  {
	    if(is_array($value) && $key == "MetaData")
	    {
		$arraykey = "";
		$arravalue = "";
		$doc[$arraykey] = $arravalue;
	    }
	    else if($key != "BinaryData")
	    {
	      $doc[$key] = $value;
	    }*/
		/*echo "<pre>";
		print_r($response);
		echo "</pre>";
		exit();*/			
	    $doc["content"] = base64_decode($response['return']);
	  }
	}
	else if($this->method == self::PHP_SOAP)
	{
	  $doc = array();
// 	    $doc["filename"] = $response->allegatoPraticaResponse->return;
	  
	  /* inutile se non ci sono altri campi variabili
	  foreach($response->DDOC->GetData->Document as $key => $value) 
	  {
	    if(is_object($value) && $key == "MetaData")
	    {
		$arraykey = "";
		$arravalue = "";
		$doc[$arraykey] = $arravalue;
	    }
	    else if($key != "BinaryData")
	    {
	      $doc[$key] = $value;
	    }
	  }*/
// 	    $doc["content"] = $response->allegatoPraticaResponse->return;	
//	}
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
   * Invia una richiesta al server SUAP e recupera i dati di un file, 
   * dato un UID.
   * 
   * Utilizza CURL per recuperare file di grandi dimensioni, da utilizzare
   * se la versione di libxml è >= 2.7.3 e ci sono problemi nel recupero dei file
   * 
   */
  function get($uid)
  {
    $doc = false;
    try
    {  
      $ch = curl_init();

      $rawRequest = "<soapenv:Envelope xmlns:soapenv='http://schemas.xmlsoap.org/soap/envelope/' xmlns:ser='http://servizi.suap.ws.pa.dedagroup.it'>
	<soapenv:Header/>
	<soapenv:Body>
	    <ser:allegatoPratica>
	      <ser:username>{$this->username}</ser:username>
	      <ser:password>{$this->password}</ser:password>
	      <ser:codiceEnte>{$this->codiceEnte}</ser:codiceEnte>
	      <ser:pkAllegato>{$uid}</ser:pkAllegato>
	    </ser:allegatoPratica>
	</soapenv:Body>
      </soapenv:Envelope>";

      curl_setopt($ch, CURLOPT_URL,            str_replace("?wsdl","",$this->wsdl));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
      curl_setopt($ch, CURLOPT_POST,           1 );
      curl_setopt($ch, CURLOPT_POSTFIELDS,     $rawRequest ); 
      curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml')); 
      
      $result = curl_exec($ch);
      $doc = array();

      $delimiter1 = "<ns1:return>";
      $delimiter2 = "</ns1:return>";
      $pos1 = stripos($result, $delimiter1)+strlen($delimiter1);
      $pos2 = stripos($result, $delimiter2);
      
      $doc['content'] = base64_decode(substr($result, $pos1, ($pos2-$pos1)));
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $doc = false;
    }
    
    return $doc;
  }
  
  /*
   * Invia una richiesta al server SUAP, recupera i dati di un file, 
   * dato un UID, e salva il file nel percorso specificato.
   * 
   * Restituisce false se non riesce a recuperare il file (utilizzare la
   * funzione debug() o getError() per ulteriori dettagli) altrimenti
   * restitituisce un array associativo contenente i seguenti campi:
   *  filename -> il nome del file recuperato
   *  path -> il percorso completo del file salvato
   *  altre chiavi -> i vari metadati del file
   */
  function getAndSave($uid, $savePath, $filename)
  {
    $doc = false;    
    try
    {
      $doc = $this->get($uid);
      if($doc)
      {
	$doc['path'] = $savePath.$filename;
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
   * Invia una richiesta al server SUAP, recupera i dati di un file, 
   * dato un UID, e restituisce il contenuto del file.
   * 
   */
  function getContent($uid)
  {
    $doc = $this->get($uid);
    return $doc['content'];
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

<?php
/*
 * *** AlfrescoClient ***
 * 
 * Libreria di comunicazione con connettore Alfresco per l'invio e la ricezione di 
 * documenti.
 * 
 * *** Esempi di utilizzo ***
 * 
 * Invio di un file
 * --------------------------------------------------------------------------
 * include("alfresco_client.php");
 * $ac = new AlfrescoClient($url);
 * $response = $ac->upload("filename.ext", "path/to/filename/",
 * "file description");
 * if($response!=false){echo "UID: $response";}//invio completato con successo
 * else{$ac->debug();//}stampo le informazioni di debug
 * 
 * Recupero delle informazioni di un file
 * --------------------------------------------------------------------------
 * include("alfresco_client.php");
 * $ac = new AlfrescoClient($url);
 * $response = $ac->get("4ec9d748-2d51-4e64-b7a4-23086fb8477b");
 * if($response!=false){print_r($response);}//file recuperato, stampo le informazioni
 * else{$ac->debug();}//stampo le informazioni di debug
 * 
 * Recupero e salvataggio di un file
 * --------------------------------------------------------------------------
 * include("alfresco_client.php");
 * $ac = new AlfrescoClient($url);
 * $response = $ac->getAndSave("4ec9d748-2d51-4e64-b7a4-23086fb8477b","docs/");
 * if($response!=false){
 * echo "<a href='{$response['path']}'>{$response['filename']}</a>";
 * //file recuperato e salvato, mostro un link al file
 * }
 * else{$ac->debug();}//stampo le informazioni di debug
 * 
 */
class AlfrescoClient
{
  private $debug;
  var $url;
  
  public function __construct($url) 
  { 
    $this->url = $url;
    $this->debug = array();
  }
  
  private function logException($e)
  {
      $this->debug['Exception']['Message'] = $e->getMessage();
      $this->debug['Exception']['Code'] = $e->getCode();
      $this->debug['Exception']['File'] = $e->getFile();
      $this->debug['Exception']['Line'] = $e->getLine();
  }
  
  private function process($xml)
  {
    $this->debug = array();
    $response = false;
    if(strlen($this->url)<1)
    {
      $this->debug['ClientError'] = "Fatal error: url needed to process request.";
    }
    else
    {
      try
      {
	$this->debug['RequestRaw'] = $xml;
	$this->debug['Request'] = htmlspecialchars($xml, ENT_QUOTES);
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL,            $this->url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt($ch, CURLOPT_POST,           1 );
	curl_setopt($ch, CURLOPT_POSTFIELDS,     $xml ); 
	curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml')); 

	$output = curl_exec($ch);
	$this->debug['ResponseRaw'] = $output;
	$this->debug['Response'] = htmlspecialchars($output, ENT_QUOTES);
	
	if(strlen($output)>0)
	{
	  $response = new SimpleXMLElement($output);
	  $this->debug['StatusCode'] = $response->esito->codice[0];    
	  $this->debug['StatusMessage'] = $response->esito->descrizione[0];    
	}
      }
      catch(Exception $e)
      {
	$this->logException($e);
	$response = false;
      }
    }
    return $response;
  }
  
  /*
   * Invia una richiesta al connettore alfresco e recupera i dati di un file, 
   * dato un UID.
   * 
   * Restituisce false se non riesce a recuperare il file (utilizzare la
   * funzione debug() o getError() per ulteriori dettagli) altrimenti
   * restitituisce un array associativo contenente i seguenti campi:
   *  filename -> il nome del file recuperato
   *  content -> il contenuto del file
   *  description -> la descrizione del file
   * 
   */
  function get($uid)
  {    
    $doc = false;
    try
    {
      $xml = "<?xml version='1.0'?>
	  <input>
	      <metodo>RICERCA</metodo>
	      <messaggio>
		  <file_id>$uid</file_id>
	      </messaggio>
	  </input>";
      
      $response = $this->process($xml);   
      
      if($response->esito->codice[0]=="1000" && $response->messaggio->file_name[0] != "")
      {
	$doc = array();
	$doc['filename'] = $response->messaggio->file_name[0];
	$doc['content'] = base64_decode($response->messaggio->file_content[0]);
      }
      else
      {
	$doc = false;
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
   * Invia una richiesta al connettore alfresco, recupera i dati di un file, 
   * dato un UID, e salva il file nel percorso specificato.
   * 
   * Restituisce false se non riesce a recuperare il file (utilizzare la
   * funzione debug() o getError() per ulteriori dettagli) altrimenti
   * restitituisce un array associativo contenente i seguenti campi:
   *  filename -> il nome del file recuperato
   *  path -> il percorso completo del file salvato
   *  description -> la descrizione del file
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
	  fwrite($fp, base64_decode($doc['content']));
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
   * Invia una richiesta al connettore alfresco, recupera i dati di un file, 
   * dato un UID, e restituisce il contenuto del file.
   * 
   */
  function getContent($uid)
  {
    $doc = $this->get($uid);
    return $doc['content'];
  }
  
  /*
   * Invia un file al connettore alfresco.
   * 
   * Restituisce false se non riesce ad inviare il file (utilizzare la
   * funzione debug() per ulteriori dettagli) altrimenti restitituisce l'UID
   * del documento salvato.
   * 
   */
  function upload($filename, $filepath, $description)
  {
    $uid = false;
    try
    {
      $fullpath = $filepath.$filename;
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
	$contents = base64_encode($contents);
	
	$xml = "<?xml version='1.0'?>
	<input>
	  <messaggio>
	    <file_description>$description</file_description>
	    <file_name>$filename</file_name>
	    <file_content>$contents</file_content>
	  </messaggio>
	</input>";
	
	$response = $this->process($xml);   
	
	if($response->esito->codice[0]=="1000" && $response->messaggio->file_id[0] != "")
	{
	  $uid = $response->messaggio->file_id[0];
	}
	else
	{
	  $response = false;
	}
      }
    }
    catch(Exception $e)
    {
      $this->logException($e);
      $uid = false;
    }
    return $uid;
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
      print "<pre>".print_r($this->debug['Exception'])."</pre>";
    }
    if(isset($this->debug['ClientError']))
    {
      print "<b>ClientError</b><br>";
      print "<pre>".$this->debug['ClientError']."</pre>";
    }
    if(isset($this->debug['StatusMessage']))
    {
      print "<b>StatusMessage</b><br>";
      print "<pre>".$this->debug['StatusMessage']."</pre>";
    }
    if(isset($this->debug['Request']))
    {
      print "<b>Request</b><br>";
      print "<pre>".$this->debug['Request']."</pre>";
    }
    if(isset($this->debug['Response']))
    {
      print "<b>Response</b><br>";
      print "<pre>".$this->debug['Response']."</pre>";
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
    else if(isset($this->debug['StatusMessage']))
    {
      $error = $this->debug['StatusMessage'];
    }
    else if(isset($this->debug['Exception']))
    {
      $error = $this->debug['Exception']['Message'];
    }
    return $error;
  }
}
?>
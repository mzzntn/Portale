<?

define ('DT_ERROR_NULL', 1);
define ('DT_ERROR_FORMAT', 2);
  
class DataT{
  var $data;
  var $pregExp;
  var $errors;
  
  function DataT(){
    $this->pregExp = '';
  }
  
  function set($value, $from=''){
    $this->data = $value;
  }
  
  function get($for=''){
      if ($for == 'db') return removeXSS($this->data);
    return $this->data;
  }
  
  function check($notNull=0){
    if ($this->pregExp && !preg_match($this->pregExp, $this->data))
      return DT_ERROR_FORMAT;
    if ($notNull & !$this->data) return DT_ERROR_NULL;
    return 0;
  }

  /**
  * void clear()
  * Clear all data in the object
  **/
  function clear(){
    unset($this->data);
  }
  
  function store(){
    #virtual
  }

  function sanitizeSQL($value){
      // Codice aggiunto da Irene il 16.12.2016 per risolvere le problematiche di inserimento caratteri speciali da web e da ws
      // WARNING: per far funzionare la conversione dei caratteri speciali è necessario aggiornare /usr/local/lib/hippo_nuova_grafica/libs/String/StringUtils.php ed assicurarsi che contenga la classe StringParser      
//       $debug = strpos($value, "NUOVO UFFICIO SPECIALE")!==false;
//       if($debug){echo "original value: [$value] encoding ".mb_detect_encoding($value)."<br>\n";}
      $value = StringParser::parse($value);  
//       if($debug){
// 	echo "parsed value: [$value] encoding ".mb_detect_encoding($value)."<br>\n";
// 	echo "parsed value entities: ".htmlentities($value)."<br>\n";
// 	exit();
//       }
      
      // vecchio codice che risolveva parzialmente il problema ma si perdevano caratteri per strada
     /*$encoding = mb_detect_encoding($value);
      
      //$showDebug = strpos($value, "VIA MONSIGNOR E. NICODEMO N. 13")!==false;
      if($encoding == "") {
	// impossibile determinare la codifica, tentativo di conversione a utf-8
	$newValue = utf8_encode($value);
	if(trim($newValue) != "") {
	  $value = $newValue;
	  $encoding = mb_detect_encoding($value);
	}
      }
      //if($showDebug){echo "$value has encoding $encoding<br>";}
      if($encoding=="UTF-8") {
	$patternU = '/[^a-zA-Z0-9 \.:,;\-_=\?!\'"\(\)\/%&€@\\\àèéìòùÀÈÉÌÒÙ<>\+\*°]/u';
	$value = preg_replace("/€/", "&euro;", $value);
        $value = preg_replace("/–/", "-", $value);
	$newValue = utf8_decode(preg_replace($patternU, "", $newValue));
	if(trim($newValue) == ""){$newValue = utf8_decode(preg_replace($patternU, "", utf8_encode($value)));}
	if(trim($newValue) == ""){$newValue = utf8_decode(preg_replace(str_replace("/u","/",$patternU), "", $value));}
	if(trim($newValue) != ""){$value = $newValue;}
	//if($showDebug){echo "Parsed value is $value<br>";}
      }*/

      // codice originale per la sanitizzazione dell'input prima dell'inserimento in DB      
      $cerca = array(chr(145), chr(146), chr(39), chr(147), chr(148), chr(34), chr(96));
      $token = array('\'', '\'', '\'', '"', '"', '"', '\'');
      $value = str_replace($cerca, $token, $value);
      $value = str_replace("\\\\", "\\", $value);
      $value = str_replace('\"', '"', $value);
      $value = str_replace ("\\'", "'", $value);
      $value = str_replace ("'", "''", $value);
      $value = str_replace("\\", "\\\\", $value);   
      
      return $value;
  }

}


?>

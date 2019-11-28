<?
/**
* string[] name_hierarchy(string)
* 
**/
function name_hierarchy($name){
  $partsArray = explode('.', $name);
  for ($i=0; $i<sizeof($partsArray); $i++){
    $prefix = ($i==0)?'':$hierarchy[$i-1];
    $hierarchy[$i] = $prefix.'.'.$partsArray[$i];
  }
  return $hierarchy;
}

/**
* string remove_accents(string)
* 
**/
function remove_accents($string){
 return strtr(
  strtr($string,
   'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
   'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
  array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
   'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}

function stripnewlines($string){
  $string = str_replace("\n", '', $string);
  $string = str_replace("\r", '', $string);
  return $string;
}

#function _($string){
#  return $string;
#}

/* StringParser - 2016.12.16
 * classe per la conversione di stringhe con codifica mista 
 * in stringhe UTF8 con codici html valide per il db ed utilizzabili dall'utente
 * indipendentemente dai caratteri speciali contenuti
 *
 * Utilizzo:
 *
 *    $stringaConvertita = StringParser::parse($string);
 */
class StringParser {
  
  /* Questa funzione converte una stringa di qualunque tipo, indipendentemente
   * dall'encoding e dai caratteri contenuti, e trasforma tutti i caratteri speciali
   * in codici HTML tranne nel caso in cui facciano già parte di un codice html valido
   * 
   */
  function parse($string, $xml=false, $html=false) {
    // individuo l'encoding della stringa
    $encoding = mb_detect_encoding($string);
//     echo "string received is $string, encoding is $encoding and xml is [$xml]<br>";
    
    // trovo tutte le & che non fanno parte di un'entita' html e le converto in &amp; o &#38; per l'xml
    $lastIndex = 0;
    $totAmpersands = substr_count($string, "&");
    for($i = 0; $i<$totAmpersands && $lastIndex<strlen($string); $i++) {
      $this_ampersand = strpos($string, "&", $lastIndex);
      $this_semicolon = strpos($string, ";", $this_ampersand);
      $next_ampersand = strpos($string, "&", $this_ampersand+1)-1;
      $substr_end = $this_semicolon<$next_ampersand||$next_ampersand<0?$this_semicolon:$next_ampersand;
      $lastIndex = $substr_end;
      $code = substr($string, $this_ampersand, $substr_end-$this_ampersand+1);
      $decoded = html_entity_decode($code, ENT_QUOTES, 'UTF-8');
      if(strlen($code)>0 && !preg_match("/&#*[^;]+;/",$code) && strpos($string,"&")!==0) {
	$string = substr_replace($string, ($xml?"&#38;":"&amp;"), $this_ampersand, 1);
      }
    }
    
    if($xml) {
      // converto tutti i codici html già presenti in caratteri, per poterli riconvertire in codici xml
//       echo "request of xml conversion of $string (encoding $encoding)<br>";
//       $newValue = utf8_encode($string);
//       if(trim($newValue) != "") {
// 	$string = $newValue;
//       }
//       $encoding = mb_detect_encoding($string);
      $string = $encoding=="UTF-8"?html_entity_decode($string, ENT_QUOTES, 'utf-8'):html_entity_decode($string);
      $string = html_entity_decode($string); // rifaccio il decode un'altra volta, perchè il primo potrebbe perdersi alcune accentate
      // rimpiazzo &euro; e &deg;, perchè non html_entity_decode non li sostituisce
      $string = str_replace("&euro;","&#8364;",$string);
      $string = str_replace("&deg;","&#176;",$string);
//       echo "now string is $string [".htmlentities($string)."] (encoding $encoding)<br>";
    }

    // sostituisco apici e doppi apici con l'entità xml corrispondente per evitare problemi durante l'inserimento in db e nei campi di testo (li lascio su html per permettere l'inserimento di html da tinymce e simili)
    $string = str_replace('"', $xml?"&#34;":$html?"&quot;":"\"", $string);
    $string = str_replace("'", $xml?"&#39;":"'", $string);
    $string = str_replace("<", $xml?"&#60;":"<", $string);
    $string = str_replace(">", $xml?"&#62;":">", $string);
    
    // individuo la presenza di caratteri non ascii all'interno della stringa
//       preg_match_all('~[^\x00-\x7F]~u', $string, $unicode);
    preg_match_all('/[^[:ascii:]"\'<>]/u', $string, $unicode); 
//     echo "non ascii characters matched with unicode regex: ".count($unicode[0])."<br>\n";
    if(count($unicode[0])<1) {
      preg_match_all('/[^[:ascii:]"\'<>]/', $string, $unicode);
//       echo "non ascii characters matched with non unicode regex: ".count($unicode[0])."<br>\n";
    }
//     echo "unicode [{$unicode[0][0]}] has length ".strlen($unicode[0][0])."<br>\n";
//     echo "unicode [".utf8_decode($unicode[0][0])."] has length ".strlen(utf8_decode($unicode[0][0]))."<br>\n";
//     print_r($unicode); echo "<br>\n";
      
    if(count($unicode[0])) {
      // converto i caratteri non ascii nelle entity corrispondenti oppure nei corrispondenti codici numerici html (decimali)
      foreach($unicode[0] as $match) {
// 	echo "unicode [$match] encoding is ".mb_detect_encoding($match)." and ";
	$htmlEntity = htmlentities($match);
	if(preg_match("/&#*[a-zA-Z0-9]+;/",$htmlEntity) && !$xml) {
	  // se è un'html entity valida e non stiamo convertendo per xml, uso quella
	  $string = str_replace($match, $htmlEntity, $string);
// 	  echo "has been encoded to $htmlEntity ".htmlentities($htmlEntity)."<br>\n";
	}
	else {
	  // altrimenti cerco di convertire in codice html
	  $decimal = self::uniord($match);
	  if($decimal>0) {
	    $htmlCode = "&#{$decimal};";
	    $string = str_replace($match, $htmlCode, $string);
// 	    echo "has been encoded to $htmlCode ".htmlentities($htmlCode)."<br>\n";
	  } else {
	    // se non ci riesco, converto in UTF8 e ci riprovo
	    $decimal = self::uniord(utf8_encode($match));
	    if($decimal>0) {
	      $htmlCode = "&#{$decimal};";
	      $string = str_replace($match, $htmlCode, $string);
// 	      echo "has been encoded to $htmlCode ".htmlentities($htmlCode)."<br>\n";
	    } else {
	      // se non ci riesco, converto in ascii e ci riprovo
	      $decimal = self::uniord(utf8_decode($match));
	      if($decimal>0) {
		$htmlCode = "&#{$decimal};";
		$string = str_replace($match, $htmlCode, $string);
// 		echo "has been encoded to $htmlCode ".htmlentities($htmlCode)."<br>\n";
	      }
	    }
	  }
	}
      }
    }
    
//     echo "<hr>\n";
      
    // visto che tutti i caratteri speciali sono stati convertiti in htmlentities o codici html, convertire stringa in ascii
    $newValue = utf8_decode($string);
    if(trim($newValue) != "") {
      $string = $newValue;
    }
    $encoding = mb_detect_encoding($string);
//     echo "now string encoding is $encoding<br>";
    
    
//     if($xml) {
//       echo "xml converted string is $string [".htmlentities($string)."]<hr>";
//     }
    return $string;
  }
  
  // questa funzione aveva dei problemi, meglio usare uniord
//   private function unicode_ord($c) {
//     if (ord($c{0}) >=0 && ord($c{0}) <= 127)
// 	return ord($c{0});
//     if (ord($c{0}) >= 192 && ord($c{0}) <= 223)
// 	return (ord($c{0})-192)*64 + (ord($c{1})-128);
//     if (ord($c{0}) >= 224 && ord($c{0}) <= 239)
// 	return (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
//     if (ord($c{0}) >= 240 && ord($c{0}) <= 247)
// 	return (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
//     if (ord($c{0}) >= 248 && ord($c{0}) <= 251)
// 	return (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
//     if (ord($c{0}) >= 252 && ord($c{0}) <= 253)
// 	return (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
//     if (ord($c{0}) >= 254 && ord($c{0}) <= 255)    //  error
// 	return FALSE;
//     return 0;
//   }
  
  private function uniord($u) {
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
  }
  
  private function replace_num_entity($ord)
  {
      $ord = $ord[1];
      if (preg_match('/^x([0-9a-f]+)$/i', $ord, $match)) {
	$ord = hexdec($match[1]);
      } else {
	$ord = intval($ord);
      }
      
      $no_bytes = 0;
      $byte = array();

      if ($ord < 128) {
	return chr($ord);
      } elseif ($ord < 2048) {
	$no_bytes = 2;
      }
      elseif ($ord < 65536) {
	$no_bytes = 3;
      }
      elseif ($ord < 1114112) {
	$no_bytes = 4;
      }
      else {
	return;
      }

      switch($no_bytes) {
	case 2:
	  $prefix = array(31, 192);
	  break;
	case 3:
	  $prefix = array(15, 224);
	  break;
	case 4:
	  $prefix = array(7, 240);
	  break;
      }

      for ($i = 0; $i < $no_bytes; $i++) {
	$byte[$no_bytes - $i - 1] = (($ord & (63 * pow(2, 6 * $i))) / pow(2, 6 * $i)) & 63 | 128;
      }

      $byte[0] = ($byte[0] & $prefix[0]) | $prefix[1];

      $ret = '';
      for ($i = 0; $i < $no_bytes; $i++) {
	$ret .= chr($byte[$i]);
      }

      return $ret;
  }
}
  
?>

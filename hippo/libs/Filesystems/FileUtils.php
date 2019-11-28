<?
/**
* bool files_compare_md5(string, string)
* Check if two files have the same md5 hash
*
* @return true if the files have the same md5, false otherwise
**/
function files_compare_md5($file1, $file2){
  $md5_1 = md5_file($file1);
  $md5_2 = md5_file($file2);
  if ($md5_1 == $md5_2) return true;
  return false;
}

function createPath($path){
  if (!$path) return;
  if ( strlen( $path) < 3) return 1;
  if ( is_dir($path) ) return;
  if  (dirname($path) == $path) return;
createPath(dirname($path));
$oldumask=umask(0002);
  mkdir($path);
umask($oldmask);
}



/**
* mixed search_dir(string, string)
* Searches the filesystem starting at $base for files named $fileName (wildcards allowed)
*
* Returns the path where a file named $fileName was found, relative to $base, if it had no wildcards;
* an array containing the paths if wildcards were supplied; false if no matching file was found.
* 
* @internal the third parameter is used internally for recursion
**/
function search_dir($base, $fileName, $relativePath=''){
  global $IMP;
  $IMP->debug("Searching dir $base for $fileName starting at $relativePath", 5);
  $directory = $base.'/'.$relativePath;
  if (!is_dir($directory)) return false;
  $d = dir($directory);
  $wildCard = false;
  if ( strpos($fileName, '*') !== false){
    $wildCard = 1;
    $pregFile = str_replace('.', '\.', $fileName);
    $pregFile = str_replace('*', '.+', $pregFile);
    $IMP->debug("Found wildcard", 8);
  }
  $results = array();
  while (false !== ($entry = $d->read())) {
    if ($entry[strlen($entry)-1] == '~') continue; #skip backups
    if ($entry[0] == '.') continue; #skip hidden files and dirs
    $entryFullPath = $directory.'/'.$entry;
    $check = 0;
    if ($wildCard && preg_match("/$pregFile/", $entry) ) $check = 1;
    elseif ($entry == $fileName) $check = 1;
    if ($check){
      $IMP->debug("Found $entryFullPath!", 5);
      if ($wildCard) array_push($results, $entryFullPath);
      else return $entryFullPath;
    }
    $newRelativePath = $relativePath.'/'.$entry;
    if ($entry != '.' && $entry != '..'  && is_dir($entryFullPath)){
      $res = search_dir($base, $fileName, $newRelativePath);
      if ($res){
        if ($wildCard) $results = array_merge($results, $res);
        else return $res;
      }
    }
  }
  if ($wildCard) return $results;
  return false;
}

/**
* 
**/
function find_file($baseArray, $fileName){
  global $IMP;
  $IMP->debug("Looking for $fileName in paths:", 6);
  $IMP->debug($baseArray, 6);
  if ( !is_array($baseArray) ) $baseArray = array($baseArray);
  $wildcard = false;
  if ( strpos($fileName, '*') !== false) $wildCard = true;
  if ($wildCard) $results = array();
  foreach ($baseArray as $base){
    $foundFiles = search_dir($base, $fileName);
    if ($foundFiles){
      if ($wildCard) $results = array_merge($results, $foundFiles);
      else return $foundFiles;
    }
  }
  if ($wildCard) return $results;
  return 0;
}

function deleteDirectory($dirname,$only_empty=false) {
   if (!is_dir($dirname))
       return false;
   $dscan = array(realpath($dirname));
   $darr = array();
   while (!empty($dscan)) {
       $dcur = array_pop($dscan);
       $darr[] = $dcur;
       if ($d=opendir($dcur)) {
           while ($f=readdir($d)) {
               if ($f=='.' || $f=='..')
                   continue;
               $f=$dcur.'/'.$f;
               if (is_dir($f))
                   $dscan[] = $f;
               else
                   unlink($f);
           }
           closedir($d);
       }
   }
   $i_until = ($only_empty)? 1 : 0;
   for ($i=count($darr)-1; $i>=$i_until; $i--) {
       #echo "\nDeleting '".$darr[$i]."' ... ";
       rmdir($darr[$i]);
   }
   return (($only_empty)? (count(scandir)<=2) : (!is_dir($dirname)));
}

function get_estensione($fileName) {
  $ext = "";
  if (preg_match('/(\.\w+)$/', $fileName, $matches)) {$ext = $matches[1];}
  return $ext;
}

function sanitizeFilename($fileName){
  $fileName = preg_replace("/[^a-zA-Z0-9\-_ \.]/i", "", str_replace("'","",html_entity_decode($fileName)));
  $fileName = str_replace(" ","_", $fileName);
  $fileName = str_replace("..",".", $fileName);
  if (strlen($fileName) > 50) $fileName = substr($fileName, 0, 40).substr($fileName, -8, 8);
  return $fileName;
}

/* Classe per la verifica dei file firmati
 * Irene - 2019.07.01
 *
 */

class VerifiedFile {  
  var $showDebug;
  var $fullPath;
  var $fileName;
  var $fileSize;
  var $extractedFileName;
  var $path;
  var $signatures;
  var $type;
  var $signed;
  var $mimeType;
  var $extractedFile;
  var $extractedFilePath;
  var $signatureFilePath;
  var $pemFilePath;
  var $error;
  var $sigleFirma = array (
    "subject" => array ( // soggetto
      "C" => "Paese",
      "O" => "Organizzazione",
      "CN" => "Firmatario",
      #"serialNumber" => "Codice fiscale",
      "GN" => "Nome",
      "SN" => "Cognome",
      "dnQualifier" => "Qualificatore DN", 
      "title" => "Titolo", 
      "E" => "Email", // email address
      "T" => "Localit&agrave;", // locality
      "ST" => "", // state of residence
      "OU" => "Unit&agrave; organizzativa", // name of the organizational unit to which the certificate owner belongs
      "C" => "Stato", // country of residence
      "STREET" => "Indirizzo", // street address
      "ALL" => "", // complete distinguished name
    ),
    "issuer" => array ( // certificate issuer
      "CN" => "Nome", // common name
      "E" => "Email", // email address
      "T" => "Localit&agrave;", // locality
      "ST" => "", // state of residence
      "O" => "Ente certificatore", // organization to which the certificate issuer belongs
      "OU" => "Tipo", // name of the organizational unit to which the certificate issuer belongs
      "C" => "Stato", // country of residence
      "STREET" => "Indirizzo", // street address
      "ALL" => "", // complete distinguished name
      "serialNumber" => "Codice fiscale",
    ),
  );
  const UNKNOWN = 0;
  const PDF = 1;
  const P7M = 2;
  
  const NO_DEBUG = 0;
  const DEBUG_COMMENTS = 1; // mostra i messaggi di debug come commenti html, utile per ambienti di produzione
  const DEBUG_MESSAGES = 2; // mostra i messaggi di debug direttamente nella pagina, per ambienti di test

  function __construct($fullPath, $extractedFileName=NULL){
    $this->showDebug = self::NO_DEBUG;
    if(!file_exists($fullPath)) {
      // if file doesn't exist won't execute any command to avoid problems if the filename requested contains malicious strings
      // assuming that 
      $this->type = self::UNKNOWN;
      $this->error = array("message"=>"File $fullPath not found","file"=>"FileUtils.php","line"=>201,"type"=>E_WARNING);
    } else {
      $this->error = false;
      $this->fullPath = $fullPath;
      $this->fileName = basename($this->fullPath);
      $pathInfo = pathinfo($this->fullPath);
      $fileStat = stat($this->fullPath);
      $this->fileSize = $fileStat['size'];
      $this->extractedFile = false;
      $extractedPath= VARPATH.'/docs';
      if (!file_exists($extractedPath)) { createPath($extractedPath); }
      $basename = basename($fullPath);
      $this->extractedFilePath = "{$extractedPath}/extracted_{$basename}";
      $this->signatureFilePath = "{$extractedPath}/signature_{$basename}";
      $this->pemFilePath = "{$extractedPath}/pem_{$basename}";

      $this->signatures = false;
      $this->signed = NULL;
      $this->mimeType = NULL;
      
      if(is_null($extractedFileName)) {
        $this->extractedFileName = str_ireplace(".p7m","",$this->fileName);
		if (!strstr($this->extractedFileName, '.')) $this->extractedFileName = $this->extractedFileName.".pdf";
      } else {
        $this->extractedFileName = $extractedFileName;
      }
          
      if($this->getMimeType() == "application/pdf" || $this->getMimeType() == "application/x-pdf") {
        $this->type = self::PDF;
      } else if($this->getMimeType() == "application/octet-stream" || $this->getMimeType() == "application/x-pkcs7-mime") {
        $this->type = self::P7M;
      } else {
        $this->type = self::UNKNOWN;
      }
    }
  }
  
  function isPdf() {
    return $this->type == self::PDF;
  }
  
  function isP7m() {
    return $this->type == self::P7M;
  }
  
  function getError() {
    $error = false;
    if($this->error!==false) {
      $error = $this->error;
    } else {
      $error = error_get_last();
    }
    return $this->error;
  }
  
  function setDebug($debugLevel) {
    $this->showDebug = $debugLevel;
  }
  
  function debugMessage($string) {
    if($this->showDebug!=self::NO_DEBUG) {
      if(is_array($string) || is_object($string)) { 
        $string = print_r($string, true); 
        if($this->showDebug==self::DEBUG_MESSAGES) { $string = "<pre>$string</pre>";}
      }
      if($this->showDebug==self::DEBUG_COMMENTS) {
        echo "<!-- $string -->\n";
      } else if($this->showDebug==self::DEBUG_MESSAGES) {
        echo "$string<br>\n";
      }
    }
  }
  
  function getFileName() {
    return $this->fileName;
  }
  
  function getExtractedFileName() {
    return $this->extractedFileName;
  }
  
  function saveExtractedFile($path=".") {
    $result = false;
    if($this->isP7m() && $this->isSigned()) {
      if($this->extractedFile->isSigned()) {
        $result = $this->extractedFile->saveExtractedFile($path);
      } else {
        if(substr($path, -1) != DIRECTORY_SEPARATOR) { $path .= DIRECTORY_SEPARATOR; }
        $result = rename($this->extractedFilePath, $path.$this->extractedFileName);
      }
    }
    return $result;
  }
  
  function getFileSize() {
    return $this->fileSize;
  }
  
  function getExtractedFileSize() {
    $size = 0;
    try {
      if($this->extractedFile) {
        if($this->extractedFile->isSigned()) {
          $size = $this->extractedFile->getExtractedFileSize();      
        } else {
          $size = $this->extractedFile->getFileSize();
        }
      } else { $this->error = array("message"=>'$this->extractedFile file is not an object',"file"=>__FILE__,"line"=>__LINE__,"type"=>E_ERROR); } 
    } catch(Exception $e) { $this->error = array("message"=>$e->getMessage(),"file"=>__FILE__,"line"=>__LINE__,"type"=>E_ERROR); }
    return $size;
  }
  
  function getMimeType() {      
    if(is_null($this->mimeType)) {
      // controllo costante per fileinfo in base alla  versione php < 5.3  (caso mapweb)
      if(defined('FILEINFO_MIME_TYPE')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $this->mimeType = finfo_file($finfo, $this->fullPath);
        finfo_close($finfo);
      } else {
        $finfo = finfo_open(); // return mime type ala mimetype extension
        $this->mimeType = finfo_file($finfo, $this->fullPath, FILEINFO_MIME);
        finfo_close($finfo);
      }
    }
    return $this->mimeType;
  }
  
  function getExtractedFileMimeType() {
    $mime = "";
    try {
      if($this->extractedFile) {
        if($this->extractedFile->isSigned()) {
          $mime = $this->extractedFile->getExtractedFileMimeType();      
        } else {
          $mime = $this->extractedFile->getMimeType();
        }
      } else { $this->error = array("message"=>'$this->extractedFile file is not an object',"file"=>__FILE__,"line"=>__LINE__,"type"=>E_ERROR); }
    } catch(Exception $e) { $this->error = array("message"=>$e->getMessage(),"file"=>__FILE__,"line"=>__LINE__,"type"=>E_ERROR); }
    return $mime;
  }
  
  function getFileContent() {
    $content = file_get_contents($this->fullPath);
    return $content;
  }
  
  function getExtractedFileContent() {
    $content = "";
    try {
      if($this->extractedFile) {
        if($this->extractedFile->isSigned()) {
          $content = $this->extractedFile->getExtractedFileContent();      
        } else {
          $content = file_get_contents($this->extractedFilePath);
        }
      } else { $this->error = array("message"=>'$this->extractedFile file is not an object',"file"=>__FILE__,"line"=>__LINE__,"type"=>E_ERROR); }
    } catch(Exception $e) { $this->error = array("message"=>$e->getMessage(),"file"=>__FILE__,"line"=>__LINE__,"type"=>E_ERROR); }
    return $content;
  }
  
  function download($signed=true) {
    $filename = $signed?$this->getFileName():$this->getExtractedFileName();
    $fileSize = $signed?$this->getFileSize():$this->getExtractedFileSize();
    $mime = $signed?$this->getMimeType():$this->getExtractedFileMimeType();
    $content = $signed?$this->getFileContent():$this->getExtractedFileContent();
    header("Expires: ".gmdate("D, d M Y H:i:s",strtotime("+ 1 day"))." GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Content-type: ".$mime);
    if (!$_SESSION['chiosco']){
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    }

    header('Content-Length: '.$fileSize);
    header("Pragma: public");
    header("Cache-Control: max-age=0");

    header('Content-Transfer-Encoding: binary');
    
    echo $content;
  }
  
  function downloadExtracted() {
    $this->download(false);
  }
  
  function isSigned() {
    if(is_null($this->signed)) {
      $this->signed = false;
      if($this->isP7m()) { 
        $this->debugMessage("<!-- file isP7m, extracting file -->");
        $this->signed = $this->extractFile();     
        $this->debugMessage("<!-- signed: {$this->signed} -->");   
      } else if($this->isPdf()) {
        if($this->extractPem()) {
          $this->debugMessage("<!-- file pem has been extracted -->");
          $pem = file_get_contents($this->pemFilePath);
          if(strlen($pem)>0){$this->signed = true;$this->debugMessage("<!-- file is signed -->");}
        } else {
          $this->debugMessage("<!-- can't extract pem -->");
        }
      }
    }
    return $this->signed;
  }
  
  /* returns the signatures from a file (or nothing */
  function getSignatures() {
    if($this->isP7m() && $this->isSigned()) {  
      if($this->extractFile() && $this->extractPem()) {
        $this->parsePem(); // this will populate $this->signatures
        if($this->extractedFile->isSigned() && $this->extractedFile->getSignatures()) {
          $this->signatures = array_merge($this->signatures, $this->extractedFile->getSignatures());
        }
      }
    } else if($this->isPdf() && $this->isSigned()) {
      $this->parsePem(); // this will populate $this->signatures
    }
    return $this->signatures;
  }
  
  /* exctracts the original file from a p7m file */
  function extractFile() {  
    $result = false;
    exec("openssl smime -verify -inform DER -in \"{$this->fullPath}\" -noverify -out \"{$this->extractedFilePath}\"");
    if (file_exists($this->extractedFilePath)) {
      // if a signature has been extracted it's a signed file
      $this->debugMessage("<!-- file_exists(\$this->extractedFilePath) {$this->extractedFilePath} -->");
      $result = true;
    } else {
      // no DER signature extracted, let's try PEM
      exec("openssl smime -verify -inform PEM -in \"{$this->fullPath}\" -noverify -out \"{$this->extractedFilePath}\"");
      if (file_exists($this->extractedFilePath)) {
        $this->debugMessage("<!-- file_exists(\$this->extractedFilePath) {$this->extractedFilePath} -->");
        // if a signature has been extracted it's a signed file
        $result = true;
      } else {
        $this->debugMessage("<!-- error, can't extract file! -->");
      }
    }
    $this->debugMessage("<!-- creating \$this->extractedFile -->");
    $this->extractedFile = new VerifiedFile($this->extractedFilePath, $this->extractedFileName);
    return $result;
  }
  
  /* exctracts a PEM file containing the signature informations from the signed file */
  function extractPem() {
    $result = false;
    if($this->isP7m()) {
      // p7m files can be verified with openssl
      $result = exec("openssl pkcs7 -inform DER -in \"{$this->fullPath}\" -print_certs -out \"{$this->pemFilePath}\"");
      if(file_exists($this->pemFilePath)) {
        // if openssl could extract a PEM file
        $result = true;
      }
      else {
        // no DER signature extracted, let's try PEM
        $result = exec("openssl pkcs7 -inform PEM -in \"{$this->fullPath}\" -print_certs -out \"{$this->pemFilePath}\"");
        if(file_exists($this->pemFilePath)) {
          $result = true;
        }
      }  
    } else if($this->isPdf()) {
      // pdf signed files need to be parsed before verifying with openssl
      $content = file_get_contents($this->fullPath);

      $regexp = '#ByteRange *\[\s*(\d+) (\d+) (\d+)#'; // subexpressions are used to extract b and c

      $bytes = array();
      preg_match_all($regexp, $content, $bytes);

      // $result[2][0] and $result[3][0] are b and c
      if (isset($bytes[2]) && isset($bytes[3]) && isset($bytes[2][0]) && isset($bytes[3][0])) {
        $start = $bytes[2][0];
        $end = $bytes[3][0];
        if ($stream = fopen($this->fullPath, 'rb')) {
          $signature = stream_get_contents($stream, $end - $start - 2, $start + 1); // because we need to exclude < and > from start and end

          fclose($stream);
        }
        
        if($signature && $signature!="") {
          if(file_put_contents($this->signatureFilePath, pack("H*" , $signature))) {
            $result = exec("openssl pkcs7 -in {$this->signatureFilePath} -inform DER -print_certs > {$this->pemFilePath}");
            if(file_exists($this->pemFilePath)) {
              // if a signature has been extracted it's a signed pdf
              $result = true;
            } else { 
              // no DER signature extracted, let's try PEM
              $result = exec("openssl pkcs7 -in {$this->signatureFilePath} -inform PEM -print_certs > {$this->pemFilePath}");
              if(file_exists($this->pemFilePath)) {
                // if a signature has been extracted it's a signed pdf
                $result = true;
              }
            }
          }
        }
      }
    }
    return $result;
  }
  
  /* reads a PEM file and outputs a readable array containing signature info */
  function parsePem() {
    $result = false;
    if(file_exists($this->pemFilePath)) {
      $pem = file_get_contents($this->pemFilePath);
      if(strlen($pem)>0){
        $this->signatures = Array();
        $result = true;
        $stringBeg = "-----BEGIN CERTIFICATE-----";
        $stringEnd = "-----END CERTIFICATE-----";
        $delimiter = "#cuthere#";
        $pem = str_replace($stringEnd, $stringEnd.$delimiter, $pem);    
        $certificates = explode($delimiter, trim($pem));
        $certIndex = 0;
        foreach($certificates as $certificate) {
          $certificate = trim($certificate);
          if(strlen($certificate)>0) {
            $this->signatures[$certIndex] = array();
            $split1 = explode($stringBeg, $certificate);
            $this->signatures[$certIndex]["key"] = trim($stringBeg.$split1[1]);
            $split2 = explode("\n", $split1[0]);
            $subjectArray = explode("/", $split2[0]);
            foreach($subjectArray as $keyvalue) {
              $data = explode("=", $keyvalue);
              if($data[0]!="subject") {
                if(isset($data[1])) { $this->signatures[$certIndex]["subject"][$data[0]] = $data[1]; }
                if ($data[0] == 'serialNumber') $this->signatures[$certIndex]["subject"][$data[0]] = substr($data[1], 3, 20);
              }
            }
            $issuerArray = explode("/", $split2[1]);
            foreach($issuerArray as $keyvalue) {
              $data = explode("=", $keyvalue);
              if($data[0]!="issuer") {
                $this->signatures[$certIndex]["issuer"][$data[0]] = $data[1];
              }
            }
            $certIndex++;
          }
        }
      }
    }
    return $result;
  }
  
}
?>

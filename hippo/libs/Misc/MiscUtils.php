<?
/**
* int get_index_from_file(string)
* Get a progressive number from a file
*
* @return the fixed filename
**/

function get_index_from_file($fileName){
  global $IMP;
  fixFileName($fileName);
  $fileLock = $fileName.'.lock';
  if (!lock($fileLock)){
    return 0;
  }
  $lock = fopen($fileName, 'a+');
  rewind($lock);
  $count = fgets($lock, 4096);
  $count = intval($count);
  if (!$count || $count < 1) $count = 1;
  $count++;
  $IMP->debug("Count: $count", 7);
  fclose($lock);
  $lock = fopen($fileName, 'w');
  fwrite($lock, $count);
  fclose($lock);
  unLock($fileLock);
  return $count;
}

function lock($lockName){
  return true;
}

function unlock($lockName){
  return true;
}

function fixForJs($text, $quoteType=''){
  $text = str_replace("'", "\'", $text);
  $text = str_replace("\n", ' ', $text);
  $text = str_replace("\r", ' ', $text);
  return $text;
}

function fixForXML($text){
  $text = str_replace("::", "_", $text);
  return $text;
}

function fixForFile($name){
  $name = str_replace("::", "_", $name);
  return $name;
}

function printSelectOptions($values, $pValue){
  foreach ($values as $key => $value){
    print "<option";
    if ($key == $pValue) print " SELECTED";
    print " value='$key'>$value</option>";
  }
}

function passToCombo($comboName, $values){
  foreach ($values as $key => $value){
    print "{$comboName}.v['$key'] = '$value';";
  }
}

function arrayToJs($name, $array){
  if (is_array($array)) foreach ($array as $key => $value){
    print "{$name}['$key'] = '$value';";
  }
}

function varToJs($var){
  if (is_string($var)) return "'$var'";
  if (!$var) return "0";
  if (is_array($var)){
    $res = "{";
    $cnt=0;
    foreach ($var as $key => $value){
      if ($cnt) $res .= ',';
      $res .= "'$key': ".varToJs($value);
      $cnt++;
    }
    $res .= '}';
    return $res;
  }
  else return "$var";
}

function array_merge_recursive2($a1, $a2){
  if (!is_array($a1) || !is_array($a2)) return;
  foreach ($a1 as $key => $value){
    if (is_array($a1[$key]) && is_array($a2[$key])){
      $res[$key] = array_merge_recursive2($a1[$key], $a2[$key]);
    }
    elseif(is_array($a1[$key]) && $a2[$key]){
      $res[$key] = array_push($a1[$key], $a2[$key]);
    }
    elseif(is_array($a2[$key])){
      $res[$key] = array_push($a2[$key], $a1[$key]);
    }
    elseif($a1[$key] && $a2[$key]){
      $res[$key] = array($a1[$key], $a2[$key]);
    }
    else{
      $res[$key] = $a1[$key];
    }
  }
  foreach ($a2 as $key=>$value){
    if (!$a1[$key]) $res[$key] = $a2[$key];
  }
  return $res;
}

if ( !function_exists('sys_get_temp_dir') )
{
   // Based on http://www.phpit.net/
   // article/creating-zip-tar-archives-dynamically-php/2/
   function sys_get_temp_dir()
   {
       // Try to get from environment variable
       if ( !empty($_ENV['TMP']) )
       {
           return realpath( $_ENV['TMP'] );
       }
       else if ( !empty($_ENV['TMPDIR']) )
       {
           return realpath( $_ENV['TMPDIR'] );
       }
       else if ( !empty($_ENV['TEMP']) )
       {
           return realpath( $_ENV['TEMP'] );
       }

       // Detect by creating a temporary file
       else
       {
           // Try to use system's temporary directory
           // as random name shouldn't exist
           $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
           if ( $temp_file )
           {
               $temp_dir = realpath( dirname($temp_file) );
               unlink( $temp_file );
               return $temp_dir;
           }
           else
           {
               return FALSE;
           }
       }
   }
}

function removeXSS($val) {
    if (is_string($val)) return htmLawed($val, array('safe'=>1));
    return $val;
}

function escapeSQL($value){
    
}

// Irene 18.07.2019
// funzione che trova spazi e newline all'inizio o alla fine di tutti i file php inclusi (prima di <? o dopo ? >)
// e permette di risolvere problematiche di file corrotti o generazione pdf fallita
function debugNewlines($comment=false) {
  $included_files = get_included_files();
  foreach($included_files as $file) {
    $includedContent = file_get_contents($file);
    if(preg_match('/\?>(\s*\n){2,}$/',$includedContent)) {
      echo ($comment?"<!--":"")."Il file $file ha degli spazi/newline di troppo alla fine del file".($comment?"-->\n":"<br>");
    }
    if(preg_match('/^(\s*\n){2,}<\?/',$includedContent)) {
      echo ($comment?"<!--":"")."Il file $file ha degli spazi/newline di troppo all'inizio del file".($comment?"-->\n":"<br>");
    }
  }
}

?>

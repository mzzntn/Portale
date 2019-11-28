<?

function sanitizeSql($string, $type=''){
  $string = str_replace("'", "''", $string);
  return $string;
}  
 
?>

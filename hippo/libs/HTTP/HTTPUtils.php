<?
function redirect($url) {
  if (headers_sent()) {
    // header già inviate, redirect tramite javascript
    print "<script type='text/javascript'>location.replace('{$url}'); </script>";
  }
  else{
    // redirect tramite php
    exit(header("Location: {$url}"));
  }
}

function arrayToGET($array, $parent=''){
  $get = '';
  foreach ($array as $key => $value){
    if ($get) $get .= '&';
    if (is_array($value)){
      if ($parent) $newParent = $parent.'['.$key.']';
      else $newParent = $key;
      $get .= arrayToGET($value, $newParent);
    }
    else{
      if ($parent) $get .= $parent.'[';
      $get .= $key;
      if ($parent) $get .= ']';
      $get .= '='.$value;
    }
  }
  return $get;
}

function getip() {
  foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $aah) {
    if (!isset($_SERVER[$aah])) continue;
    $curip = $_SERVER[$aah];
    $curip = explode('.', $curip);
    if (count($curip) !== 4) break; // If they've sent at least one invalid IP, break out
    foreach ($curip as $sup) if (($sup = intval($sup)) < 0 or $sup > 255) break 2;
    $curip_bin = $curip[0] << 24 | $curip[1] << 16 | $curip[2] << 8 | $curip[3];
    foreach (array(
      //    hexadecimal ip  ip mask
      array(0x7F000001,     0xFFFF0000), // 127.0.*.*
      array(0x0A000000,     0xFFFF0000), // 10.0.*.*
      array(0xC0A80000,     0xFFFF0000), // 192.168.*.*
    ) as $ipmask) {
      if (($curip_bin & $ipmask[1]) === ($ipmask[0] & $ipmask[1])) break 2;
    }
    return join('.', $curip);
  }
  return $_SERVER['REMOTE_ADDR'];
}

?>

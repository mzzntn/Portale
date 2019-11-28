<?
  
class RichTextInput extends BasicInput{
  var $value;
  var $width;
  var $height;
  var $readOnly;
  
  function RichTextInput($name){
    parent::BasicWidget($name);
    $this->addClass('input');
  }

  function setValue($value){
    $this->value = $value;
  }
  
  function prepare($value){
    global $IMP;
    //:FIXME: I really don't know why this happens
    if (is_array($value) && isset($value['content'])) $value = $value['content'];
    $files = $this->widgetParams->getFiles();
    $file = $files[$this->parentWidget->htmlName][$this->inputName.'_file'];
    if ($file['name'] && file_exists($file['tmp_name'])){
      if (preg_match('/\.(\w+)$/', trim($file['name']), $matches)){
        $uploadedExt = $matches[1];
      }
      if (in_array($uploadedExt, array('html', 'htm'))) return file_get_contents($file['tmp_name']);
      if ($uploadedExt != 'zip') return;
      $zip = zip_open($file['tmp_name']);
      if ($zip){
        $searches = array();
        $replaces = array();
        while ($zip_entry = zip_read($zip)){
          $entryName = zip_entry_name($zip_entry);
          if ($entryName[strlen($entryName)-1] == '/'){
            continue;
          }
          $entryDir = dirname($entryName);
          $entryFileName = basename($entryName);
          if ($entryFileName[0] == '.') continue;
          $tmpFile = tempnam('/tmp', 'zip_files');
          $tmpFp = fopen($tmpFile, 'w+');
          $extension = '';
          if (preg_match('/\.(\w+)$/', trim($entryFileName), $matches)){
            $extension = $matches[1];
          }
          if (in_array($extension, array('html', 'htm'))){
            if (zip_entry_open($zip, $zip_entry, 'r')){
              $value = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
              zip_entry_close($zip_entry);
            }
            $base = $entryDir;
          }
          else{
            if (zip_entry_open($zip, $zip_entry, "r")) {
              while ($buf = zip_entry_read($zip_entry, 1024)){
                fwrite($tmpFp, $buf);
              }
              zip_entry_close($zip_entry);
            }
            fclose($tmpFp);
            if (in_array($extension, array('png', 'gif', 'jpeg', 'jpg'))){
              $fileName = $IMP->images->store($tmpFile, $entryFileName);
              $newPath=URL_WEBDATA.'/img/orig/'.$fileName;
            }
            else{
              $fileName = $IMP->files->store($tmpFile, $entryFileName);
              $newPath=URL_WEBDATA.'/'.$fileName;
            }
            $search = '/(\'|")[^\'"]+?'.preg_quote($entryFileName, '/').'(\'|")/';
            $replace = '\1'.$newPath.'\2';
            $searches[] = $search;
            $replaces[] = $replace;
          }
        }
      }
      $value = preg_replace($searches, $replaces, $value);
      $styles = array();
      if (preg_match('_<\s*style\s*>(.+?)</\s*style\s*>_is', $value, $matches)){
        $styles = $matches;
      }
      $value = preg_replace('/<\\/?\s*html\s*>/i', '', $value);
      $value = preg_replace('/<\\/?\s*body\s*>/i', '', $value);
      $value = preg_replace('_<\s*head\s*>.*?</\s*head\s*>_is', '', $value);
      $value = preg_replace('_<\s*script\s*>.*?</\s*script\s*>_is', '', $value);
      for ($i=1; $i<sizeof($styles); $i++){
        $value = '<style>'.$styles[$i].'</style>'.$value;
      }
      /*
      if (function_exists('tidy_repair_string')){
        tidy_setopt('show-body-only', TRUE);
        $value = tidy_repair_string($value);
      }
      */
    }
    /*
    $replace = array();
    if (preg_match_all('_img src=[\'"](http://.+?)[\'"]_', $value, $matches)){
      foreach ($matches[1] as $match){
        $fileName = basename($match);
        $tmpFile = tempnam('/tmp', 'hippo_');
        copy($match, $tmpFile);
        $fileName = $IMP->images->store($tmpFile, $fileName);
        $destUrl = URL_WEBDATA.'/img/orig';
        $replace[$match] = $destUrl.'/'.$fileName;
      }
    }
    foreach ($replace as $orig => $new){
      $value = str_replace($orig, $new, $value);
    }
    */
    
    return $value;
  }
  
  
}


?>

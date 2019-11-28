<?php

class Files{
  var $basePath;
  
  function Files($basePath=''){
    if (!$basePath) $basePath = PATH_WEBDATA.'/';
    $this->basePath = $basePath;
  }

  function getName($sourceFilePath, $fileName, $destPath=''){
    if ($destPath) $destPath .= '/';
	$fileName = str_replace(array(' ', chr(224), chr(232), chr(233), chr(236), chr(242), chr(249), chr(250), chr(128), chr(64), chr(94), chr(176), chr(35), chr(38), chr(37), chr(163),chr(36)),
                            array('_', 'a', 'e', 'e', 'i', 'o', 'u', 'u', '', '', '', '', '', '', '', '', ''),
                            $fileName);
    $destFile = $this->basePath.$destPath.$fileName;
    while (file_exists($destFile)){
      if (filesize($destFile) == filesize($sourceFilePath)){
        if (md5_file($destFile) == md5_file($sourceFilePath)){
          return $fileName;
        }
      }
      $extension = '';
      $number = 0;
      if (preg_match('/(.+)(\d*)\.(.+)$/', $fileName, $matches)){
        $fileName = $matches[1];
        $number = $matches[2];
        $extension = $matches[3];
      }
      $number++;
      $fileName .= $number;
      if ($extension) $fileName .= '.'.$extension;
      $destFile = $this->basePath.$destPath.$fileName;
    }
    return $fileName;
  }
  
  //FIXME: probably it should check if the file is an image and use Images
  function store($sourceFilePath, $fileName, $destPath=''){
    $fileName = $this->getName($sourceFilePath, $fileName, $destPath);
    if ($destPath[0] != '/' && $destPath[1] != ':') $destFile = $this->basePath.'/'.$destPath.'/'.$fileName;
    else $destFile = $destPath.'/'.$fileName;
    copy($sourceFilePath, $destFile);
    return $fileName;
  }


}

?>

<?php

class Images extends Files{
  
  function Images(){
    parent::Files();
    $this->basePath .= 'img/';
  }
  
  function isImage($filePath){
    //:FIXME: some better detection!
    if (preg_match('/\.(jpg|jpeg|png)$/', $filePath)) return true;
    return false;
  }

  function store($sourceFilePath, $fileName){
    $fileName = $this->getName($sourceFilePath, $fileName, 'orig');
    $destFile = $this->basePath.'orig/'.$fileName;
    if (!file_exists($destFile)){
      parent::store($sourceFilePath, $fileName, 'orig');
      $image = new Image($sourceFilePath);
      $image->name = $fileName;
      $image->copySmaller($this->basePath.'thumb', 150, 150);
    }
    return $fileName;
  }

}

?>

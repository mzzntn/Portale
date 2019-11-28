<?

class Image{
  var $name;
  var $path;
  var $extension;
  var $type;
  var $height;
  var $width;
  var $ratio;
  var $img;
  
  function Image($path){
    $this->path = $path;
    $path_parts = pathinfo($path);
    $this->name = $path_parts['basename'];
    $this->extension = $path_parts['extension'];
    $imageInfo = getimagesize($path);
    switch($imageInfo[2]){
      case 1:
        $this->type = 'gif';
        break;
      case 2:
        $this->type = 'jpg';
        break;
      case 3:
        $this->type = 'png';
        break;
      case 4:
        $this->type = 'swf';
        break;
    }
    $this->height = $imageInfo[1];
    $this->width = $imageInfo[0];
    $this->ratio = $this->height/$this->width;
    switch($this->type){
      case 'jpg':
        $this->img = imagecreatefromjpeg($path);
        break;
      case 'gif':
        $this->img = imagecreatefromgif($path);
	break;
      case 'png':
        $this->img = imagecreatefrompng($path);
	break;
    }
    if (!$this->img){
      return 0;
    }
  }
  
  function copySmaller($destDir, $maxWidth, $maxHeight){
    if ($this->width <= $maxWidth && $this->height <= $maxHeight){
      return copy($this->path, $destDir.'/'.$this->name);
    }
    $mRatio = $maxHeight/$maxWidth;
    $relRatio = $this->ratio/$mRatio;
    if ($relRatio >= 1){
      $dHeight = $maxHeight;
      $dWidth = $dHeight/$this->ratio;
    }
    else{
      $dWidth = $maxWidth;
      $dHeight = $dWidth*$this->ratio;
    }
    $im = ImageCreateTrueColor($dWidth, $dHeight);
    imagecopyresampled($im, $this->img, 0, 0, 0, 0, $dWidth, $dHeight, $this->width, $this->height);
    if ($this->type == 'gif' && imagetypes() & IMG_GIF) $create = 'gif';
    elseif ($this->type == 'png' && imagetypes() & IMG_PNG) $create = 'png';
    else $create = 'jpeg';
    $func = 'image'.$create;
    $func($im, $destDir.'/'.$this->name, 70);
  }
    
  
  function debug(){
    print "NOME: $this->nome<br>";
    print "PERCORSO: $this->percorso<br>";
    print "TIPO: $this->tipo<br>";
    print "ESTENSIONE: $this->estensione<br>";
    print "ALTEZZA: $this->altezza<br>";
    print "LARGHEZZA: $this->larghezza<br>";
    print "IMG: $this->img<br>";
  }
}
    


?>

<?

class IniRW{
  var $file;
  var $config;
  var $comments;

  function IniRW($file){
    $this->file = $file;
    if (!file_exists($file)){
      if (!touch($file)) die("Impossibile creare il file $file. Per favore, controlla i permessi o crealo manualmente");
    }
    $this->read();
  }

  function write(){
    $fp = fopen($this->file, 'w');
    if (is_array($this->comments['pre'])) foreach ($this->comments['pre'] as $comment){
      fwrite($fp, $comment."\n");
    }
    foreach(array_keys($this->config) as $section){
      fwrite($fp, "\n[$section]\n");
      if (is_array($this->comments[$section]['pre']))
      foreach ($this->comments[$section]['pre'] as $comment){
        fwrite($fp, $comment."\n");
      }
      foreach($this->config[$section] as $key => $val){
        fwrite($fp, "$key=$val\n");
      }
      if (is_array($this->comments[$section][$key]))
      foreach ($this->comments[$section][$key] as $comment){
        fwrite($fp, $comment."\n");
      }
    }
    fclose($fp);
  }

  function read(){
    if (!file_exists($this->file)) die("File $file does not exist\n");
    $raw = file($this->file);
    foreach ($raw as $row){
      $row = trim($row);
      if ($row[0] == ';'){
        if (!$section) $this->comments['pre'][] = $row;
        if (!$lastVar) $lastVar = 'pre';
        $this->comments[$section][$lastVar][] = $row;
      }
      if (preg_match('/\[(.+)\]/', $row, $matches)){
        $section = $matches[1];
        $this->config[$section] = array();
        $this->comments[$section] = array();
      }
      elseif (preg_match('/(.+?)=(.*)/', $row, $matches)){
        $var = $matches[0];
        $val = $matches[1];
        $this->config[$section][$var] = $val;
        $this->comments[$section][$var] = array();
        $lastVar = $var;
      }
    }
    print_r($this->config);
  }

}
 
?>
<?
include_once(LIBS.'/Data/DataTypes/text.php');

class DataT_html extends dataT_text{
  
  function get($for=''){
    if (function_exists('tidy_repair_string')){
      $this->data = tidy_repair_string($this->data, array('show-body-only' => 'TRUE'));
    }
    if ($for == 'db'){
      $this->data = preg_replace('/<style>.+?<\/style>/', '', $this->data);
      #$this->data = preg_replace('/http:\/\/.+?'.preg_quote(URL_WEBDATA, '/').'/', URL_WEBDATA, $this->data);
      if (HOME){
        $this->data = preg_replace('/http:\/\/.+?'.preg_quote(HOME, '/').'/', HOME, $this->data);
        $this->data = str_replace(HOME, 'HOME', $this->data);
      }
      else{
        $this->data = preg_replace('/(href=["\'])\//', '$1HOME/', $this->data);
      }
      $this->data = str_replace(URL_WEBDATA, 'URL_WEBDATA', $this->data);
      //$this->data = str_replace("<br>", "<br>\n", $this->data);
      $this->data = parent::get($for);
    }
    else{
      $this->data = strip_tags($this->data, '<b><i><a><span><div><p><br><table><td><tr><th><img><center><h1><h2><h3><h4><h5><h6><hr><ul><ol><li><u><strong><embed><object><param>');
      $this->data = str_replace('URL_WEBDATA', 'http://'.SERVER.URL_WEBDATA, $this->data);
      $this->data = str_replace('HOME', 'http://'.SERVER.HOME, $this->data);
      $this->data = str_replace('http://'.SERVER.'http://'.SERVER, 'http://'.SERVER, $this->data);
    }
    
    return $this->data;
  }

}



?>

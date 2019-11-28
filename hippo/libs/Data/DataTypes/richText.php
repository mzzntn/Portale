<?
include_once(LIBS.'/Data/DataTypes/text.php');
  
class DataT_richText extends dataT_text{

  
  function get($for=''){
    if ($for == 'db'){
      if (function_exists('tidy_repair_string')){
        tidy_setopt('show-body-only', TRUE);
        $this->data = tidy_repair_string($this->data);
      }
      $this->data = preg_replace('/<style>.+?<\/style>/', '', $this->data);
      $this->data = str_replace(URL_WEBDATA, 'URL_WEBDATA', $this->data);
      if (HOME) $this->data = str_replace(HOME, 'HOME', $this->data);
      //$this->data = str_replace("<br>", "<br>\n", $this->data);
      $this->data = parent::get($for);
    }
    else{
      $this->data = str_replace('URL_WEBDATA', URL_WEBDATA, $this->data);
      $this->data = str_replace('HOME', HOME, $this->data);
    }
    
    return $this->data;
  }

}



?>

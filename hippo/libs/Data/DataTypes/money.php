<?

class DataT_money extends dataT{
  
  function set($value, $from=''){
    $this->data = round(floatval($value), 100);
  }
  
  function get($for=''){
    $result = $this->data;
    if ($for == 'user'){
      $result = number_format($result, 2, ',', '.');
    }
    return $result;
  }

}

?>

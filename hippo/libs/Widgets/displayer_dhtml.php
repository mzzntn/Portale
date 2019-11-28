<?

class Displayer_dhtml extends Displayer_html{
  
  function display($optionString=''){
    global $IMP;
    $this->loadJs('divControls');
    if ($IMP->jsDebug) $this->loadJs('DebugConsole');
    return parent::display($optionString);
  }

}


?>

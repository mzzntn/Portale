<?
ini_set('allow_call_time_pass_reference', true);
$IMP->config['Table']['maxJsSort'] = 30;

class d_Table_html extends Displayer_html{

  function start(){
    $W = & $this->w;
    if ($W->context) $this->contextStruct = $W->typeSpace->getStructure($W->context['struct']);
    while($W->data->moveNext()){
      $id = $W->data->get('id');
      foreach ($W->elements as $elementName){
        if ($W->config['displayer']['prepare_element'][$elementName]){
	  //il php non mi lascia piu' passare per reference & $W... verificare che non crei problemi
          @call_user_func($W->config['displayer']['prepare_element'][$elementName],  $W, $elementName);
        }
        else $this->prepareElement($elementName, $id);
      }
      //if (is_array($this->w->config['customElements'])){
       // foreach ($this->w->config['customElements'] as $
      
      //}
    }
    if ($W->config['dataHook'] && function_exists($W->config['dataHook'])){
      $W->data = call_user_func($W->config['dataHook'], $W->data);
    }
    $this->loadJs('divControls');
  }
  
  function printScripts(){
    $current_page = 1;
    $paramName = $this->name."_full_page";
    if(isset($_SESSION['table_pages']) && isset($_SESSION['table_pages'][$paramName])) {
      $current_page = $_SESSION['table_pages'][$paramName];
    }
  ?>
  <script type='text/javascript' defer>
      var page = <?=is_numeric($current_page)?$current_page:"false"?>;
      var paginatorPOSTurl = "<?=HOME?>/portal/js_session_manager.php";
      var paginatorPOSTparam = "<?=$paramName?>";
  </script>
  <?
  }
  
  function prepareElement($elementName, $id=0){
    $W = & $this->w;
    if ($W->contextElements[$elementName]) $type = $this->contextStruct->getExtendType($W->context['el'], $elementName);
    else $type = $W->struct->type($elementName);
    if (!$type) return;
    if (!$W->typeSpace->isBaseType($type)){
      $lastEl = $elementName;
      $nameParent = '';
      while (!$W->typeSpace->isBaseType($type)){
        if ($nameParent) $nameParent .= '.';
        $nameParent .= $lastEl;
        $struct = $W->typeSpace->getStructure($type);
        $names = $struct->getNames();
        $type = $struct->type($names[0]);
        $lastEl = $names[0];
      }
      $elText = '';
      $cnt = 0;
      while ($W->data->moveNext($elementName)){
        $cnt++;
        if ($elText) $elText .= '<br>';
        if ($W->config['maxSubElements'] >= 0 && $cnt > $W->config['maxSubElements']){
          $elText .= '[...]';
          break;
        }
        $elText .= '-';
        foreach ($names as $name){
          $elPath = $nameParent.'.'.$name;
          $elValue = $W->data->get($elPath);
          $elText .= $elValue.' ';
        }
      }
      $W->data->set($elementName, $elText);
    }
    else{ //base type
      $orig = $W->data->get($elementName);
      $obj = & $W->typeSpace->getObj($type);
      $obj->set($orig);
      $val = $obj->get('user');
      if (strlen($val) > 200) {	
	$val = StringParser::parse(substr(html_entity_decode($val), 0, 200).' [...]');
      }
      $val = preg_replace('/(\S{30})/', "$1 ", $val);
      $W->data->set($elementName, $val);
      $W->data->set('_'.$elementName, $orig);
    }
    if ($W->config['contextAdmin'] && $W->contextElements[$elementName]){
      $type = $this->contextStruct->getExtendType($W->context['el'], $elementName);
      $value = $W->data->get($elementName);
      $inputName = "{$W->config['contextAdmin']}[_{$W->context['el']}][$id][$elementName]";
      $html = $this->createFormInput($type, $value, $inputName);
      $W->data->set($elementName, $html);
    }
  }
  
  function createFormInput($type, $value, $inputName){
    switch($type){
      case 'int':
        $html = "<input type='text' value='$value' size='3' name='$inputName'>";
        break;
      case 'order':
        $html = "<input type='text' value='$value' size='3' name='$inputName'>";
        break;
      case 'bool':
        $html = "<input type='checkbox' value='1' name='$inputName' ";
        if ($value) $html .= "CHECKED";
        $html .= ">";
        break;
      default:
        $html = "<input type='text' value='$value' size='8' name='$inputName'>";
    }
    return $html;
  }   
  
  function label($element){
    if ($this->w->config['labels'][$element]) return $this->w->config['labels'][$element];
    return $this->w->struct->label($element);
  }
  
  function  buildLift(){
  }

}


?>

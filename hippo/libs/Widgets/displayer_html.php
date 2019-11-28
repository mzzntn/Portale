<?

class Displayer_html extends Displayer{
  var $classString;
  
  function display($optionString=''){
    global $IMP;
    //if ($IMP->defaults['display'] == 'html' || $IMP->defaults['display'] == 'html.dhtml'){
    //  $this->loadJs('DebugConsole');
    //}
    $css = $this->getCSSFile();
    if ($css) $this->includeCSS($css);
    if ($IMP->config['adminMode']) $this->startAdmin();
    parent::display($optionString);
    if ($IMP->config['adminMode']) $this->endAdmin();
  }
  
  function getCSSFile(){
    $url = '/widgets';
    if ($this->w->nameSpace) $nameSpace = $this->w->nameSpace;
    else $nameSpace = 'base';
    $url .= '/'.$nameSpace;
    if ($this->w->path) $url .= '/'.$this->w->path;
    $url .= '/'.$this->w->widgetType;
    if ($this->style) $file = $this->style.'.css';
    else $file = 'default.css';
    $url .= '/'.$file;
    $pathPrefix = defined("PATH_CSS_WIDGETS")?PATH_CSS_WIDGETS:PATH_CSS;
    $urlPrefix = defined("URL_CSS_WIDGETS")?URL_CSS_WIDGETS:URL_CSS;
    $path = $pathPrefix.$url;
    $url = $urlPrefix.$url;
    if (!file_exists($path)) return '';
    return $url;
  }
  
  function includeCSS($url){
    print "<link rel='stylesheet' type='text/css' href='{$url}'>";
  }
  
  
  function startAdmin(){
    $wName = $this->name;
    print "<div id='{$wName}_admin'>";
    print "<a href='javascript: {$wName}_togAdmin()'>Amministra</a> {$this->w->name}";
    print "<div id='{$wName}_adminmenu'>";
    print "</div>";
  }
  
  function endAdmin(){
    global $IMP;
    $wName = $this->name;
    print "</div>";
    $this->loadJs('divControls');
    $this->loadJs('menu');
    $this->loadJs('cssBrowser');
?>
<script>
div = getObj('<?=$wName?>_admin');
menuDiv = getObj('<?=$wName?>_adminmenu');
//menuDiv.style.visibility = 'hidden';
var m = new TreeMenu(menuDiv);
divs = new Array();
<?
    if ($this->w->structName && $this->w->id){
?>
div1 = document.createElement('div');
div1.innerHTML = 'Edita';
div1.onclick = function(e){ window.open("<?=$IMP->config['admin']?>?<?=$IMP->config['adminWidget']?>[widget]=<?=$this->w->structName?>&id=<?=$this->w->id?>")};
divs.push(div1);
<?
    }
?>
div2 = document.createElement('div');
div2.innerHTML = 'Stili';
cssBrowser = new CSSBrowser(div);
cssBrowser.config['admin'] = '<?=$IMP->config['adminStyles']?>';
cssBrowser.init();
styles = cssBrowser.buildMenu();
div2.childMenu = styles;
divs.push(div2);
m.setArray(divs);
m.init();

function <?=$wName?>_togAdmin(){
  div = getObj('<?=$wName?>_adminmenu');
  if (div.style.display == 'none') div.style.display = '';
  else div.style.display = 'none';
}
</script>
<?
  }

  function getCSSClass($element=''){
    global $C;
    if ($C['hippo']['legacy_css_classes']) return $this->w->getCSSClass($element);
    else return $element;
  }

  function loadJs($script){
    global $IMP;
    $IMP->loadJs($script);
  }

  
  function arrayToJs($name, $array){
    global $IMP;
    $IMP->debug("Calling arrayToJs for array $name:", 5);
    $IMP->debug($array);
    if (!is_array($array)) return;
    foreach($array as $key => $value){
      if (!$value) continue;
      if (is_array($value)){
        if (is_numeric($key)) $newName = "{$name}[$key]";
        else $newName = "{$name}['$key']";
        print "$newName = new Array();";
        $this->arrayToJs($newName, $value);
      }
      else{
        if (!is_numeric($value) || !is_bool($value)) 
          $value = "'".str_replace("'", "\'", $value)."'";
        if (is_numeric($key)) $newName = "{$name}[$key]";
        else $newName = "{$name}['$key']";
        print "$newName = $value;";
      }
    }
  }
  

}


?>

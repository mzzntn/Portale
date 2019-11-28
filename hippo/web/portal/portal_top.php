<?
  function tornaAlPortale($returnOption="redirect") {
    global $C;
    $url = HOME."/portal/";
    $returnValue = false;
    if($C['portal']['spider_portal']) {
      // se spider è attivo torna al portale spider
      $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($C['portal']['spider_portal']);
    }
    switch($returnOption) {
      case "button":
          $returnValue = '<div class="back"><a class="btn" href="'.$url.'">Torna al portale</a></div>';
        break;
      case "link":
          $returnValue = '<a href="'.$url.'">Torna al portale</a>';
        break;
      case "url":
          $returnValue = $url;
        break;
      case "redirect":
      default:
        redirect($url);
        break;
    }

    return $returnValue;
  }

  if ($C['portal']['spider_portal']){
    include_once(HIPPO.'/libs/ext/simplehtmlDOM/simple_html_dom.php');

    $rifare = false;
    if (!file_exists(VARPATH.'/spider_portal_top')) $rifare = true;
    else{
      if (filesize(VARPATH.'/spider_portal_top') == 0 || filesize(VARPATH.'/spider_portal_bottom') == 0) $rifare = true;
      $leggoFile = file_get_contents(VARPATH.'/spider_portal_top');
      $urlParti = explode('/', $C['portal']['spider_portal']);
      $urlBase = $urlParti[0]."//".$urlParti[2];
      for ($i = 1; $i <= 10; $i++) {
        if (strpos($leggoFile , $urlBase.'/public/_c/portal.'.$i.'.js')){
          $verificaH = @get_headers($urlBase.'/public/_c/portal.'.$i.'.js');
          if(strpos($verificaH[0],'404')) $rifare = true;
        }
      }
    }

    if ($rifare) {
        $separator = '----PORTAL----';
        $p = explode( '/', $C['portal']['spider_portal']);
        $spider = '';
        foreach ($p as $u => $v){
                if($u == '0') $spider .= $v.'//';
                if($u == '2') $spider .= $v;
        }
	$html = file_get_html($C['portal']['spider_portal']); 
        $link = $html->find('link');
        foreach ($link as $a => $b){
                if($html->find('link', $a)->rel == 'Shortcut Icon'){
                        $newl1 = $spider.$html->find('link', $a)->href;
                        $html->find('link', $a)->href = $newl1;
                }
                if($html->find('link', $a)->rel == 'stylesheet' && $a > 0){
                        $newl1 = $spider.$html->find('link', $a)->href;
                        $html->find('link', $a)->href = $newl1;
                }
        }
        $scripts = $html->find('script');
        foreach ($scripts as $a => $b){
                $newl1 = $spider.$html->find('script', $a)->src;
                $html->find('script', $a)->src = $newl1;
        }
        $nome = $html->find('h1', 0)->innertext;
        $newNome = str_replace(Chr(195), chr(224), $nome);
        $html->find('h1', 0)->innertext = $newNome;
	$html->find('title', 0)->innertext = $newNome;
	$html->find('strong', 0)->innertext = $newNome;
        $html->find('a', 0)->href = 'http://'.SERVER.URL_APP_PORTAL;
        $head = $html->find('head', 0);
        $head->innertext .= '
    <link rel="stylesheet" type="text/css" href="'.URL_CSS.'/common.css" media="screen">
    <link rel="stylesheet" type="text/css" href="'.URL_CSS.'/common_defaultstyle.css" media="screen">    
    <link rel="stylesheet" type="text/css" href="'.URL_CSS.'/portal.css" media="screen">
    <link rel="stylesheet" type="text/css" href="'.URL_CSS.'/portal_defaultstyle.css" media="screen">
    <script language="javascript" type="text/javascript" src="'.URL_JS.'/Jquery/jquery-1.4.2.min.js"></script>
    <script language="javascript" type="text/javascript" src="'.URL_JS.'/Jquery/init.js"></script>
        ';
        $html->find('div[id=portal]', 0)->innertext = $separator;
        $str = $html->outertext;
        list($top, $bottom) = explode($separator, $str);
        $fp = fopen(VARPATH.'/spider_portal_top', 'w');
        fwrite($fp, $top);
        fclose($fp);
        $fp = fopen(VARPATH.'/spider_portal_bottom', 'w');
        fwrite($fp, $bottom);
        fclose($fp);
    }      
    readfile(VARPATH.'/spider_portal_top');    
    return;
  }
  else include_once('startupUtente.php');
  
include_once(PATH_APP_PORTAL.'/portal_heading.php');
?>

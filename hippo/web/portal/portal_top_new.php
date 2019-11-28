<?
  $C['style'] = "2016"; // imposta lo stile per questa applicazione, verrà applicato in tutti i widget
//   include_once(PATH_APP_PORTAL.'/jscss_manager.php');
  if(isset($_GET["layout"])) {
    $_SESSION["layout"] = $_GET["layout"]=="false"?false:true;
  }
  
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
  
  $defer = /*' defer="defer"'*/"";
  /* 
   * Crea i tag <script> o <style> aggiungendo il numero di versione per forzare il caricamento in caso di aggiornamento
   */
  function getCssJs($fileName="") {
    global $defer;
    $html = "";
    $command = "";
    if($fileName !="") {
      $ext = explode(".", $fileName);
      $command = 'svnversion '.HOMEPATH.DIRECTORY_SEPARATOR.$ext[count($ext)-1].DIRECTORY_SEPARATOR;
    } else {
      $urlPieces = explode("/",$_SERVER[REQUEST_URI]);
      $appName = $urlPieces[2];
      $command = 'svnversion '.HOMEPATH.DIRECTORY_SEPARATOR.$appName.DIRECTORY_SEPARATOR."js";
      $html .= "";
    }
//     $html .= "\t\t"."<!-- Command $command -->"."\n";
    $revision = trim(shell_exec($command));
    if($revision !="" ) { 
      $rev = explode(":", $revision);
      $revision = "=".$rev[count($rev)-1];
      $revision = chunk_split($revision, 2,".");
      $revision = substr($revision, 0, -1);
      $revision = "?v$revision"; 
    }
//     $html .= "\t\t"."<!-- Revision $revision -->"."\n";
    if(strpos($fileName, ".js")!==false) {
      $html .= "\t\t".'<script src="'.URL_JS.'/'.$fileName.$revision.'"'.$defer.'></script>'."\n";
    } else if(strpos($fileName, ".css")!==false) {
      $html .= "\t\t".'<link rel="stylesheet" type="text/css" href="'.URL_CSS.'/'.$fileName.$revision.'">'."\n";
    } else {
      $page_js = "js/".basename($_SERVER["SCRIPT_FILENAME"], ".php").".js";
      if(file_exists(getcwd()."/".$page_js)){
        $html .= "\t\t"."<!-- Script specifico per pagina -->"."\n";
        $html .= "\t\t".'<script src="'.$page_js.$revision.'"'.$defer.'></script>'."\n";
      } else {
//         $html .= "\t\t"."<!-- file ".getcwd()."/".$page_js." does not exist so it isn't included -->";
      }
    }
    return $html;
  }
  
  $jscssHtml = "\t\t"."<!-- Script e css inclusi da portal php -->"."\n";
  // prendo la versione di svn per forzare l'aggiornamento di script e stili
  
//   $jscssHtml .= "\t\t".'<link rel="stylesheet" type="text/css" href="'.URL_CSS.'/portal_nuovo.css">'."\n";
  $jscssHtml .= getCssJs("portal_nuovo.css");
  $jscssHtml .= "\t\t".'<script src="'.URL_JS.'/bootbox.min.js"'.$defer.'></script>'."\n";
//   $jscssHtml .= "\t\t".'<script src="'.URL_JS.'/portal-php.js"'.$defer.'></script>'."\n";
  $jscssHtml .= getCssJs("portal-php.js");
  $jscssHtml .= getCssJs();
//   $page_js = "js/".basename($_SERVER["SCRIPT_FILENAME"], ".php").".js";
//   if(file_exists(getcwd()."/".$page_js)){
//     $jscssHtml .= "\t\t"."<!-- Script specifico per pagina -->"."\n";
//     $jscssHtml .= "\t\t".'<script src="'.$page_js.'?v='.$revision.'"'.$defer.'></script>'."\n";
//   } else {
//     $jscssHtml .= "\t\t"."<!-- file ".getcwd()."/".$page_js." does not exist so it isn't included -->";
//   }
  
  if($C['portal']['spider_portal']) {
    // utilizza header e footer di spider    
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
	else $rifare = true;
      }
    }

    if ($rifare) {

       $p = explode( '/', $C['portal']['spider_portal']);
        $spider = '';
        foreach ($p as $u => $v){
                if($u == '0') $spider .= $v.'//';
                if($u == '2') $spider .= $v;
        }

      // bisogna creare il file e scriverci dentro l'html
      $headerSeparator = '<div id="portal_main" class="col-lg-12">';
      $footerSeparator = '<footer id="portal_bottom" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';

      $fullHtml = file_get_contents($C['portal']['spider_portal']);
      
      $split = explode($headerSeparator, $fullHtml);
      
      $headerHtml = $split[0].$headerSeparator; 

      
      // sostituisco tutti i link a $C['portal']['spider_portal'] 
      // in modo che passino prima per HOME."/portal/torna_a_spider.php"
      preg_match_all('/a href *= *["\']([^"\']+)["\']/i',$headerHtml, $urls);
//       print_r($urls);
      for($i=0; $i<count($urls[1]);$i++) {
	$url = $urls[1][$i];
	$href = $urls[0][$i];
	if(strpos($url, substr($C['portal']['spider_portal'],0, strlen($C['portal']['spider_portal'])-1))!==false){
	  $new_href = str_replace($url, HOME."/portal/torna_a_spider.php?pagina=".urlencode($url), $href);
	  $headerHtml = str_replace($href, $new_href, $headerHtml);
	}
      }
      $headerHtml = str_replace('"/public', '"'.$spider.'/public', $headerHtml);
      $headerHtml = str_replace('"/spider', '"'.$spider.'/spider', $headerHtml);
      
      $footerHtml = substr($split[1], strpos($split[1], $footerSeparator), strlen($split[1]));
      
      file_put_contents(VARPATH.'/spider_portal_top', $headerHtml);
      file_put_contents(VARPATH.'/spider_portal_bottom', $footerHtml);
    }
    
    include_once(PATH_APP_PORTAL.'/servizi.php');  
    
    if(isset($_SESSION['phpCAS']['attributes']['nome'])) {
      // utente loggato
      $headerHtml = file_get_contents(VARPATH.'/spider_portal_top');
      // TODO usare $C['portal']['spider_portal']
      // <a href="/openweb/portal/torna_a_spider.php?pagina=http%3A%2F%2Fgela.soluzionipa.it%2Fportal%2Fautenticazione">ACCEDI<br>REGISTRATI</a>
      $headerHtml = preg_replace(
	'|<a href="[^"]+">ACCEDI<br>[.\n ]*<span>REGISTRATI</span></a>|',
	'<a href="'.HOME.'/portal/torna_a_spider.php?pagina=http%3A%2F%2F'.SERVER.HOME.'%2Fportal">CIAO<br>'.$_SESSION['phpCAS']['attributes']['nome'].'</a></div><div class="col-lg-1 col-md-1 logout_link">
      <a title="Logout" href="'.HOME.'/portal/torna_a_spider.php?pagina=http%3A%2F%2F'.SERVER.'%2Fportal%2Fautenticazione%2Flogout">
      <span class="glyphicon glyphicon-log-out" aria-hidden="true"></span>
      </a>',
	$headerHtml
      );
      $headerHtml = str_replace('http://',"//",$headerHtml);
      $headerHtml = str_replace('https://',"//",$headerHtml);
      $headerHtml = str_replace(' defer="defer"',"",$headerHtml);
      include_once(PATH_APP_PORTAL.'/portal_menu_new.php');
      
      $menuTop = '<nav id="menu_responsive_laterale"';
      $menuBottom = '</nav>';
      
      $splitTop = explode($menuTop, $headerHtml);
      $splitBottom = explode("#$menuBottom#", preg_replace('/('.preg_quote($menuBottom, '/').')/', "#$1#", $splitTop[1], 1));

      $header = "{$splitTop[0]}\n{$menuHtml}\n\n{$splitBottom[1]}";
      
      /*$jscssHtml = getCustomJscss("top");
      $jscssHtml .= getJscss("/", $jscss);
      $jscssHtml .= getCustomJscss("bottom");*/
      
      $htmlOk = str_replace("</head>","$jscssHtml</head><!--logged user {$_SERVER['HTTP_HOST']}-->",$header);      
      if((isset($_SESSION["layout"]) && $_SESSION["layout"]==false) || isset($_GET["temp_layout"])) {        
        $htmlOk = preg_replace('/<span class="hide" id="menu_top_fixed"((?!<\/header>).+)<\/header>/s', "", $htmlOk);
      }
      echo $htmlOk;
    }
    else {
      $header = file_get_contents(VARPATH.'/spider_portal_top');
      $header = str_replace(' defer="defer"',"",$header);
      $header = str_replace('http://',"//",$header);
      $header = str_replace('https://',"//",$header);

      
      /*$jscssHtml = getCustomJscss("top");
      $jscssHtml .= getJscss("/", $jscss);
      $jscssHtml .= getCustomJscss("bottom");*/
      
      $htmlOk = str_replace("</head>","$jscssHtml</head><!-- not logged user -->",$header);         
      if((isset($_SESSION["layout"]) && $_SESSION["layout"]==false) || isset($_GET["temp_layout"])) {        
        $htmlOk = preg_replace('/<span class="hide" id="menu_top_fixed"((?!<\/header>).+)<\/header>/s', "", $htmlOk);
      }
      echo $htmlOk;
    }
  }
  else {
    $jscssHtml2 = "\t\t"."<!-- Script e css inclusi da spider (copia locale) -->"."\n";
    $jscssHtml2 .= "\t\t".'<link rel="stylesheet" type="text/css" href="'.URL_CSS.'/spid.portal.1.css">'."\n";
    $jscssHtml2 .= "\t\t".'<script src="'.URL_JS.'/spid.portal.2.js"'.$defer.'></script>'."\n";
    $jscssHtml2 .= "\t\t".'<script src="'.URL_JS.'/spid.jquery.ui.datepicker-it.js"'.$defer.'></script>'."\n";
    
    $jscssHtml = $jscssHtml2.$jscssHtml;
    
    // utilizza header e footer di php
    include_once(PATH_APP_PORTAL.'/servizi.php');  
    include_once(PATH_APP_PORTAL.'/portal_heading_new.php');
  }
?>

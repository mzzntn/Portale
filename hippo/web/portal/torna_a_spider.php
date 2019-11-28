<?
  include_once('init.php');
  $validPage = false;
  if( isset($_GET["pagina"]) ) {
    $isLoop = strpos($_GET["pagina"],"torna_a_spider.php")!==false;
    $isOnServer = strpos($_GET["pagina"],SERVER)!==false;
    $isSpider = $C['portal']['spider_portal'] && strpos($_GET["pagina"],$C['portal']['spider_portal'])!==false;
    $validPage = !$isLoop && ($isOnServer || $isSpider);
  }
  
  if(isset($_GET["pagina"]) && $validPage) {
    if ($C['portal']['spider_portal']){
      $IMP->security->logoutCAS();
    }
    header("Location: ".$_GET["pagina"]);
  } else {
    if ($C['portal']['spider_portal']){
      $IMP->security->logoutCAS();
      header("Location: {$C['portal']['spider_portal']}");
    } else {
      header("Location: ".(stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://').SERVER."/portal");
    }
  }
?>

<?
// levo dalla query string le var passate per l'accessibilita'
if(($sqs = $_SERVER['QUERY_STRING'])!=''){
  $sqs = preg_replace('/([&]+)?(fsize|contrast|css)\=[a-zA-Z]+/i',    '', $sqs);
  $sqs.= $sqs!=''? '&amp;': '';
}
?>
<!DOCTYPE html>
<html>
  <head> 
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- shiv solution per problemi html5 semantico per IE < 9 --> 
    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.js"></script>
    <![endif]-->
    
    <title><?=$C['ente']['nome_ente']?> - Portale dei servizi</title>
    
    <?=$jscssHtml?>
  </head>
  
  <body>
    <div>
      <noscript><h3>Per la completa fruizione del sito si devono abilitare i JavaScript.</h3></noscript>
      <div class="container-fluid perc_margini">
	<div class="row">
	  <span class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="menu_top_fixed"></span>	  
	  
	  <header id="portal_top" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <div id="header_border_fixed"></div>
	    <div id="contenuto_header" class="row">
	      <div id="ente" class="col-lg-5 col-md-5 col-sm-6 col-xs-6">
		<div id="img_sfondo_stemma">
		  <a href="<?=HOME?>/portal/index.php">
		    <div id="img_stemma"></div>
		  </a>
		</div>
		<div id="testo_ente">
		  <div id="testo_ente_small">Comune di</div>
		  <div id="testo_ente_big"><?=substr($C['ente']['nome_ente'],10,150)?></div>
<!--	          <div id="testo_ente_big"><?=$C['ente']['nome_ente']?></div>-->
		</div>
	      </div>
	      
	      <nav id="topbar" class="col-lg-7 col-md-7 hidden-sm hidden-xs">
		<div class="row">
		  <div class="col-lg-offset-8 col-md-offset-8 col-lg-2 col-md-2 text-center">
		    <a href="<?=HOME?>/portal/servizi_pubblici.php">SERVIZI<br>PUBBLICI</a>
		  </div>  
		</div>
	      </nav>	      
	      
	      <div id="topbar-responsive" class="hidden-lg hidden-md col-sm-6 col-xs-6">
		<div class="row">
		  <!-- icona composta per nuove comunicazioni private-->
		  <div class="col-sm-4 col-sm-offset-4 col-xs-4 col-xs-offset-4 menu">
		    <a href="#my-menu"><i id="my-button" class="fa fa-bars fa-3x"></i></a>
		  </div>
		</div>
	      </div>
	    </div>

	    <!-- includo qui il menu laterale responsive che deve essere presente in tutte le pagine del sito -->
	    
	    <?include_once(PATH_APP_PORTAL.'/portal_menu_new.php'); echo $menuHtml;?>
	  </header>
	  
	  <section id="portal_container" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <div id="portal_main" class="col-lg-10 col-lg-offset-1">
	      <div id="dialog"></div>

<?
// accessibility stuff
// font size
if(isset($_GET['fsize'])){
  switch($_GET['fsize']){
    default:       $fontClass = "normal_font"; break;
    case "big":    $fontClass = "big_font";    break;
    case "small":  $fontClass = "small_font";  break;
  }
  $_SESSION['wca_fsize'] = $fontClass;
}else{
  $_SESSION['wca_fsize'] = isset($_SESSION['wca_fsize'])? $_SESSION['wca_fsize']: "normal_font";
} 
// contrast
if(isset($_GET['contrast'])){
  switch($_GET['contrast']){
    default:               $contrast = 'defaultstyle';      break;
    case 'highcontrast':   $contrast = 'highcontraststyle'; break;
  }
  $_SESSION['wca_contrast'] = $contrast;
}else{
  $_SESSION['wca_contrast'] = isset($_SESSION['wca_contrast'])? $_SESSION['wca_contrast']: 'defaultstyle';
}
// css disable
if(isset($_GET['css'])){
  switch($_GET['css']){
    default:        $cssdisable = false; break;
    case 'disable': $cssdisable = true; break;
  }
  $_SESSION['wca_cssdisable'] = $cssdisable; 
}else{
  $_SESSION['wca_cssdisable'] = isset($_SESSION['wca_cssdisable'])? $_SESSION['wca_cssdisable']: false;
}
// levo dalla query string le var passate per l'accessibilita'
if(($sqs = $_SERVER['QUERY_STRING'])!=''){
  $sqs = preg_replace('/([&]+)?(fsize|contrast|css)\=[a-zA-Z]+/i',    '', $sqs);
  $sqs.= $sqs!=''? '&amp;': '';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
          "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title><?=$C['ente']['nome_ente']?> - Portale dei servizi</title>
<?
if($_SESSION['wca_cssdisable']===false){
?>
    <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/common.css" media="screen">
    <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/common_<?=$_SESSION['wca_contrast']?>.css" media="screen">    
    <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/portal.css" media="screen">
    <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/portal_<?=$_SESSION['wca_contrast']?>.css" media="screen">
    <script language="javascript" type="text/javascript" src="<?=URL_JS?>/Jquery/jquery-1.4.2.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?=URL_JS?>/Jquery/init.js"></script>
<?
}
?>    
</head>
<body>
<script type="text/javascript">
function getObj(name){
  var obj;
  if (typeof(name) == 'object') obj = name;
  else if (document.getElementById) obj = document.getElementById(name);
  else if (document.all) obj = document.all[name];
  else if (document.layers) obj = getNN4Obj(document, name);
  if (obj && !obj.id) obj.id = 'obj'+(idCount++);
  return obj;
}
function menutoggle(divId, parent){
  div = getObj(divId);
  if (!div || !div.style) return;
  if (parent) parentlink = getObj(parent);
  if (div.style.display == 'none'){
    if (parentlink) parentlink.className = 'expanded';
    div.style.display = '';
  }else{
    if (parentlink) parentlink.className = '';
    div.style.display = 'none';
  }
}
</script>
<!-- this is da page -->
<div id="container" class="<?=$_SESSION['wca_fsize']?>">
    <a name="top"></a>
    <!-- header -->
    <div id="header">
    <table><tr><td>
        <a href='/portal'><img src="/public/img/stemma.png" alt="Stemma" /> </a>
        </td><td><h1><?=$C['ente']['nome_ente']?></h1></td></tr></table>
    </div>
    <!-- /header -->
    <!-- topbar  
    <div id="topbar">
        <div id="link_accessibilita">
            <span class="hide">Dimensione Testo:</span>
            <a href="<?=$_SERVER['PHP_SELF'].'?'.$sqs.'fsize=small'?>"  class="fontsize fs_small<?=$_SESSION['wca_fsize']=='80%'?   'on': ''?>" title="Diminuisci la dimensione del testo">&nbsp;<span class="hide">Piccolo</span></a>
            <a href="<?=$_SERVER['PHP_SELF'].'?'.$sqs.'fsize=normal'?>" class="fontsize fs_normal<?=$_SESSION['wca_fsize']=='100%'? 'on': ''?>" title="Dimensione testo normale">&nbsp;<span class="hide">Normale</span></a>
            <a href="<?=$_SERVER['PHP_SELF'].'?'.$sqs.'fsize=big'?>"    class="fontsize fs_big<?=$_SESSION['wca_fsize']=='120%'?    'on': ''?>" title="Aumenta la dimensione del testo">&nbsp;<span class="hide">Grande</span></a>&nbsp;|&nbsp;
	    <a href="<?=$_SERVER['PHP_SELF'].'?'.$sqs.'contrast='.($_SESSION['wca_contrast']=='highcontraststyle'? 'normal': 'highcontrast').($_SESSION['wca_cssdisable']===true? '&css=enable': '')?>"><?=$_SESSION['wca_contrast']=='highcontraststyle'? 'Colori Tradizionali': 'Alto Contrasto'?></a>&nbsp;|&nbsp;
            <a href="<?=$_SERVER['PHP_SELF'].'?'.$sqs.'css='.($_SESSION['wca_cssdisable']? 'enable': 'disable')?>"><?=$_SESSION['wca_cssdisable']? 'Versione Normale': 'Versione Testuale'?></a>
        </div>    
        <a href='<?=URL_APP_PORTAL?>/index.php'>Portale Del Cittadino</a>&nbsp;|&nbsp;
<?php
if (defined('URL_APP_CMS')){
?>
        <a href="<?=URL_APP_CMS?>/">Torna al Sito</a>   
<?
}
?>
        <span class="hide">&nbsp;|&nbsp;<a href="#content">Vai ai Contenuti</a></span>      
    </div> -->
    <!-- /topbar -->      

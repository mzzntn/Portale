<?
  if ($IMP->defaults['display'] != 'html' && $IMP->defaults['display'] != 'html.dhtml') return;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
          "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>OpenWEB - Amministrazione</title>
    <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/admin-portal_admin.1.css" media="screen">
    <!--<link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/login.css" media="screen">      -->
    <link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/common_adminstyle.css" media="screen">   
    <!--<link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/bootstrap-glyphicons.css" media="screen">   -->
    
    <style type="text/css">
    @import url("//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css");
    </style>
    <!--<style type="text/css">
    @import url("//netdna.bootstrapcdn.com/bootstrap/3.0.0-rc2/css/bootstrap-glyphicons.css");
    </style>-->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
</head>
<body id="spider-admin">
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
<!-- <div id="container" class="admin"> -->
    <!-- topbar  -->
    <!-- <div id="topbar">  
        <a href='<?=$_SERVER['PHP_SELF']?>'>Amministrazione</a>&nbsp;|&nbsp;
<?php
if (defined('URL_APP_CMS')){
?>        
        <a href="<?=URL_APP_CMS?>/index.php">Sito</a>&nbsp;|&nbsp;
<?php
}
if (defined('URL_APP_PORTAL')){
?>
        <a href="<?=URL_APP_PORTAL?>/">Portale</a>         
<?php
}

if (defined('PATH_ALBO') && defined('URL_APP_PRATICHE') && $IMP->security->checkAdmin()){
    ?>
        &nbsp;|&nbsp;<a href="<?=PATH_ALBO?>/inserimento.php">Inserimento Manuale</a> 
<?php
}

if (defined('URL_APP_PRATICHE') && $IMP->security->checkAdmin()){
        ?>
        &nbsp;|&nbsp;<a href="<?=URL_APP_PRATICHE?>/pratiche_full.php">Cancellazione  Manuale</a>
<?php
}

?>
    </div>-->
    <!-- /topbar -->     

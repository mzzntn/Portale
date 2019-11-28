<? include_once('../../init.php'); ?>
<?
$IMP->config['inlineJs'] = true;
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/default.css">
<link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/info.css">
<link rel="stylesheet" type="text/css" href="<?=URL_CSS?>/widgets/base/Form/inputs/RichTextInput/default.css">
<TITLE>Editor</TITLE>
<script>
<?
/*
<script src='../divControls.js'></script>
<!--<script src='../debugConsole.js'></script>-->
<script src='../comboBox.js'></script>
<script src='myHTML.php'></script>
*/
include_once('../divControls.js');
include_once('../comboBox.js');
include_once('myHTML.php');
?>
</script>
<script>
var myHTML;
window.unselectable = 'on' ;
function initMyHTML(){

  myHTML = new MyHTML('myHTML');
  myHTML.master = window.top.master;
  myHTML.hidden = myHTML.master.hidden;
  myHTML.baseUrl = myHTML.master.baseUrl;
  myHTML.toolsUrl = myHTML.master.toolsUrl;
  myHTML.className = myHTML.master.className + " fullscreen";

  <?
  global $IMP;
  if ($IMP->security->options['editor']['toolbar']){
    //print "myHTML.config.toolbar = myHTML.config.modes['".$IMP->security->options['editor']['toolbar']."'];";
    print "myHTML.config.toolbarMode = '".$IMP->security->options['editor']['toolbar']."';";
  }
  else{
  ?>
  myHTML.config.toolbarMode = 'full';
  <?
  }
  ?>

  myHTML.init();
  //myHTML.setValue(myHTML.master.doc.documentElement.innerHTML);
  myHTML.setValue(myHTML.master.doc.body.innerHTML);
  myHTML.div.style.width = '100%';
  myHTML.div.style.height = '100%';
  myHTML.iframe.style.width='100%';
  myHTML.iframe.style.height='90%';
}
function saveChanges(){
  try{
    myHTML.master.setValue(myHTML.doc.body.innerHTML);
  }
  catch(exc){
  }
}

window.onunload = saveChanges;
</script>
</head>
<body class='myHTML fullscreen' onload='initMyHTML()'>
<div id='myHTML'></div>

</body>
</html>

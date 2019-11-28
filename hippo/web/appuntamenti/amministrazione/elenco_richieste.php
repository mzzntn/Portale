<?
include_once('../../init.php');
// $IMP->debugLevel = 3;
if (!$IMP->security->checkAdmin()) redirect('login.php');

include_once('appuntamenti_top.php');

// echo "<pre>".print_r($_POST,true)."</pre>";
// 
// echo "<pre>".print_r($_REQUEST,true)."</pre>";
// echo "<pre>".print_r($_SESSION,true)."</pre>";

if(count($_POST)) {
  $_REQUEST['table_appuntamenti']['page']=1;
  $_REQUEST['table_appuntamenti']['start']=0;
  $_SESSION['table_pages'] = array("appuntamenti::prenotazione_start" => 0);
}

?>
<script type="text/javascript">
setTimeout(function() {
  console.log("reload "+Date.now());
  location.reload();
}, 30*60*1000);
</script>
<?

$search = & $IMP->getWidget('Form/SearchForm', 'search_appuntamenti');
$search->setStruct('appuntamenti::prenotazione');
if ($_REQUEST['clear']){
  $search->clearParams();
}
$search->config['method'] = 'get';

$search->generateFromStructure();
$query = $search->generateQuery();

if ((!isset($_REQUEST['search_appuntamenti']['inizio_1']) && !isset($_SESSION['widgets']['search_appuntamenti']['inizio_1'])) ||  $_REQUEST['clear']){ 
  $date1 = & dt_DateTime();
  $date1->now();
  $date1->h = 0;
  $date1->m = 0;
  $date1->s = 0;
  $date2 = & dt_DateTime();
  $date2->fromTimestamp(strtotime("+1 week"));
  $date2->h = 23;
  $date2->m = 59;
  $date2->s = 59;
  if (!$query) $query = new QueryParams();
  $query->addRange('inizio', $date1->toISO(), $date2->toISO());
  $date2->h = 0;
  $date2->m = 0;
  $date2->s = 0;
  $search->inputs->inizio_1->setValue($date1->toUser());
  $search->inputs->inizio_2->setValue($date2->toUser());
}

if(isset($_REQUEST['search_appuntamenti']['inizio_2']) && $_REQUEST['search_appuntamenti']['inizio_2'] != "" && !isset($_REQUEST['clear'])) {
  $date1 = & dt_DateTime();
    if(isset($_REQUEST['search_appuntamenti']['inizio_1']) && $_REQUEST['search_appuntamenti']['inizio_1'] != "") {
    $dateExp = explode("/",$_REQUEST['search_appuntamenti']['inizio_1']);
    $date1->day = $dateExp[0]; 
    $date1->month = $dateExp[1]; 
    $date1->year = $dateExp[2]; 
  } else {    
    $date1->now();
  }
  $date1->h = 0;
  $date1->m = 0;
  $date1->s = 0;
  $date2 = & dt_DateTime();
  $dateExp = explode("/",$_REQUEST['search_appuntamenti']['inizio_2']);
  $date2->day = $dateExp[0]; 
  $date2->month = $dateExp[1]; 
  $date2->year = $dateExp[2]; 
  $date2->h = 23;
  $date2->m = 59;
  $date2->s = 59;
  if (!$query) $query = new QueryParams();
  $query->addRange('inizio', $date1->toISO(), $date2->toISO());
  $date2->h = 0;
  $date2->m = 0;
  $date2->s = 0;
  $search->inputs->inizio_1->setValue($date1->toUser());
  $search->inputs->inizio_2->setValue($date2->toUser());
}

if(isset($_REQUEST['table_appuntamenti']['page']) && isset($_SESSION['widgets']['search_appuntamenti'])) {
  $date1 = & dt_DateTime();
    if(isset($_SESSION['widgets']['search_appuntamenti']['inizio_1']) && $_SESSION['widgets']['search_appuntamenti']['inizio_1'] != "") {
    $dateExp = explode("/",$_SESSION['widgets']['search_appuntamenti']['inizio_1']);
    $date1->day = $dateExp[0]; 
    $date1->month = $dateExp[1]; 
    $date1->year = $dateExp[2]; 
  } else {    
    $date1->now();
  }
  $date1->h = 0;
  $date1->m = 0;
  $date1->s = 0;
  $date2 = & dt_DateTime();
  $dateExp = explode("/",$_SESSION['widgets']['search_appuntamenti']['inizio_2']);
  $date2->day = $dateExp[0]; 
  $date2->month = $dateExp[1]; 
  $date2->year = $dateExp[2]; 
  $date2->h = 23;
  $date2->m = 59;
  $date2->s = 59;
  if (!$query) $query = new QueryParams();
  $query->addRange('inizio', $date1->toISO(), $date2->toISO());
  $date2->h = 0;
  $date2->m = 0;
  $date2->s = 0;
  $search->inputs->inizio_1->setValue($date1->toUser());
  $search->inputs->inizio_2->setValue($date2->toUser());
}

if (($C['appuntamenti']['stato_richieste_default'] == '2' && !isset($_REQUEST['search_appuntamenti']['stato'])) ||  $_REQUEST['clear']){
  $search->inputs->stato->setValue(2);
  $query->addParam('stato', '2');
}


$search->inputsOrder = array('persona', 'stato', 'operatore', 'inizio', 'fine', 'tipo');
$search->setTemplate('appuntamenti');
$search->prepareDisplayer();

if ($query)
{
  $table = & $IMP->getWidget('Table', 'table_appuntamenti');
  $table->config['elements'] = array('persona', 'id', 'stato','operatore', 'inizio', 'fine', 'tipo');
  $table->config['admin'] = 'prenotazione.php?id=';
  $table->setStruct('appuntamenti::prenotazione');
  $table->setParams($query);
  $table->config['sort'] = array('inizio' => 'ASC');
  $table->setTemplate('appuntamenti');
  
  $table->load();
}

// echo "<pre>".print_r($_SESSION,true)."</pre>";

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>

<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-6 col-md-6 col-sm-12 col-xs-12">Gestione Appuntamenti</h3>
	<div class='col-lg-6 col-md-6 col-sm-12 col-xs-12 mt20'>
	  <div class='pull-right'><a class='btn btn-default' href="login.php?action=logout">Esci <i class="glyphicon glyphicon-log-out"></i></a>
	  </div>
	</div>
      </div>
      <div class="row ricerca">
      <?
	  if ($search) $search->display();
	  ?>
	
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	  <div id="appuntamenti_table" class="table">
	  <?

// echo "<pre>".print_r($_SESSION,true)."</pre>";
      if($table) {
      
        $table->display();
      }
	  ?>
	  </div>
	</div>
      </div>

      <div class="bottoni_pagina">
	<div class="row">
	  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <div class="back">
	      <?=tornaAlPortale("button")?>
	    </div>
	    <?
//	    if (!$_SESSION['operatore']){
	    ?>
	    <? if (defined('URL_APP_CARICAMENTO_PRATICHE')){ ?>
	    <a class='btn btn-default btn-bigger' href= '<?=URL_APP_CARICAMENTO_PRATICHE?>/amministrazione/istanze.php'>Gestione Istanze</a>
	    <? } 
      if (!$_SESSION['livello']){
      ?>
	    <a class='btn btn-default btn-bigger' href= 'tabelle.php'>Tabelle di base</a>
	    <?
         }
//	    }
	    ?>
	  </div>
	</div>
      </div>
      
    </div>
  </div>
</div>

<? } else { ?>
<link rel='stylesheet' type='text/css' href='<?=URL_CSS?>/appuntamenti_admin.css'>
<?
#$IMP->debugLevel = 3;
?>

  <div class="boxSpacer">&nbsp;</div>
<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div> 
    <div class="boxSpacer">&nbsp;</div>
    <div class="boxContent">
<?
if ($_SESSION['operatore']){
?>
        <h3 class="heading">&nbsp;&nbsp;&nbsp;Gestione Appuntamente<div style='float: left;'><a class="highlight" href= 'login.php?action=logout'>Esci</a></div></h3>
<?
}
else{
?>
        <h3 class="heading">&nbsp;&nbsp;&nbsp;Gestione Appuntamenti<div style='float: left;'><a class="highlight" href= 'login.php?action=logout'>Esci</a></div><div style='float: right;'>
<?
    if (defined('URL_APP_CARICAMENTO_PRATICHE')){ 
?>
	<a class="highlight" href= '<?=URL_APP_CARICAMENTO_PRATICHE?>/amministrazione/istanze.php'>Gestione Istanze</a>
<? } ?>
<a class="highlight" href= 'tabelle.php'>Tabelle di base</a></div></h3>
<?
}
if ($search) $search->display();

      if($table) {
      
        $table->display();
      }
?>
    </div> 
    <div class="clear"></div>
    <div class="leftBottomCorner"></div>
    <div class="rightBottomCorner"></div> 
    <div class="boxSpacer">&nbsp;</div>   
</div>

<?
}
include_once('../appuntamenti_bottom.php');
?>

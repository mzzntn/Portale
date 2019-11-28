<?
include_once('./init.php');
include_once('./appuntamenti_top.php');
#chiusure fisse annuali: GG/MM
$chiusureAnnuali = $C['appuntamenti']['chiusureAnnuali'];


$id_operatore = isset($_GET['operatore_appuntamento_visible'])?$_GET['operatore_appuntamento_visible']:null;
$id_tipo = isset($_GET['tipo_appuntamento'])?$_GET['tipo_appuntamento']:null;
$giorno_appuntamento = isset($_GET['giorno_appuntamento'])&&strlen($_GET['giorno_appuntamento'])?$_GET['giorno_appuntamento']:time();
$durata_minima; // viene impostata in base alla durata minima presente in db su tipo appuntamento
$mostra_prenotati = isset($_GET['mostraPrenotati']);

if ($_REQUEST['servizio']) $_SESSION['praticaAppuntamento'] = '';
if ($_REQUEST['idPratica']) $_SESSION['praticaAppuntamento'] = $_REQUEST['idPratica'];

$loader = & $IMP->getLoader('appuntamenti::prenotazione');
$loader->addParam('utente', $master);
$loader->requestAll();
$presi = $loader->load();
//print_r($presi);
//print $master;
$strPrecedenti = false;
if ($presi->listSize() > 0) $strPrecedenti = "<div style='float: right;'><a class='highlight' href= '".URL_APP_APPUNTAMENTI."/appuntamenti_elenco.php'>Elenco Appuntamenti</a></div>";
//print $presi->listSize();


$loader = & $IMP->getLoader('appuntamenti::operatore');
$loader->request('tipi', 2);
$loader->request('tipi.id');
$loader->addOrder('nome', 'ASC');
$loader->requestAll();
$operatore_load = $loader->load();
//$tipi = $operatore_load->getList('tipi');
$operatoreSelect = "";
$first = true;
while($operatore_load->moveNext()) 
{
  $add = '';
  if(($first && $id_operatore == null))
  {
    $id_operatore = $operatore_load->get('id');
    $add = ' checked';
  }
  else if ($id_operatore == $operatore_load->get('id'))
  {
    $add = ' checked';
  }
  $operatoreSelect .= "<input type='radio' name='operatore_appuntamento_visible' id='operatore_appuntamento' value='".$operatore_load->get('id')."'{$add}> <b>".$operatore_load->get('nome')."</b><br>".$operatore_load->get('descrizione')."<br><br>";

  $first = false;
}

$loader = & $IMP->getLoader('appuntamenti::operatore');
$loader->addParam('id', $id_operatore);
$variabiliOperatore = $loader->load();

if ($variabiliOperatore->get('limitaMesi')) $C['appuntamenti']['limitaMesi'] = $variabiliOperatore->get('limitaMesi');

$loader = & $IMP->getLoader('appuntamenti::tipo');
$bindingTipo = $IMP->bindingManager->getBinding('appuntamenti::tipo');
$db = $bindingTipo->getDbObject();

$sql = "SELECT ID_APPUNTAMENTI__TIPO  FROM appuntamenti__operatore_ref_appuntamenti__tipo WHERE ID_APPUNTAMENTI__OPERATORE = {$id_operatore};";
$db->execute($sql);
$tipi = array();
while ($db->fetchrow()){
     $tipi[]  = $db->result('ID_APPUNTAMENTI__TIPO');
}

$loader = & $IMP->getLoader('appuntamenti::tipo');
$loader->addParam('id', $tipi);
$loader->requestAll();
$tipoPrenotazione_load = $loader->load();
$tipoSelect = "";
$tipoDurate = "";
$first = true;
while($tipoPrenotazione_load->moveNext()) 
{
  $add = '';
  if(($first && $id_tipo == null))
  {
    $id_tipo = $tipoPrenotazione_load->get('id');
    $add = ' selected';
  }
  else if ($id_tipo == $tipoPrenotazione_load->get('id'))
  {
    $add = ' selected';
  }
  $tipoSelect .= "<option value=".$tipoPrenotazione_load->get('id')."{$add}>".$tipoPrenotazione_load->get('tipo')." (".$tipoPrenotazione_load->get('durata')." minuti)</option>";
  $tipoDurate .= "".$tipoPrenotazione_load->get('id').":".$tipoPrenotazione_load->get('durata').",";
  if($durata_minima == null || $tipoPrenotazione_load->get('durata')<$durata_minima) {
    if($durata_minima != null) {
      $diff = $tipoPrenotazione_load->get('durata') - $durata_minima;
      $diff = abs($diff);
      $durata_minima = $tipoPrenotazione_load->get('durata'); 
      if($diff>0) {
	$durata_minima = $diff;
      }
    }
    else {
      $durata_minima = $tipoPrenotazione_load->get('durata'); 
    }
//     echo "minima: [$durata_minima] - tipo: [".$tipoPrenotazione_load->get('durata')."] - diff [$diff]<br>";
  }
}
#$IMP->debugLevel= 3;


$loader = & $IMP->getLoader('appuntamenti::orario');
$loader->addParam('operatore', $id_operatore);
$loader->requestAll();
$orario_load = $loader->load();
$orarioArray = array();
$orarioJs = "";
while($orario_load->moveNext()) 
{
  $apertura = explode(":",$orario_load->get('orario_apertura'));
  $chiusura = explode(":",$orario_load->get('orario_chiusura'));
  $giorno = $orario_load->get('giorno');
//   echo "giorno ".$orario_load->get('giorno')." - apertura ".$orario_load->get('orario_apertura')." - chiusura ".$orario_load->get('orario_chiusura')."<hr>";
  $fascia = array("oH"=>$apertura[0], "oM"=>$apertura[1], "cH"=>$chiusura[0], "cM"=>$chiusura[1]);
  if(!is_array($orarioArray[$giorno])){$orarioArray[$giorno]=array();}
  if($apertura[0]<12)
  {
    
    $orarioArray[$giorno][] = $fascia;
  }
  else
  {
    $orarioArray[$giorno][] = $fascia;
  }
}

foreach($orarioArray as $giorno => $fasce)
{
  if($giorno==7){$giorno=0;}
  $orarioJs .= "'$giorno':{";
  foreach($fasce as $fascia => $ore)
  {
    $orarioJs .= "'$fascia':{";
    foreach($ore as $key => $value)
    {
      $orarioJs .= "'$key':".intval($value).",";
    }
    $orarioJs .= "},";
  }
  $orarioJs .= "},\n";
}

$loader = & $IMP->getLoader('appuntamenti::chiusura');
// $loader->addParam('operatore', $id_operatore);
$loader->requestAll();
$chiusura_load = $loader->load();
$date1 = & dt_DateTime();
$date1->now();
$anno = $date1->year;
$anno2 = $anno+1;
$mese = $date1->month;
$giorno = $date1->day;
if ($C['appuntamenti']['giorniAnticipo'] > 0){
        $chiusureAnnuali[] = $giorno."/".$mese;
        for ($cnt = 1; $cnt<$C['appuntamenti']['giorniAnticipo']; $cnt++){
                $giorno = $giorno + 1;
                echo "giorno now is $giorno<br>";
                $chiusureAnnuali[] = $giorno."/".$mese;
        }
}
$secondo = false;
$chiusuraJs = "'";
foreach ($chiusureAnnuali as $c){
        if ($secondo) $chiusuraJs .= ",'";
        $d = explode('/',$c);
        $chiusuraJs .= strtotime($anno.'-'.$d[1].'-'.$d[0].'T00:00:00')."000'";
        $secondo = true;
}
while($chiusura_load->moveNext())
{
  if($chiusura_load->get('operatore')=='0'||$chiusura_load->get('operatore')==''||$chiusura_load->get('operatore')==$id_operatore) {
    $chiusuraJs .= ", \n";
    $timestamp = strtotime($chiusura_load->get('giorno_chiusura'));
    $chiusuraJs .= "'{$timestamp}000'";
  }
}


// echo "giorno appuntamento is [$giorno_appuntamento]<br>";
if($giorno_appuntamento != null)
{
//   echo "carico le prenotazioni <br>";
  $loader = & $IMP->getLoader('appuntamenti::prenotazione');
  
  $qp = new QueryParams();
  $dtInizio = & dt_DateTime();
  $dtFine = & dt_DateTime();
  $dtInizio->fromTimeStamp(substr($giorno_appuntamento,0,10));
  $dtInizio->h = 0;
  $dtInizio->m = 0;
  $dtInizio->s = 0;
  $dtFine->fromTimeStamp(substr($giorno_appuntamento,0,10));
  $dtFine->h = 23;
  $dtFine->m = 59;
  $dtFine->s = 59;
  $qp->addRange('inizio', $dtInizio->toISO(), $dtFine->toISO());
  $giorno_appuntamento = $dtInizio->timeStamp();
//   echo "dtInizio: ".$dtInizio->toISO().", dtFine: ".$dtFine->toISO()."<br>";
  
  $loader->setParams($qp);
  $loader->addParam('operatore', $id_operatore);
  $loader->addParam('stato', 3, "<>");
  $loader->requestAll();
  $prenotati_load = $loader->load();
  $prenotatiJs = "";
  $first = true;
  while($prenotati_load->moveNext()) 
  {
    if(!$first){ $prenotatiJs .= ", \n";}
    
    $inizio = $prenotati_load->get('inizio');
    $fine = $prenotati_load->get('fine');
    
//     echo "inizio: $inizio, fine: $fine<br>";
    $prenotatiJs .= "{'inizio':".strtotime($inizio)."000,'fine':".strtotime($fine)."000}";
    
    $first=false;
  }
//   echo "giorno_appuntamento: ".substr($giorno_appuntamento,0,10)."<br>";
}

?>
<script type='text/javascript'>
var maxMonths = <?=$C['appuntamenti']['limitaMesi']?"{$C['appuntamenti']['limitaMesi']}":"6"?>;
var maxDate = '+'+maxMonths+'m';
var giornoAppuntamento = <?=($giorno_appuntamento != null)?$giorno_appuntamento."000":"false";?>;
var durataMinima = <?=$durata_minima?>; // durata minima appuntamento
var mostraPrenotati = <?=$mostra_prenotati?"true":"false"?>;

function getSelectedDate()
{
  return new Date(<?=$giorno_appuntamento?>);
}

var typeDuration = {<?=$tipoDurate?>};

var closingDays = new Array
(
  <?=$chiusuraJs?>
  
);

var openingDays = 
{
  <?=$orarioJs?>
};

var bookedHours = new Array
(
  <?=$prenotatiJs?>
);

</script>
<style type='text/css'>
.ui-datepicker-multi-2 .ui-datepicker-group {
    width: 48%;
}
</style>
<?
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Prenotazione appuntamento <?=$strPrecedenti?></h3>
      </div>
      <div class="row">

	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	  <div id="appuntamenti_table" class="table">
	  
	    <form method='GET' class='form form-horizontal' id='form_appuntamento'>  
	      <div class="form-group">
		<label for="operatore_appuntamento" class="sr-only">Sportello/operatore</label>
		<label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Sportello/operatore</label>
		<div class="col-lg-4 col-md-4 col-sm-4">
		    <?=$operatoreSelect;?>
		</div>
		<label for="operatore_appuntamento" class="sr-only">Tipo appuntamento</label>
		<label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipo appuntamento</label>
		<div class="col-lg-4 col-md-4 col-sm-4">
		    <select name="tipo_appuntamento" id='tipo_appuntamento' class="form-control" tabindex="2"><?=$tipoSelect;?></select>
		</div>
	      </div>
	      
	      <div class="form-group">
		<label for="giorno_appuntamento" class="sr-only">Giorno</label>
		<label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Giorno</label>
		<div class="col-lg-6 col-md-10 col-sm-10">
		    <input type='hidden' name='giorno_appuntamento' id='giorno_appuntamento_hid'>
		    <div id="giorno_appuntamento" style='display:inline-block; width:100%;'></div>
		    <label for='giorno'>Hai scelto il giorno:</label> <span id='giorno'></span>
		</div>
		<label for="operatore_appuntamento" class="sr-only">Orario</label>
		<label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Orario</label>
		<div class="col-lg-2 col-md-2 col-sm-2">
		    <div style='display:inline-block; width: 70%;' id='orario_appuntamento_div'></div>
		</div>
	      </div>
	    </form>
	    
	  </div>
	</div>
      </div>


      <div class="bottoni_pagina">
	<div class="row">
	  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <?=tornaAlPortale("button")?>
	    <form method='GET' id='form_appuntamento_riepilogo' action='appuntamento_riepilogo.php' style='display: inline-block;'>
		<input type='hidden' name='tipo_appuntamento' id='tipo_appuntamento_hidden'>
		<input type='hidden' name='operatore_appuntamento' id='operatore_appuntamento_hidden'>
		<input type='hidden' name='giorno_appuntamento' id='giorno_appuntamento_hidden' value='<?=$giorno_appuntamento?>'>
		<input type='hidden' name='orario_appuntamento' id='orario_appuntamento_hidden'>
		<input type='submit' class='btn btn-success btn-bigger' name='richiedi' value='Prenota' id='richiediSubmit' disabled>
	    </form>
	    <? if ($strPrecedenti){ ?>	   
		<a class='btn btn-default btn-bigger' href= 'appuntamenti_elenco.php'>Elenco Appuntamenti</a> 
	    <? } ?>
	  </div>
	</div>
      </div>
      
    </div>
  </div>
</div>	  
<?} else {?>
<script src="js/appuntamenti.js" defer="defer"></script>

<?if(!$C["portal"]["spider_portal"]){?>
<link rel="stylesheet" href="css/jquery-ui-1.10.4.custom.min.css">
<script src="js/jquery-1.10.2.js"></script>
<script src="js/jquery-ui-1.10.4.custom.min.js"></script>
<script src="js/jquery.ui.datepicker-it.js"></script>
<?}?>
<style type='text/css'>

form .divRow {
    border-bottom: 1px solid #D5D5D5;
    padding: 5px;
}
</style>
<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div> 
    <div class="boxSpacer">&nbsp;</div>
    <div class="boxContent">
  <div class='sala'>
    <div class='titolo'><h3>Prenotazione appuntamento <?=$strPrecedenti?></h3></div>
	<form method='GET' class='rowform' id='form_appuntamento'>
		<div class='divRow'>
			<label for='operatore_appuntamento'>Sportello/operatore</label>
			<div style='display:inline-block;'><?=$operatoreSelect;?></div>
		</div>
		<div class='divRow'>
			<label for='tipo_appuntamento'>Tipo appuntamento</label>
			<select name="tipo_appuntamento" id='tipo_appuntamento' tabindex="2"><?=$tipoSelect;?></select>
		</div>
		<div class='divRow'>
			<label for='giorno_appuntamento'>Giorno</label>
			<div id="giorno_appuntamento" style='display:inline-block;'></div>
			<input type='hidden' name='giorno_appuntamento' id='giorno_appuntamento_hid'><br>
			<label for='giorno'>Hai scelto il giorno</label><span id='giorno'></span>
		</div>
		<div class='divRow'>
			<label for='orario_appuntamento'>Orario</label>
			<div style='display:inline-block; width: 70%;' id='orario_appuntamento_div'></div>
		</div>
	</form>
	<form method='GET' class='rowform' id='form_appuntamento_riepilogo' action='appuntamento_riepilogo.php'>
		<input type='hidden' name='tipo_appuntamento' id='tipo_appuntamento_hidden'>
		<input type='hidden' name='operatore_appuntamento' id='operatore_appuntamento_hidden'>
		<input type='hidden' name='giorno_appuntamento' id='giorno_appuntamento_hidden' value='<?=$giorno_appuntamento?>'>
		<input type='hidden' name='orario_appuntamento' id='orario_appuntamento_hidden'>
		<div class='row buttons'>
  			<input type='submit' name='richiedi' value='Richiedi appuntamento' id='richiediSubmit' disabled>
		</div>
	</form>
</div>
</div>
</div>
<?
}
include_once('./appuntamenti_bottom.php');
?>

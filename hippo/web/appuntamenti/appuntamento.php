<?
include_once('./init.php');
include_once('./amministrazione/email.php');
include_once('./appuntamenti_top.php');

$stato_richiesta_default = $C['appuntamenti']['stato_richieste_default'];
//$IMP->debugLevel = 3;
// print_r($_GET);
if(isset($_GET['invio']))
{  

}
if ($_SESSION['praticaAppuntamento'] && defined('URL_APP_CARICAMENTO_PRATICHE')){
    $loader = & $IMP->getLoader('caricamento_pratiche::pratica');
    $loader->addParam('id',  $_SESSION['praticaAppuntamento']);
    $loader->request('tipologia.descrizione');
    $istanza = $loader->load();
    $pratica = "<li><strong>Pratica:</strong> ".$istanza->get('tipologia.descrizione')." nr. ".$_SESSION['praticaAppuntamento']." del ".dateToUser($istanza->get('dataInizio'))."</li>";
	
}

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>

<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <form method='GET' class='rowform' id='form_appuntamento_riepilogo'>
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-6 col-md-6 col-sm-12 col-xs-12">Riepilogo appuntamento</h3>
	<div class='col-lg-6 col-md-6 col-sm-12 col-xs-12 mt20'>
	  <div class='pull-right'><a class='btn btn-default' href="<?=URL_APP_APPUNTAMENTI?>/appuntamenti_elenco.php">Torna all'elenco</a>
	  </div>
	</div>
      </div>
      <div class="row ricerca">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	<?	
	  if(isset($_GET['invio']))
{
//$IMP->debugLevel = 3;
//print_r($_GET);
    $storer = & $IMP->getStorer('appuntamenti::prenotazione');
    $storer->set('id', $_GET['id']);
    $storer->set('stato', '3');
    $idPrenotazione = $storer->store();
    email_annullamento($_REQUEST['id'], $_REQUEST['note_annullamento']);

?>
	    <p class='alert alert-danger'>La prenotazione &egrave; stata annullata.</p>
<?

}
else{
if ($_REQUEST['id']){
        $loader = & $IMP->getLoader('appuntamenti::prenotazione');
        $loader->addParam('utente', $master);
        $loader->addParam('id', $_REQUEST['id']);
        $loader->request('stato.stato');
        $loader->request('tipo.tipo');
	$loader->request('tipo.durata');
        $loader->request('operatore.nome');
        $loader->addOrder('inizio', 'DESC');
        $loader->requestAll();
        $preso = $loader->load();
	$oggi = strtotime(date('Y-m-d'));
	$dtA = strtotime(substr($preso->get('inizio'),0,10));
	$annullabile = false;
	if (($dtA > $oggi) && $preso->get('stato.id') < 3) $annullabile = true;

}
  

?>
	<ul>
	  <li><b>Sportello/operatore:</b> <?=$preso->get('operatore.nome')?></li>
	  <li><b>Tipologia:</b> <?=$preso->get('tipo.tipo')?></li>
	  <li><b>Giorno:</b> <?=dateToUser($preso->get('inizio'))?></li>
  	  <li><b>Orario:</b> <?=timeToUser($preso->get('inizio'))?> (durata <?=$preso->get('tipo.durata')?> minuti)</li>
<!--	  <li><b>Particelle Edificiali:</b> <?=$preso->get('ped')?></li>
	  <li><b>Comune Catastale:</b> <?=$preso->get('comuneCatastale')?></li>-->
	  <li><b>Note:</b> <?=$preso->get('note')?></li>
	  <li><b>Stato:</b> <?=$preso->get('stato.stato')?></li>
	</ul>
<?
if ($annullabile){
?>
	<p class="alert alert-info">Puoi annullare l'appuntamento cliccando sul tasto Annulla appuntamento.</br>
        Nel campo note qui sotto, puoi inserire eventuali comunicazioni all'operatore.</p>

     <?=$errori?>

                 <div class='row'>
                        <label for='note_annullamento'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Note</label>
                        <textarea id='note_annullamento' name='note_annullamento' value='<?=$_GET['note_appuntamento']?>'></textarea>
                </div>

                <input type='hidden' name='id' id='id' value='<?=$_REQUEST['id']?>'>

<?
}
}
?>

		
	</div>	
      </div>
    </div>
  </div>

  <div class="bottoni_pagina">
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	<div class="back">
	  <a href='appuntamenti_elenco.php' class='btn' >Torna all'elenco</a>
	  <?if ($annullabile){?>
		<button type='submit' class='btn btn-success btn-bigger' name='invio'>Annulla appuntamento</button>
	  <?}?>
	</div>
      </div>
    </div>
  </div>
  
  </form>
  
</div>

<?
} else { ?>
<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div> 
    <div class="boxSpacer">&nbsp;</div>
    <div class="boxContent">

<?

if(isset($_GET['invio']))
{
//$IMP->debugLevel = 3;
//print_r($_GET);
    $storer = & $IMP->getStorer('appuntamenti::prenotazione');
    $storer->set('id', $_GET['id']);
    $storer->set('stato', '3');
    $idPrenotazione = $storer->store();
    email_annullamento($_REQUEST['id'], $_REQUEST['note_annullamento']);

?>
  <div class='sala'>
    <div class='titolo'><h3>Conferma prenotazione appuntamento</h3></div>
        <p>L'appuntamento con progressivo <?=$idPrenotazione?> &egrave; stato annullato</p>
        <br><br>
        <p><a href='<?=URL_APP_PORTAL?>' class='highlight' >Torna al portale</a></p>
     </div>
</div>
<?

}
else{
if ($_REQUEST['id']){
        $loader = & $IMP->getLoader('appuntamenti::prenotazione');
        $loader->addParam('utente', $master);
        $loader->addParam('id', $_REQUEST['id']);
        $loader->request('stato.stato');
        $loader->request('tipo.tipo');
        $loader->request('operatore.nome');
        $loader->addOrder('inizio', 'DESC');
        $loader->requestAll();
        $preso = $loader->load();
	$oggi = strtotime(date('Y-m-d'));
	$dtA = strtotime(substr($preso->get('inizio'),0,10));
	$annullabile = false;
	if (($dtA > $oggi) && $preso->get('stato.id') < 3) $annullabile = true;

}
  

?>
    <div class='titolo'><h3>Riepologo appuntamento <div style='float: right;'><a class='highlight' href= '<?=URL_APP_APPUNTAMENTI?>/appuntamenti_elenco.php'>Torna all'elenco</a></div></h3></div>
	<ul>
	  <li><b>Sportello/operatore:</b> <?=$preso->get('operatore.nome')?></li>
	  <li><b>Tipologia:</b> <?=$preso->get('tipo.tipo')?></li>
	  <li><b>Giorno:</b> <?=dateToUser($preso->get('inizio'))?></li>
  	  <li><b>Orario:</b> dalle <?=timeToUser($preso->get('inizio'))?> alle <?=timeToUser($preso->get('fine'))?></li>
<!--	  <li><b>Particelle Edificiali:</b> <?=$preso->get('ped')?></li>
	  <li><b>Comune Catastale:</b> <?=$preso->get('comuneCatastale')?></li>-->
	  <li><b>Note:</b> <?=$preso->get('note')?></li>
	  <li><b>Stato:</b> <?=$preso->get('stato.stato')?></li>
	</ul>
<?
if ($annullabile){
?>
	<p>Puoi annullare l'appuntamento cliccando sul tasto Annulla appuntamento.</br>
        Nel campo note qui sotto, puoi inserire eventuali comunicazioni all'operatore.</p>

     <?=$errori?>

	<form method='GET' class='rowform' id='form_appuntamento_riepilogo'>
                 <div class='row'>
                        <label for='note_annullamento'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Note</label>
                        <textarea id='note_annullamento' name='note_annullamento' value='<?=$_GET['note_appuntamento']?>'></textarea>
                </div>

                <input type='hidden' name='id' id='id' value='<?=$_REQUEST['id']?>'>
                <div class='row buttons'>
                        <input type='submit' name='invio' value="Annulla appuntamento">
                </div>
        </form>

<?
}
?>

</div>
</div>
<?
}
}
include_once('./appuntamenti_bottom.php');
?>

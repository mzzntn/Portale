<?
include_once('./init.php');
include_once('./amministrazione/email.php');
include_once('./appuntamenti_top.php');

$stato_richiesta_default = $C['appuntamenti']['stato_richieste_default'];
//$IMP->debugLevel = 3;
// print_r($_GET);
if ($_SESSION['praticaAppuntamento'] && defined('URL_APP_CARICAMENTO_PRATICHE')){
    $loader = & $IMP->getLoader('caricamento_pratiche::pratica');
    $loader->addParam('id',  $_SESSION['praticaAppuntamento']);
    $loader->request('tipologia.descrizione');
    $istanza = $loader->load();
    $dataIstanza = dateToUser($istanza->get('dataInizio'));
    if(isset($C['style']) && $C['style']=="2016") { 
       $pratica = "<div class='form-group'>
            <label class='sr-only'>Prativa</label>
            <label class='col-lg-2 col-md-2 col-sm-2 control-label-left'>Pratica</label>
            <div class='col-lg-4 col-md-4 col-sm-4'>
                <p class='form-control-static'>{$istanza->get('tipologia.descrizione')} nr. {$_SESSION['praticaAppuntamento']} del {$dataIstanza}</p>
            </div></div>
           ";      
    } else {
	$pratica = "<li><strong>Pratica:</strong> ".$istanza->get('tipologia.descrizione')." nr. ".$_SESSION['praticaAppuntamento']." del ".$dataIstanza."</li>";
    }
}

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
}
else {
?>
<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div> 
    <div class="boxSpacer">&nbsp;</div>
    <div class="boxContent">

<?
}

if(isset($_GET['annulla']))
{

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Conferma prenotazione appuntamento</h3>
      </div>
      <div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	  <div class='alert alert-warning'>
	    <p>La tua richiesta di appuntamento &egrave; stata annullata</p>
	  </div>
	</div>
	
      </div>

      <div class="bottoni_pagina">
	<div class="row">
	  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <?=tornaAlPortale("button")?>
	    <a href='appuntamenti.php' class= 'btn btn-default btn-bigger' >Torna agli appuntamenti</a>
	  </div>
	</div>
      </div>
      
    </div>
  </div>
</div>	  
<?} else {?>
<div class='sala'>
    <div class='titolo'><h3>Conferma prenotazione appuntamento</h3></div>
        <p>La tua richiesta di appuntamento &egrave; stata annullata</p>
        <br><br>
        <p ><a href='<?=URL_APP_PORTAL?>' class='highlight' >Torna al portale</a> <a href='appuntamenti.php' class= 'highlight' >Torna agli appuntamenti</a></p>
     </div>
</div>

<?
}
}
if(isset($_GET['invio']))
{
#$IMP->debugLevel = 3;
#print_r($_GET);
    $loader = & $IMP->getLoader('appuntamenti::prenotazione');
    $loader->addParam('operatore', $_GET['operatore_appuntamento']);
    $loader->addParam('tipo', $_GET['tipo_appuntamento']);
    $qp = new QueryParams();
    $qp->addRange('inizio', $_GET['inizio_appuntamento'], $_GET['fine_appuntamento']);
    $qp->addRange('fine', $_GET['inizio_appuntamento'], $_GET['fine_appuntamento']);
    $loader->setParams($qp);
    $loader->addParam('stato', 3, "<>");
    $loader->addParam('operatore', $_GET['operatore_appuntamento']);
    $loader->requestAll();
    $prenotati_load = $loader->load();

#     echo "<pre>".print_r($prenotati_load, true)."</pre>";

    $sforaChiusura = false;

    if(!preg_match("/^\d{4}\-\d{2}\-\d{2}T\d{2}:\d{2}:\d{2}$/",$_GET['fine_appuntamento'])) {
      // errore, fine appuntamento non ha il formato corretto
      if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
        ?>
        <div id="portal_content" class="appuntamenti">
          <div id="navigatore_portale" class=""> </div>
          <div id="appuntamenti_layout">
            <div id="appuntamenti_index">
              <div class="titolo_pagina row">
                <h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Conferma prenotazione appuntamento</h3>
              </div>
              <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
                  <div class='alert alert-error'>
                    <p>Si &egrave; verificato un errore durante la prenotazione. Torna agli appuntamenti per effettuare una nuova prenotazione.</p>
                  </div>
                </div>
              </div>


              <div class="bottoni_pagina">
                <div class="row">
                  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <a href='appuntamenti.php' class= 'btn btn-default btn-bigger' >Torna agli appuntamenti</a>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
        <?
      } else {
        ?>
        <div class='sala'>
          <div class='titolo'><h3>Errore</h3></div>
              <p>Si &egrave; verificato un errore durante la prenotazione. Torna agli appuntamenti per effettuare una nuova prenotazione.</p>
              <br><br>
              <p><a href='<?=URL_APP_APPUNTAMENTI?>/appuntamenti.php' class='highlight' >Torna agli appuntamenti</a></p>
          </div>
        </div>
        <?
      }
    } else {
    $d = substr($_GET['fine_appuntamento'], 0, 10);
    $a = explode('-', $d);
    $giornoAppuntamento = date(w,mktime(0,0,0, $a[1], $a[2], $a[0]));
    $loader = & $IMP->getLoader('appuntamenti::orario');
    $loader->addParam('operatore', $_GET['operatore_appuntamento']);
    $loader->addParam('giorno.id', $giornoAppuntamento);
    $loader->requestAll();
    $orario_load = $loader->load();

    $timestampGiorno = substr($_GET['giorno_appuntamento'], 0, strlen($_GET['giorno_appuntamento'])-3);
    while($orario_load->moveNext()) {
      $chiusura = explode(":",$orario_load->get('orario_chiusura'));
      $orarioAppuntamento = explode(":",$_GET['orario_appuntamento']);
      $fineAppuntamentoDate = new DateTime($_GET['fine_appuntamento']);
      $orarioChiusuraDate = new DateTime();
      $orarioChiusuraDate->setTimestamp($timestampGiorno);
      $orarioChiusuraDate->setTime($chiusura[0], $chiusura[1]);
      //if($fineAppuntamentoDate>$orarioChiusuraDate) { $sforaChiusura = true; }
    }
    if($prenotati_load->moveNext()) {
      if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
        ?>
        <div id="portal_content" class="appuntamenti">
          <div id="navigatore_portale" class=""> </div>
          <div id="appuntamenti_layout">
            <div id="appuntamenti_index">
              <div class="titolo_pagina row">
                <h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Conferma prenotazione appuntamento</h3>
              </div>
              <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
                  <div class='alert alert-error'>
                    <p>La tipologia, sportello e orario scelti non sono pi&ugrave; disponibili. Torna agli appuntamenti per effettuare una nuova prenotazione.</p>
                  </div>
                </div>
              </div>

              <div class="bottoni_pagina">
                <div class="row">
                  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <a href='appuntamenti.php' class= 'btn btn-default btn-bigger' >Torna agli appuntamenti</a>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
        <?
      } else {
        ?>
        <div class='sala'>
          <div class='titolo'><h3>Errore</h3></div>
              <p>Non &egrave; possibile effettuare la prenotazione a causa di un errata impostazione del browser. Torna agli appuntamenti e aggiorna la pagina premendo contemporaneamente i tasti CTRL (Control - Mela in ambiente Apple) ed il tasto funzione F5. Se il problema persiste pulisci la cache del browser mediante le apposite funzione e riprova!</p>
              <br><br>
              <p><a href='<?=URL_APP_APPUNTAMENTI?>/appuntamenti.php' class='highlight' >Torna agli appuntamenti</a></p>
          </div>
        </div>
        <?
      }
    }

    else if($sforaChiusura) {
      if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
        ?>
        <div id="portal_content" class="appuntamenti">
          <div id="navigatore_portale" class=""> </div>
          <div id="appuntamenti_layout">
            <div id="appuntamenti_index">
              <div class="titolo_pagina row">
                <h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Conferma prenotazione appuntamento</h3>
              </div>
              <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
                  <div class='alert alert-error'>
                    <p>Non &egrave; possibile effettuare la prenotazione a causa di un errata impostazione del browser. Torna agli appuntamenti e aggiorna la pagina premendo contemporaneamente i tasti CTRL (Control - Mela in ambiente Apple) ed il tasto funzione F5. Se il problema persiste pulisci la cache del browser mediante le apposite funzione e riprova!</p>
                  </div>
                </div>
              </div>

              <div class="bottoni_pagina">
                <div class="row">
                  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <a href='appuntamenti.php' class= 'btn btn-default btn-bigger' >Torna agli appuntamenti</a>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
        <?
      } else {
        ?>
        <div class='sala'>
          <div class='titolo'><h3>Errore</h3></div>
              <p>Non &egrave; possibile effettuare la prenotazione a causa di un errata impostazione del browser. Torna agli appuntamenti e aggiorna la pagina premendo contemporaneamente i tasti CTRL (Control - Mela in ambiente Apple) ed il tasto funzione F5. Se il problema persiste pulisci la cache del browser mediante le apposite funzione e riprova!</p>
              <br><br>
              <p><a href='<?=URL_APP_APPUNTAMENTI?>/appuntamenti.php' class='highlight' >Torna agli appuntamenti</a></p>
          </div>
        </div>
        <?
      }
    }
    else {
    $storer = & $IMP->getStorer('appuntamenti::prenotazione');
    $storer->set('persona', $nomePersona);
    $storer->set('utente', $master);
    $storer->set('operatore', $_GET['operatore_appuntamento']);
    $storer->set('tipo',$_GET['tipo_appuntamento']);
    $storer->set('email', $email);
    $storer->set('telefono', $telefono);
    $storer->set('inizio', $_GET['inizio_appuntamento']);
    $storer->set('fine', $_GET['fine_appuntamento']);
    $storer->set('note', $_GET['note_appuntamento']);
    $storer->set('stato', $stato_richiesta_default);
    if ($_SESSION['praticaAppuntamento']) $storer->set('nrIstanza', $_SESSION['praticaAppuntamento']);
    $idPrenotazione = $storer->store();
    if ($stato_richiesta_default == 2){
        email_conferma($idPrenotazione);
    }

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Conferma prenotazione appuntamento</h3>
      </div>
      <div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	  <div class='alert alert-success'>
	    <p>La tua richiesta di appuntamento &egrave; stata registrata con progressivo <?=$idPrenotazione?></p>
	    <p>Riceverai a breve una mail di conferma.</p>
	  </div>
	</div>
      </div>

      <div class="bottoni_pagina">
	<div class="row">
	  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <?=tornaAlPortale("button")?>
	  </div>
	</div>
      </div>
      
    </div>
  </div>
</div>	  
<?} else {?>
  <div class='sala'>
    <div class='titolo'><h3>Conferma prenotazione appuntamento</h3></div>
        <p>La tua richiesta di appuntamento &egrave; stata registrata con progressivo <?=$idPrenotazione?></p>
        <p>Riceverai a breve una mail di conferma.</p>
        <br><br>
        <p><a href='<?=URL_APP_PORTAL?>' class='highlight' >Torna al portale</a></p>
     </div>
</div>
<?
       }
     }
   }
}

elseif(isset($_GET['richiedi']))
{

$loader = & $IMP->getLoader('appuntamenti::tipo');
$loader->addParam('id', $_GET['tipo_appuntamento']);
$loader->requestAll();
$tipo = $loader->load();
$tipoNome = $tipo->get('tipo');
$durata = $tipo->get('durata');
$istruzioni = $tipo->get('istruzioni');
$tipologia = $tipoNome." (".$durata." minuti)";

$loader = & $IMP->getLoader('appuntamenti::operatore');
$loader->addParam('id', $_GET['operatore_appuntamento']);
$loader->requestAll();
$operatore = $loader->load();
$operatoreNome = $operatore->get('nome');


if (strlen($_GET['orario_appuntamento']) == 4){
	$oraI = substr($_GET['orario_appuntamento'],0,1);
        $minutiI = substr($_GET['orario_appuntamento'],2,2);
}
else {
        $oraI = substr($_GET['orario_appuntamento'],0,2);
        $minutiI = substr($_GET['orario_appuntamento'],3,2);
}

$minutiF = $minutiI + $durata;
$oraF = $oraI;
while($minutiF >= 60){
	$minutiF = $minutiF -60;
	$oraF += 1;
}
if (strlen($oraF) == 1) $oraF = '0'.$oraF;
if (strlen($oraI) == 1) $oraI = '0'.$oraI;
if (strlen($minutiF) == 1) $minutiF = '0'.$minutiF;
$inizio = date("Y-m-d",substr($_GET['giorno_appuntamento'],0,10))."T".$oraI.":".$minutiI.":00";
$fine = date("Y-m-d",substr($_GET['giorno_appuntamento'],0,10))."T".$oraF.":".$minutiF.":00";

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Conferma prenotazione appuntamento</h3>
      </div>
      <form method='GET' class='form form-horizontal' id='form_appuntamento_riepilogo'> 
      <div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	  <div class="form-group">
            <label class="sr-only">Sportello/operatore</label>
            <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Sportello/operatore</label>
            <div class="col-lg-4 col-md-4 col-sm-4">
                <p class="form-control-static"><?=$operatoreNome?></p>
            </div>
            <label class="sr-only">Tipologia</label>
            <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Tipologia</label>
            <div class="col-lg-4 col-md-4 col-sm-4">
                <p class="form-control-static"><?=$tipologia?></p>
            </div>
	  </div>
	  <div class="form-group">
            <label class="sr-only">Giorno</label>
            <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Giorno</label>
            <div class="col-lg-4 col-md-4 col-sm-4">
                <p class="form-control-static"><?=date("d/m/Y",substr($_GET['giorno_appuntamento'],0,10))?></p>
            </div>
            <label class="sr-only">Orario</label>
            <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Orario</label>
            <div class="col-lg-4 col-md-4 col-sm-4">
                <p class="form-control-static"><?=$_GET['orario_appuntamento']?></p>
            </div>
	  </div>
	  <?=$pratica?>
	  <input type='hidden' name='tipo_appuntamento' id='tipo_appuntamento' value='<?=$_GET['tipo_appuntamento']?>'>
	  <input type='hidden' name='operatore_appuntamento' id='operatore_appuntamento' value='<?=$_GET['operatore_appuntamento']?>'>
	  <input type='hidden' name='inizio_appuntamento' id='inizio_appuntamento' value='<?=$inizio?>'>
	  <input type='hidden' name='fine_appuntamento' id='fine_appuntamento' value='<?=$fine?>'>
	  
	  <div class="form-group">
	    <label for="note_appuntamento" class="sr-only">Note</label>
	    <label class="col-lg-2 col-md-2 col-sm-2 control-label-left">Note</label>
	    <div class="col-lg-10 col-md-10 col-sm-10">
		<textarea id='note_appuntamento' name='note_appuntamento' class='form-control'></textarea>
	    </div>
	  </div>
	  <?
	  if ($istruzioni){
	  ?>
	  <div class='alert alert-warning'>
	    <p><strong>Istruzioni: <?=$istruzioni?></strong></p>
	  </div>
 	  <?
	  }
	  ?>  

	</div>
      </div>

      <div class="bottoni_pagina">
	<div class="row">
	  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <div class='back'>
	      <input type='submit' name='annulla' class="btn" value='Annnulla richiesta'>
	    </div>
	    <input type='submit' name='invio' class="btn btn-success btn-bigger" value='Conferma richiesta'>	    
	  </div>
	</div>
      </div>
      </form>
      
    </div>
  </div>
</div>	  
<?} else {?>
    <div class='titolo'><h3>Conferma prenotazione appuntamento</h3></div>
	<ul>
	  <li><b>Sportello/operatore:</b> <?=$operatoreNome?></li>
	  <li><b>Tipologia:</b> <?=$tipologia?></li>
	  <li><b>Giorno:</b> <?=date("d/m/Y",substr($_GET['giorno_appuntamento'],0,10))?></li>
	  <li><b>Orario:</b> <?=$_GET['orario_appuntamento']?></li>
	  <?=$pratica?>
	</ul>
	<form method='GET' class='rowform' id='form_appuntamento_riepilogo'>
		<div class='row'>
			<label for='note_appuntamento'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Note</label>
			<textarea id='note_appuntamento' name='note_appuntamento'></textarea>
		</div>
		<input type='hidden' name='tipo_appuntamento' id='tipo_appuntamento' value='<?=$_GET['tipo_appuntamento']?>'>
		<input type='hidden' name='operatore_appuntamento' id='operatore_appuntamento' value='<?=$_GET['operatore_appuntamento']?>'>
		<input type='hidden' name='inizio_appuntamento' id='inizio_appuntamento' value='<?=$inizio?>'>
		<input type='hidden' name='fine_appuntamento' id='fine_appuntamento' value='<?=$fine?>'>
		<div class='row buttons'>
  			<input type='submit' name='invio' value='Conferma richiesta'>
			<input type='submit' name='annulla' value='Annnulla richiesta'>
		</div>
	</form>
</div>
<?
}
}
 
?>
</div>
<?
include_once('./appuntamenti_bottom.php');
?>

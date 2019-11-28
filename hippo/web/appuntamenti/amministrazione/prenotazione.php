<?
include_once('../../init.php');
include_once('email.php');
include_once('appuntamenti_top.php');

$loader = & $IMP->getLoader('appuntamenti::prenotazione');
$id = $_REQUEST['id'];
$loader->addParam('id', $id);
$loader->requestAll();
$loader->request('operatore.nome');
$p = $loader->load();
#print_r($p);
$p = $p->getRow();
$inizio = & dt_DateTime();
$inizio->fromISO($p->inizio);
$fine = & dt_DateTime();
$fine->fromISO($p->fine);
$oraInizio = $inizio->h.':'.$inizio->m;
$oraFine = $fine->h.':'.$fine->m;
$giorno = $inizio;
$giorno->clearTime();
$allegato = $p->allegato;
$nr_istanza = $p->get('nrIstanza');
if ($nr_istanza){
    $loader = & $IMP->getLoader('caricamento_pratiche::pratica');
    $loader->addParam('id',  $nr_istanza);
    $loader->request('tipologia.descrizione');
    $istanza = $loader->load();
    $pratica = $istanza->get('tipologia.descrizione')." nr. ".$istanza->get('id')." del ".dateToUser($istanza->get('dataInizio'))." <a href='".URL_APP_CARICAMENTO_PRATICHE."/amministrazione/istanza.php?id=".$istanza->get('id')."' traget='_new' >dettagli ...</a>";

}

if ($_REQUEST['conferma']){
	$storer = & $IMP->getStorer('appuntamenti::prenotazione');
	$storer->addParam('id', $id);
	$storer->set('stato', 2);
	$storer->store();
	$p->stato = 2;
	email_conferma($id);
}
elseif ($_REQUEST['rifiuta'] || $_REQUEST['annulla']){
        if ($_REQUEST['rifiuta']) $tipo = 'R';
        else $tipo = 'A';
  	email_rifiuto($id, $_REQUEST['note_rifiuto'], $tipo);
	$storer = & $IMP->getStorer('appuntamenti::prenotazione');
	$storer->addParam('id', $id);
	$storer->set('stato', 3);
	$storer->store();
	$p->stato = 3;
}
elseif ($_REQUEST['salva_note']){
	$storer = & $IMP->getStorer('appuntamenti::prenotazione');
	$storer->addParam('id', $id);
	$storer->set('noteUfficio', $_REQUEST['note_ufficio']);
	$storer->store();
	$p->noteUfficio = $_REQUEST['note_ufficio'];
}
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>

<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-12 col-md-12 col-sm-12 col-xs-12">Prenotazione nr. <?=$p->get('id')?> richiesta da: <?=$p->get('persona')?></h3>
      </div>
      <div class="row ricerca">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">

          <div id="info" class="form-horizontal clearfix">
          
            <div class="col-md-6 col-sm-12">        
              <div class="form-group">
                <label class="col-sm-2 control-label">Per operatore:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><?=$p->get('operatore.nome')?></p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label">Il giorno:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><?=dateToUser($giorno->toISO())?></p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label">Orario:</label>
                <div class="col-sm-10">
                  <p class="form-control-static">dalle <?=$oraInizio?> alle <?=$oraFine?></p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label">Pratica:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><?=$pratica?></p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label">Note:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><?=$p->note?></p>
                </div>
              </div>
              <?
              if($allegato) {
                $personaggio = $p->persona;
                $indice = strpos($allegato, $personaggio);
                $allegato = substr($allegato, $indice);
                ?>
                <div class="form-group">
                  <label class="col-sm-2 control-label">Allegato:</label>
                  <div class="col-sm-10">
                    <p class="form-control-static"><a href='<?=URL_APP_PORTAL?>/getDoc.php?f=files/<?=$allegato?>' class='btn btn-default'>[Scarica]</a>
                    <a href='<?=URL_WEBDATA?>/files/<?=$allegato?>' class='btn btn-default' target='_blank'>[Apri in una nuova finestra]</a></p>
                  </div>
                </div>
                <?
              }
              ?>
            </div>
            
            <div class="col-md-6 col-sm-12">
              <div class="form-group">
                <label class="col-sm-2 control-label">E-mail:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><a href='mailto: <?=$p->email?>'><?=$p->email?></a></p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label">Telefono:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><?=$p->telefono?></p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label">Cellulare:</label>
                <div class="col-sm-10">
                  <p class="form-control-static"><?=$p->cellulare?></p>
                </div>
              </div>
            </div>
            
          </div>
          
          <div id="azioni" class="row">
            <?
            if ($p->stato == 1){
            ?>
            <form action='<?=$_SERVER['PHP_SELF']?>' method='GET'>
              <input type='hidden' name='id' value='<?=$id?>'>
              <div class="col-md-6 col-sm-12">
                <div class="form-group">
                  <p class="alert alert-info">Per <strong>confermare</strong> la prenotazione ed inviare un'e-mail all'utente, clicca sul tasto "Conferma".</p>
                  <p class="alert alert-warning">Per <strong>rifiutare</strong> la prenotazione, inserisci se vuoi un testo da inviare all'utente e clicca su <input type='submit' class="btn btn-success btn-bigger" name='annulla' value='Rifiuta'></p>
                </div>
                <div class="form-group">
                  <label for="note_rifiuto">Testo annullamento:</label>
                  <textarea class="form-control" name='note_rifiuto' id='note_rifiuto' rows='10'></textarea>
                </div>
              </div>
            
            </form>
            <?
            }
            elseif($p->stato == 2){
              if (!$_SESSION['livello']){
            ?>

            <form action='<?=$_SERVER['PHP_SELF']?>' method='GET'>
                <input type='hidden' name='id' value='<?=$id?>'>
                <div class="col-md-6 col-sm-12">
                  <div class="form-group">
                    <label for="note_ufficio">Note per l'ufficio:</label>
                    <textarea class="form-control" name='note_ufficio' id='note_ufficio' rows='10'><?=$p->get('noteUfficio')?></textarea>
                  </div>
                  <div class="form-group">
                    <p class='alert alert-success'>La prenotazione &egrave; stata confermata. Per aggiungere delle note inserisci un testo da salvare e clicca su <input type='submit' class="btn btn-default btn-bigger" name='salva_note' value='Salva note'></p>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12">
                  <div class="form-group">
                    <label for="note_rifiuto">Testo annullamento:</label>
                    <textarea class="form-control" name='note_rifiuto' id='note_rifiuto' rows='10'></textarea>
                  </div>
                  <div class="form-group">
                    <p class="alert alert-warning">Per <strong>annullare</strong> la prenotazione, inserisci se vuoi un testo da inviare all'utente e clicca su <input type='submit' class="btn btn-success btn-bigger" name='annulla' value='Annulla'></p>
                  </div>
                </div>

            </form>
            <?
              }
            }
            else{
            ?>
            <div class="col-md-6 col-sm-12">
              <p class='alert alert-danger'>La prenotazione &egrave; stata rifiutata.</p>
            </div>
            <?  
            }
            ?>
          </div>
        </div>
	
      </div>
    </div>
  </div>

  <div class="bottoni_pagina">
    <div class="row">
      <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="back">
          <a href='elenco_richieste.php' class='btn' >Torna all'elenco</a>
        </div>
      </div>
    </div>
  </div>
  
</div>

<? 
include_once('../appuntamenti_bottom.php');
} else { 


    $pratica = "<p><strong>Pratica:</strong> $pratica</p>";
?>
<link rel='stylesheet' type='text/css' href='<?=URL_CSS?>/appuntamenti_admin.css'>
<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div>
    <div class="boxSpacer">&nbsp;</div>
    <div class="boxContent">
	<div id="info">
	<h3>Prenotazione nr. <?=$p->get('id')?> richiesta da: <?=$p->get('persona')?></h3>
	<p>Per operatore: <?=$p->get('operatore.nome')?>, il giorno <?=dateToUser($giorno->toISO())?>, dalle <?=$oraInizio?> alle <?=$oraFine?>.</p>
	<?=$pratica?>
	<p>Note: <?=$p->note?></p>
<?
	if($allegato) {
	    $personaggio = $p->persona;
	    $indice = strpos($allegato, $personaggio);
	    $allegato = substr($allegato, $indice);

?>
	<p>Allegato: 
	    <a href='getDoc.php?f=files/<?=$allegato?>'>[Scarica]</a>
	    <a href='<?=URL_WEBDATA?>/files/<?=$allegato?>' target='_blank'>[Apri in una nuova finestra]</a>
	</p>

<?
	}
?>
<br>
<h4>Riferimenti</h4>
E-mail: <a href='mailto: <?=$p->email?>'><?=$p->email?></a><br>
Telefono: <?=$p->telefono?><br>

</div>
<div id="azioni">
<?
if ($p->stato == 1){
?>
<form action='<?=$_SERVER['PHP_SELF']?>' method='GET'>
  <input type='hidden' name='id' value='<?=$id?>'>
Per <strong>confermare</strong> la prenotazione ed inviare un'e-mail all'utente, clicca qui: <input type='submit' name='conferma' value='Conferma'><br><br>
Per <strong>rifiutare</strong> la prenotazione, inserisci se vuoi un testo da inviare all'utente e clicca su "Rifiuta:"<br>
 <textarea name='note_rifiuto' cols='50' rows='10'></textarea> <br>
 <input type='submit' name='rifiuta' value='Rifiuta'>
</form>
<?
}
elseif($p->stato == 2){
?>
<p>La prenotazione &egrave; stata <strong>confermata</strong>.</p>
<form action='<?=$_SERVER['PHP_SELF']?>' method='GET'>
  <input type='hidden' name='id' value='<?=$id?>'>
Per <strong>annullare</strong> la prenotazione, inserisci se vuoi un testo da inviare all'utente e clicca su "Annulla:"<br>
 <textarea name='note_rifiuto' cols='50' rows='10'></textarea> <br>
 <input type='submit' name='annulla' value='Annulla'>
</form>

<?
}
else{
?>
La prenotazione &egrave; stata annullata.
<?  
}
?>
</div>
<div>
<a href='elenco_richieste.php' class='highlight' >Torna all'elenco</a>
</div>
</div>
</div>
<?
include_once('../../portal/admin/bottom.php');
}
?>

<?
include_once('./init.php');
include_once('./appuntamenti_top.php');

//$IMP->debugLevel= 3;
$loader = & $IMP->getLoader('appuntamenti::prenotazione');
$loader->addParam('utente', $master);
$loader->request('stato.stato');
$loader->request('tipo.tipo');
$loader->request('operatore.nome');
$loader->addOrder('inizio', 'DESC');
$loader->requestAll();
$presi = $loader->load();

//print_r($persi);
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
}


if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>

<div id="portal_content" class="appuntamenti">
  <div id="navigatore_portale" class=""> </div>
  <div id="appuntamenti_layout">
    <div id="appuntamenti_index">
      <div class="titolo_pagina row">
	<h3 class="verde pb10 col-lg-6 col-md-6 col-sm-12 col-xs-12">Nuovo appuntamento</h3>
	<div class='col-lg-6 col-md-6 col-sm-12 col-xs-12 mt20'>
	  <div class='pull-right'><a class='btn btn-default' href="<?=URL_APP_APPUNTAMENTI?>/appuntamenti.php">Nuovo appuntamento <i class="glyphicon glyphicon-plus"></i></a>
	  </div>
	</div>
      </div>
      <div class="row ricerca">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 mt20">
	  <div id="appuntamenti_table" class="table">
	    <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='elenco_appuntamenti_table'>
	      <thead>
		    <tr>
		    <th>Gestisci</th>
		    <th>Operatore</th>
		    <th>Tipo</th>
		    <th>Data</th>
		    <th>Inizio</th>
		    <th>Fine</th>
		    <th>Stato</th>
		    <th>Note</th>
		    </tr>
	      </thead>
	      <tbody>

	    <?
	    while ($presi->moveNext()){
		    $id = $presi->get('id');
	    ?>
		    <tr>
		    <td><a href='<?=URL_APP_APPUNTAMENTI?>/appuntamento.php?id=<?=$id?>'>Vai</a></td>
		    <td><?=$presi->get('operatore.nome')?></td>
		    <td><?=$presi->get('tipo.tipo')?></td>
		    <td><?=dateToUser($presi->get('inizio'))?> </td>
		    <td><?=timeToUser($presi->get('inizio'))?></td>
		    <td><?=timeToUser($presi->get('fine'))?></td>
		    <td><?=$presi->get('stato.stato')?></td>
		    <td><?=$presi->get('note')?></td>
		    </tr>
	    <?
	    }
	    ?>
	      </tbody>
	    </table>
	  </div>
	</div>
      </div>

      <div class="bottoni_pagina">
	<div class="row">
	  <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <div class="back">
	      <?=tornaAlPortale("button")?>
	    </div>
	  </div>
	</div>
      </div>
      
    </div>
  </div>
</div>

<? } else { ?>

<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div> 
    <div class="boxSpacer">&nbsp;</div>
    <div class="boxContent">
  <div class='sala'>
    <div class='titolo'><h3>Elenco Appuntamenti<div style='float: right;'><a class='highlight' href= '<?=URL_APP_APPUNTAMENTI?>/appuntamenti.php'>Nuovo appuntamento</a></div></h3></div>
<div align='center'>

<table class='rowList' >
	<tr>
	<th>Gestisci</th>
	<th>Operatore</th>
	<th>Tipo</th>
	<th>Data</th>
	<th>Inizio</th>
	<th>Fine</th>
	<th>Stato</th>
	<th>Note</th>
	</tr>

<?
while ($presi->moveNext()){
	$id = $presi->get('id');
?>
	<tr>
	<td><a href='<?=URL_APP_APPUNTAMENTI?>/appuntamento.php?id=<?=$id?>'>Vai</a></td>
	<td><?=$presi->get('operatore.nome')?></td>
	<td><?=$presi->get('tipo.tipo')?></td>
	<td><?=dateToUser($presi->get('inizio'))?> </td>
	<td><?=timeToUser($presi->get('inizio'))?></td>
	<td><?=timeToUser($presi->get('fine'))?></td>
	<td><?=$presi->get('stato.stato')?></td>
	<td><?=timeToUser($presi->get('note'))?></td>
	</tr>
<?
}
?>
</table>
</div>
</div>
</div>
</div>
<?
}
include_once('./appuntamenti_bottom.php');
?>

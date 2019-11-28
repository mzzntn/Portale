<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
if(isset($_REQUEST["test"])) {
  echo "<pre>".print_r($W->data)."</pre>";
}
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
  ?>
  <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
    <thead>
      <tr>
        <th></th>
        <th>Tipo</th>
        <th>Nr Istanza</th>
        <th>Data</th>
	<th>Descrizione</th>
        <th>Stato</th>
	<th>Presentatore</th>
	<th>Nr Definitivo</th>
	<th>Nr Protocollo</th>
      </tr>
    </thead>
    <tbody>
    <?
while ($W->data->moveNext()){
  $descrizione = $W->data->get('descrizione');
  $tipo = substr($W->data->get('tipologia'),1,200);
  $nrPratica = '';
  if ($W->data->get('PR_PRAT_NUM1')) $nrPratica = $W->data->get('PR_PRAT_NUM1')."/".$W->data->get('PR_PRAT_NUM2')."/".$W->data->get('PR_PRAT_NUM3');
  $stato = 'In compilazione';
  if ($W->data->get('okImportazione') == '2') $stato = 'Attesa firma';
  elseif ($W->data->get('okImportazione') == '1'){
	$stato =  'Inviata ma non scaricata';
	if ( $W->data->get('scaricata') == 1) $stato = 'Scaricata ma non acquisita';
	if ($nrPratica || $W->data->get('praticaImportata')) $stato = 'Acquisita';
	elseif ($W->data->get('noAllegati')) $stato = 'Attesa downalod senza allegati';
  }

//  if ( strlen($descrizione) > 255) $descrizione = substr($descrizione, 0, 255).'...';
?>
      <tr class="paginated_element">
        <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a>&nbsp;&nbsp;&nbsp;</td>
        <td><?=$tipo?></td>
        <td align='center'><?=$W->data->get('id')?></td>
        <td><?=dateToUser($W->data->get('dataInizio'))?></td>
        <td><?=$descrizione?></td>
        <td><?=$stato?></td>
        <td><?=$W->data->get('utenteDescrizione')?></td>
        <td><?=$nrPratica?></td>
        <td><?=$W->data->get('nrProtocollo')?></td>
      </tr>
<?
}
?>
    </tbody>
  </table>
  <!--<input type='hidden' id='items_per_page' value="10" />  
  <input type='hidden' id='show_per_page' value="10" />  
  <input type='hidden' id='current_page' value="1" />  
  <input type='hidden' id='max_page_in_navbar' value="7"/>
  <div class="page_navigation pagination" id='paginator_div'><ul></ul></div>-->
  
  <?
  // vecchio paginatore
  if ($W->config['maxElements'] && $W->resultRows) $showPageNavigator = true;

  if($showPageNavigator)
  {
      $pageNavigator = new PageNavigator($W->resultRows);
      $pageNavigator->setItemsPerPage($W->config['maxRows']);
      $pageNavigator->setTableName($W->name);
      $pageNavigator->displayPageJump(true);
      $pageNavigator->setDisplayPages(8);
      /*$pageNavigator->setDisplayStyle(PageNavigator::DISPLAY_FULL);*/ // mostra precedente e successivo al posto delle frecce
  }

  if($showPageNavigator)
  {   
      $pageNavigator->display($_GET[$W->name]["page"]);
  }
  // fine vecchio paginatore
  
  $D->printScripts();
}
else {
?>
<div class='contenitore risultati'>
  <table class='rowList center' border='0' >

    <tbody id='<?=$D->name?>'>
      <tr>
        <th></th>
        <th>Tipo</th>
        <th>Nr Istanza</th>
        <th>Data</th>
	<th>Descrizione</th>
        <th>Stato</th>
	<th>Presentatore</th>
	<th>Nr Definitivo</th>
	<th>Nr Protocollo</th>
      </tr>
<?
while ($W->data->moveNext()){
  $descrizione = $W->data->get('descrizione');
  $tipo = substr($W->data->get('tipologia'),1,200);
  $nrPratica = '';
  if ($W->data->get('PR_PRAT_NUM1')) $nrPratica = $W->data->get('PR_PRAT_NUM1')."/".$W->data->get('PR_PRAT_NUM2')."/".$W->data->get('PR_PRAT_NUM3');
  $stato = 'In compilazione';
  if ($W->data->get('okImportazione') == '2') $stato = 'Attesa firma';
  elseif ($W->data->get('okImportazione') == '1'){
	$stato =  'Inviata ma non scaricata';
	if ( $W->data->get('scaricata') == 1) $stato = 'Scaricata ma non acquisita';
	if ($nrPratica || $W->data->get('praticaImportata')) $stato = 'Acquisita';
	elseif ($W->data->get('noAllegati')) $stato = 'Attesa downalod senza allegati';
  }

//  if ( strlen($descrizione) > 255) $descrizione = substr($descrizione, 0, 255).'...';
?>
      <tr>
        <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a>&nbsp;&nbsp;&nbsp;</td>
        <td><?=$tipo?></td>
        <td align='center'><?=$W->data->get('id')?></td>
        <td><?=dateToUser($W->data->get('dataInizio'))?></td>
        <td><?=$descrizione?></td>
        <td><?=$stato?></td>
        <td><?=$W->data->get('utenteDescrizione')?></td>
        <td><?=$nrPratica?></td>
        <td><?=$W->data->get('nrProtocollo')?></td>
      </tr>
<?
}
?>
    </tbody>
  </table>
<div class="pageNav">
    <p>
<?
if ($W->config['maxElements'] && $W->resultRows) $showPageNavigator = true;

if($showPageNavigator)
{
    $pageNavigator = new PageNavigator($W->resultRows);
    $pageNavigator->setItemsPerPage($W->config['maxRows']);
    $pageNavigator->setTableName($W->name);
    $pageNavigator->setDisplayStyle(PageNavigator::DISPLAY_FULL); // mostra precedente e successivo al posto delle frecce
    //pageNavigator->displayPageJump(true); // mostra il menu scelta pagina
}

if($showPageNavigator)
{
    $pageNavigator->display($_GET[$W->name]["page"]);
}
?>
  </div>
</div>
<? } ?>

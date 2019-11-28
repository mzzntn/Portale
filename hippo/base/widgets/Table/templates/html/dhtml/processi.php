<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
  ?>
  <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
    <thead>
      <tr>
	<th>Dettagli</th>
	<th>Procedimento</th>
	<th>Tipologia</th>
	<th>Oggetto</th>
	<th>Data Richiesta</th>
	<th>Richiedente</th>
	<th>Esito</th>
	<th>Riesame / Ricorso</th>
	<th>Esito Riesame / Ricorso</th>
      </tr>
    </thead>
    <tbody>
<?
    while ($W->data->moveNext()){
    #  if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 100).'...';
    if ($W->data->get('dtPrRiesame')) $dtRiesame = " (".dateToUser($W->data->get('dtPrRiesame')).")";
    if ($W->data->get('dtPrEsitoRiesame')) $dtEsitoRiesame = " (".dateToUser($W->data->get('dtPrEsitoRiesame')).")";
    $riesame = $W->data->get('riesame').$dtRiesame;
    $esitoRiesame = $W->data->get('riesameEsito').$dtEsitoRiesame; 
    ?>
	<tr class="paginated_element">
            <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td> 
	    <td align='center'><?=str_replace('-', '', $W->data->get('procedimento.nome'))?></td>
            <td align='center'><?=str_replace('-', '', $W->data->get('tipologia'))?></td>
            <td align='center'><?=$W->data->get('oggetto')?></td>
            <td align='center'><?=dateToUser($W->data->get('dtPrDomanda'))?></td>
	    <td align='center'><?=$W->data->get('richiedente')?></td>
            <td align='center'><?=str_replace('-', '', $W->data->get('esito'))?></td>
	    <td align='center'><?=$riesame?></td>
	    <td align='center'><?=$esitoRiesame?></td>
	</tr>
    <?
    }
    ?>
    </tbody>
  </table>
  <!--<input type='hidden' id='items_per_page' value="15" />  
  <input type='hidden' id='show_per_page' value="15" />  
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
?>
    </p>
</div>

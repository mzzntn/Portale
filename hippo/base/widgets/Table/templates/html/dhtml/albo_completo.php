<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
  ?>
  <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
    <thead>
      <tr>
	<th>Numero</th>
	<th>Ente</th>
	<th>Oggetto</th>
	<th>Atto</th>
	<th>Data affissione</th>
	<th>Fine Pubblicazione</th>
      </tr>
    </thead>
    <tbody>
<?
    while ($W->data->moveNext()){
    #  if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 100).'...';
      $idAnnuale=substr($W->data->get('ID_ANNUALE'),0,4);
    ?>
	<tr class="paginated_element">
	  <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('NPROG')?>'><?=substr($W->data->get('ID_ANNUALE'),0,4)?>/0<?=substr($W->data->get('ID_ANNUALE'),4,6)?></a></td>
	  <td><?=$W->data->get('ENTE')?></td>
	  <td><?=$W->data->get('OGGETTO')?></td>
	  <td><?=$W->data->get('TIPOATTO.DESC_ATTO')?></td>
	  <td><?=dateToUser($W->data->get('DATA_AFFIS'))?></td>
	  <td><?=dateToUser($W->data->get('SCADENZA'))?></td>
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
<table class='rowList center' cellspacing='0'>
    <tbody id='<?=$D->name?>'>
        <tr>
            <th>Numero</th>
            <th>Ente</th>
            <th>Oggetto</th>
            <th>Atto</th>
	    <th>Data affissione</th>
	    <th>Fine Pubblicazione</th>
        </tr>
    <?
    while ($W->data->moveNext()){
#      if ( strlen($descrizione) > 200) $descrizione = substr($descrizione, 0, 200).'...';
      $idAnnuale=substr($W->data->get('ID_ANNUALE'),0,4);
    ?>
        <tr>
        <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('NPROG')?>'><?=substr($W->data->get('ID_ANNUALE'),0,4)?>/<?=substr($W->data->get('ID_ANNUALE'),4,6)?></a></td>
            <td><?=$W->data->get('ENTE')?></td>
            <td><?=$W->data->get('OGGETTO')?></td>
	    <td><?=$W->data->get('TIPOATTO.DESC_ATTO')?></td>
	    <td><?=dateToUser($W->data->get('DATA_AFFIS'))?></td>
            <td><?=dateToUser($W->data->get('SCADENZA'))?></td>
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
    //$pageNavigator->displayPageJump(true); // mostra il menu scelta pagina
}

if($showPageNavigator)
{   
    $pageNavigator->display($_GET[$W->name]["page"]);
}

?>
  </p>  
</div>             
<?
}
?>

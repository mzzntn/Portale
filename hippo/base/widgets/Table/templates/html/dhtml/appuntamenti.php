<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
  ?>
  <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover table-medium table-wrap" id='<?=$D->name?>'>
    <thead>
      <tr>
	<th>Dettagli</th>
	<th>Persona</th>
        <th>Note</th>
	<th>Note ufficio</th>
	<th>Operatore</th>
	<th>Inizio</th>
	<th>Fine</th>
	<th>Stato</th>
	<th>Tipo</th>
      </tr>
    </thead>
    <tbody>
    <?
    while ($W->data->moveNext()){
      $descrizione = $W->data->get('descrizione');
    ?>
      <tr class="paginated_element">
	<td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td>
	<td align='center'><?=$W->data->get('persona')?></td>
        <td align='center'><?=$W->data->get('note')?></td>
	<td align='center'><?=$W->data->get('noteUfficio')?></td>
	<td align='center'><?=$W->data->get('operatore')?></td>
	<td align='center'><?=$W->data->get('inizio')?></td>
	<td align='center'><?=$W->data->get('fine')?></td>
	<td align='center'><?=$W->data->get('stato')?></td>
	<td align='right'><?=$W->data->get('tipo')?></td>
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
                <th>Dettagli</th>
                <th>Persona</th>
                <th>Operatore</th>
                <th>Inizio</th>
                <th>Fine</th>
                <th>Stato</th>
        		<th>Tipo</th>
            </tr>
      <?
      while ($W->data->moveNext()){
        $descrizione = $W->data->get('descrizione');
//        if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 100).'...';
      ?>
            <tr>
                <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td>
                <td align='center'><?=$W->data->get('persona')?></td>
                <td align='center'><?=$W->data->get('operatore')?></td>
                <td align='center'><?=$W->data->get('inizio')?></td>
                <td align='center'><?=$W->data->get('fine')?></td>
                <td align='center'><?=$W->data->get('stato')?></td>
                <td align='right'><?=$W->data->get('tipo')?></td>
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
        
        if ($W->config['maxElements'] && $W->resultRows) $showPageNavigator = true;

        if($showPageNavigator)
{
    $pageNavigator = new PageNavigator($W->resultRows);
    $pageNavigator->setItemsPerPage($W->config['maxRows']);
    $pageNavigator->setTableName($W->name);
    $pageNavigator->setDisplayStyle(PageNavigator::DISPLAY_FULL); // mostra precedente e successivo al posto delle frecce
//    $pageNavigator->displayPageJump(true); // mostra il menu scelta pagina
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

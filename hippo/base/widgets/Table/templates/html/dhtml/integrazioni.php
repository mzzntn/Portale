<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
global $IMP;
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
  ?>
  <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
    <thead>
      <tr>
        <th></th>
        <th>Progressivo</th>
        <th>Pratica</th>
        <th>Data/Ora</th>
        <th>Oggetto</th>
        <th>Utente</th>
      </tr>
    </thead>
    <tbody>
    <?
while ($W->data->moveNext()){
  $loader = & $IMP->getLoader('portal_spider::utente');
  $loader->addParam('id', $W->data->get('utente'));
  $utente = $loader->load();
  $strUtente = $utente->get('cognome')." ".$utente->get('nome');
  $nrPratica = '';
  $nrPratica = $W->data->get('pratica.cod1')."/".$W->data->get('pratica.cod2')."/".$W->data->get('pratica.cod3.anno');
?>
      <tr class="paginated_element">
        <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a>&nbsp;&nbsp;&nbsp;</td>
        <td><?=$W->data->get('id')?></td>
        <td><?=$nrPratica?></td>
        <td><?=$W->data->get('cr_date')?></td>
        <td><?=$W->data->get('oggetto')?></td>
        <td><?=$strUtente?></td>
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
	<th>Progressivo</th>
        <th>Pratica</th>
	<th>Data/Ora</th>
        <th>Oggetto</th>
	<th>Utente</th>
      </tr>
<?
while ($W->data->moveNext()){
  $loader = & $IMP->getLoader('portal_spider::utente');
  $loader->addParam('id', $W->data->get('utente'));
  $utente = $loader->load();
  $strUtente = $utente->get('cognome')." ".$utente->get('nome'); 
  $nrPratica = '';
  $nrPratica = $W->data->get('pratica.cod1')."/".$W->data->get('pratica.cod2')."/".$W->data->get('pratica.cod3.anno');
?>
      <tr>
        <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a>&nbsp;&nbsp;&nbsp;</td>
        <td><?=$W->data->get('id')?></td>
        <td><?=$nrPratica?></td>
        <td><?=$W->data->get('cr_date')?></td>
        <td><?=$W->data->get('oggetto')?></td>
        <td><?=$strUtente?></td>
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

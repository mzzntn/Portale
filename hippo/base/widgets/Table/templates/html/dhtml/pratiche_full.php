<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
    <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
      <thead>
	<tr>
            <th></th>
            <th>Tipo</th>
            <th>Numero</th>
            <th>Descrizione</th>
            <th>Data</th>
            <th>Cod Estrazione</th>
	</tr>
      </thead>
      <tbody>
	<?
	while ($W->data->moveNext()){
	  $descrizione = $W->data->get('descrizione');
	  if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 100).'...';
	?>
	    <tr class="paginated_element">
		<td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td>
<? if ($_SESSION['trasparenza']){ ?>
                <td><?=$W->data->get('provvedimenti.tipo.nome')?></td>
                <td><?=$W->data->get('provvedimenti.num2')?></td>
                <td><?=$descrizione?></td>
                <td><?=dateToUser($W->data->get('provvedimenti.data'))?></td>

<? } else { ?>
		<td><?=$W->data->get('tipo.nome')?></td>
		<td><?=$W->data->get('cod2')?></td>
		<td><?=$descrizione?></td>
		<td><?=dateToUser($W->data->get('inizio'))?></td>
<? } ?>
		<td><?=$W->data->get('codEstr')?></td>
	    </tr>
	<?
	}
	?>
      </tbody>
    </table>
<?
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
 
 } else { ?>
<table class='rowList center' cellspacing='0'>
    <tbody id='<?=$D->name?>'>
        <tr>
            <td colspan="6">
<div id="pageNav">
    <?
    if ($W->config['maxElements'] && $W->resultRows) $showPageNavigator = true;
    
    if($showPageNavigator&&$W->start > 1){
      $prev = $W->start - $W->config['maxRows'];
      if ($prev < 1) $prev = 1;
    ?>
    <a href='<?=$_SERVER['PHP_SELF']?>?<?=$W->name?>[start]=<?=$prev?>'>Indietro</a>
    <?
    }
    ?>
    <p>pagina&nbsp;
        <?
	$start = $W->getParam('start');
	if (!$start) $start = 1;
	$start += 1;
	$pagina = ceil($start/$W->config['maxRows']);
        $pagine = ceil($W->resultRows/$W->config['maxRows']);
        print $pagina."&nbsp;di&nbsp;". $pagine;
        ?>
    </p>
    <?
    $next = $W->start + $W->config['maxRows'];
    if($showPageNavigator&&$next < $W->resultRows){
    ?>
    <a href='<?=$_SERVER['PHP_SELF']?>?<?=$W->name?>[start]=<?=$next?>'>Avanti</a>
    <?
    }
    ?>
</div>             
            </td>
        </tr>
        <tr>
            <th></th>
            <th>Tipo</th>
            <th>Numero</th>
            <th>Descrizione</th>
            <th>Data</th>
            <th>Cod Estrazione</th>
        </tr>
    <?
    while ($W->data->moveNext()){
      $descrizione = $W->data->get('descrizione');
      if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 100).'...';
    ?>
        <tr>
            <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td>
            <td><?=$W->data->get('tipo.nome')?></td>
            <td><?=$W->data->get('cod2')?></td>
            <td><?=$descrizione?></td>
            <td><?=dateToUser($W->data->get('inizio'))?></td>
            <td><?=$W->data->get('codEstr')?></td>
        </tr>
    <?
    }
    ?>
    </tbody>
</table> 
<? } ?>

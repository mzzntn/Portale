<?
include_once(LIBS.'/Widgets/PageNavigator.class.php');
if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
  ?>
  <table class="pagination_content table table-bordered table-striped table-responsive row_linked table-hover" id='<?=$D->name?>'>
    <thead>
            <tr>
                <th>Espandi</th>
                <th>Settore</th>
                <th>Descrizione</th>
		<th>Termine conclusione proceduimento</th>
                <th>Periodo</th>
    		<th>Attivati</th>
		<th>Conclusi entro i termini</th>
		<th>Conclusi oltre i termini</th>
		<th>Note esplicative</th>
            </tr>
    </thead>
    <tbody>
      <?
      while ($W->data->moveNext()){
        $descrizione = $W->data->get('nome');
        if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 200).'...';
      ?>
            <tr class="paginated_element">
                <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td>
                <td align='center'><?=$W->data->get('settore.nome')?></td>
                <td><?=$descrizione?></td>
		<td><?=$W->data->get('termine')?></td>
                <td><?=$W->data->get('rilevazioni.periodo.descrizione')?></td>
		<td><?=$W->data->get('rilevazioni.attivati')?></td>
		<td><?=$W->data->get('rilevazioni.entro')?></td>
		<td><?=$W->data->get('rilevazioni.oltre')?></td>
		<td><?=$W->data->get('rilevazioni.note')?></td>
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
                <th>Espandi</th>
                <th>Settore</th>
                <th>Descrizione</th>
		<th>Termine conclusione proceduimento</th>
                <th>Periodo</th>
    		<th>Attivati</th>
		<th>Conclusi entro i termini</th>
		<th>Conclusi oltre i termini</th>
		<th>Note esplicative</th>
            </tr>
      <?
      while ($W->data->moveNext()){
        $descrizione = $W->data->get('nome');
        if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 200).'...';
      ?>
            <tr>
                <td><a href='<?= $W->config['admin'] ?><?=$W->data->get('id')?>'>Vai</a></td>
                <td align='center'><?=$W->data->get('settore.nome')?></td>
                <td><?=$descrizione?></td>
		<td><?=$W->data->get('termine')?></td>
                <td><?=$W->data->get('irilevazini.periodo.descrizione')?></td>
		<td><?=$W->data->get('rilevazioni.attivati')?></td>
		<td><?=$W->data->get('rilevazioni.entro')?></td>
		<td><?=$W->data->get('rilevazioni.oltre')?></td>
		<td><?=$W->data->get('rilevazioni.note')?></td>
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
        /*if($showPageNavigator&&($W->start > 1)){
          $prev = $W->start - $W->config['maxRows'];
          if ($prev < 1) $prev = 1;
        ?>
        <a href='<?=$_SERVER['PHP_SELF']?>?<?=$W->name?>[start]=<?=$prev?>'>&lt;&lt;&lt;</a>
        <?
        }
        ?>
        pagina&nbsp;
        <? 
      	$decimi=10;
      	$modulo =($W->resultRows) %($W->config['maxRows']);
      	if ($modulo>0)
      		  $pagina=(($W->resultRows) /($W->config['maxRows']))- ($modulo/$decimi)+1;
      	else  $pagina=(($W->resultRows) /($W->config['maxRows']));
        $pagina = ceil($pagina);
      	if ($prev) $contatore=$prev+$W->config['maxRows'];
      		else   $contatore=$next-$W->config['maxRows'];
      	$modulo_pag=($contatore) %($W->config['maxRows']);
      	$numero_pagina=$contatore/($W->config['maxRows'])- ($modulo_pag/$decimi)+1;
        $numero_pagina = ceil($numero_pagina);
        if (!$numero_pagina) $numero_pagina = 1;
      	print '<b>'.($numero_pagina).'</b>&nbsp;di&nbsp;<b>'.$pagina.'</b>';
        $next = $W->start + $W->config['maxRows'];
        if($showPageNavigator&&($next < $W->resultRows)){
        ?>
        <a href='<?=$_SERVER['PHP_SELF']?>?<?=$W->name?>[start]=<?=$next?>'>&gt;&gt;&gt;</a>
        <?
        }*/
        ?>
    </p>
</div>
<? } ?>

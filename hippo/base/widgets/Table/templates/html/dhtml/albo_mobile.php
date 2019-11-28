<?php
$adminMobileHref = str_replace("albo/","albo/mobile/",$W->config['admin']);
?>

<table class='rowList center rowLinks' cellspacing='0'>
    <tbody id='<?=$D->name?>'>
        <tr>
            <th>Numero</th>
            <th>Oggetto</th>
            <th>Atto</th>
      <th>Data affissione</th>
      <th>Fine Pubblicazione</th>
        </tr>
    <?
    while ($W->data->moveNext()){
      if ( strlen($descrizione) > 100) $descrizione = substr($descrizione, 0, 100).'...';
      $idAnnuale=substr($W->data->get('ID_ANNUALE'),0,4);
      $addTrClass = "";
      if(strpos($W->data->get('OGGETTO'), "PUBBLICAZIONE ANNULLATA")>-1){$addTrClass = " nulled";}
    ?>
        
        <tr class='link<?=$addTrClass?>'>
          <td><a href='<?= $adminMobileHref ?><?=$W->data->get('NPROG')?>'><?=substr($W->data->get('ID_ANNUALE'),0,4)?>/<?=substr($W->data->get('ID_ANNUALE'),4,6)?></a></td>
          <td><a href='<?= $adminMobileHref ?><?=$W->data->get('NPROG')?>'><?=$W->data->get('OGGETTO')?></a></td>
          <td><a href='<?= $adminMobileHref ?><?=$W->data->get('NPROG')?>'><?=$W->data->get('TIPOATTO.DESC_ATTO')?></a></td>
          <td><a href='<?= $adminMobileHref ?><?=$W->data->get('NPROG')?>'><?=dateToUser($W->data->get('DATA_AFFIS'))?></a></td>
          <td><a href='<?= $adminMobileHref ?><?=$W->data->get('NPROG')?>'><?=dateToUser($W->data->get('SCADENZA'))?></a></td>
        </tr>
        
    <?
    }
    ?>
    </tbody>
</table> 

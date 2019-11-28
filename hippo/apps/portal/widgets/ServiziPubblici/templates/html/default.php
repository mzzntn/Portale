        <h3>Sezione Pubblica</h3>
			  <div class="menucnt">
			     <a href='<?=URL_APP_PORTAL?>'>Indice</a>
<?
  while ($W->servizi->moveNext()){
    $url = $W->servizi->get('url');
    $codEstr = $W->servizi->get('codEstr');
    if (!strstr($url, 'http://')) $url = HOME.'/'.$url;
    if ($codEstr) $url .= '?codEstr='.$codEstr;
?>
			     <a href='<?=$url?>'><?=$W->servizi->get('nome')?></a>
<?
  }
?>
			  </div>

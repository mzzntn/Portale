<div id="elenco_servizi_pubblici" class="col-md-6 col-xs-6 text-right">    
  <ul>
    <?
      while ($W->servizi->moveNext()) {
	$url = $W->servizi->get('url');
	$codEstr = $W->servizi->get('codEstr');
	if (!strstr($url, 'http://')) $url = HOME.'/'.$url;
	if ($codEstr) $url .= '?codEstr='.$codEstr;
	echo '<li>
	  <a class="nome_servizio" href="'.$url.'">'.$W->servizi->get('nome').'</a>
	  <br>
	  <div class="descr_servizio lead">'.$W->servizi->get('descrizione').'</div>
	</li>';
      }
    ?>
  </ul>
</div>        

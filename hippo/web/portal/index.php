<?
  include_once('init.php');
if ($C['portal']['spider_portal']){
   $IMP->security->logoutCAS();
   header("Location: ".$C['portal']['spider_portal']);
}

  include_once(PATH_APP_PORTAL.'/portal_top_new.php');
  #include_once(PATH_APP_PORTAL.'/portal_right.php');
  #include_once(PATH_APP_PORTAL.'/portal_left.php');
 #$IMP->debugLevel = 3;
?>   

<div id="portal_content" class="portal">
  <div class="testo_introduzione_index lead">
    <p class="visibility-580">Tutti i servizi del portale sono attivi comodamente da casa Vostra e senza limiti di orario.</p>
    <p>I servizi attualmente attivi sono i seguenti:</p>
  </div>
  
  <div id="navigatore_portale" class="nascondi_sotto_500">
    <span class="text-center central_nav">
      <div class="freccia freccia_completa">
	<div class="navigatore navigatore_completo no_separatore text-uppercase">
	  <span>Servizi Pubblici</span>
	</div>
      </div>
    </span>
  </div>

  <div class="index">
    <div id="elenchi_servizi" class="row">
      <?
	$loader = & $IMP->getLoader('portal::servizioPubblico');
    $loader->addOrder('posizione');
	$siti = $loader->load();
      ?>
      <div id="elenco_servizi_pubblici" class="col-md-12 col-xs-12 text-center">
	<ul>
	  <?
	    while ($siti->moveNext()) {
	      $url = $siti->get('url');
	      $codEstr = $siti->get('codEstr');
	      if (!strstr($url, 'http://')) $url = HOME.'/'.$url;
	      if ($codEstr) $url .= '?codEstr='.$codEstr;
	      echo '<li>
		<a class="nome_servizio" href="'.$url.'">'.$siti->get('nome').'</a>
		<br>
		<div class="descr_servizio lead">'.$siti->get('descrizione').'</div>
	      </li>';
	    }
	  ?>
	</ul>
      </div> 
    </div>
  </div>

  <!--<div id="navigatore_portale">
    <span class="text-center central_nav">
      <span class="freccia freccia_sx_big">
	<span class="navigatore left_navigatore">
	  <a id="link_serv_pubblici" href="<?=HOME?>/portal/servizi_pubblici.php">SERVIZI PUBBLICI</a>
	</span>
      </span>
      <span class="freccia freccia_dx_big">
	<span class="navigatore right_navigatore">
	  <a id="link_serv_riservati" href="<?=HOME?>/portal/servizi_privati.php">SERVIZI PRIVATI</a>
	</span>
      </span>
    </span>
  </div>

  <div class="index">
    <div id="elenchi_servizi" class="row">
      <?
	/*$serviziPubblici = & $IMP->getWidget('portal::ServiziPubblici_new');
	$serviziPubblici->display();
	$serviziPrivati = & $IMP->getWidget('portal::ServiziPrivati');
	$serviziPrivati->display();*/
      ?>
    </div>
  </div>-->
</div>

<?
include_once(PATH_APP_PORTAL.'/portal_bottom_new.php');
?>

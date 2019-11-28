<?
   ob_start();
?>

<nav id="menu_responsive_laterale" class="" style="display: none;">
  <div>
      <p>
	  <a href="<?=tornaAlPortale("url");?>">
	  <i class="fa fa-home"></i>
	  Home
	  </a>
      </p>
      <ul>
      <?
	  if ($C['portal']['spider_portal'] && count($utenteServizi)>0) { // portale spider - loggato
	    $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($C['portal']['spider_portal']);
	    ?>
	    <li>
		<span>
		    <a href="<?=$url?>">
		    <i class="fa fa-user"></i>
		    <?=$_SESSION['phpCAS']['attributes']['nome']?> <?=$_SESSION['phpCAS']['attributes']['cognome']?>
		    </a>
		</span>
	    </li>
	    <? $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($C['portal']['spider_portal']."servizi"); ?>
	    <li>
		<span>
		    <a href="<?=$url?>">
		    <i class="fa fa-pencil-square-o"></i>
		    Gestione Servizi
		    </a>
		</span>
	    </li>
	    <li class="divider"></li>
	      <li>
		  <span>
		      <a href="#serv_pubblici">
			  <i class="fa fa-users"></i>
			  Servizi Pubblici
		      </a>
		  </span>
	      </li>
	      <li>
		  <span>
		      <a href="#serv_privati">
			  <i class="fa fa-user-secret"></i>
			  Servizi Privati
		      </a>
		  </span>
	      </li>
	      
	      <? $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($C['portal']['spider_portal']."autenticazione/logout"); ?>
	      <li class="divider"></li>
	      <li>
		  <span>
		      <a href="<?=$url?>" title="Logout">
			  <i class="fa fa-sign-out"></i>
			  Logout
		      </a>
		  </span>
	      </li>
	    <?
	  }
	  else if($C['portal']['spider_portal']) { // portale spider - non loggato
	    $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($C['portal']['spider_portal']."autenticazione");
	  ?>	  
	  <li class="divider"></li>
	  <li>
	      <span>
		  <a href="<?=$url?>" title="Logint">
		      <i class="fa fa-sign-in"></i>
		      Accedi
		  </a>
	      </span>
	  </li>
	  <?
	  }
	  else { // portale php
	  ?>
	  <li class="divider"></li>
	  <li>
	      <span>
		  <a href="#serv_pubblici">
		      <i class="fa fa-users"></i>
		      Servizi Pubblici
		  </a>
	      </span>
	  </li>
	  <?
	  }
	  ?>
      </ul>

      <!-- subpanel -->
      <div id="serv_pubblici" class="Panel">
	<p>Lista servizi</p>
	<ul><?
	foreach($serviziPubblici as $nome => $url) {
	  $isSpider = (strpos($url, substr($C['portal']['spider_portal'],0, strlen($C['portal']['spider_portal'])-1))!==false);
	  if($C['portal']['spider_portal'] && $isSpider) {
	    $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($url);
	  }
	  ?>
	    <li>
	    <a class="nome_servizio" href="<?=$url?>"><?=$nome?></a>
	    </li>
	  <?
	}?>
	</ul>
      </div>
      <?
      if($C['portal']['spider_portal'] && count($utenteServizi)>0) {
      ?>
      <div id="serv_privati" class="Panel">
	<p>Lista servizi</p>
	<ul><?
	foreach($utenteServizi as $nome => $url) {
	  $isSpider = (strpos($url, substr($C['portal']['spider_portal'],0, strlen($C['portal']['spider_portal'])-1))!==false);
	  if($C['portal']['spider_portal'] && $isSpider) {
	    $url = HOME."/portal/torna_a_spider.php?pagina=".urlencode($url);
	  }
	  ?>
	    <li>
	    <a class="nome_servizio" href="<?=$url?>"><?=$nome?></a>
	    </li>
	  <?
	}?>
	</ul>
      </div>
      <? } ?>
  </div>
</nav>

<?
   $menuHtml = ob_get_contents();
   ob_end_clean();
?>
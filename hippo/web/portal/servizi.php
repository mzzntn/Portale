<?
include_once('init.php');

$test = false;
if ($C['portal']['spider_portal']){
	$loader = &$IMP->getLoader('portal_spider::servizio');
        $servizi = $loader->load();

	$serviziPubblici = array();
	$serviziPrivati = array();
	while ($servizi->moveNext()){
		$tipo = $servizi->get('accesso');
		if ($tipo == 'nascosto') continue;
		elseif ($tipo == 'pubblico'){
			$serviziPubblici[$servizi->get('nome')] = $servizi->get('url');

		}
		else{
			$serviziPrivati[$servizi->get('nome')] = $servizi->get('url');;	
		}

	}
	#servizi utente solo se autenticato
	if ($C['portal']['cas']){
		if($_REQUEST['servizio'] && !isset($_SESSION['servizio_cas'])){
			$servizioCAS = $_REQUEST['servizio'];
			$_SESSION['servizio_cas'] = $servizioCAS;
			$IMP->security->requireCAS($servizioCAS);
		}
		$idUtente = $_SESSION['phpCAS']['attributes']['id'];
        	$loader = &$IMP->getLoader('portal_spider::utenteServizi');
	        $loader->addParam('utente', $idUtente);
		$loader->addParam('stato', 'attivo');
		$loader->request('servizio.nome');
		$loader->request('servizio.url');
	        $serviziUtente = $loader->load();	
		$utenteServizi = array();
		while ($serviziUtente->moveNext()){
			$utenteServizi[$serviziUtente->get('servizio.nome')] = $serviziUtente->get('servizio.url');
		}	
	}
}
else{
	#servizi pubblici
	$loader = &$IMP->getLoader('portal::servizioPubblico');
        $servizi = $loader->load();
	
	$serviziPubblici = array();
	while ($servizi->moveNext()){
	        $url = $servizi->get('url');
		if (substr($url, 0, 4) != 'http') {$url = HOME."/".$url;}
	        if ($servizi->get('codEstr')) {$url .= '?codEstr='.$servizi->get('codEstr');}
                $serviziPubblici[$servizi->get('nome')] = $url;
        }
	
}
if ($test){
	print "<br>Servizi Pubblici<br>";
	print_r($serviziPubblici);
	print "<br>Servizi Privati<br>";
	print_r($serviziPrivati);
	print "<br>Servizi Utente<br>";
	print_r($utenteServizi);
	print "<br>Dati in sessione<br>";
	print "<br>Nome: ".$_SESSION['phpCAS']['attributes']['nome'];
	print "<br>Cognome: ".$_SESSION['phpCAS']['attributes']['cognome'];
	print "<br>Id: ".$_SESSION['phpCAS']['attributes']['id'];
	print "<br>Codice Fiscale: ".$_SESSION['phpCAS']['attributes']['codice_fiscale'];
	print "<br>Master: ".$_SESSION['phpCAS']['attributes']['codice_master'];
}

?>

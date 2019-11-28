<?
include_once('../init.php');
//$IMP->debugLevel = 3;
if (!$IMP->security->checkAdmin()) redirect('login.php');
include_once('top.php');
$administrator = $IMP->widgetFactory->getWidget('Administrator', 'administrator');
if ($_SESSION['operatore'] && !$_SESSION['settore']) $_SESSION['settore'] = '9999999';
                
if($_SESSION["settore"]) {
	$params = new QueryParams();
	$params->set('settore', $_SESSION["settore"]);
	$IMP->config['alwaysParams']['benefici::procedimento']['settore'] = $_SESSION["settore"];
	$IMP->config['alwaysParams']['benefici::ufficio']['settore'] = $_SESSION["settore"];
	$IMP->config['alwaysParams']['benefici::modulistica']['procedimento.settore'] = $_SESSION["settore"];
	$IMP->config['alwaysParams']['benefici::rilevazione']['procedimento.settore'] = $_SESSION["settore"];
	$IMP->config['alwaysParams']['benefici::retribuzione']['responsabile.settori'] = $_SESSION["settore"];
	if (!$_REQUEST['form_benefici::procedimento']) $IMP->config['alwaysParams']['benefici::responsabile']['settori'] = $_SESSION["settore"];
	$IMP->config['alwaysParams']['benefici::settore']['id'] = $_SESSION["settore"];
	if (defined('URL_APP_TRASPARENZA')) $IMP->config['alwaysParams']['trasparenza::pagina']['settori'] = $_SESSION["settore"]; 
} 
if (!$C['portal']['spider_portal']){
	$administrator->section('Portal');
        $administrator->config['form']['portal::utente']['showSelect']['portal::servizioPrivato'] = true;
/*        $administrator->administer('portal::utente', 'Utenti');
        $administrator->administer('portal::ditta', 'Aziende');
        $administrator->administer('portal::servizioPrivato', 'Sezione riservata');
	$administrator->administer('portal::servizioInterno', 'Sezione nascosta');*/
        $administrator->administer('portal::servizioPubblico', 'Sezione pubblica');
        $administrator->administer('operatore', 'Operatori');
}
if (defined('URL_APP_TRASPARENZA') || defined('URL_APP_BENEFICI')){
	$administrator->section('Trasparenza');
	if (defined('URL_APP_TRASPARENZA')){
		$administrator->administer('trasparenza::pagina', 'Amministrazione trasparente');
		if (defined('URL_APP_PRATICHE') &&  !$_SESSION['operatore']) $administrator->administer('trasparenza::sezioniPagina', 'Sezioni automatiche');
	}
	if (defined('URL_APP_BENEFICI')){
	   if (!$_SESSION['operatore']){	
		$administrator->administer('benefici::ufficio', 'Benefici/Appalti Ufficio');
		$administrator->administer('benefici::modalita', 'Benefiici/Appalti Modalita');
		$administrator->administer('benefici::normativa', 'Benefici/appalti Normativa');
                $administrator->administer('benefici::cosafareper', 'Sezioni Cosa Fare Per');
                $administrator->administer('benefici::ruolo', 'Ruoli Politici/OIV');
                $administrator->administer('benefici::politico', 'Politici/Componenti OIV');
                $administrator->administer('benefici::ruolo', 'Ruoli Politici/OIV');
                $administrator->administer('benefici::attoNomina', 'Atti di Nomina politici');
                $administrator->administer('benefici::incarico', 'Incarichi Politici');
	}
        $administrator->administer('benefici::responsabile', 'Responsabili/Dirigenti');
        $administrator->administer('benefici::retribuzione', 'Retribuzioni');
        $administrator->administer('benefici::settore', 'Struttura - Settori');	
	if (!$_SESSION['operatore']){
		$administrator->administer('benefici::periodo', 'Procedimenti - periodo');	
	}
	$administrator->administer('benefici::ufficio', 'Struttura - Uffici');
	$administrator->administer('benefici::procedimento', 'Procedimenti - descrizione');     
	$administrator->administer('benefici::modulistica', 'Procedimenti - modulistica');
	if (!$_SESSION['operatore']){
		$administrator->administer('benefici::impostazioni', 'Procedimenti - dati di base');
		$administrator->administer('benefici::rilevazione', 'Procedimenti - rilevazioni');
	}	
     }
}
if (defined('URL_APP_APPUNTAMENTI')){
	$administrator->section('Dati Appuntamenti');
	$administrator->administer('appuntamenti::operatore', 'Operatori');
	$administrator->administer('appuntamenti::orario', 'Orari');
	$administrator->administer('appuntamenti::tipo', 'Tipologia');
	$administrator->administer('appuntamenti::chiusura', 'Giorni chiusura');
	$administrator->administer('appuntamenti::prenotazione', 'Prenotazioni');
}
if (defined('PATH_APP_CARICAMENTO_PRATICHE')){
	$administrator->section('Caricamento Pratiche');
	$administrator->administer('caricamento_pratiche::macrocategoriaDocumentazione', 'Macro Catogoria');
	$administrator->administer('caricamento_pratiche::categoriaDocumentazione', 'Categoria Principale');
	$administrator->administer('caricamento_pratiche::documentazione', 'Documentazione');
	$administrator->administer('caricamento_pratiche::civ_area', 'Aree applicative');
	$administrator->administer('caricamento_pratiche::impostazioni', 'Impostazioni');
	$administrator->administer('caricamento_pratiche::domanda', 'Domande ulteriori');
	$administrator->administer('caricamento_pratiche::domandeAllegato', ' Domande - allegati');
	$administrator->administer('caricamento_pratiche::domandePagamento', ' Domande - pagamenti');
	$administrator->administer('caricamento_pratiche::domandeProc', 'Procedimenti - domande');
	if ($C['portal']['spider_moduli']){
		$administrator->administer('caricamento_pratiche::modelloModulo', 'Moduli Dinamici');
		$administrator->administer('caricamento_pratiche::modelloModuloEvento', 'Moduli Dinamici Evento');
	}
	$administrator->administer('caricamento_pratiche::modelloAutogenerato', 'Modelli Autogenerati');
}
if (defined('PATH_APP_ICI')) {
        $administrator->section('Dati IUC');
        $administrator->administer('ici::documenti', 'Documentazione');
        $administrator->administer('ici::ravvedimento', 'Ravvedimento');
        $administrator->administer('ici::imutasi', 'Corrispondenza aliquote');
        $administrator->section('Dati IMU');
        $administrator->administer('ici::II_TAB_ANNO', 'Annualita');
        $administrator->administer('ici::II_TAB_ATTRIB', 'Attributi');
        $administrator->administer('ici::II_TAB_ALIQ', 'Aliquote');
        $administrator->administer('ici::II_ATTRIBUTI_CATEG', 'Attributi specifici per categoria');
        $administrator->section('Dati TASI');
        $administrator->administer('ici::TS_DATI_ANNUALI', 'Annualita');
        $administrator->administer('ici::TS_ATTRIBUTI', 'Attributi');
        $administrator->administer('ici::TS_ALIQUOTE', 'Aliquote');
        $administrator->administer('ici::TS_ATTRIBUTI_CATEG', 'Attributi specifici per categoria');
}
if (defined('PATH_APP_TARSU') && false){
        $administrator->section('TARI');
        $administrator->administer('tarsu::M1_TAB_CAT', 'Categorie');
        $administrator->administer('tarsu::M1_TAB_RPS', 'Tariffe');
        $administrator->administer('tarsu::M1_TAB_RIDUZ', 'Riduzioni');
        $administrator->administer('tarsu::M1_TAB_RID_PERC', 'Percentuali riduzione');
}

$administrator->section('Link e funzioni');
$link = array();
$C['portal']['spider_portal'] = 'http://newopenweb.soluzionipa.it/portal';
if ($C['portal']['spider_portal']){
	$link['Amministrazione Portale'] =  'http://'.SERVER.'/admin/portal/';
}
if (defined('URL_APP_ALBO')){
        $link['Inserimento manuale albo'] = 'http://'.SERVER.URL_APP_ALBO.'/inserimento.php';
}
if (defined('URL_APP_PRATICHE')){
        $link['Cancellazione pratiche'] = 'http://'.SERVER.URL_APP_PRATICHE.'/pratiche_full.php';
}
if (defined('URL_APP_BENEFICI')){
	$link['Amministrazione benefici'] = 'http://'.SERVER.URL_APP_BENEFICI.'/admin/';
}
if (defined('URL_APP_APPUNTAMENTI')){
	$link['Gesitone appuntamenti'] = 'http://'.SERVER.URL_APP_APPUNTAMENTI.'/amministrazione/';
}
if (defined('PATH_APP_CARICAMENTO_PRATICHE')){
 	$link['Configurazione multipla domande'] =  'http://'.SERVER.URL_APP_CARICAMENTO_PRATICHE.'/amministrazione/configurazione.php';
	$link['Gestione Istanze Online'] = 'http://'.SERVER.URL_APP_CARICAMENTO_PRATICHE.'/amministrazione/';
	$link['Configurazione Caricamento pratiche'] =  'http://'.SERVER.URL_APP_CARICAMENTO_PRATICHE.'/amministrazione/configurazione.php';
}

$administrator->start();
if ($administrator->widgets['form']->structName == 'portal::servizioPrivato'){
        $autInput = & $administrator->widgets['form']->createInput('SelectInput', 'autenticazione');
        $autInput->generateFromArray(array('1'=>'Interna', '2'=>'Form', '3'=>'HTTP'));
        $autInput->setValue($administrator->widgets['form']->data->autenticazione);
}

$administrator->display();

include_once('bottom.php');

?>

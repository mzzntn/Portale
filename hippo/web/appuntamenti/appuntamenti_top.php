<?
#### check-in Utente
// check Amministratore
if ($IMP->security->checkAdmin()) $admin = true;

// check Utente e raccolta dati

if ($C['portal']['cas']){
    $servizioCAS = $_REQUEST['servizio'];
    $_SESSION['servizio_cas'] = $servizioCAS;
    $IMP->security->requireCAS($servizioCAS);
    $master = $IMP->security->casUser->id;
    if (!$master) return;
} 
else{
    $strutturaUtente = $C['portal']['struttura_utenti'];
    $strutturaDitte = $C['portal']['struttura_ditte'];
    if (!$IMP->security->checkDomain($strutturaUtente) && !$IMP->security->checkDomain($strutturaDitte)){
        if (defined('URL_APP_PORTAL')) $IMP->security->redirectToLogin(URL_APP_PORTAL);
        else $IMP->security->redirectToLogin(LOGIN);
    }
   $master = loadEl('portal::utente', 'master', array('login' => $IMP->security->login));
}
// TODO a cosa serve questo if a parte distruggere la grafica della pagina?
//if (!$master) return;

if ($C['portal']['cas']){
     $loader = &$IMP->getLoader('portal_spider::utente');
     $loader->addParam('id', $master);
      
}
else {
    $loader = &$IMP->getLoader('portal::utente');
    $loader->addParam('login', $IMP->security->login);
    $list = $loader->load();
}
$loader->requestAll();
$utente = $loader->load();
$nome = $utente->get('nome');
$cognome = $utente->get('cognome');
$nomePersona = $nome ." ". $cognome;
$email = $utente->get('email');
$telefono = $utente->get('telefono');

### variabili usate 
$idOperatoreUNO = 2;

### creazione pagina
include_once(PATH_APP_PORTAL.'/portal_top_new.php');
if(!$C["portal"]["spider_portal"]){?>
<script type='text/javascript' src='<?=URL_APP_APPUNTAMENTI?>/js/jquery.ui.datepicker-it.js'></script>
<?}?>

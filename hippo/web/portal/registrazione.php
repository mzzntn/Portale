<?
include_once('init.php');
$showMenu = true;
include_once(PATH_APP_PORTAL.'/portal_top.php');
//$IMP->debugLevel = 3;
$strutturaUtente = $C['portal']['struttura_utenti'];
$strutturaDitte = $C['portal']['struttura_ditte'];
if ($_REQUEST['ditta']) $ditta = true;
if ($utente){  //preso da startupUtente.php
 if ($IMP->security->checkDomain($strutturaDitte)) $ditta = true;
  else $ditta = false;
  if ($ditta) $struct = $strutturaDitte;
  else $struct = $strutturaUtente;
  $loader = & $IMP->getLoader($struct);
  $loader->addParam('login', $IMP->security->login);
  $loader->request('id');
  $list = $loader->load();
  $id = $list->get('id');
    
}
$form1 = & $IMP->getWidget('portal::FormRegistrazione', 'reg');

if ($id) $form1->setId($id);
if (!$ditta) $form1->setStruct($strutturaUtente);
else{
  $form1->setStruct($strutturaDitte);
  $form1->ditta = true;
}
$form1->generateFromStructure();

$IMP->security->disablePolicy($strutturaUtente);
$IMP->security->disablePolicy($strutturaDitte);
$stored = $form1->storeData();
$form1->loadData();
$IMP->security->reEnablePolicy($strutturaUtente);
$IMP->security->reEnablePolicy($strutturaDitte);
?>
<div id="register" class="content"> 
<div id="pageBox">
    <div class="leftTopCorner"></div>
    <div class="rightTopCorner"></div> 
    <div class="boxContent">
        <h3>Registrazione al Portale</h3>        
<?
if ($stored){
  $IMP->widgetParams->clear('reg');
  $form1->sendRegistrationMails($stored, $utente, $ditta);
  if ($id){ 
?>
    <p class="highlight">Modifica eseguita. Se ha richiesto la registrazione a nuovi servizi, la contatteremo al più presto per comunicarne l'attivazione. <?=isset($form1->changed_password)? '<br>La sua password è stata aggiornata.': ''?></p>
<?
  }
  else{
?>
    <p class="highlight">La sua richiesta di registrazione è stata accettata. La contatteremo al più presto per comunicarle i dati di accesso ai servizi da lei richiesti.</p>
<?
  }
?>
    <p><a href = '<?=URL_APP_PORTAL?>/'>Indice</a></p>
<?  
}
else{
  $form1->display();
}
$form1->clearParams();
?>   
    </div> 
    <div class="leftBottomCorner"></div>
    <div class="rightBottomCorner"></div>    
</div> 
</div>     
<?
include_once(PATH_APP_PORTAL.'/portal_bottom.php');
?>

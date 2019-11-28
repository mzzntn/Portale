<?
include_once('init.php');
$strutturaUtente = $C['portal']['struttura_utenti'];
$strutturaDitte = $C['portal']['struttura_ditte'];
?>
        <h3>Sezione Privata</h3>
			  <div class="menucnt">
<? 
  if ($IMP->security->checkDomain($strutturaUtente) || $IMP->security->checkDomain($strutturaDitte)){
    $userId = $IMP->security->userId();
    $loader = & $IMP->getLoader($strutturaUtente);
    $loader->addParam('login', $IMP->security->login);
    $utente = $loader->load();
?>
  <p class="info">Benvenuto, <b><?=$utente->get('nome')?> <?=$utente->get('cognome')?></b>.</p>
  <a href='<?=URL_APP_PORTAL?>/registrazione.php'>I tuoi dati</a>
  <a href='<?=$_SERVER['PHP_SELF']?>?action=logout'>Logout</a>
<?     
  if (sizeof($siti) < 1){
    print "Non sei attualmente registrato ad alcun servizio.";
  }else foreach ($siti as $id => $dett){
?>
    <a class='menuRow' href="<?=$dett['link']?>"><?=$dett['nome']?></a>
<?
        }
?>  
<?
  }
  else{
?>

	<a href='<?=URL_APP_PORTAL?>/registrazione.php'>Registrati!</a>
    <form class="smallform" method="post" action="<?=URL_APP_PORTAL_AUTH?>/index.php">
        <p><input type='hidden' name='go' value='<?=$_REQUEST['go']?>'></p>
        <p><label for='login'>Utente</label><input type='text' id='login' name='login' value="" size="12"></p>
        <p><label for='password'>Password</label><input type='password' id='password' name='password' value="" size="12"></p>
        <div class="buttons"><input class="button" type="submit" value="Accedi"></div>
    </form> 
<?
  }
  if ($_REQUEST['login'] && $_REQUEST['password']){
    if ($_SESSION['loginFailed']){
?>
<p class="error">Utente o Password errati</p>
<? 
    }
  }
  if ($_REQUEST['action'] == 'logout'){
?>
<p class="error">Logout eseguito.</p>
<? 
    $IMP->security->logout();
  }
?>  
			  </div>

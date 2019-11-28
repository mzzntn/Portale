<?php
include_once('../../init.php');
include_once('startdb.php');

// se sono già loggato e non sto facendo logout faccio un redirect sulla pagina di amministrazione senza richiedere l'autenticazione
if ($IMP->security->checkAdmin() && !isset($_REQUEST['logout']) &&(!isset($_REQUEST['action']) || $_REQUEST['action'] != 'logout')) redirect('index.php');

if($C['portal']['start_auth']['auditor'] && !isset($_REQUEST['username'])) {
  // il login tramite auth_hub fa l'override di qualunque altro metodo di login
  if(isset($_GET['jwt'])&& $_GET['jwt']!="") {
    // ho ricevuto un token jwt coi dati di autenticazione
    $IMP->security->loginStart("index.php","appuntamenti",$_GET['jwt']);
  } else if (isset($_REQUEST['login'])) {
    // non ho ricevuto nessun token, effettuo login auth_hub
    $IMP->security->loginStart("login.php","appuntamenti", false, isset($_REQUEST['auth'])?$_REQUEST['auth']:false);
  } else if (isset($_REQUEST['logout'])||$_REQUEST['action'] == 'logout') {
    if($_SESSION['jwt']) {
      $IMP->security->logoutStart("../index.php","elenco_richieste");
    } else {
      $IMP->security->logout();
      $_SESSION['userId'] = 0;
      $_SESSION['operatore'] = '';
      unset($_SESSION['soap']);
      unset($_SESSION['soapu']);
      redirect('index.php');
      return;
    }
  }
  if(isset($_REQUEST["error"])) {/*echo "si &egrave verificato un errore.";*/}
}



if ($_REQUEST['username'] && (($IMP->security->loginOperatore($_REQUEST['username'], $_REQUEST['password'])) || ($IMP->security->login($_REQUEST['username'], $_REQUEST['password'])))){
      $_SESSION['userId'] = $IMP->security->userId;
        if ($_REQUEST['go']) redirect($_REQUEST['go']);
        else redirect('elenco_richieste.php');
        return;
}
if ($_REQUEST['action'] == 'logout'){
      $IMP->security->logout();
        $_SESSION['userId'] = 0;
        $_SESSION['opertatore'] = '';
          redirect('login.php');
          return;
}
include_once('appuntamenti_top.php');

if(isset($C['style']) && $C['style']=="2016") { // nuova grafica
?>
<div id="portal_content"> 
  <div id="login_portale" class="autenticazione col-lg-12 col-md-12 col-sm-12 col-xs-12">
    <div class="row"> 
      <div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 col-xs-12">
 <? if(!isset($C['portal']['start_auth']['auditor']) || $C['portal']['start_auth']['old_login']) { ?>
          <?=$_REQUEST['username']? print '<div class="alert alert-danger"> Nome utente o password errati. </div>': '<div class="alert alert-danger"> L\'accesso alla sezione richiesta richiede l\'autenticazione. </div>'?>
          <div id="autenticazione_login">
          <form id="login_form" action="<?=$_SERVER['PHP_SELF']?>" method="post" role="form" class="form-horizontal">
            <div class="form-group">
              <input type="text" name="username" id="username" tabindex="1" class="form-control" placeholder="Username" value="">
            </div>
            <div class="form-group">
              <input type="password" name="password" id="password" tabindex="2" class="form-control" placeholder="Password">
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success btn-block" tabindex="3" value="Accedi">ACCEDI</button>
            </div>
          </form>
          <? }
          if(isset($C['portal']['start_auth']['auditor']) && $C['portal']['start_auth']['old_login']) {
            echo "<p class='text-center'>oppure</p>";
          }
          if(!isset($_GET["login"])) { ?>
            <? if(isset($_GET["error"])) { ?>
            <div class="alert alert-danger"><?=$IMP->security->getErrorMessage($_GET["error"])?></div>
            <? }
            if($C['portal']['start_auth']['show_buttons']===true) {
              foreach($C['portal']['start_auth']['auth_type'] as $auth) {
                echo "<a href='login.php?login&auth={$auth}' class='btn btn-success btn-azure btn-block'><i class='fa fa-{$C['portal']['start_auth']['buttons'][$auth]['icon']}' aria-hidden='true'></i> {$C['portal']['start_auth']['buttons'][$auth]['label']}</a><br>";
              }
            } else if($C['portal']['start_auth']['show_buttons']!==false) {
              $auth = $C['portal']['start_auth']['show_buttons'];
              echo "<a href='login.php?login&auth={$auth}' class='btn btn-success btn-azure btn-block'><i class='fa fa-{$C['portal']['start_auth']['buttons'][$auth]['icon']}' aria-hidden='true'></i> {$C['portal']['start_auth']['buttons'][$auth]['label']}</a><br>";
            } ?>
          <?}?>
	</div>	
	</div>
      </div>
    </div>
  </div>
</div>
<?
}
else {
?>
<br><br><br>
<div class="smallform center">
    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
        <p><label for='login'>Username</label><input type='text' id='login' name='login'></p>
        <p><label for='password'>Password</label><input type='password' id='password' name='password'></p>
        <p class="error"><?=$_REQUEST['login']? print "Utente o Password errati": "E' necessario autenticarsi per poter proseguire"?></p>
        <div class='buttons'>
            <input type="submit" value="Accedi" class="button">
        </div>
    </form>
</div>    
<?
}
include_once('../appuntamenti_bottom.php');
?>

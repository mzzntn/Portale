<?
include_once('../init.php');
#$IMP->debugLevel = 3;

if($C['portal']['start_auth']['auditor'] && !isset($_REQUEST['username'])) {
  // il login tramite auth_hub fa l'override di qualunque altro metodo di login
  if(isset($_GET['jwt'])&& $_GET['jwt']!="") {
    // ho ricevuto un token jwt coi dati di autenticazione
    $IMP->security->loginStart("index.php?".http_build_query($_GET),"portal",$_GET['jwt']);
  } else if (isset($_REQUEST['login'])) {
    // non ho ricevuto nessun token, effettuo login auth_hub
    $IMP->security->loginStart("login.php","portal", false, isset($_REQUEST['auth'])?$_REQUEST['auth']:false);
  } else if (isset($_REQUEST['logout'])||$_REQUEST['action'] == 'logout') {
    if($_SESSION['jwt']) {
      $IMP->security->logoutStart("../index.php","portal");
    } else {
      $IMP->security->logout();
      $_SESSION['userId'] = 0;
      redirect('index.php');
      return;
    }
  }
  if(isset($_REQUEST["error"])) {/*echo "si &egrave verificato un errore.";*/}
}

if ($_REQUEST['username'] && $IMP->security->login($_REQUEST['username'], $_REQUEST['password'])){
  $_SESSION['userId'] = $IMP->security->userId;
  if ($_REQUEST['go']) redirect($_REQUEST['go']);
  else redirect('index.php');
  return;
}
if ($_REQUEST['action'] == 'logout'){
  $IMP->security->logout();
  $_SESSION['userId'] = 0;
  redirect('index.php');
  return;
}

if ($_REQUEST['username'] && $IMP->security->login($_REQUEST['username'], $_REQUEST['password']) || ($IMP->security->loginOperatoreNew($_REQUEST['username'], $_REQUEST['password']))){
  $_SESSION['userId'] = $IMP->security->userId;
  if ($_REQUEST['go']) redirect($_REQUEST['go']);
  else redirect('index.php');
  return;
}
require_once('top.php');
?>

<div id="content">
  <div class="login-page">

    <h3>Administration</h3>

    <form action="<?=$_SERVER['PHP_SELF']?>" method="POST">
      <div class="error" role="alert">
	<?=$_REQUEST['login']?"Login errata":""?>
      </div>
      <div class="row">
	<div class='col-md-12'>
	<label for="login">Login:</label>
	<input type="text" name="username" id="username" value="" class='form-control'>
	</div>
      </div>
      <div class="row">
	<div class='col-md-12'>
	<label for="password">Password:</label>
	<input type="password" name="password" id="password" class='form-control'>
	</div>
      </div>
      <div class="row">
	<div class='col-md-12'>
	<input type="submit" name="submit" value="Login">
	</div>
      </div>
      <div class="row">
	<?
	    if($C['portal']['start_auth']['show_buttons']===true) {
              foreach($C['portal']['start_auth']['auth_type'] as $auth) {
                echo "<a href='login.php?login&auth={$auth}' class='btn btn-success btn-azure btn-block'><i class='fa fa-{$C['portal']['start_auth']['buttons'][$auth]['icon']}' aria-hidden='true'></i> {$C['portal']['start_auth']['buttons'][$auth]['label']}</a><br>";
              }
            } else if($C['portal']['start_auth']['show_buttons']!==false) {
              $auth = $C['portal']['start_auth']['show_buttons'];
              echo "<a href='login.php?login&auth={$auth}' class='btn btn-success btn-azure btn-block'><i class='fa fa-{$C['portal']['start_auth']['buttons'][$auth]['icon']}' aria-hidden='true'></i> {$C['portal']['start_auth']['buttons'][$auth]['label']}</a><br>";
            } ?>
      </div>
    </form>
  </div>
</div>
<?
require_once('bottom.php');
?>

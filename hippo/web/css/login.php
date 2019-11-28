<?
include_once('../init.php');
#$IMP->debugLevel = 3;
if ($_REQUEST['login'] && $IMP->security->login($_REQUEST['login'], $_REQUEST['password'])){
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
require_once('top.php');
?>

<div id="content">
  <div class="login-page">

    <h3>Administration</h3>

    <form action="<?=$_SERVER['PHP_SELF']?>" method="POST">
      <div class="error alert alert-warning alert-dismissible" role="alert">
	<p><?=$_REQUEST['login']? print "Utente o Password errati": "E' necessario autenticarsi per poter proseguire"?></p>
      </div>
      <div class="row">
	<div class='col-md-12'>
	<label for="login">Username:</label>
	<input type="text" name="login" id="login" value="" class='form-control'>
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
	<input type="submit" name="submit" value="Accedi">
	</div>
      </div>
    </form>
  </div>
</div>


<!--<br><br><br>
<div class="smallform center">
    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
        <p><label for='login'>Username</label><input type='text' id='login' name='login'></p>
        <p><label for='password'>Password</label><input type='password' id='password' name='password'></p>
        <p class="error"><?=$_REQUEST['login']? print "Utente o Password errati": "E' necessario autenticarsi per poter proseguire"?></p>
        <div class='buttons'>
            <input type="submit" value="Accedi" class="button">
        </div>
    </form>
</div>    -->
<?
require_once('bottom.php');
?>

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
include_once(PATH_APP_PORTAL.'/admin/top.php');
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
	<input type="submit" name="submit" value="Login">
	</div>
      </div>
    </form>
  </div>
</div>
<?
include_once(PATH_APP_PORTAL.'/admin/bottom.php');
?>

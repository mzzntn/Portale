<?
  include_once('init.php');
  //$IMP->debugLevel = 3;
  include_once(PATH_APP_PORTAL.'/portal_top.php');
  //include_once(PATH_APP_PORTAL.'/portal_right.php');
  //include_once(PATH_APP_PORTAL.'/portal_left.php');
#  include_once('utente.php');
?> 
  <div style='text-align:center'>
  <div style='margin: auto; text-align: left; width: 25em;'>
        <p>
            Per proseguire è necessario effettuare il login: 
        <form class="smallform" method="post" action="<?=URL_APP_PORTAL_AUTH?>/index.php">
        <input type='hidden' name='go' value='<?=$_REQUEST['go']?>'>
        <p><label for='login'>Utente</label><input type='text' id='login' name='login' value="" size="16"/></p>
        <p><label for='password'>Password</label><input type='password' id='password' name='password' value="" size="16"/></p>
        <div class="buttons"><input class="button" type="submit" value="Accedi"></div>
    </form> 
<?
    if ($_REQUEST['login'] && $_REQUEST['password']){
    if (!$IMP->security->login($_REQUEST['login'], $_REQUEST['password'])){
?>
<p class="error">Utente o Password errati</p>
<? 
    }
  }
?>  
</div>
</div>
<? 
if ($_SERVER['HTTPS'] == 'on'){
?>
<script src="https://ssl.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-3255602-2";
urchinTracker();
</script>
<?
}
?>
<?
include_once(PATH_APP_PORTAL.'/portal_bottom.php');
?>

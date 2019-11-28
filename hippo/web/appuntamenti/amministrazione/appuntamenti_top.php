<?
#### check-in Utente
// check Amministratore
if ($IMP->security->checkAdmin()) $admin = true;

// check Utente e raccolta dati

### creazione pagina
include_once(PATH_APP_PORTAL.'/portal_top_new.php');
if(!$C["portal"]["spider_portal"]){?>
<script type='text/javascript' src='<?=URL_APP_APPUNTAMENTI?>/js/jquery.ui.datepicker-it.js'></script>
<?}?>

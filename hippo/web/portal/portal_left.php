<!-- left column !-->
<div id="leftcolumn" class="column menu">  
<?
$serviziPubblici = & $IMP->getWidget('portal::ServiziPubblici');
$serviziPubblici->display();
include_once('utente.php');
?>
</div>
<!-- /left column !-->

<?
if ($C['portal']['spider_portal']){
    readfile(VARPATH.'/spider_portal_bottom');    
    return;
}
if (is_array($siti)) foreach(array_keys($siti) as $i){
  if ($siti[$i]['loginFunc']) print "<script>".$siti[$i]['loginFunc']."</script>";
  if ($siti[$i]['loginForm']) print $siti[$i]['loginForm'];
}

?>
    <!-- bottom links -->
    <div id="bottomlinks">
        <ul>
            <li class="leftCorner"><a href="#top">Torna Su</a></li>
        </ul>     
    </div>
    <!-- /bottom links -->
    <!-- footer -->
    <div id="footer"> 
        <div style="float: right; padding: 10px;">
            <a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01 Strict" height="31" width="88"></a><br>
            <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://<?=SERVER.$_SERVER['PHP_SELF'].'?'.$sqs?>"><img src="http://www.w3.org/Icons/valid-css" alt="Valid CSS" height="31" width="88"></a>        
        </div>
        <div>
            <p>Copyright &copy; <?=date('Y')?> by <?=$C['ente']['nome_ente']?> - <?=$C['ente']['indirizzo']?></p>
            <ul>
                <li><span class="blue"><strong>E-MAIL </strong><a href='mailto:<?=$C['ente']['email_ente']?>'><?=$C['ente']['email_ente']?></a></span></li>
                <li>
                    <span class="blue">
                        <strong>TELEFONO </strong><?=$C['ente']['telefono']?>
<?php
if ($C['ente']['fax']){
?>
                        <strong>FAX </strong><?=$C['ente']['fax']?>
<?
}
?>                    
                    </span>
                </li>
<?php
if ($C['ente']['partita_iva']){
?>
                <li><span class="blue"><strong>PARTITA IVA </strong><?=$C['ente']['partita_iva']?></span></li>     
<?php
}
?>
            </ul>
            <br/><p>Sono utilizzati cookie Tecnici di sessione per consentire l'autenticazione</p>
        </div>                
    </div>       
    <!-- /footer -->

</div>
<!-- /this is da page -->
</body>
</html>

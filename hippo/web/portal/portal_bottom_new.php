<? 
if ($C['portal']['spider_portal']) {
  // utilizza header e footer di spider
  if((isset($_SESSION["layout"]) && $_SESSION["layout"]==false)|| isset($_GET["temp_layout"])) {
    echo preg_replace('/<footer((?!<\/footer>).+)<\/footer>/s', "", file_get_contents(VARPATH.'/spider_portal_bottom'));
  } else {
    readfile(VARPATH.'/spider_portal_bottom');
  }
}
else {
  // utilizza header e footer di php
  if (is_array($siti)) foreach(array_keys($siti) as $i){
    if ($siti[$i]['loginFunc']) print "<script>".$siti[$i]['loginFunc']."</script>";
    if ($siti[$i]['loginForm']) print $siti[$i]['loginForm'];
  }

?>

	      </div>
	    </div>
	  </section>
	  
	  <footer id="portal_bottom" class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
	    <div id="hide_footer_background">
	      <div id="centered_content" class="lead text-center">
		<?=$C['ente']['nome_ente']?>
		<?=$C['ente']['indirizzo']?>
		Telefono <?=$C['ente']['telefono']?> - Fax <?=$C['ente']['fax']?> - P.Iva <?=$C['ente']['partita_iva']?>
	      </div>
	    </div>  
	    <div id="visible_footer">
	      <div class="row">
		<div id="link_copy" class="col-lg-offset-2 col-lg-3 col-md-offset-1 col-md-3 col-sm-4 col-xs-4">
		  <span id="copy_intero">&copy; <?=date('Y')?> <?=$C['ente']['nome_ente']?></span>
		  <span id="copy_logo">>&copy; <?=date('Y')?> <?=$C['ente']['nome_ente']?></span>
		</div>

		<div id="social_links" class="col-lg-3 col-md-3 col-sm-3 col-xs-3">

		</div>
		<div id="mail_link" class="col-lg-3 col-md-3 col-sm-3 col-xs-2">
		  <a class="email_contact" href="mailto:<?=$C['ente']['email_ente']?>?subject=Richiesta Informazioni">
		  <i class="fa fa-envelope-o"></i> <span id="testo_mail"><?=$C['ente']['email_ente']?></span></a>
		</div>

		<div class="col-lg-1 col-md-1 col-sm-1 col-xs-1 back-to-top">
		  <a href="#"><i class="fa fa-chevron-circle-up fa-2x"></i></a>
		</div>
	      </div>
	    </div>

	  </footer>
    
	</div>
      </div>
    </div>
  </body>
</html>

<?
}
?>

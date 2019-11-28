<?php
//include_once('../../../../init.php');
//include_once(dirname(__FILE__).'/../../../../init.php');
include_once(base64_decode($_GET["c"])."/init.php"); // passo il path di installazione codificato in base64 per renderlo un po' meno visibile
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Inserimento Link Pagina Interna</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
   	<script type="text/javascript" src="js/filterlist.js"></script>
	<script type="text/javascript" src="js/dialog.js"></script>
</head>
<body>

<form onsubmit="InternalinkDialog.insert();return false;" action="#">
	<p>Per creare un <strong>link alle pagine interne</strong> basta selezionare il titolo della pagina a cui fare riferimento e inserirla.</p>
	<p>Titolo della pagina a cui far riferimento:</p>

	<?	
    if (defined('URL_APP_CARICAMENTO_PRATICHE') && false){
        $struttura = 'caricamento_pratiche::documentazione';
        $url = URL_APP_CARICAMENTO_PRATICHE.'/documentazione.php';
    }
    elseif(defined('URL_APP_TRASPARENZA')){
	$struttura = 'trasparenza::pagina';
	$url = URL_APP_TRASPARENZA.'/pagina.php';
    }
    else{
        $struttura ='cms::pagina';
        $url = URL_APP_CMS.'/pagina.php';
    }
	$loader = & $IMP->getLoader($struttura);
	$loader->requestAll();
	$pagina = $loader->load();
	?>
		<select name="link_id" size="10" style="width: 98%">
	<?
	while ($pagina->moveNext()){
		$idPagina = $pagina->get('id');
		$titolo = $pagina->get('titolo');
	?>
		<option value="<?=$url?>?id=<?=$idPagina?>"><?=$titolo?></option>
	<?
	}	
	?>
		</select>
	<br><br>
		
		<div>Filtro:
			<input type="button" value="Reset" onclick="InternalinkDialog.reset();" />

<?
	$alfabeto = array( "A", "B", "C", "D", "E", "F" ,"G" ,"H", "I", "L", "M", "N", "O", "P", "Q", "R", "S", "T" ,"U", "V", "Z");
	// Inserimento di tutte le lettere per il filtraggio
	foreach($alfabeto as $value) {
?>
			<input type="button" value="<?=$value?>" onclick="InternalinkDialog.sort('^<?=$value?>');" />
		
<?		
	}
?>
		</div><br>
		<div>Filtro Espressione Regolare:
			<input name="regexp" onkeyup="InternalinkDialog.regexp_set(this.value)">
			<input onclick="InternalinkDialog.regexp_clear();" value="Clear" type="button">
		</div>
		<br><br>
	
		<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="insert" name="insert" value="{#insert}" onclick="InternalinkDialog.insert();" />
		</div>

		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
		</div>
		
	</div>
</form>
</p>
</body>
</html>

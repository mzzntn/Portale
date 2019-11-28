<? 
include_once('../init.php');

if ($_GET['aggiornaSetup']){
 print "<p>Aggiorno setup db:<br>";
 $jsonSetup = file_get_contents('http://app.soluzionipa.it/openweb/clienti/services/');
 $arraySetup = json_decode($jsonSetup, true);
 $cnt = 0;
 foreach ($arraySetup as $c) {
  if ($c['applicazione'] == 'ente' && $c['codice'] == 'nome') $c['codice'] = 'nome_ente';
  $loader = & $IMP->getLoader('setup');
  $loader->addParam('applicazione', $c['applicazione']);
  $loader->addParam('codice', $c['codice']);
  $d = $loader->load();
  if (!$d->get('id')){
      $storer = & $IMP->getStorer('setup');
      $storer->set("applicazione", $c['applicazione']);
      $storer->set("codice", $c['codice']);
      $storer->set("accesso", $c['accesso']);
      $storer->set("tipo", $c['tipo']);
      $storer->set("valoreDefault", $c['valoreDefault']);     
      $storer->set("note", $c['note']); 
      $storer->store();
      echo "inserito ".$c['applicazione'].".".$c['codice']."</p>";
  }
  $cnt++;
 }
 echo "Finito, processati {$cnt} codici";
}
elseif ($_GET['attivaSetupDB']){
 print "<p>Attiva setup su DB<br>";
 $loader = & $IMP->getLoader('setup');
 $l = $loader->load();
 $cnt = 0;
 while ($l->moveNext()){
   if(isset($IMP->appconf[$l->get('applicazione')]) && isset($IMP->appconf[$l->get('applicazione')][$l->get('codice')])) {
      $value = $IMP->appconf[$l->get('applicazione')][$l->get('codice')];
      if(is_array($value)) {  
  $value = json_encode($value);
      }
      $storer = & $IMP->getStorer('setup');
      $storer->checkMode("applicazione");
      $storer->checkMode("codice");      
      $storer->set("applicazione",$l->get('applicazione'));
      $storer->set("codice",$l->get('codice'));
      $storer->set("valore",$value);
      $storer->store();
      $cnt++;
   }
 } 
 $storer = & $IMP->getStorer('setup');
 $storer->checkMode("applicazione");
 $storer->checkMode("codice");
 $storer->set("applicazione","setupDB");
 $storer->set("codice","hippo");
 $storer->set("valore", "1");
 $storer->store();
 echo "Setup su DB attivo, riportati su file {$cnt} valori</p>";
}
elseif($_GET['tabelle']){
	$campoId = array(appuntamenti__prenotazione, benefaccessocivico, benefallegato, benefdocumentazione, benefpartecipante, benefprocesso, benefretribuzione, benefrilevazione, benefversato, caricamento_pratiche__documentazione, caricamento_pratiche__domandeallegato, caricamento_pratiche__domandepagamento, caricamento_pratiche__domandeproc, caricamento_pratiche__modelloautogenerato, caricamento_pratiche__modellomodulo, caricamento_pratiche__modellomoduloevento, caricamento_pratiche__impostazioni, commissione__odg, commissione__odgfiles, delibere_b__allegato, delibere_b__catasto, delibere_b__cig, delibere_b__comunicazionedocumento, delibere_b__export, delibere_b__file, delibere_b__indirizzo, delibere_b__iter, delibere_b__metadato, delibere_b__onere, delibere_b__provv, delibere_b__referente, delibere_b__trasparenza, gi_tab_attpra, ii_cat_catas, ii_tab_aliq, ii_tab_anno, ii_tab_attrib, ii_ver_ver, imudettdic, imudichiarazione, iciversato, documenti, imuf24, m1_master, ii_attributi_categ, imudettdic, imudichiarazione, imuimmobile, imutasi, imuversato, logcalcolo, m1_tab_uff, m1_tabgi_carpra, m1_tabpr_alleg, messenger__email_queue, messenger__email_sms, tarsu__exp_tarsu, tarsu__m1_tab_att, tarsu__m1_tab_cat, tarsu__m1_tab_rid_perc, tarsu__m1_tab_riduz, tarsu__m1_tab_rps, tarsu__v_extr_f24pag, tarsu__v_extr_ratef24, tarsu__v_extr_ruoli, tarsu__v_extr_sgravi, tarsu__v_extr_ubicateg, tarsu__v_extr_ubicazioni, tasif24, tasiimmobile, tasirate, tasiversato, ts_aliquote, ts_attributi, ts_attributi_categ, ts_dati_annuali, ts_fasce_detr, ts_tab_f24, v_ow_md_cat_template, v_owcp_albo, v_owcp_alleg, v_owcp_applicazioni, v_owcp_classi_ot, v_owcp_comuni, v_owcp_defpra, v_owcp_dest_uso, v_owcp_docproc, v_owcp_ente, v_owcp_md_cat_template, v_owcp_md_categorie, v_owcp_md_datasource, v_owcp_md_template, v_owcp_md_values, v_owcp_nazioni, v_owcp_pracla, v_owcp_praref, v_owcp_refer, v_owcp_tab_vincoli, v_owcp_tipi_evento, v_owcp_tipi_intervento, v_owcp_tippra, v_owcp_vie, v_owcp_zone, v_owcp_zone_sottozone, albo__m1_tab_uff, albo__ms_affis, albo__ms_allegato, wf_tab_docpra);
	
	$campoId_tab = array(wf_tab_accessoatti, wf_tab_alleg, wf_tab_att, wf_tab_metadata, wf_tab_obj, wf_tab_pag, wf_tab_pra, wf_tab_prog, wf_tab_ref, wf_tab_risposta, wf_tab_vincolo, wf_tab_zona_sottozona);

    $campoNprog = array (ms_affis, ms_casacom, ms_notif);

	$campoIdalleg = array(ms_allegato);

	$nonUsate = array(gi_tab_praobj, gi_tab_provv, gi_ref_prat, m1_ana_catas, m1_comuni, m1_indir, m1_tab_centrocos, m1_tab_vie, ii_dic_ctit, ii_dic_dich, ii_dic_immob, ii_file1, ii_file2, ii_file3, ii_numero_figli, ii_pertinenze, m1_tabgi_pracla, m1_tabgi_praref, m1_tabgi_tippra, m1_tabgi_tippro, m1_tabot_catas, m1_tabot_dim, m1_tabot_obj, m1_tabot_tipdes, m1_trib_anag, m1_tabpr_prat, m1_trib_anag);
	
	$chiaviId = array(appuntamenti__chiusura, appuntamenti__operatore, appuntamenti__operatore_ref_appuntamenti__tipo, appuntamenti__orario, appuntamenti__stato, appuntamenti__tipo, benefattonomina, benefbeneficio, benefcosafareper, benefcosafareper_ref_benefici__procedimento, benefdipendente, benefdipendente_ref_benefici__ufficio, benefgruppo, benefici__cosafareper_ref_benefprocedimento, benefici__dipendente_ref_benefufficio, benefici__responsabile_ref_benefprocedimento, benefimpostazioni, benefincarico, benefmodalita, benefmodulistica, benefnormativa, benefperiodo, benefpolitico, benefprocedimento, benefresponsabile, benefresponsabile_ref_trasparenza__retribuzione, benefruolo, benefsettore, benefsospensione, beneftipo, benefufficio, caricamento_pratiche__categoriadocumentazione, caricamento_pratiche__domanda, caricamento_pratiche__flagallegati, caricamento_pratiche__macrocategoriadocumentazione, caricamento_pratiche__risposta, caricamento_pratiche__tipikit, caricamento_pratiche__tipologiekit, commissione__commissione, commissione__componente, commissione__seduta, delibere_b__anno, delibere_b__annoprovv, delibere_b__comunicazione, delibere_b__esito, delibere_b__pratica, delibere_b__tecnico, delibere_b__tipopratica, delibere_b__tipoprovv, delibere_b__tiporef, delibere_b__ufficio, trasparenza__cosafareper, trasparenza__pagina, trasparenza__ruolo, trasparenza__settore, trasparenza__sezionipagina, operatore, setup);
	
	$loader = & $IMP->getLoader('setup');
	$bindingSetup = $IMP->bindingManager->getBinding('setup');
	$db = $bindingSetup->getDbObject();
	
	$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = '{$config['defaultdb']['name']}' AND TABLE_TYPE LIKE '%TABLE%';";
	$result = mysql_query($sql);
	print $config['defaultdb']['name'];
	while ($row = mysql_fetch_row($result)) {
		$table = $row[0];
		echo "Table: {$table}";
		if (in_array($table, $campoId)){
			$sqlId = "select * from INFORMATION_SCHEMA.COLUMNS where table_name='{$table}' and table_schema='{$config['defaultdb']['name']}' and column_name='ID' and EXTRA like '%auto_increment%'";
			$db->execute($sqlId);
			if (!$db->fetchrow()){
				$db->execute("ALTER TABLE `{$table}` DROP ID");
				$db->execute("ALTER TABLE `{$table}` ADD `ID` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY(`ID`)");
				echo " - creato auto increment";
			}
			else " - a posto!";
	    }
		elseif (in_array($table, $campoId_tab)){
			$sqlIdTab = "select * from INFORMATION_SCHEMA.COLUMNS where table_name='{$table}' and table_schema='{$config['defaultdb']['name']}' and column_name='ID_TAB' and EXTRA like '%auto_increment%'";
			$db->execute($sqlIdTab);
			if (!$db->fetchrow()){
				$sqlV = "SHOW INDEXES FROM {$table} WHERE Key_name = 'PRIMARY'";
				$db->execute($sqlV);
				if (!$db->fetchrow()){
					$db->execute("ALTER TABLE `{$table}` ADD PRIMARY KEY(`ID_TAB`)");
					if(mysql_error()) die(" - Attenzione: si &egrave; verificato un errore fatale sull'aggiunta della chiave primaria. ".mysql_error());
					else echo "<br>{$table} has a primary key"."\n";
				}
				$sqlAI = "ALTER TABLE $table CHANGE ID_TAB ID_TAB INT(11) NOT NULL AUTO_INCREMENT";
                $db->execute($sqlAI);
			}
			else " - a posto!";
	    }
		elseif (in_array($table, $campoNprog)){
			$sqlNprog = "select * from INFORMATION_SCHEMA.COLUMNS where table_name='{$table}' and table_schema='{$config['defaultdb']['name']}' and column_name='NPROG' and EXTRA like '%auto_increment%'";
			$db->execute($sqlNprog);
			if (!$db->fetchrow()){
				$db->execute("ALTER TABLE `{$table}` ADD PRIMARY KEY(`NPROG`)");
				$sqlV = "SHOW INDEXES FROM {$table} WHERE Key_name = 'PRIMARY'";
				$db->execute($sqlV);
				if (!$db->fetchrow()){
					if(mysql_error()) die(" - Attenzione: si &egrave; verificato un errore fatale sull'aggiunta della chiave primaria. ".mysql_error());
					else echo "<br>{$table} has a primary key"."\n";
				}
				$sqlAI = "ALTER TABLE $table CHANGE NPROG NPROG INT(11) NOT NULL AUTO_INCREMENT";
                $db->execute($sqlAI);
			}
			else " - a posto!";
	    }
		elseif (in_array($table, $campoIdalleg)){
			$sqlIdalleg = "select * from INFORMATION_SCHEMA.COLUMNS where table_name='{$table}' and table_schema='{$config['defaultdb']['name']}' and column_name='IDALLEG' and EXTRA like '%auto_increment%'";
			$db->execute($sqlIdalleg);
			if (!$db->fetchrow()){
				$sqlV = "SHOW INDEXES FROM {$table} WHERE Key_name = 'PRIMARY'";
				$db->execute($sqlV);
				if (!$db->fetchrow()){
					$db->execute("ALTER TABLE `{$table}` ADD PRIMARY KEY(`IDALLEG`)");
					if(mysql_error()) die(" - Attenzione: si &egrave; verificato un errore fatale sull'aggiunta della chiave primaria. ".mysql_error());
					else echo "<br>{$table} has a primary key"."\n";
				}
				$sqlAI = "ALTER TABLE $table CHANGE IDALLEG IDALLEG INT(11) NOT NULL AUTO_INCREMENT";
                $db->execute($sqlAI);
			}
			else " - a posto!";
	    }
		elseif(in_array($table, $nonUsate)){
			$sqlD = "drop table {$table}";
			$db->execute($sqlD);
			echo $table. " - cancellata";
	    }
		elseif(in_array($table, $chiaviId)){
			$sqlId = "select * from INFORMATION_SCHEMA.COLUMNS where table_name='{$table}' and table_schema='{$config['defaultdb']['name']}' and column_name='ID' and EXTRA like '%auto_increment%'";
			$db->execute($sqlId);
			if (!$db->fetchrow()){
				$sqlV = "SHOW INDEXES FROM {$table} WHERE Key_name = 'PRIMARY'";
				$db->execute($sqlV);
				if (!$db->fetchrow()){
					$db->execute("ALTER TABLE `{$table}` ADD PRIMARY KEY(`ID`)");
					if(mysql_error()) die("Attenzione: si &egrave; verificato un errore fatale sull'aggiunta della chiave primaria. ".mysql_error());
					else echo "<br>{$table} has a primary key"."\n";
				}
				$sqlAI = "ALTER TABLE $table CHANGE ID ID INT(11) NOT NULL AUTO_INCREMENT";
                $db->execute($sqlAI);
			}
			else " - a posto!";
	    }
		else{
			echo " - ignoro";
	    }
		echo "<br>";
	}

}

if (!$IMP->security->checkAdmin()) redirect('login.php');

include_once(PATH_APP_PORTAL.'/admin/top.php');

if ($config['nosequenze']){

  $loader = & $IMP->getLoader('setup');
  $loader->addParam('applicazione', "setupDB");
  $loader->addParam('codice', "hippo");
  $setupDb = $loader->load();
  if ($setupDb->get('valore') == 1) $stato = "Attivo";
  else $stato = "Non attivo";

  print "<hr>";
  print "SETUP SU DATABASE:";
  print "<ul>";
  print "<li>Stato: $stato</li>";
  print "<li><a href='{$_SERVER['PHP_SELF']}?aggiornaSetup=1'>Aggiorna definizioni setup</a></li>";
  print "<li><a href='{$_SERVER['PHP_SELF']}?tabelle=1'>Crea primary key e autoincrement</a></li>";
  if ($stato == 'Non attivo' && ($setupDb->listSize() > 0)) print "<li><a href='{$_SERVER['PHP_SELF']}?attivaSetupDB=1'>Attiva setup du db</a></li>";
  if ($stato == 'Attivo') print "<li><a href='".HOME."/admin/tabelle.php'>Gestione valori setup</a></li>";
  print "</ul><hr>";

}

include_once(PATH_APP_PORTAL.'/admin/bottom.php');
?>


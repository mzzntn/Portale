<?

function email_conferma($id_prenotazione){
  global $IMP;
  global $C;

  $loader = & $IMP->getLoader('appuntamenti::prenotazione');
  $loader->addParam('id', $id_prenotazione);
  $loader->request('tipo.tipo');
  $loader->requestAll();
  $p = $loader->load();
  $p = $p->getRow();
  $inizio = & dt_DateTime();
  $inizio->fromISO($p->inizio);
  $fine = & dt_DateTime();
  $fine->fromISO($p->fine);
  $giorno = $inizio;
  $giorno->clearTime();
  $giorno = dateToUser($giorno->toISO());
  $oraInizio = substr($inizio->data,11,2).":".substr($inizio->data,14,2);
  $oraFine = substr($fine->data,11,2).":".substr($fine->data,14,2);

  $loader = & $IMP->getLoader('appuntamenti::operatore');
  $loader->addParam('id', $p->operatore);
  $loader->requestAll();
  $tipo = $p->get('tipo.tipo');
  $o = $loader->load();
  $from = $o->get('email');
  $testo_email = "Gentile {$p->persona}, il suo appuntamento per {$tipo} il giorno {$giorno}, dalle ore {$oraInizio} alle ore {$oraFine} e' stato confermato.\n\n
  Distinti saluti, l'amministrazione comunale.";
	$subject = "Conferma appuntamento - {$p->get('tipo.tipo')}";
	$headers = "From: ".$from."\r\n";
	#$headers = 'MIME-Version: 1.0' . "\r\n";
	#$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	mail($p->email, $subject, $testo_email, $headers);

       if ($C['appuntamenti']['stato_richieste_default'] == 2){
       	   $subject = "Appuntamento {$p->persona} - {$giorno} - {$oraInizio} - {$oraFine} - {$p->get('tipo.tipo')}";
           $headers = "From: ".$from."\r\n";
           $testo_email = "E' stata appena inviata conferma dell'appuntamento in oggetto al seguente indirizzo {$p->email}.\n\nNote utente: {$p->note}";
           mail($from, $subject, $testo_email, $headers);
       }
}

function email_rifiuto($id_prenotazione, $note='' , $tipo){
  global $IMP;
  global $C;

  if ($ipo == 'R'){
     $labelOggetto = 'Disdetta appuntamento';
     $labelStato = 'rifiutato';
  }
  else{
     $labelOggetto = 'Annullamento appuntamento';	
     $labelStato = 'annullato';
  }

  $loader = & $IMP->getLoader('appuntamenti::prenotazione');
  $loader->addParam('id', $id_prenotazione);
  $loader->request('tipo.tipo');
  $loader->requestAll();
  $p = $loader->load();
  $p = $p->getRow();
  $inizio = & dt_DateTime();
  $inizio->fromISO($p->inizio);
  $fine = & dt_DateTime();
  $fine->fromISO($p->fine);
  $giorno = $inizio;
  $giorno->clearTime();
  $giorno = dateToUser($giorno->toISO());
  $oraInizio = substr($inizio->data,11,2).":".substr($inizio->data,14,2);
  $oraFine = substr($fine->data,11,2).":".substr($fine->data,14,2);

  $loader = & $IMP->getLoader('appuntamenti::operatore');
  $loader->addParam('operatore', $p->operatore);
  $loader->requestAll();
  $o = $loader->load();
  $tipo = $p->get('tipo.tipo');
  $from = $o->get('email');
  $testo_email = "Gentile {$p->persona}, il suo appuntamento per il giorno {$giorno}, dalle ore {$oraInizio} alle ore {$oraFine} e' stato {$labelStato}";
  if ($note) $testo_email .= ":\r\n ".$note;
  else $testo_email .= ".\r\n Ci scusiamo per l'inconveniente.";
  $testo_email .= "\r\n\r\nDistinti saluti, l'amministrazione comunale.";
  $subject = $labelOggetto;
  $headers = "From: ".$from."\r\n";

  #$headers = 'MIME-Version: 1.0' . "\r\n";
  #$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
  mail($p->email, $subject, $testo_email, $headers);
}

function email_annullamento($id_prenotazione, $note){
  global $IMP;
  global $C;

  $loader = & $IMP->getLoader('appuntamenti::prenotazione');
  $loader->addParam('id', $id_prenotazione);
  $loader->request('tipo.tipo');
  $loader->requestAll();
  $p = $loader->load();
  $p = $p->getRow();
  $inizio = & dt_DateTime();
  $inizio->fromISO($p->inizio);
  $fine = & dt_DateTime();
  $fine->fromISO($p->fine);
  $giorno = $inizio;
  $giorno->clearTime();
  $giorno = dateToUser($giorno->toISO());
  $oraInizio = substr($inizio->data,11,2).":".substr($inizio->data,14,2);
  $oraFine = substr($fine->data,11,2).":".substr($fine->data,14,2);
  $loader = & $IMP->getLoader('appuntamenti::operatore');
  $loader->addParam('id', $p->operatore);
  $loader->requestAll();
  $tipo = $p->get('tipo.tipo');
  $o = $loader->load();
  $mailO = $o->get('email');
  $testo_email = "L'appuntamento di {$p->persona} per {$tipo} del giorno {$giorno}, dalle ore {$oraInizio} alle ore {$oraFine} e' stato annullato dall'utente con la seguente motivazione: {$note}.\n\n
  E' stata ripristinata la disponbilita' nel calendario.";
        $subject = "Annullamento appuntamento - {$tipo} - {$giorno} {$oraInizio} - {$oraFine}";
        $headers = "From: ".$p->email."\r\n";
        #$headers = 'MIME-Version: 1.0' . "\r\n";
        #$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        mail($mailO, $subject, $testo_email, $headers);
}
?>

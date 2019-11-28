<?

function email_conferma($id_prenotazione){
  global $IMP, $C;
  $loader = & $IMP->getLoader('appuntamenti::prenotazione');
  $loader->addParam('id', $id_prenotazione);
  $loader->requestAll();
  $loader->request('operatore.nome');
  $loader->request('operatore.email');
  $loader->request('tipo.tipo');
  $loader->request('tipo.durata');
  $p = $loader->load();
  $p->moveNext();
  $inizio = & dt_DateTime();
  $inizio->fromISO($p->get("inizio"));
  $fine = & dt_DateTime();
  $fine->fromISO($p->get("fine"));
  $giorno = $inizio;
  $giorno->clearTime();
  $giorno = dateToUser($giorno->toISO());
  $oraInizio = substr($inizio->data,11,2).":".substr($inizio->data,14,2);
  $oraFine = substr($fine->data,11,2).":".substr($fine->data,14,2); 
  $durata = $p->get('tipo.durata');
  
  //descrizione: Comune di XX - Opertatore XX - tipo prenotazione
  $mailData = array(
    "nome mittente" => 
      "Comune di ".$C['ente']['nome_ente']." - ".$p->get("operatore.nome"),
    "email mittente" => 
      $p->get("operatore.email"),
    "nome destinatario" => 
      $p->get("persona"),
    "email destinatario" => 
      $p->get("email"),
      // TEST
      //"andrea.grazian@soluzionipa.it",
    "oggetto email" => 
      "Conferma appuntamento ".$p->get("tipo.tipo"),
    "messaggio email" => 
      "<p>Gentile ".$p->get("persona").", il suo appuntamento per ".$p->get("tipo.tipo")." il giorno {$giorno}, alle ore {$oraInizio} (durata {$durata} minuti) &egrave; stato confermato.</p><p>Distinti saluti, l'amministrazione comunale.</p>",
    "data/ora appuntamento" => 
      $p->get("inizio"),
    "durata appuntamento" => 
      $p->get("tipo.durata"),
    "descrizione appuntamento" => 
      "Comune di ".$C['ente']['nome_ente'].": Appuntamento per ".$p->get("tipo.tipo")." - ".$p->get("operatore.nome").($p->get("note")?" - Note: ".$p->get("note"):""),
    "oggetto appuntamento" => 
      "Appuntamento per ".$p->get("tipo.tipo")
  );
  
  sendIcalEmail($mailData);
  
  if ($C['appuntamenti']['stato_richieste_default'] == 2){        
    $mailData = array(
      "nome mittente" => 
	$p->get("persona"),
      "email mittente" => 
	$p->get("email"),
      "nome destinatario" => 
	$p->get("operatore.nome"),
      "email destinatario" => 
	$p->get("operatore.email"),
	// TEST
	//"andrea.grazian@soluzionipa.it",
      "oggetto email" => 
	"Appuntamento ".$p->get("persona")." - ".$p->get("tipo.tipo")." - {$giorno} - {$oraInizio} - {$oraFine}",
      "messaggio email" => 
	"<p>&Egrave; stata appena inviata conferma dell'appuntamento in oggetto al seguente indirizzo ".$p->get("email").".</p><p>Note utente: ".$p->get("note")."</p>",
      "data/ora appuntamento" => 
	$p->get("inizio"),
      "durata appuntamento" => 
	$p->get("tipo.durata"),
      "descrizione appuntamento" => 
	$p->get("persona")." - ".$p->get("tipo.tipo").($p->get("note")?" - Note: ".$p->get("note"):""),
      "oggetto appuntamento" => 
	$p->get("persona")." - ".$p->get("tipo.tipo")
    );
    
    sendIcalEmail($mailData);
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
     $labelOggetto = 'Annuallamento appuntamento';
     $labelStato = 'annullato';
  }

  $loader = & $IMP->getLoader('appuntamenti::prenotazione');
  $loader->addParam('id', $id_prenotazione);
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
  $oraInizio =  substr($inizio->data,11,2).":".substr($inizio->data,14,2);
  $oraFine = substr($fine->data,11,2).":".substr($fine->data,14,2);
  $loader = & $IMP->getLoader('appuntamenti::operatore');
  $loader->addParam('id', $p->operatore);
  $loader->requestAll();
  $o = $loader->load();
  $from = $o->get('email');
  $testo_email = "Gentile {$p->persona}, il suo appuntamento per il giorno {$giorno}, delle ore {$oraInizio} e' stato {$labelStato} per il seguente motivo";
  if ($note) $testo_email .= ":\r\n ".$note;
/* Personalizzazione per Alessandria
  else $testo_email .= ".\r\n Ci scusiamo per l'inconveniente.";
  $testo_email .= "\r\n\r\nDistinti saluti, l'amministrazione comunale.";*/
  $testo_email .= "\r\n\r\nDistinti saluti.";
  $testo_email .= "\r\nCittà di Alessandria - Settore Servizi Demografici\r\n\r\nContatti\r\nPer la carta di identità: anagrafe@comune.alessandria.it\r\nPer il riconoscimento di cittadinanza: ufficio.cittadinanze@comune.alessandria.it";
#fine personalizzazione
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

function sendIcalEmail($mailData) {
  global $C;
  if(is_array($C['ente']['indirizzo'])) {
    $meeting_location = implode ( " " , $C['ente']['indirizzo'] ); //Where will your meeting take place
  } else {
    $meeting_location = $C['ente']['indirizzo'];
  }
  
  //Convert MYSQL datetime and construct iCal start, end and issue dates
  $meetingstamp = strtotime($mailData["data/ora appuntamento"] . " UTC");    
  $dtstart= gmdate("Ymd\THis",$meetingstamp);
  $dtend= gmdate("Ymd\THis",$meetingstamp+$mailData["durata appuntamento"]);
  $todaystamp = gmdate("Ymd\THis");
  
  //Create unique identifier
  $cal_uid = date('Ymd').'T'.date('His')."-".rand().$_SERVER['HTTP_HOST'];
  
  //Create Mime Boundry
  $mime_boundary = "Appuntamento".md5(time());
  $mime_boundary2 = "Appuntamento".md5(time()+1);
	  
  //Create Email Headers
  $headers = "From: ".$mailData["nome mittente"]." <".$mailData["email mittente"].">\n";
  $headers .= "Reply-To: ".$mailData["nome mittente"]." <".$mailData["email mittente"].">\n";
  
  $headers .= "MIME-Version: 1.0\n";
  $headers .= "Content-Type: multipart/mixed; boundary=\"$mime_boundary\"\n";
  
  /* END HEADERS */
  
  /* ICAL */ 

  $ical = "BEGIN:VCALENDAR
PRODID:-//Google Inc//Google Calendar 70.9054//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
DTSTART:{$dtstart}
DURATIOn:PT{$mailData["durata appuntamento"]}M
DTSTAMP:20190327T112958Z
ORGANIZER;CN={$mailData["nome mittente"]}:mailto:{$mailData["email mittente"]}
UID:{$cal_uid}
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=
 TRUE;CN={$mailData["nome mittente"]};X-NUM-GUESTS=0:{$mailData["email mittente"]}
CREATED:{$todaystamp}
DESCRIPTION:{$mailData["descrizione appuntamento"]}
LAST-MODIFIED:{$todaystamp}
LOCATION:{$meeting_location}
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:{$mailData["oggetto appuntamento"]}
TRANSP:OPAQUE
END:VEVENT
END:VCALENDAR
";
  
  //Create Email Body
  $message .= "--$mime_boundary\n";
  $message .= "Content-Type: multipart/alternative; boundary=\"{$mime_boundary2}\"\n";
  
  $message .= "--$mime_boundary2\n";
  $message .= "Content-Type: text/plain; charset=\"UTF-8\"; format=flowed; delsp=yes\nContent-Transfer-Encoding: base64\n\n"; 
  
  $message .= chunk_split(base64_encode($mailData["messaggio email"]), 76);  
  $message .= "--$mime_boundary2\n";
  $message .= "Content-Type: text/html; charset=\"UTF-8\"\nContent-Transfer-Encoding: quoted-printable\n\n";
  
  $html = "<html>\n<body>\n{$mailData["messaggio email"]}</body>\n</html>\n";
  $message .= chunk_split($html, 75, "=\n");
  $message .= "--$mime_boundary2\n";
  $message .= "Content-Type: text/calendar; charset=\"UTF-8\"; method=REQUEST\nContent-Transfer-Encoding: 7bit\n\n";
  
  $message .= $ical;   
  $message .= "\n--$mime_boundary2--\n";
  $message .= "--$mime_boundary\n";
  $message .= "Content-Type: application/ics; name=\"appuntamento.ics\"\nContent-Disposition: attachment; filename=\"appuntamento.ics\"\nContent-Transfer-Encoding: 8bit\n\n";
  $message .= chunk_split(base64_encode($ical), 76)."\n";  
  $message .= "--$mime_boundary--\n";        
  
  //SEND MAIL
  mail( $mailData["email destinatario"], $mailData["oggetto email"], $message, $headers );

}
?>

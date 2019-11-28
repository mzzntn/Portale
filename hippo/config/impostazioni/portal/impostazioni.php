<?
define('PATH_PORTAL', '/portal');
$struttura_utenti = 'portal::utente';
$struttura_ditte = 'portal::ditta';
$lunghezza_min_password = 6;
$lunghezza_max_password = 10;
$url_amministrazione = 'gestione/index.php';
$email_from = '[non-rispondere@comune.it]';
$email_ente = 'info@comune.paperopoli.it';
#attivare se non disponibile librerie openSSL per la verifica della firma
$noOpenSSL = false;

#parametri di integrazione con portal e applicazioni di spider
$spider_portal = "http://portale.comune.alessandria.it/portal/";
#$spider_pagamenti = "http://newopenweb2.soluzionipa.it/portal/servizi/pagamenti/";
$spider_moduli = false;

$start_auth['auditor'] = "https://start.soluzionipa.it/auth_hub";
$start_auth['tenant'] = "spid-portal";
$start_auth['auth_type'] = array("aad","up");
$start_auth['show_buttons'] = true; // true per mostrare un bottone per ogni tipo di autenticazione specificato in auth_type, stringa per mostrare solo quello specificato, false per non mostrarli
$start_auth['buttons'] = array(
  "aad" => array("icon"=>"windows","label"=>"Accedi con Civilia Next"),
  "up" => array("icon"=>"user","label"=>"Accedi con login unificato"),
); // definisce icone e testi dei pulsanti per il login, per ogni tipologia di autenticazione
$start_auth['local_users'] = true; // se true prende i permessi dal database locale
$start_auth['old_login'] = false; // mostra anche il vecchio login su db locale
$start_auth['ws_user'] = "admin2"; // username fisso per l'utilizzo dei webservices locali via web
$start_auth['ws_password'] = "admin"; // username fisso per l'utilizzo dei webservices locali via web

?>

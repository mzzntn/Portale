<?
global $IMP;
$d = & $W->data ;
$D->subject = "Modifica registrazione";
$D->headers = 'MIME-Version: 1.0' . "\r\n";
$D->headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$D->headers .= 'From: '.$W->from."\r\n";
?>

L'utente <?=$d->nome?> <?=$d->cognome?> (<a href='<?=$W->adminPage?>=<?=$d->id?>'>vedi</a>)
ha modificato i propri dati. E' richiesta la registrazione ai seguenti servizi:<br>
<br>
<?
while ($d->moveNext('siti')){
?>
<?=$d->get('siti.nome')?><br>
<?
}
?>

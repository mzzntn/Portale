<?
global $IMP;
$d = & $W->data ;
$D->subject = "Richiesta di registrazione";
$D->headers = 'MIME-Version: 1.0' . "\r\n";
$D->headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$D->headers .= 'From: '.$W->from."\r\n";
?>

L'utente <?=$d->nome?> <?=$d->cognome?> (<a href='<?=$W->adminPage?>=<?=$d->id?>'>vedi</a>)
ha richiesto la registrazione ai seguenti servizi:<br>
<br>
<?
if ($d) while ($d->moveNext('siti')){
?>
<?=$d->get('siti.nome')?><br>
<?
}
?>
<br>
L'utente ha chiesto che la password gli sia comunicata via<br>
<? if ($W->avvisaEmail){
?>-email<br>
<?
}
if ($W->avvisaSnail){
?>-posta ordinaria<br>
<?
}
?>

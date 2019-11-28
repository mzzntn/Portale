<?
global $IMP;
$d = & $W->data ;
$D->subject = "Conferma registrazione";
$D->headers .= 'From: '.$W->from."\r\n";
?>

Gentile <?=$d->nome?> <?=$d->cognome?>,

abbiamo processato la sua registrazione ai seguenti servizi:

<?
while ($d->moveNext('siti')){
?>
<?=$d->get('siti.nome')?>
<?
}
?>

I suoi dati di collegamento al sito sono:
-login: <?=$d->get('user.login')?>
-password: <?=$d->get('user.password')?>

Grazie e distinti saluti.

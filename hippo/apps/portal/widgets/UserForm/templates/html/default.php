<?
$D->printCheckScript();
?>
<div class=''>
<?
  if ($W->label) print $W->label;
  elseif ($W->struct) print $W->struct->label();
?>
</div>
<?
  if (!$D->manualOpen){ #Yeah, it's a kludge
?>
<form action='<?=$_SERVER['PHP_SELF']?>' method='POST' onSubmit='return check_<?=$this->w->htmlName?>()'>
<?
 }

?>
<input type='hidden' name='<?=$D->name?>[id]' value='<?=$W->id?>'>
<table>
<tr>
<td>Login:</td><td><?= $W->login ?></td>
</tr>
<tr>
<td>Nome:</td><td><? $W->inputs->nome->display() ?></td>
<td>Cognome:</td><td><? $W->inputs->cognome->display() ?></td>
</tr>
<tr>
<td>Data di nascita:</td><td><? $W->inputs->dataNascita->display() ?></td>
<td>Indirizzo:</td><td><? $W->inputs->indirizzo->display() ?></td>
</tr>
<tr>
<td>Sesso:</td><td><? $W->inputs->sesso->display() ?></td>
</tr>
<tr>
<td>Email:</td><td><? $W->inputs->email->display() ?></td>
<td>Codice fiscale:</td><td><? $W->inputs->cf->display() ?></td>
</tr>
<tr>
<td>Telefono:</td><td><? $W->inputs->telefono->display() ?></td>
<td>Fax:</td><td><? $W->inputs->fax->display() ?></td>
</tr>
<tr>
<td>Cellulare:</td><td><? $W->inputs->cellulare->display() ?></td>
<td>Codice master</td><td><? $W->inputs->master->display() ?></td>
</tr>
<tr>
<td>Tipo documento:</td><td><? $W->inputs->tipodocumento->display() ?></td>
<td>Nr documento:</td><td><? $W->inputs->nrdocumento->display() ?></td>
</tr>
<tr>
<td>Data documento:</td><td><? $W->inputs->datadocumento->display() ?></td>
<td>Rilasciato da:</td><td><? $W->inputs->rilasciato->display() ?></td>
</tr>
<tr>
<td>Cambia password:</td><td><input type='password' name='password1'></td>
<td>Ripeti password:</td><td><input type='password' name='password2'></td>
</tr>
<tr>
<td>Siti attivi:</td>
<td colspan='3'><? $W->inputs->siti->display() ?></td>
</tr>
<tr>
<td>Password siti:</td>
<td colspan='3'>
<table border='1'>
<tr>
<th>Nome</th><th>Login</th><th>Password</th>
</tr>

<? if ($W->data->siti) while ($W->data->siti->moveNext()){ ?>
<tr>
<td align='center'><?= $W->sitiUtente->get('nome') ?></td>
<td align='center'><input type='text' name='sitiUtente[<?=$W->sitiUtente->get('id')?>][login]' value='<?=$W->sitiUtente->get('login')?>'></td>
<td align='center'><input type='text' name='sitiUtente[<?=$W->sitiUtente->get('id')?>][password]' value='<?=$W->sitiUtente->get('password')?>'></td>
</tr>
<? } ?>
</table>

</td>
<tr>
<td colspan='4' align='center'>
<input type='submit' class='<?=$D->getCSSClass('button')?>' name='submit_<?=$D->name?>' value='<?=$D->submitText?>'>
</td>
</tr>
</table>
<?
  if (!$D->manualOpen){
?>
</form>
<?
  }
?>

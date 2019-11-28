
<form action='<?=$_SERVER['PHP_SELF']?>' method='<?=$W->config['method']?>' id='<?=$D->name?>_form' >
<table id='<?=$D->name?>_table' class='<?=$this->getCSSClass('table')?>'>
<tr>
<td>
<table>
	<tr>
		<td class='txt_standard'>Tipo provvedimento</td>
		<td><? $W->inputs->provvedimenti->inputs->tipo->display() ?></td>
		<td class='txt_standard'>Nr. provvedimento</td>
		<td><? $W->inputs->provvedimenti->inputs->num2->display() ?></td>
 
	</tr>


	<tr>
		<td class='txt_standard'>Oggetto</td>
		<td colspan='3'><? $W->inputs->descrizione->display("size=40") ?></td>
	</tr>

	<tr>
		<td></td>
		<td class='txt_standard'>da (gg/mm/aaaa)</td>
		<td></td>
		<td class='txt_standard'>a (gg/mm/aaaa)</td>
	</tr>
	<tr>
		<td class='txt_standard'>Data Pubblicazione</td>
		<td><? $W->inputs->iter->inputs->data_1->display() ?></td>
		<td></td>
		<td><? $W->inputs->iter->inputs->data_2->display() ?></td>
	</tr>
	<tr>
		<td class='txt_standard'>Ufficio</td>
		<td colspan='3'><? $W->inputs->ufficio->display(); ?></td>
	</tr>
	<tr>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td colspan='2'><input type='submit' name='submit' value='Cerca'>
		<input type='submit' name='clear' value = 'Nuova Ricerca'></td>
	</tr>
	<tr>
		<td></td>
	</tr>
</table>
</form>


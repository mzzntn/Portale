<?
$i1 = & $W->inputs;
$required = '(*)';
?>  
<?
if (!$W->id){
    if(!$W->ditta){
?>
    <p>Per registrare un'azienda, clicchi <a href='<?=$_SERVER['PHP_SELF']?>?ditta=1'>qui</a>.</p>
<?
    }else{
?>
    <p>Per registrare una persona fisica, clicchi <a href='<?=$_SERVER['PHP_SELF']?>?ditta=0'>qui</a>.</p>
<?
    }
?>     
    <p>La password le verrà  inviata dopo che la sua registrazione sarà  stata processata.</p>
<?
}
?> 
    <p class="highlight">I Campi obbligatori sono marcati con <?=$required?></p>
<?

if ($W->error){
?>
    <ul class='error'><?foreach ($W->errors as $error) print "<li>$error</li>";?></ul>
<?
}
?>
<?
$this->printCheckScript();
$D->formStart();
?>
    <input type='hidden' name='ditta' value='<?=$W->ditta?>'>
    <?
if ($W->ditta){
?>
        <fieldset>
            <legend>Registrazione Azienda</legend>
            <p><label for="reg_nomeDitta">Nome azienda<?if ($W->required['nomeDitta']) print $required?></label> <? $i1->nomeDitta->d(); ?></p>
            <p><label for="reg_partitaIva">Partita iva<?if ($W->required['partitaIva']) print $required?></label> <? $i1->partitaIva->d(); ?></p>
        </fieldset>                      
<?
}
?>           
    <div class="registercol">
        <fieldset>
            <legend>Dati Anagrafici</legend>
            <p><label for="reg_nome">Nome<?if ($W->required['nome']) print $required?></label><? $i1->nome->d(); ?></p>
            <p><label for="reg_cognome">Cognome<?if ($W->required['cognome']) print $required?></label><? $i1->cognome->d(); ?></p>
            <p><label for="reg_sesso">Sesso<?if ($W->required['sesso']) print $required?></label><? $i1->sesso->d(); ?></p>
            <p><label for="reg_indirizzo">Indirizzo<?if ($W->required['indirizzo']) print $required?></label><? $i1->indirizzo->d(); ?></p>   
            <p><label for="reg_comune">Comune<?if ($W->required['comune']) print $required?></label><? $i1->comune->d(); ?></p>
            <p><label for="reg_provincia">Provincia<?if ($W->required['provincia']) print $required?></label><? $i1->provincia->d(); ?></p>
            <p><label for="reg_dataNascita">Data di nascita<?if ($W->required['dataNascita']) print $required?></label><? $i1->dataNascita->d(); ?></p>
            <p><label for="reg_comuneNascita">Comune di nascita<?if ($W->required['comuneNascita']) print $required?></label><? $i1->comuneNascita->d(); ?></p>
            <p><label for="reg_provinciaNascita">Provincia di nascita<?if ($W->required['provinciaNascita']) print $required?></label><? $i1->provinciaNascita->d(); ?></p>          
        </fieldset>
        <fieldset>
            <legend>Documenti</legend>
            <p><label for="reg_tipodocumento">Tipo Documento<?if ($W->required['tipodocumento']) print $required?></label><? $i1->tipodocumento->d(); ?></p>  
            <p><label for="reg_nrdocumento">Nr Documento<?if ($W->required['nrdocumento']) print $required?></label><? $i1->nrdocumento->d(); ?></p>
            <p><label for="reg_datadocumento">Data Documento<?if ($W->required['datadocumento']) print $required?></label><? $i1->datadocumento->d(); ?></p>
            <p><label for="reg_rilasciato">Rilasciato da<?if ($W->required['rilasciato']) print $required?></label><? $i1->rilasciato->d(); ?></p>
            <p><label for="reg_cf">Codice Fiscale<?if ($W->required['cf']) print $required?></label> <? $i1->cf->d(); ?></p>           
        </fieldset>     
        <fieldset>
            <legend>Dati di Contatto</legend>
            <p><label for="reg_email">Email<?if ($W->required['email']) print $required?></label> <? $i1->email->d(); ?></p>
            <p><label for="reg_telefono">Telefono<?if ($W->required['telefono']) print $required?></label> <? $i1->telefono->d(); ?></p>
            <p><label for="reg_fax">Fax<?if ($W->required['fax']) print $required?></label> <? $i1->fax->d(); ?></p>
            <p><label for="reg_cellulare">Cellulare<?if ($W->required['cellulare']) print $required?></label> <? $i1->cellulare->d(); ?></p>           
        </fieldset> 
        <fieldset>
            <legend>Informazioni per la Registrazione</legend>
            <p>
              <label for="reg_login">Nome Utente <?=$required?>
              </label> <?if (!$W->id){ $i1->login->d(); }else{ print "<b>{$i1->login->value}</b>"; }?>&nbsp;&nbsp;&nbsp;
              <span>(esempio nome.cognome,...)</span>
            </p>
            <?if ($W->id){?>
    <p><label for='old'>Password corrente:</label>
    <input type='password' name='old' id='old' /></p>
    <p><label for='new1'>Nuova password:</label>
    <input type='password' name='new1' id='new1' /></p>
    <p><label for='new2'>Ripeti password:</label>
    <input type='password' name='new2' id='new2' /></p>   
            <?}?>    
            <?if (!$W->id){?>
            <p>Desidera ricevere la password:<br/>
            <p class="indentleft"><label for="rcv_email">via email</label> <input type='checkbox' id='rcv_email' name='rcv_email' value='1' <?= ($_REQUEST['rcv_email'])?'checked':'' ?>/></p>
            <p class="indentleft"><label for="rcv_snail">via posta</label> <input type='checkbox' id='rcv_snail' name='rcv_snail' value='1' <?=($_REQUEST['rcv_snail'])?'checked':''?>/></p>         
            <?}?>
        </fieldset>                  
                
<?
if ($i1->siti->length() > 0){
?>
        <fieldset>
            <legend>Servizi Richiesti</legend>
<? 
    $i1->siti->d(); 
?>    
        </fieldset>     
<?
}
?>    
<?
if (!$W->id){
?>
    </div>   
    <div class="textbox">
        <a id='toggle_informativa' href="javascript: toggle('informativa', 'toggle_informativa', 'Visualizza l\'informativa sulla tutela del trattamento dei dati personali', 'Nascondi l\'informativa sulla tutela del trattamento dei dati personali')">Visualizza l'informativa sulla tutela del trattamento dei dati personali</a>
        <div id="informativa">
            <?=$C['portal']['informativa_registrazione']?>
        </div>
        <script type='text/javascript'>
            document.getElementById('informativa').style.display='none';
        </script>
    </div>
<?
}
else{
?>
    <p class="info">
        Se richiede la registrazione a nuovi servizi, le invieremo comunicazione all'indirizzo e-mail fornitoci
        non appena avremo effettuato
        l'attivazione.
    </p>
<?
}
?>
<?
if (!$W->id){
?>
    <p>Cliccando su "Procedi" dichiara di aver letto e accettato l'informativa sul trattamento dei dati personali</p>
<?
}
?>
    <div class="buttons">
        <input type='submit' name='submit' value='Procedi' class="button">
        <input type='reset' value='Azzera' class="button">
    </div>
<?
$D->formEnd();
?>

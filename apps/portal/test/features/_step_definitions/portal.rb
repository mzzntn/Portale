# -*- encoding : utf-8 -*-
Dato /^che la attivazione utenti automatica( non)? è attiva$/ do |non|
    Spider.config.set('portal.attivazione_utenti_automatica', !non)
end

Dato /^che il controllo cellulare alla registrazione( non)? è attivo$/ do |non|
    unless non
        Spider.config.set('messenger.sms.backend', 'test')
        require 'apps/messenger/backends/sms/test'
    end
    Spider.config.set('portal.conferma_cellulare', !non)
end

Dato /^che il controllo e-mail alla registrazione( non)? è attivo$/ do |non|
    Spider.config.set('portal.conferma_email', !non)
end

Dato /^che il controllo cambio cellulare( non)? è attivo$/ do |non|
    unless non
        Spider.config.set('messenger.sms.backend', 'test')
        require 'apps/messenger/backends/sms/test'
    end
    Spider.config.set('portal.conferma_cambio_cellulare', !non)
end

Dato /^che sono iscritto al portale$/ do
    utente_portale = Portal::Utente.create(
        :nome => 'Test',
        :cognome => 'De Testis',
        :codice_fiscale => 'a',
        :sesso => 'M',
        :provincia_nascita => 'PD',
        :comune_nascita => 'Padova', 
        :data_nascita => Date.today,
        :provincia_residenza => 'PD',
        :comune_residenza => 'Padova',
        :tipo_documento => 'CI',
        :numero_documento => '12345',
        :data_documento => Date.today,
        :documento_rilasciato => 'Comune di Padova',
        :email => 'test@test.com',
        :cellulare => '032112345',
        :stato => 'attivo'
    )
    utente_login = Portal::UtenteLogin.create(
        :username => 'test',
        :password => 'test',
        :utente_portale => utente_portale
    )
    @username_utente = 'test'
    @password_utente = 'test'
    @utente_portale = utente_portale
    @utente_login = utente_login
    @id_utente_portale = utente_portale.id
    @id_utente_login = utente_login.id
end

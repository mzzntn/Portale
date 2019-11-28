# -*- encoding : utf-8 -*-
Quando /^mi registro al portale$/ do
    @email_registrata = 'test@test.com'
    @cellulare_registrato = '032112345'
    @link_conferma_cellulare = nil
    
    visit Portal::PortalController.url('registrazione')
    within "#form_registrazione" do
        fill_in 'form_registrazione-nome',                  :with => 'Test'
        fill_in 'form_registrazione-cognome',               :with => 'De Testis'
        fill_in 'form_registrazione-codice_fiscale',        :with => 'a'
        select 'Maschio' , :from => 'form_registrazione-sesso'
        fill_in 'form_registrazione-provincia_nascita',     :with => 'PD'
        fill_in 'form_registrazione-comune_nascita',        :with => 'Padova'
        fill_in 'form_registrazione-data_nascita',          :with => '01/01/2001'
        fill_in 'form_registrazione-provincia_residenza',   :with => 'PD'
        fill_in 'form_registrazione-comune_residenza',      :with => 'Padova'
        select "Carta d'Identità", :from => 'form_registrazione-tipo_documento'
        fill_in 'form_registrazione-numero_documento',      :with => '12345'
        fill_in 'form_registrazione-data_documento',        :with => '02/02/2002'
        fill_in 'form_registrazione-documento_rilasciato',  :with => 'Comune di Padova'
        fill_in 'form_registrazione-email',                 :with => @email_registrata
        fill_in 'form_registrazione-cellulare',             :with => @cellulare_registrato
        
        fill_in 'username',  :with => 'test'
        fill_in 'password',  :with => 'testpwd'
        fill_in 'password2', :with => 'testpwd'
        click_on 'Invia richiesta di registrazione'
    end
    registrazione_eseguita = Portal::PortalController.url('registrazione_eseguita')
    unless page.current_path == registrazione_eseguita
        Spider.logger.error("Registrazione fallita:")
        begin
            Spider.logger.error(page.find('#form_registrazione .errors').text)
        rescue
        end
    end
    page.current_path.should == registrazione_eseguita
    if (Spider.conf.get('portal.conferma_email') || Spider.conf.get('portal.conferma_cellulare'))
        contatti_pendenti = page.find('.contatti-pendenti').text
        if Spider.conf.get('portal.conferma_email')
            contatti_pendenti.should match(/mail/)
        end
        if Spider.conf.get('portal.conferma_cellulare')
            contatti_pendenti.should match(/cellulare/)
            @link_conferma_cellulare = page.find('.attesa-cellulare a')[:href]
        end

    end

end

Dato /^che mi sono registrato al portale$/ do
    Quando "mi registro al portale"
end


Allora /^il mio utente deve essere in stato "([^\"]+)"$/ do |stato|
    utente = Portal::Utente.load(:email => @email_registrata)
    utente.stato.id.should == stato
end

Allora /^l'amministratore deve ricevere una notifica dell'iscrizione$/ do
    @email_notifica_iscrizione = nil
    Mail::TestMailer.deliveries.each do |msg|
        if msg.to.first == Spider.conf.get('portal.email_amministratore')
            @email_notifica_iscrizione = msg
            break
        end
    end
    @email_notifica_iscrizione.should_not == nil
end

Quando /^l.amministratore riceve la notifica dell.iscrizione$/ do
    Allora "l'amministratore deve ricevere una notifica dell'iscrizione"
end

Quando /^l.amministratore attiva l.utente della notifica$/ do
    page.driver.header 'Accept-Language', 'it-IT'
    match = /(http:\/\/(?:[\.:\d\w\/\?]+))\s*/.match(@email_notifica_iscrizione.body.to_s)
    raise "Non trovo il link nel testo: #{@email_notifica_iscrizione.body}" unless match
    link = match[1]
    visit(link)
    fill_in 'login', :with => 'admin'
    fill_in 'password', :with => 'admin'
    click_on 'Login'
    select 'Attivo', :from => 'admin-switcher-portal_utentelogin-form-stato'
    click_on 'Salva'
end

Allora /^devo ricevere una notifica dell.attivazione$/ do
    email = Mail::TestMailer.deliveries.each do |msg|
        if msg.to.first == @email_registrata && msg.body.to_s =~ /il tuo account è stato attivato/
            email = msg
            break
        end
    end
    email.should_not == nil
end

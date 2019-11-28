# -*- encoding : utf-8 -*-
Allora /^devo ricevere la e-mail di conferma indirizzo e-mail$/ do
    @email_conferma = nil
    Mail::TestMailer.deliveries.each do |msg|
        if msg.to.first == @email_registrata
            @email_conferma = msg
            break
        end
    end
    @email_conferma.should_not == nil
end

Dato /^che ho ricevuto la e-mail di conferma indirizzo e-mail$/ do
    Allora "devo ricevere la e-mail di conferma indirizzo e-mail"
end

Allora /^devo ricevere l.sms di conferma numero di cellulare$/ do
    @sms_conferma = nil
    num = @nuovo_cellulare || @cellulare_registrato
    Spider::Messenger::Backends::SMS::Test.sent_sms.each do |msg|
        if msg.to == num
            @sms_conferma = msg
            break
        end
    end
    @sms_conferma.should_not == nil
    @codice_conferma_cellulare = nil
    if @sms_conferma.text =~ /Chiave di controllo: (.+)/
        @codice_conferma_cellulare = $1
    end
    @codice_conferma_cellulare.should_not == nil
end

Dato /^che ho ricevuto l.sms di conferma numero di cellulare$/ do
    Allora "devo ricevere l'sms di conferma numero di cellulare"
end

Quando /^clicco sul link di conferma$/ do
    match = /(http:\/\/(?:[\.:\d\w\/\?]+)controllo=[\w\d]+)\s*/.match(@email_conferma.body.to_s)
    raise "Non trovo il link nel testo: #{@email_conferma.body}" unless match
    link = match[1]
    visit(link)
end

Quando /^vado al link di conferma cellulare e inserisco il codice di conferma$/ do
    visit @link_conferma_cellulare
    fill_in 'controllo', :with => @codice_conferma_cellulare
    click_on 'Conferma numero'
end

Dato /^che sono loggato al portale$/ do
    visit Portal::PortalController.url('autenticazione')
    fill_in 'login', :with => @username_utente
    fill_in 'password', :with => @password_utente
    click_on 'Accedi'
end

Quando /^cambio il mio numero di cellulare$/ do
    page.driver.header 'Accept-Language', 'it-IT'
    Dato "che sono loggato al portale"
    visit Portal::PortalController.url('dettagli_utente')+'?modifica'
    @nuovo_cellulare = '0333987654'
    fill_in 'form_registrazione-cellulare', :with => @nuovo_cellulare
    click_on 'Salva'
end

Allora /^il mio cellulare deve essere( non)? confermato$/ do |non|
    utente = Portal::Utente.load(:id => @id_utente_portale)
    utente.cellulare_confermato?.should == !non
end

Dato /^che il mio cellulare( non)? è confermato$/ do |non|
    utente = Portal::Utente.load(:id => @id_utente_portale)
    utente.cellulare_confermato = !non
    utente.save
end

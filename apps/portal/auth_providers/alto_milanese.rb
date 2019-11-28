# -*- encoding : utf-8 -*-
require 'apps/portal/models/auth_providers/alto_milanese/utente_alto_milanese'
require 'soap/wsdlDriver'

module Portal
    
    class AltoMilanese < Spider::PageController
        include AuthProvider
        include Spider::Messenger::MessengerHelper
        
        auth_provider({
            :label => 'alto_milanese',
            :nome => 'Alto milanese',
            :descrizione => 'Servizio di autenticazione attraverso il sistema informativo sovracomunale Alto Milanese'
        })
        
        
        __.action
        def index
            redirect Spider.conf.get('portal.alto_milanese.url') unless @request.params['token_id']
            wsdl = SOAP::WSDLDriverFactory.new(Spider.conf.get('portal.alto_milanese.wsdl'))
            soap = wsdl.create_rpc_driver
            soap.wiredump_file_base = Spider.paths[:log]+'/wiredump'
            response = soap.AutenticationDataExtended(:ClientKey => Spider.conf.get('portal.alto_milanese.client_key'), :Token => @request.params['token_id'])
            dati = {}
            if !response || !response.autenticationDataExtendedResult || !response.autenticationDataExtendedResult.string || !response.autenticationDataExtendedResult.string[0].respond_to?(:empty?) || response.autenticationDataExtendedResult.string[0].empty?
                Spider.logger.error("Alto milanese: autenticationDataExtendedResult non trovato")
                errore_autenticazione "Il sistema remoto non ha concesso l'autorizzazione. Si prega di riprovare."
            end
            dati_resp = response.autenticationDataExtendedResult.string
            [:codice_fiscale, :codice_comune, :livello_autorizzazione, :tipo_soggetto, :cognome, :nome,
            :data_nascita, :sesso, :partita_iva, :ragione_sociale, :indirizzo_residenza, :luogo_residenza, :provincia_residenza,
            :cap_residenza, :telefono, :cellulare, :email, :provincia_nascita, :comune_nascita].each do |campo|
                v = dati_resp.shift
                dati[campo] = v.is_a?(String) ? v.strip : nil
            end
            if !dati[:cognome] || dati[:cognome].empty?
                Spider.logger.error("Alto milanese: cognome non trovato")
                errore_autenticazione "Il sistema remoto non ha fornito i dettagli dell'utente. Si prega di riprovare in seguito."
            end
            utente = UtenteAltoMilanese.load(:chiave => dati[:codice_fiscale])
            utente_portale = nil
            creato = false
            Spider.logger.debug("DATI: #{dati.inspect}")
            if utente
                #sovrascrivo l'email se mi è arrivata una mail diversa...
                utente.utente_portale.email = dati[:email]
                utente.utente_portale.save
                
                utente_portale = utente.utente_portale
                    # Spider.logger.debug("Caricato utente portale #{utente_portale}")
            else
                utente = UtenteAltoMilanese.new(:chiave => dati[:codice_fiscale])
                utente_portale = Portal::Utente.new
                utente_portale.nome = dati[:nome]
                utente_portale.cognome = dati[:cognome]
                begin
                    utente_portale.codice_fiscale = dati[:codice_fiscale]
                rescue Spider::Model::FormatError
                end
                utente_portale.sesso = dati[:sesso]
                utente_portale.comune_nascita = dati[:comune_nascita]
                utente_portale.provincia_nascita = dati[:provincia_nascita]
                utente_portale.data_nascita = Date.strptime(dati[:data_nascita], "%d/%m/%Y") if dati[:data_nascita]
                utente_portale.comune_residenza = dati[:luogo_residenza]
                utente_portale.provincia_residenza = dati[:provincia_residenza]
                utente_portale.indirizzo_residenza = dati[:indirizzo_residenza]
                utente_portale.cap_residenza = dati[:cap_residenza]
                utente_portale.email = dati[:email]
                utente_portale.telefono = dati[:telefono]
                utente_portale.cellulare = dati[:cellulare]
                utente_portale.stato = 'confermato'
                utente_portale.save
                utente.tipo_soggetto = dati[:tipo_soggetto]
                utente.utente_portale = utente_portale
                utente.utente_esterno.insert
                utente.insert
                if dati[:ragione_sociale] && !dati[:ragione_sociale].empty?
                    ditta = Portal::Ditta.new
                    ditta.ragione_sociale = dati[:ragione_sociale]
                    ditta.partita_iva = dati[:partita_iva]
                    ditta.referente = utente_portale
                    ditta.save
                end
                creato = true
            end
            if (!@request.utente_portale || @request.utente_portale != utente_portale)
                @request.utente_portale = utente_portale
            end
            @request.utente_portale.authenticated(:alto_milanese)
            utente.save_to_session(@request.session)
            if creato
                email_amministratore_utente_registrato(utente_portale.id)
                #VIENE IMPOSTATO A CONFERMATO SEMPRE, mod del 27/09/2016
                # if (attivazione_automatica)
                #     email_amministratore_utente_registrato(utente_portale.id)
                # else
                #     email_amministratore_attesa_conferma(utente_portale.id)
                #     @scene.utente = utente_portale
                #     render('auth_providers/attesa_attivazione')
                #     done
                # end
            end
            redirect(Portal.url)
        end
       
        def errore_autenticazione(messaggio)
            @scene.messaggio_errore = messaggio
            render 'auth_providers/autenticazione_fallita'
            done
        end
        

       def email_amministratore_attesa_conferma(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/#{Portal.url}/amministrazione/utenti_portale_alto_milanese/#{scene.utente.utente_alto_milanese.id}"
            scene.auth_provider = 'Sistema informativo Alto Milanese'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'),
                Spider.conf.get('portal.email_amministratore'), headers)
        end

        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/#{Portal.url}/amministrazione/utenti_portale_alto_milanese/#{scene.utente.utente_alto_milanese.id}"
            scene.auth_provider = 'Sistema informativo Alto Milanese'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'),
                Spider.conf.get('portal.email_amministratore'), headers)
        end
        
    end
    
end

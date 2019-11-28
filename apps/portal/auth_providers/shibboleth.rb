# -*- encoding : utf-8 -*-
require 'apps/portal/lib/auth_provider'
require 'apps/portal/models/auth_providers/shibboleth/utente_shibboleth'
require 'openssl'
require "base64"

require 'net/http'
require 'uri'

module Portal
    class Shibboleth < Spider::PageController
        include HTTPMixin
        include AuthProvider
        include Spider::Messenger::MessengerHelper 
        

        #verifica_presenza_configurazioni('federa', ['idp_metadata', 'destination_service_url', 'idp_sso_target_url', 'requester_identificator'])

        auth_provider({
            :label => 'shibboleth',
            :nome => 'Autenticazione',
            :descrizione => 'cliccare per autenticarsi ai servizi on-line forniti dal Comune di Cinisello Balsamo'
        })

        __.action
        def index
            
            redirect self.class.http_s_url('Login').gsub("http","https")
        end
        
        __.html
        def Login
            Spider.logger.debug("** Contatto idp tramite Shibboleth **")
        end


        __.html
        def assertion_consumer
            Spider.logger.debug("** Ritorno da idp shibboleth **")
            Spider.logger.debug @request.env
            user_id = @request.env['SHIB_USERID']
            unless user_id.blank?
                #è presente la sessione
                utente = UtenteShibboleth.load(:chiave => user_id)
                utente_portale = nil
                creato = false
                if !utente.blank?
                    #sovrascrivo l'email se mi è arrivata una mail diversa...
                    utente.utente_portale.email = @request.env['SHIB_EMAIL']
                    utente.utente_portale.save

                    utente_portale = utente.utente_portale
                    # Spider.logger.debug("Caricato utente portale #{utente_portale}")
                else
                    utente = UtenteShibboleth.new(:chiave => user_id)
                    utente_portale = Portal::Utente.new

                    utente_portale.nome = @request.env['SHIB_NOME']
                    utente_portale.cognome = @request.env['SHIB_COGNOME']
                    utente_portale.codice_fiscale = @request.env['SHIB_CODICE_FISCALE']
                    utente_portale.email = @request.env['SHIB_EMAIL']
                    
                    utente_portale.comune_nascita = "test"
                    utente_portale.provincia_nascita = "test"
                    utente_portale.stato_nascita = "test"
                    utente_portale.data_nascita = Date.strptime("01/01/2013", "%d/%m/%Y")
                    utente_portale.comune_residenza = "test"
                    utente_portale.indirizzo_residenza = "test"
                    utente_portale.provincia_residenza = "test"
                   

                    attivazione_automatica = Spider.conf.get('portal.attivazione_utenti_automatica')
                    utente_portale.stato = attivazione_automatica ? 'confermato' : 'attesa'
                    utente_portale.save
                    utente.utente_portale = utente_portale
                    utente.utente_esterno.insert
                    utente.insert
                    creato = true

                end
                if (!@request.utente_portale || @request.utente_portale != utente_portale)
                    @request.utente_portale = utente_portale
                end
                @request.utente_portale.authenticated(:shibboleth)
                utente.save_to_session(@request.session)
                #@request.utente_portale.save_to_session(@request.session)
                if creato
                    if (attivazione_automatica)
                        #mando mail
                        email_amministratore_utente_registrato(utente_portale.id)
                    else
                        #mando mail
                        email_amministratore_attesa_conferma(utente_portale.id)
                        @scene.utente = utente_portale
                        render('auth_providers/shibboleth/attesa_attivazione')
                        done
                    end
                end
                redirect Portal.http_s_url
                
            else    
                #non è presente la sessione utente, rimando al login
                redirect self.class.http_s_url('Login').gsub("http","https")
            end


            #salvo l'utente e creo la sessione per l'utente
            redirect Portal::PortalController.http_s_url.gsub("http","https")
        end
        
        # __.xml
        # def logout_service
            
        # end


        # __.html
        # def errore_autenticazione(messaggio)
        #     @scene.messaggio_errore = messaggio
        #     Spider.logger.error("Errore autenticazione: #{messaggio}")
        #     render 'auth_providers/shibboleth/autenticazione_fallita'
        #     done
        # end
        
        
        def email_amministratore_attesa_conferma(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_shibboleth_cinisello/#{scene.utente.utente_shibboleth.id}"
            scene.auth_provider = 'Gestore Identità Cinisello Balsamo'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_shibboleth_cinisello/#{scene.utente.utente_shibboleth.id}"
            scene.auth_provider = 'Gestore Identità Cinisello Balsamo'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
       
        
    end
    
end

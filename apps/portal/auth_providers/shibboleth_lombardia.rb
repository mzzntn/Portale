# -*- encoding : utf-8 -*-
require 'apps/portal/lib/auth_provider'
require 'apps/portal/models/auth_providers/shibboleth_lombardia/utente_shibboleth_lombardia'
require 'openssl'
require "base64"

require 'net/http'
require 'uri'

module Portal
    class ShibbolethLombardia < Spider::PageController
        include HTTPMixin
        include AuthProvider
        include Spider::Messenger::MessengerHelper 
        
        auth_provider({
            :label => 'shibboleth_lombardia',
            :nome => 'Autenticazione',
            :descrizione => 'cliccare per autenticarsi ai servizi on-line forniti dal Comune'
        })

     
        __.action
        def index
            redirect self.class.http_s_url('Shibboleth.sso/Login')
        end
        
        __.html
        def Login
            Spider.logger.debug("** Contatto idp tramite Shibboleth **")
        end

        __.html
        def assertion_consumer
            Spider.logger.debug("** Ritorno da idp shibboleth **")
            #Spider.logger.debug @request.env
            user_id = @request.env['saml_attribute_userID']
            unless user_id.blank?
                #presente la sessione
                utente = UtenteShibbolethLombardia.load(:chiave => user_id)
                utente_portale = nil
                creato = false
                if !utente.blank?
                    
                    utente_portale = utente.utente_portale
                    # Spider.logger.debug("Caricato utente portale #{utente_portale}")
                else
                    utente = UtenteShibbolethLombardia.new(:chiave => user_id)
                    utente_portale = Portal::Utente.new

                    utente_portale.nome = @request.env['saml_attribute_nome']
                    utente_portale.cognome = @request.env['saml_attribute_cognome']
                    utente_portale.codice_fiscale = @request.env['saml_attribute_codicefiscale']
                    utente_portale.email = @request.env['saml_attribute_emailAddress'] || @request.env['saml_attribute_userID']
    
                    #parametri obbligatori che metto a '_'
                    utente_portale.comune_nascita = @request.env['saml_attribute_luogoNascita']
                    utente_portale.provincia_nascita = @request.env['saml_attribute_provinciaNascita']
                    utente_portale.stato_nascita = @request.env['saml_attribute_statoNascita']
                    utente_portale.data_nascita = Date.strptime(@request.env['saml_attribute_dataNascita'], "%d/%m/%Y")
                    utente_portale.comune_residenza = "-" #@request.env['HTTP_COMUNERESIDENZA'] contiene il codice istat
                    utente_portale.indirizzo_residenza = (@request.env['HTTP_INDIRIZZORESIDENZA'].blank? ? "-" : @request.env['HTTP_INDIRIZZORESIDENZA']) 
                    utente_portale.provincia_residenza = "AA"
                    utente_portale.cellulare = @request.env['saml_attribute_cellulare']
                    utente_portale.telefono = ""
                    utente_portale.sesso = @request.env['saml_attribute_sesso']
                    #HTTP_USERIDENTIFIED mi dice se l'utente ha un autenticazione forte 
                    #HTTP_USERENABLED mi dice se è attivo o disabilitato per quelche motivo

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
                @request.utente_portale.authenticated(:shibboleth_lombardia)
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
                        render('auth_providers/shibboleth_lombardia/attesa_attivazione')
                        done
                    end
                end
                redirect Portal.http_s_url 
            else    
                #non è presente la sessione utente, rimando al login
                redirect self.class.http_s_url('Shibboleth.sso/Login')
            end
            #salvo l'utente e creo la sessione per l'utente
            redirect Portal::PortalController.http_s_url
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
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_shibboleth_regione_lombardia/#{scene.utente.utente_shibboleth_lombardia.id}"
            scene.auth_provider = 'Gestore Identità Regione Lombardia'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_shibboleth_regione_lombardia/#{scene.utente.utente_shibboleth_lombardia.id}"
            scene.auth_provider = 'Gestore Identità Regione Lombardia'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
       
        
    end
    
end

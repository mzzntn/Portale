# -*- encoding : utf-8 -*-
require 'ruby-saml-federazione-trentina'
require 'apps/portal/lib/auth_provider'
require 'apps/portal/models/auth_providers/pat_trentino/utente_pat_trentino'
require 'openssl'
require "base64"

require 'net/http'
require 'uri'

module Portal
    
    class PatTrentino < Spider::PageController
        include HTTPMixin
        include AuthProvider
        include Spider::Messenger::MessengerHelper 
        include FederazioneTrentina::Saml::Coding

        verifica_presenza_configurazioni('pat_trentino', ['idp_metadata', 'idp_name_qualifier', 'destination_service_url', 'idp_sso_target_url', 'requester_identificator'])

        auth_provider({
            :label => 'pat_trentino',
            :nome => 'C.R.S. Federazione PAT trentina',
            :logo => 'pat_trentino/pat_trentino.png',
            :descrizione => "Accedi al Servizio con la Carta Provinciale dei Servizi - CPS o con una Carta Nazionale dei Servizi. Se non l'hai ancora fatto, <a href='http:\//www.cartaservizi.provincia.tn.it/attivazione/'>attiva la tua Tessera Sanitaria</a> per accedere al servizio"
        })

        __.xml
        def sp_metadata
            meta = FederazioneTrentina::Saml::Metadata.new
            settings = get_saml_settings
            @response.headers['Content-Type'] = 'application/samlmetadata+xml'
            $out << meta.generate(settings)
        end



        __.action
        def index
            saml_settings = get_saml_settings
            request = FederazioneTrentina::Saml::Authrequest.new(saml_settings)
            #creo un instanza di FederazioneTrentina::Saml::Authrequest
            auth_request = request.create
             # Based on the IdP metadata, select the appropriate binding 
            # and return the action to perform to the controller
            meta = FederazioneTrentina::Saml::Metadata.new(saml_settings)
            signature = get_signature(auth_request.uuid,auth_request.request,"http://www.w3.org/2000/09/xmldsig#rsa-sha1")

            redirect meta.create_sso_request( auth_request.request, {   :RelayState   => request.uuid,
                                                                        :SigAlg       => "http://www.w3.org/2000/09/xmldsig#rsa-sha1",
                                                                        :Signature    => signature,    
                                                                        :language     => "it" 
                                                                    } )
        end
        
        __.html
        def assertion_consumer
            target = @request.params['target']
            saml_response = @request.params['SAMLResponse'] 
            
            if !saml_response.nil? 
                #leggo i settaggi dati dalla federazione PAT trentino
                settings = get_saml_settings
                #creo un oggetto response
                response = FederazioneTrentina::Saml::Response.new(saml_response)
                #assegno alla response i settaggi
                response.settings = settings
                #estraggo dal Base64 l'xml, forse servirà..
                saml_response_dec = Base64.decode64(saml_response)

                #controllo validità response, da finire
                errore_autenticazione "La response non è valida" unless response.is_valid?

                attributi_utente = response.attributes
                codice_fiscale = attributi_utente[:CodiceFiscale]

                #name_id non è usato come chiave di utente_esterno perchè cambia sempre, uso codicefiscale come chiave
                #name_id = response.name_id
                
                unless codice_fiscale
                    Spider.logger.error "L'Idp Trentino non ha restituito un name_id valido"
                    errore_autenticazione "il sistema di autenticazione remoto non ha trovato l'utente"
                end
                #utente = UtenteRegioneLombardia.load(:chiave => attributi_utente[:name_id])
                utente = UtentePatTrentino.load(:chiave => codice_fiscale)
                utente_portale = nil
                creato = false
                if utente
                    #sovrascrivo l'email se mi è arrivata una mail diversa...
                    utente.utente_portale.email = attributi_utente[:emailAddressPersonale]
                    utente.utente_portale.save
                    
                    utente_portale = utente.utente_portale
                    # Spider.logger.debug("Caricato utente portale #{utente_portale}")
                else
                    utente = UtentePatTrentino.new(:chiave => codice_fiscale)
                    utente_portale = Portal::Utente.new
                    utente_portale.nome = attributi_utente[:nome]
                    utente_portale.cognome = attributi_utente[:cognome]
                    utente_portale.codice_fiscale = attributi_utente[:CodiceFiscale]
                    utente_portale.sesso = attributi_utente[:sesso]
                    utente_portale.comune_nascita = attributi_utente[:luogoNascita]
                    utente_portale.provincia_nascita = attributi_utente[:provinciaNascita]
                    utente_portale.stato_nascita = attributi_utente[:statoNascita]
                    utente_portale.data_nascita = Date.strptime(attributi_utente[:dataNascita], "%d/%m/%Y") unless attributi_utente[:dataNascita].blank?
                    utente_portale.email = attributi_utente[:emailAddressPersonale]
                    utente_portale.cellulare = attributi_utente[:cellulare]
                    utente_portale.comune_residenza = attributi_utente[:cittaResidenza]
                    utente_portale.indirizzo_residenza = attributi_utente[:indirizzoResidenza]
                    utente_portale.provincia_residenza = attributi_utente[:provinciaResidenza]
                    utente_portale.telefono = attributi_utente[:telefono]
                    utente_portale.stato = 'confermato'
                    utente_portale.save
                    utente.utente_portale = utente_portale
                    utente.utente_esterno.insert
                    utente.insert
                    creato = true
                end

                if (!@request.utente_portale || @request.utente_portale != utente_portale)
                    @request.utente_portale = utente_portale
                end
                # Spider.logger.debug("Utente portale: #{@request.utente_portale}")
                @request.utente_portale.authenticated(:pat_trentino)
                utente.save_to_session(@request.session)
                #@request.utente_portale.save_to_session(@request.session)
                if creato
                    email_amministratore_utente_registrato(utente_portale.id)
                    #VIENE IMPOSTATO A CONFERMATO SEMPRE, mod del 27/09/2016
                    # if (attivazione_automatica)
                    #     #mando mail
                    #     email_amministratore_utente_registrato(utente_portale.id)
                    # else
                    #     #mando mail
                    #     email_amministratore_attesa_conferma(utente_portale.id)
                    #     @scene.utente = utente_portale
                    #     render('auth_providers/crs_trentino/attesa_attivazione')
                    #     done
                    # end
                end
                redirect Portal.http_s_url
                    

            else
                errore_autenticazione "Non sono stati ricevuti dati per l'utente"
            end    
        end
        
        __.xml
        def logout_service
            #controllo indirizzo dell' identity provider e faccio logout
            settings = get_saml_settings
            # LogoutRequest accepts plain browser requests w/o paramters 
            logout_request = FederazioneTrentina::Saml::LogoutRequest.new( :settings => settings )

            # Since we created a new SAML request, save the transaction_id 
            # in the session to compare it with the response we get back.
            # You'll need a shared session storage in a clustered environment.
            @request.session[:transaction_id] = logout_request.transaction_id

            # Create a new LogoutRequest for this session Name ID
            request_content = logout_request.create( :name_id => "fabiano", :session_index => "indice_sessione" )
            redirect request_content
            #$out << request_content

                
        end


        __.html
        def errore_autenticazione(messaggio)
            @scene.messaggio_errore = messaggio
            Spider.logger.error("Errore autenticazione CRS Trentino: #{messaggio}")
            render 'auth_providers/crs_trentino/autenticazione_fallita'
        end
        

        def get_saml_settings
            settings = FederazioneTrentina::Saml::Settings.new
            if Spider.conf.get('portal.pat_trentino.https_server')
                local_portal_url = Portal.http_s_url
            else
                local_portal_url = Portal.http_s_url
            end

            settings.assertion_consumer_service_url     = local_portal_url+'/auth/pat_trentino/assertion_consumer'
            settings.issuer                             = local_portal_url+'/auth/pat_trentino/sp_metadata'
            settings.sp_cert                            = File.join(Spider.paths[:certs],"default/cert.pem")
            settings.single_logout_service_url          = local_portal_url + '/auth/pat_trentino/logout_service'
            settings.sp_name_qualifier                  = local_portal_url + Spider.conf.get('portal.pat_trentino.sp_name_qualifier') 
            settings.idp_name_qualifier                 = Spider.conf.get('portal.pat_trentino.name_identifier_format')
            # Optional for most SAML IdPs
            #settings.authn_context = "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport"
            #assegno ai settings un array da configurazione
            settings.name_identifier_format             = Spider.conf.get('portal.pat_trentino.name_identifier_format')
            settings.destination_service_url            = Spider.conf.get('portal.pat_trentino.destination_service_url')
            settings.single_logout_destination          = Spider.conf.get('portal.pat_trentino.single_logout_destination')
            settings.authn_context                      = Spider.conf.get('portal.pat_trentino.authn_context')
            settings.requester_identificator            = Spider.conf.get('portal.pat_trentino.requester_identificator')
            settings.skip_validation                    = Spider.conf.get('portal.pat_trentino.skip_validation')
            settings.idp_sso_target_url                 = Spider.conf.get('portal.pat_trentino.idp_sso_target_url')
            settings.idp_metadata                       = Spider.conf.get('portal.pat_trentino.idp_metadata')
            settings
        end

        def get_signature(relayState, request, sigAlg)
            #url encode relayState
            relayState_encoded = escape(relayState)
            #deflate e base64 della samlrequest
            deflate_request_B64 = encode(deflate(request))
            #url encode della samlrequest
            deflate_request_B64_encoded = escape(deflate_request_B64)
            #url encode della sigAlg
            sigAlg_encoded = escape(sigAlg)
            querystring="RelayState=#{relayState_encoded}&SAMLRequest=#{deflate_request_B64_encoded}&SigAlg=#{sigAlg_encoded}"
            #puts "**QUERYSTRING** = "+querystring
            digest = OpenSSL::Digest::SHA1.new(querystring.strip)  
            pk = OpenSSL::PKey::RSA.new File.read(File.join(Spider.paths[:certs],"default/private/key.pem"))
            qssigned = pk.sign(digest,querystring)
            deflated_qs = Zlib::Deflate.deflate(qssigned, 9)[2..-5]
            Base64.encode64(deflated_qs).gsub(/\n/, "")       
        end

        
        def email_amministratore_attesa_conferma(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_crs_pat_trentino/#{scene.utente.utente_pat_trentino.id}"
            scene.auth_provider = 'C.R.S. Federazione Trentino'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_crs_pat_trentino/#{scene.utente.utente_pat_trentino.id}"
            scene.auth_provider = 'C.R.S. Federazione Trentino'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
       
        
    end
    
end

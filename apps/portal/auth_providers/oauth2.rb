# -*- encoding : utf-8 -*-
#Controller che espone link per fare login Oauth2 su OES

require 'apps/portal/lib/auth_provider'
require 'httparty'
require 'jwt'


module Portal
    
    class Oauth2 < Spider::PageController
        include HTTPMixin
        include AuthProvider
        include Spider::Messenger::MessengerHelper 

        auth_provider({
            :label => 'oauth2',
            :nome => 'Oauth2',
            :link_personalizzato => true,
            :descrizione => 'Accedi con Oauth2',
            :no_model => true
        })

        
        #1: arriva sid_sessione e id_utente, devo fare redirect a oauth/authorize per iniziare giro con access token
        #2: arriva code, faccio chiamata con httparty a oauth/token per avere il token jwt

        __.action
        def callback
            #arriva un jwt con sid e id utente
            jwt_sid_id = @request.params['j']
            unless jwt_sid_id.blank?
                hash_jwt_sid_id = JWT.decode jwt_sid_id, Spider.conf.get('portal.secret_auth_hub'), true, { algorithm: 'HS256' }
                #ricevo il code, redirect su 
                @request.session['sid_oauth2'] = hash_jwt_sid_id[0]['sid']
                @request.session['id_utente'] = hash_jwt_sid_id[0]['id_utente']
                redirect Spider.conf.get('portal.url_oauth2')+"/oauth/authorize?client_id=#{Spider.conf.get('portal.client_id_oauth2')}&redirect_uri=#{self.class.https_url('callback')}&response_type=code"
                done
            end 
            code = @request.params['code']
            unless code.blank?
                #faccio la chiamata a oauth/token
                hash_params ={
                    'client_id' => Spider.conf.get('portal.client_id_oauth2'),
                    'client_secret' => Spider.conf.get('portal.secret_oauth2'),
                    'code' => code,
                    'grant_type' => 'authorization_code',
                    #'grant_type' => 'client_credentials',
                    'redirect_uri' => self.class.https_url('callback') 
                }
                url = "#{Spider.conf.get('portal.url_oauth2')}/oauth/token"
                response = HTTParty.post(url, 
                   :body => hash_params,
                   :headers => { 'Content-Type' => 'application/x-www-form-urlencoded' },
                   :follow_redirects => true,
                :timeout => 5000000 )
                
                token_jwt = response.parsed_response['access_token']
                #"eyJhbGciOiJub25lIn0.eyJpc3MiOiJPRVMiLCJpYXQiOjE1NTI0ODMzMDksImp0aSI6IjMwNDZjMGQ5LTFmNTMtNDg3My05NWM4LTdkM2M0OWQ1MWEyNyIsInVzZXIiOnt9fQ."
                unless token_jwt.blank?
                    #faccio il decode, uso come chiave il secret dell'applicazione
                    jwt_hash = JWT.decode token_jwt, Spider.conf.get('portal.secret_oauth2'), true, { algorithm: 'HS256' }
                    # {
                    #     "iss": "OES",
                    #     "iat": 1552574999,
                    #     "jti": "b6064c6c-2e50-4f9a-857f-c8d46df22cf1",
                    #     "user": {
                    #       "nome": "Andrea",
                    #       "cognome": "Grazian",
                    #       "codice_fiscale": "BRRRRT68L71B300L",
                    #       "id": 1,
                    #       "sid_sessione": "99a9cf9a-eb8f-4320-84fe-c4bc80416d81",
                    #       "application_id": 1
                    #     }
                    #   }
                    sid = @request.session['sid_oauth2'] #carico dalla sessione il sid salvato
                    final_redirect = @request.params['final_redirect'] #redirect esterno
                    layout_web = @request.session['layout_web'] unless @request.session['layout_web'].nil?
                    @request.session = Spider::Session.get(sid)
                    unless @request.session.blank?
                        @request.session.restore #carica i dati
                        utente_login = Portal::UtenteLogin.load(:id => @request.session[:auth]['Portal::UtenteLogin'][:id] )
                        @request.session['layout_web'] = layout_web unless layout_web.nil?
                        unless utente_login.blank?
                            @request.utente_portale = utente_login.utente_portale
                            utente_login.utente_portale.save_to_session(@request.session)
                            @request.cookies = Spider::HTTP.parse_query(@request.env['HTTP_COOKIE'], ';')
                            @response.cookies['sid'] = @request.session.sid
                            @response.cookies['sid'].path = '/'

                        else
                            Spider.logger.error "\n Utente non trovato in database \n"
                            #utente non trovato
                            @request.session.flash['errore'] = "Utente non trovato! Rifare l'autenticazione."
                        end
                    else
                        Spider.logger.error "\n Sessione da sid non presente \n"
                        #utente non trovato
                        @request.session.flash['errore'] = "Utente non trovato! Rifare l'autenticazione."
                    end
                    if final_redirect.blank?
                        #Entro sul portale
                        redirect Portal::PortalController.https_url
                    else
                        #redirect su applicazione esterna, devo passare sid e id_utente in un jwt per poter fare chiamate
                        if final_redirect.include?('?')
                            final_redirect += "&auth=no" unless @request.session.flash['errore'].blank?
                        else
                            final_redirect += "?auth=no" unless @request.session.flash['errore'].blank?
                        end
                        redirect final_redirect
                    end
                else
                    #utente non trovato
                    @request.session.flash['errore'] = "Utente non trovato! Rifare l'autenticazione."
                end
            else
                Spider.logger.error "\n Parametro code non presente \n"
                #utente non trovato
                @request.session.flash['errore'] = "Problemi di autenticazione! Rifare la login."
            end
            
            if jwt_sid_id.blank? && code.blank?
                #errore, mando a pagina di autenticazione
                @request.session.flash['errore_auth'] = "Problemi nell'autenticazione!"
                Spider.logger.error "\n Parametro j o code non presente in autenticazione Oauth2 \n"
            end
            redirect Portal::ControllerAutenticazione.https_url
            
        end


       
        
        
        def logout_service
                           
        end


        __.html
        def errore_autenticazione(messaggio)
            @scene.messaggio_errore = messaggio
            Spider.logger.error("Errore autenticazione Oauth2: #{messaggio}")
            render 'auth_providers/oauth2/autenticazione_fallita'
            done
        end
       
        
    end
    
end

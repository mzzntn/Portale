# -*- encoding : utf-8 -*-
require 'apps/core/auth/lib/login_authenticator'
require 'cgi'
require 'sanitize'

module Portal


    class ControllerAutenticazione < Spider::Auth::LoginController
        include HTTPMixin
        include Visual
        include AutenticazionePortale
        include Spider::Messenger::MessengerHelper rescue NameError
        begin
          include Spider::CASServer::CASLoginMixin
        rescue NameError
          #Spider.logger.error("-- CASLoginMixin non caricato! --")
        end
        
        CHIAVE = Spider.conf.get('portal.secret_auth_hub') #stessa chiave usata anche con auth_hub

        Portal.auth_providers.each do |provider|
            next unless provider.details[:controller] && provider.details[:label]
            route "auth/#{provider.details[:label]}", provider.details[:controller]
        end

        def self.user
            Portal::UtenteLogin
        end
        
        def self.default_redirect
            HTTPMixin.reverse_proxy_mapping("")+'/'+Portal.route_url
        end
        

        def get_params_from_rack_env
            @request.env['rack.input'].rewind
            if !@request.env['rack.input'].blank? && @request.env['rack.input'].respond_to?(:read) && !@request.env['rack.input'].read.blank?
                @request.env['rack.input'].rewind
                params = @request.env['rack.input'].read
            else
                params = {}
            end
            Spider.logger.debug "\n PARAMS: #{params}"
            JSON.parse(params)
        end

        def before(action='', *params)
        
            super
        
            #se faccio una chiamata di tipo javascript vado nel metodo before per la parte js
            if !(@request.env['CONTENT_TYPE'] =~ /application\/json/).nil?
                before_js
            end

        end


        #metodo before per chiamate ajax
        def before_js
            #setto i params che arrivano nel @request.env['rack.input']
            @request.params = @request.params.merge(get_params_from_rack_env)
        end

        #Ricevo da form di login fatto con react una chiamata ajax per fare la login
        #TO-DO: devono arrivare parametri codificati
        __.json :method => :POST
        def login_jwe
            username_san = sanitize_all(@request.params['username'])
            password_san = sanitize_all(@request.params['password'])
            #Spider.logger.debug("\n\n user #{username_san} psw #{password_san}")
            user = Portal::UtenteLogin.authenticate(:login, :username => username_san, :password => password_san,:ignore_cas => true)
            if user
                user.save_to_session(@request.session)
                on_success(user)
                utente_login = Portal::UtenteLogin.new(@request.session[:auth]['Portal::UtenteLogin'][:id])
                id_utente_portale = utente_login.utente_portale.id
                h = utente_login.cut(:nome => 0, :cognome => 0, :codice_fiscale => 0)
                h[:id] = id_utente_portale
                h = h.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                #carico tutti gli id dei servizi privati associati all'utente
                array_servizi_privati = []
                user.utente_portale.servizi_privati.each{ |servizio|
                    array_servizi_privati << servizio[:servizio].id if (servizio.stato.id == 'attivo' && servizio.web_service == true)
                }
                $out << { :stato => "ok",
                          :sid_sessione => @request.session.sid,
                          :dati_utente => h,
                          :servizi_privati => array_servizi_privati
                        }.to_json
            else
                if @request.env['HTTP_ACCEPT'].include?('json')
                    $out << {:stato => "ko"}.to_json
                else
                    raise Forbidden.new("Autenticazione non valida")
                end
                done               
            end
        end

        __.json :method => :POST
        def get_login_session
            if !@request.env['HTTP_AUTHORIZATION'].blank? #authorization con token jwt
                token = @request.env['HTTP_AUTHORIZATION']
                raise Forbidden.new("Formato token non corretto: Bearer <token jwt>") unless token.include?('Bearer ')
                token_jwt = token.gsub('Bearer ','')
                #verifica della firma
                begin
                    jwt_decoded = JWT.decode(token_jwt, CHIAVE,'HS256')[0]    
                rescue JWT::VerificationError => ext
                    raise Forbidden.new("Verifica della firma fallita su token jwt!")
                end
                sid = jwt_decoded['sid']
                unless sid.blank?
                    if _carica_sessione(sid)
                        #controllo se utente corrente uguale a quello inviato con jwt
                        if @request.utente_portale.id.to_s == jwt_decoded['id_utente']
                            #controllo servizio se attivo, arriva in jwt l'id del servizio, es: tributi
                            if @request.utente_portale.servizio_privato?(jwt_decoded['id_app'])
                                payload = {
                                    :cf => @request.utente_portale.codice_fiscale,
                                    :nome => @request.utente_portale.nome,
                                    :cognome => @request.utente_portale.cognome,
                                    :id => @request.utente_portale.id,
                                    :sid => @request.session.sid
                                }
                                #se ho richiesto info per api next includo nel jwt le varie conf in base all'app chiamante
                                if jwt_decoded['api_next']
                                    #questa chiamata serve per caricare le configurazioni che hanno come inizio "api_next.#{jwt_decoded['id_app']}"
                                    conf_patch = Spider.conf.get("api_next.#{jwt_decoded['id_app']}.fake")
                                    conf_presenti = Spider.conf.get("api_next.#{jwt_decoded['id_app']}")
                                    unless conf_presenti.blank?
                                        api_next = {}
                                        conf_presenti.options.each{ |chiave_conf, conf|
                                            api_next[chiave_conf] = Spider.conf.get("api_next.#{jwt_decoded['id_app']}.#{chiave_conf}")
                                        }
                                        payload[:api_next] = api_next
                                    else
                                        raise Forbidden.new("Richieste api_next di app #{jwt_decoded['id_app']} ma non presenti")
                                    end
                                end
                                token = JWT.encode payload, CHIAVE, 'HS256'
                                $out << { :stato => "ok",
                                          :token => token
                                          #:url_stampa => "aHR0cDovL2NpdmlsaWFuZXh0LnNvbHV6aW9uaXBhLml0L29wZW53ZWIvX2ljaS9pbXV0YXNpX3N0YW1wYS5waHA=", #  "urlstampab64",
                                          #:assets => "WyJodHRwOi8vY2l2aWxpYW5leHQuc29sdXppb25pcGEuaXQvcHVibGljL19jL3BvcnRhbC4xLmNzcyJd", #"assetsb64"
                                         }.to_json
                                done
                            else
                                raise Forbidden.new("Servizio non attivo per l'utente!")
                            end
                        else
                            raise Forbidden.new("Utente che richiede di accedere non autenticato!")
                        end    
                    else
                        self.class.http_s_url('login')
                        #raise Forbidden.new("Problemi nel ripristino della sessione!")
                    end
                else
                    raise Forbidden.new("Sid mancante!No session")
                end
            else
                raise Forbidden.new("Bearer token mancante!No session")
            end
            @response.cookies['sid'] = @request.session.sid
            @response.cookies['sid'].path = '/'
        end

        __.html
        def index
            @scene.autenticazione_interna = Spider.conf.get('portal.autenticazione_interna')
            @scene.registrazione_utenti = Spider.conf.get('portal.registrazione_utenti')
            @scene.autenticazioni_esterne = []

            @scene.msg_errore = @request.session.flash['errore_auth']

            Portal.auth_providers.each do |provider|
                aut = provider.details
                aut[:link] = "#{self.class.http_s_url}/auth/#{aut[:label]}"
                @scene.autenticazioni_esterne << aut
            end
            if Spider.app?('cas_server')
                @scene.cas_login_ticket = generate_login_ticket
            end
            #faccio il sanitize di questo parametro per evitare xss
            @scene.redirect = sanitize_all(@request.params['redirect'])
            @scene.attesa_attivazione_account = @request.session.flash[:attesa_attivazione_account]
            #carico una var per il navigatore
            @scene.autenticazione = true
            render('autenticazione')
        end

        # __.html
        # def password_dimenticata
        #     unless @request.params.blank?
        #         parametri = "?"+@request.params.to_a.map { |x| "#{x[0]}=#{x[1]}" }.join("&")
        #     end
        #     parametri ||= ""
        #     redirect Portal::PortalController.http_s_url('password_dimenticata'+parametri)
        # end

        #metodi per username e password dimenticata, versione html e json

        __.html
        __.json
        def username_dimenticato
            valori_output = nil
            if @request.post? && @request.params['mode'] != 'embed'
                campi_da_controllare = ["email_usr_dim", "cf_usr_dim"]
                valori_output = check_input_form(@request.post, campi_da_controllare)
                if valori_output['esito'] == 'ok'
                    email = valori_output['email_usr_dim'].strip.downcase
                    cf = valori_output['cf_usr_dim'].strip.upcase
                    utenti_trovati = UtenteLogin.where(:email => email, :codice_fiscale => cf)
                    if utenti_trovati.length == 0
                        valori_output['esito'] = "Errore"
                        valori_output['messaggio_errore'] = "E-mail o Codice Fiscale errato"
                        @scene.errore = valori_output['esito']
                    elsif utenti_trovati.length > 1
                        valori_output['esito'] = "Errore"
                        valori_output['messaggio_errore'] = "Non c'è un risultato univoco, contattare l'amministratore"
                        @scene.errore = valori_output['esito']
                    else
                        #se l'utente trovato non è ancora stato confermato dall'amministratore non invio la nuova password
                        if utenti_trovati[0].stato == 'confermato' || utenti_trovati[0].stato == 'attivo'
                            #invio username tramite mail 
                            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Servizio Recupero Nome Utente"}
                            scene = Spider::Scene.new
                            scene.nome = utenti_trovati[0].nome
                            scene.cognome = utenti_trovati[0].cognome
                            scene.username = utenti_trovati[0].username
                            send_email('recupera_username', scene, Spider.conf.get('portal.email_from'), utenti_trovati[0].email, headers)
                            valori_output['esito'] = "Ok"
                            valori_output['messaggio_successo'] = "E-mail per recupero username inviata."
                            #redirect self.class.http_s_url('username_inviato') if @request.format != :json
                        else
                            mail_admin = "<a href=\"mailto:#{ Spider.conf.get('portal.email_amministratore') }\">#{Spider.conf.get('portal.email_amministratore')}</a>"
                            valori_output['esito'] = "Errore"
                            valori_output['messaggio_errore'] = "L'utente non è stato confermato dall'amministratore, operazione non possibile<br/>Contattare l'amministratore #{mail_admin}."
                            @scene.errore = valori_output['esito']
                        end
                    end
                end
            end    
            if @request.format == :json
                if valori_output.blank?
                    $out << { :ok => "false",
                              :cod_errore => "effettuare_richiesta_post" }.to_json
                else
                    $out << valori_output.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }.to_json
                end
            else
                
                
                if @request.params['mode'] == 'embed'
                    render 'username_dimenticato', :layout => nil
                else
                    @scene.dati = valori_output
                    render 'username_dimenticato'
                end
            end
        end

        __.html
        __.json
        def password_dimenticata
            valori_output = nil
            if @request.post? && @request.params['mode'] != 'embed'
                campi_da_controllare = ["username_psw_dim", "cf_psw_dim"]
                valori_output = check_input_form(@request.post, campi_da_controllare)
                if valori_output['esito'] == 'ok'
                    username = valori_output['username_psw_dim'].strip
                    cf = valori_output['cf_psw_dim'].strip.upcase
                    utenti_trovati = UtenteLogin.where(:username => username, :codice_fiscale => cf)
                    if utenti_trovati.length == 0
                        valori_output['esito'] = "Errore"
                        valori_output['messaggio_errore'] = "Username o Codice Fiscale errato"
                        @scene.errore = valori_output['esito']
                    elsif utenti_trovati.length > 1
                        valori_output['esito'] = "Errore"
                        valori_output['messaggio_errore'] = "Non c'è un risultato univoco, contattare l'amministratore"
                        @scene.errore = valori_output['esito']
                    else
                        #se l'utente trovato non è ancora stato confermato dall'amministratore non invio la nuova password
                        if utenti_trovati[0].stato == 'confermato' || utenti_trovati[0].stato == 'attivo' 
                            #creare nuova password
                            nuova_password = rand(36**8).to_s(36)
                            #salvare nuova password in db
                            utenti_trovati[0].password = nuova_password
                            utenti_trovati[0].save
                            #invio password tramite mail e redirigo in pagina di esito
                            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Servizio Recupero Password"}
                            scene = Spider::Scene.new
                            scene.nome = utenti_trovati[0].nome
                            scene.cognome = utenti_trovati[0].cognome
                            scene.password = nuova_password 
                            send_email('recupera_password', scene, Spider.conf.get('portal.email_from'), utenti_trovati[0].email, headers)
                            #send_email('amministratore/utente_modificato', scene, Spider.conf.get('portal.email_from'), 
                            #Spider.conf.get('portal.email_amministratore'), headers) 
                            valori_output['esito'] = "Ok"
                            valori_output['messaggio_successo'] = "E-mail per recupero password inviata."
                            #redirect self.class.http_s_url('password_inviata') if @request.format != :json
                        else
                            mail_admin = "<a href=\"mailto:#{ Spider.conf.get('portal.email_amministratore') }\">#{Spider.conf.get('portal.email_amministratore')}</a>"
                            valori_output['esito'] = "Errore"
                            valori_output['messaggio_errore'] = "L'utente non è stato confermato dall'amministratore, operazione non possibile<br/>Contattare l'amministratore #{mail_admin}."
                            @scene.errore = valori_output['esito']
                        end    
                    end
                end
            end
            if @request.format == :json
                if valori_output.blank?
                    $out << { :ok => "false",
                              :cod_errore => "effettuare_richiesta_post" }.to_json
                else
                    $out << valori_output.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }.to_json
                end
            else
                
                if @request.params['mode'] == 'embed'
                    render 'password_dimenticata', :layout => nil
                else
                    @scene.dati = valori_output
                    render 'password_dimenticata'
                end
            end
        end


        #logout che effettua anche la logout cas
        __.action
        def logout
            #se ho fatto un'autenticazione CAS faccio il logout
            if Spider.app?('cas_server') && !@request.utente_portale.blank?
                Spider.logger.debug("-- Effettuo logout CAS --")
                #caso utente registrato sul portale con utente login
                unless @request.utente_portale.utente_login.nil?
                    username = @request.utente_portale.utente_login.username
                    logout_cas(username)    
                #TO-DO: logout cas con utenteesterno
                #else
                    
                end
                
            end
            Spider.logger.debug("-- Cancello sessioni #{@request.session[:auth]} --")
            @request.session[:auth] = nil
            @scene.did_logout = true
            #cancello le variabili in sessione per la strong authentication
            @request.session['strong_auth_valid'] = nil
            @request.session['strong_auth_da_servizio'] = nil
            @request.session['cellulare_appena_modificato'] = nil

            redirect Portal::PortalController.http_s_url
        end


        #metodo utilizzato per app ios, non deve essere cambiato il nome perchè
        #sennò non funziona più pratiche edilizie
        __.json
        def j_login
            user = authenticate(:ignore_cas => true)
            if user
                user.save_to_session(@request.session)
                on_success(user)
                #vecchia versione che restituisce l'id di utente login e non quello del portale
                #h = Portal::UtenteLogin.new(@request.session[:auth]['Portal::UtenteLogin'][:id]).cut(:nome => 0, :cognome => 0, :codice_fiscale => 0, :id => 0)
                utente_login = Portal::UtenteLogin.new(@request.session[:auth]['Portal::UtenteLogin'][:id])
                id_utente_portale = utente_login.utente_portale.id
                h = utente_login.cut(:nome => 0, :cognome => 0, :codice_fiscale => 0)
                h[:id] = id_utente_portale
                h = h.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                #carico tutti gli id dei servizi privati associati all'utente
                array_servizi_privati = []
                user.utente_portale.servizi_privati.each{ |servizio|
                    array_servizi_privati << servizio[:servizio].id if (servizio.stato.id == 'attivo' && servizio.web_service == true)
                }
                $out << { :ok => "true",
                          :sid_sessione => @request.session.sid,
                          :dati_utente => h,
                          :servizi_privati => array_servizi_privati
                        }.to_json
            else
                $out << {:ok => "false"}.to_json
            end
        end
        
        #passo il sid, carico la sessione e faccio un redirect al portale. NON ABILITATA
        # __.action
        # def carica_sessione
        #     sid = @request.params['sid']
        #     portal_action = @request.params['portal_action']
        #     portal_action ||= ''
        #     @request.session = Spider::Session.get(sid)
        #     unless @request.session.blank?
        #         @request.session.restore #carica i dati
        #         utente_login = Portal::UtenteLogin.load(:id => @request.session[:auth]['Portal::UtenteLogin'][:id] )
        #         unless utente_login.blank?
        #             @request.utente_portale = utente_login.utente_portale
        #             utente_login.utente_portale.save_to_session(@request.session)
        #             @request.cookies = Spider::HTTP.parse_query(@request.env['HTTP_COOKIE'], ';')
        #             @response.cookies['sid'] = @request.session.sid
        #             @response.cookies['sid'].path = '/'
        #         else
        #             #utente non trovato
        #             @request.session.flash['errore'] = "Utente non trovato! Rifare l'autenticazione."
        #         end
        #     end
        #     redirect Portal::PortalController.http_s_url(portal_action)
        # end

        def _carica_sessione(sid)
            @request.session = Spider::Session.get(sid)
            unless @request.session.blank?
                @request.session.restore #carica i dati
                return false if @request.session[:auth].blank?
                utente_login = Portal::UtenteLogin.load(:id => @request.session[:auth]['Portal::UtenteLogin'][:id] )
                unless utente_login.blank?
                    @request.utente_portale = utente_login.utente_portale
                    utente_login.utente_portale.save_to_session(@request.session)
                    @request.cookies = Spider::HTTP.parse_query(@request.env['HTTP_COOKIE'], ';')
                else
                    #utente non trovato
                    return false
                end
                return true
            else
                return false
            end
            
        end    

        def cas_user_attributes(user)
            u = user.utente_portale
            h = {
                :id => u.id,
                :nome => u.nome,
                :cognome => u.cognome,
                :codice_fiscale => u.codice_fiscale,
                :sesso => u.sesso ? u.sesso.id : nil
            }
            u.attributi_aggiuntivi.each do |a|
                h[a.attributo_utente.id.to_sym] = a.valore unless a.attributo_utente.blank?
            end
            h
        end
        
        def cas_service_allowed?(service, user)
            #Spider.logger.debug("Controllo se #{user} può accedere al servizio #{service}")
            return false unless user.utente_portale
            srv = CGI::unescape(service.strip.downcase)
            user.servizi_privati.each do |servizio|
                #Spider.logger.debug("Servizio #{servizio.url}")
                return true if srv.index(servizio.url.strip.downcase) == 0
            end
            return false
        end
        
        def get_user
            user = Portal::UtenteLogin.authenticate(:login, :username => @request.params['login'], :password => @request.params['password'])
            return nil unless user && user.utente_portale
            #return nil unless controllo_utente_portale(user.utente_portale)
            return user
        end

        def sanitize_all(param)
            param = Sanitize.fragment(param) unless param.nil?
            param = "" unless (param =~ /((and)|(or)).*[1]+.+[1]+/i).nil?
            param
        end

        private

        #ritorna un hash del tipo
        # { 'esito' : ok/Errore,
        #    'messaggio_errore' : 'Messaggio'/non presente,
        #    'nome_campo_1' : 'blank'/'valore_campo_1',
        #    'nome_campo_2' : 'blank'/'valore_campo_2',
        #    ...
        # } 
        def check_input_form(dati_post, nomi)
            risultato = {
                        "esito" => "ok"
            }
            nomi.each{ |campo_input|
                if dati_post[campo_input].blank?
                    risultato['esito'] = 'Errore'
                    risultato['messaggio_errore'] = 'I campi non possono essere vuoti!'
                    risultato[campo_input] = 'blank'
                else
                    risultato[campo_input] = dati_post[campo_input]
                end
            }
            risultato
        end


    end


end

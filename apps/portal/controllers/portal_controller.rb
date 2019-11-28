# -*- encoding : utf-8 -*-
require 'apps/portal/controllers/mixins/helper_autenticazione'
require 'apps/portal/controllers/controller_autenticazione'
require 'apps/portal/controllers/controller_servizi'
#per prototipo Parabiago
require 'apps/portal/controllers/dossier_controller'
require 'apps/portal/controllers/notifiche_controller'

require 'nokogiri'
require 'open-uri'
require 'mail_verifier' if RUBY_VERSION >= '2'
require 'sanitize'

module Portal
    
    class PortalController < Spider::PageController
        include HTTPMixin, StaticContent
        #include Spider::SAML2Mixin
        include Spider::Messenger::MessengerHelper rescue NameError

        include AutenticazionePortale
        
        route 'api', Portal::APIController
        route 'servizi', Portal::ControllerServizi
        route 'autenticazione', Portal::ControllerAutenticazione
        route %r{configurazione_servizio/(.+)}, :configurazione_servizio
        
        ##route 'newapi', Portal::NewApi

        #per prototipo Parabiago
        route 'dossier_utente', Portal::DossierController
        route 'notifiche', Portal::Notifiche::NotificheController
        
        Portal.auth_providers.each do |provider|
            details = provider.details
            route "auth/#{details[:label]}", details[:controller]
        end
        
        @@layout = {}

        # sso_role :idp
        #         sso_services :sso, :single_logout
        #         saml2_bindings :http_redirect
        
        layout 'portal', :single_layout => true

        def return_401(message_log=nil)
            Spider.logger.error "\n #{message_log}" unless message_log.blank?
            @response.status=401
            @response.headers['Content-Type'] = 'text/html'
            @response.body=["Query string non valida!"]
            #raise Forbidden.new "Query string non valida!"
            done
        end

        def before(action='', *params)
            super
            
            #controllo se sto facendo una chiamata non ajax (es login oauth)..gli attacchi owasp sono get e post
            if !@request.env['HTTP_ACCEPT'].blank? && !@request.env['HTTP_ACCEPT'].include?('json')
                #SI DEVONO CONTROLLARE LE QUERY STRING DI QUESTO TIPO:
                #NON INTERCETTARE
                #service=http%3A%2F%2Fportal.comune.chiampo.vi.it%2Fopenweb%2Fappuntamenti%2Fappuntamenti.php%3Fservizio%3Dappuntamenti%26layout%3Dtrue&amp;ticket=ST-1571391727r5AFC6074300BDE2194
                #ritorno_ricerca=1&[_w][lista_persone][page]=1
                #INTERCETTARE
                # ZAP' AND '1'='1' 
                # query OR 1=1
                # query' OR '1'='1' 
                # ZAP" AND "1"="1"

                #aggiunto controllo centralizzato su query string per attacco sql injection AND '1'='1'
                if !@request.env['QUERY_STRING'].blank? && !(CGI::unescape(@request.env['QUERY_STRING']) =~ /((and)|(or))(.){1,2}[1](.){0,1}[=](.){0,1}[1]/i).nil? #trova un attacco
                    return_401("Errore in query string con controlli sql injection")
                end

                if !@request.params['layout'].blank? && ( @request.params['layout'].to_s != 'true' && @request.params['layout'].to_s != 'false' )
                    return_401("Errore in query string per param layout")
                end
                
                #controllo query string, se ho caratteri da sanificare ritorno 401. La query string la si vede con CGI::unescape
                if !@request.env['QUERY_STRING'].blank? && !(CGI::unescape(@request.env['QUERY_STRING']) =~ /((\%3C)|<)((\%2F)|\/)*[a-z0-9\%]+((\%3E)|>)/ix ).nil? 
                    return_401("Errore in query string per sanificazione caratteri")
                end
            
            end
            

            @scene.portale = true
            @scene.servizi_pubblici = []
            @scene.servizi_privati = []
            @scene.servizi_privati_utente = []
                       
            #permette di personalizzare un messaggio dopo aver fatto il login
            @scene.login_effettuato = true unless @request.utente_portale.blank?
            
            #se sono presenti dei pagamenti pendenti mostro un messaggio all'utente
            if action.blank? && !defined?(Pagamenti).blank? && !@request.utente_portale.blank?  #controllo se sono nella index del portal, solo li mi serve sta variabile
                @scene.pagamenti_pendenti_presenti = Pagamenti.pagamenti_pendenti_presenti?(@request.utente_portale)
            end
            
            # setto nella scene se sono attive le notifiche
            notifiche_attive = false
            if Spider.conf.get('apps').include?('notifiche') && !@request.utente_portale.blank?
                notifiche_attive = true
            end
            @scene.notifiche_attive = notifiche_attive

            #setto nella scene la variabile servizi_privati_utente che contiene i servizi privati attivi per l'utente
            if Portal.const_defined?(:Servizio)
                self.class.carica_elenco_servizi(@scene,@request,@request.utente_portale)
            end
            #ordino la lista dei servizi in base all'attributo posizione
            @scene.servizi_pubblici.sort!{ |a,b| 
                a.ordina_posizione(b) 
            }

            #ordino la lista dei servizi privati in base all'attributo posizione
            @scene.servizi_privati_utente.sort!{ |a,b|
                a.ordina_posizione(b)
            }

            array_action_consentite = ['conferma_gdpr','autenticazione/logout','informativa_gdpr']

            @scene.autenticazione_interna = Spider.conf.get('portal.autenticazione_interna')
            @scene.registrazione_utenti = Spider.conf.get('portal.registrazione_utenti')
            #se ho fatto l'autenticazione interna del portale
            if !@request.utente_portale.blank? && @request.utente_portale.utente_login != nil
                array_action_consentite_psw_normativa = ['conferma_gdpr','autenticazione/logout', 'cambia_password']
                #controllo la scadenza della password se uso la password normativa
                if Spider.conf.get('portal.abilita_password_normativa') && !array_action_consentite_psw_normativa.include?(action)
                    utente = @request.utente_portale
                    #controllo se la data_cambio_password è minore di sei mesi fa
                    data_cambio_password = ( utente.data_cambio_password.blank? ? Date.parse(utente.obj_created.to_s) : utente.data_cambio_password )
                    if Date.today > (data_cambio_password >> 6)
                        redirect PortalController.http_s_url('cambia_password?scaduta=t')
                        done
                    end 

                end
            end    

            #autenticazione forte a livello di login 
            if !@request.utente_portale.blank? && self.class.auth_forte_da_fare(@request)
                #richiedo autenticazione forte
                    # controllo se è attivo un backend per gli sms
                if Spider.conf.get('messenger.sms.backend').blank?
                    Spider.logger.error "Configurare messenger.sms.backend per abilitare l\'autenticazione forte sul sito"
                else

                    #se ho appena confermato il cellulare allora ho anche fatto un'autenticazione forte
                    if @request.session['cellulare_appena_modificato'] == true
                        @request.session['strong_auth_valid'] = true
                        @request.session['cellulare_appena_modificato'] = nil
                    else
                        # controllo se presente il cellulare e se è confermato
                        utente = @request.utente_portale
                        if utente.cellulare.blank?
                            @request.session.flash['errore_strong_auth'] = "Inserire il numero di cellulare nella sezione 'Contatti'"
                            redirect self.class.http_s_url('dettagli_utente?modifica')
                            done
                        else 
                            #cellulare presente, controllo se è confermato o no
                            if utente.cellulare_confermato.blank?
                                @request.session.flash['errore_strong_auth'] = "Per completare l'autenticazione confermare il numero di cellulare inserendo il codice di verifica o inviare un nuovo codice"
                                redirect self.class.http_s_url('dettagli_utente')
                                done
                            else
                                # il cellulare è presente e confermato, mando sms con chiave, salvo chiave in tab Utenti e mostro view
                                utente_da_db = Portal::Utente.new(utente.id)
                                invio_sms_riuscito = utente_da_db.invia_sms_autenticazione_forte
                                Spider.logger.error "Invio sms per auth forte fallito" unless invio_sms_riuscito
                                if invio_sms_riuscito
                                    @request.session.flash['messaggio_info'] = "Inserire il codice di autenticazione inviato tramite sms al suo numero di cellulare #{utente.cellulare}"
                                    redirect self.class.http_s_url('codice_autenticazione')
                                    done
                                end
                            end

                        end
                    end

                end
            end

            if !array_action_consentite.include?(action) #se logout oppure sono sulle action per gdpr non entro
                #se non passo il parametro modifica_provider_esterno controllo se sto usando un provider esterno

                if !@request.utente_portale.blank? 
                    id_utente_portale = @request.utente_portale.id
                    #se sono abilitate le autenticazioni esterne 
                    auth_esterne = Spider.conf.get('portal.autenticazioni_esterne')
                    utente_corrente_esterno = false
                    utente_esterno = nil
                    unless auth_esterne.blank?
                        
                        #l'utente portale ha un utente esterno collegato allora disabilito la modifica
                        auth_esterne.each{ |auth|
                            user_model_auth = "Utente"+Spider::Inflector.camelize(auth)
                            next unless Portal.const_defined?(user_model_auth.to_sym)
                            user_model_auth_klass = Portal.const_get(user_model_auth)
                            utente_esterno = user_model_auth_klass.where( :utente_portale => id_utente_portale )
                            if utente_esterno.length > 0
                                utente_corrente_esterno = true 
                                #carico provider (spid, eidas)
                                #Se sono entrato con EIDAS devo bloccare i campi che l'utente ha deciso di trasmettere
                                #se sono con eidas ho l'utente utente_spid_agid attivo, ricavo provider e campi da bloccare
                                @scene.provider_utente = utente_esterno[0].provider
                                @scene.attributi_eidas = utente_esterno[0].get_attributi_eidas
                            end
                        }
                    end
                    @scene.utente_corrente_esterno = utente_corrente_esterno
                    dati_completi = Portal.controlla_campi_obbligatori(@request.utente_portale)
                    @scene.completa_dati = !dati_completi && Spider.conf.get('portal.completa_campi_auth_esterne')
                    @scene.blocca_campi = Spider.conf.get('portal.campi_readonly_auth_esterne')
                    #carico dalla sessione questa var per bloccare i dati del doc di identità se ci sono i dati da spid
                    @scene.doc_ident_da_spid = @request.session['doc_ident_da_spid'] if @request.session['doc_ident_da_spid'] == true
                    
                    if utente_corrente_esterno && !dati_completi && !@request.params.has_key?('modifica')
                        redirect Portal.http_s_url('dettagli_utente?modifica')
                        done
                    end 
                    # if utente_corrente_esterno && !Portal.controlla_campi_obbligatori(@request.utente_portale) && \
                    #  ( !@request.params['_w'].blank? && @request.params['_w']['form_registrazione'].blank? && @request.params['_w']['form_registrazione']['submit'].blank?)
                    #     redirect Portal.http_s_url('dettagli_utente?modificaProviderEsterno')
                    # end
                end      
            end
            
            #Se ho passato il controllo dei dati spid controllo il gdpr
            #GDPR: se non ho accettato e non ho mai richiesto la cancellazione devo confermare la GDPR
            if Spider.conf.get('portal.abilita_gdpr_utente')
                if !@request.utente_portale.blank? && !array_action_consentite.include?(action)
                    if @request.utente_portale.accettazione_gdpr.blank?
                        redirect self.class.http_s_url('conferma_gdpr')
                        done
                    end
                end
            end


            #setto una var se presente l'url del sito comunale
            @scene.url_sito = Spider.conf.get('portal.url_sito')

            #passo nella scene il numero di comunicazioni private non lette se presente comunicazioni
            if defined?(Comunicazioni) != nil && !@request.utente_portale.blank?
                @scene.num_com_non_lette = Comunicazioni::ComunicazioniController.comunicazioni_private_non_lette(@request.utente_portale.id)
            end
            
            

            #carico nella scene un array con i dati che arrivano da un idp (Federa) per mostrare un messaggio nella pagina di dettaglio utente
            unless @request.session['dati_da_idp'].blank?
                @scene.dati_da_idp = @request.session['dati_da_idp']
            end
            #Gestione layout web o app nativa
            #se arriva il parametro @request.env['layout'] valorizzato a false setto una variabile che poi nelle view uso per togliere testata e footer
            unless @request.env['HTTP_LAYOUT'].blank?
                @scene.layout_web = @request.env['HTTP_LAYOUT'].blank? || (!@request.env['HTTP_LAYOUT'].blank? && @request.env['HTTP_LAYOUT'] == 'true')
                @request.session['layout_web'] = @scene.layout_web #salvo in sessione 
            else
                #se non ho il parametro potrebbe essere il caso dell'app nativa con redirect interni
                @scene.layout_web = ( @request.session['layout_web'].nil? ? true : @request.session['layout_web'] )
            end

            #metto nella scene il sid e id_utente che serve per servizi che usano oauth2 come tributi
            @scene.sid = @request.session.sid unless @request.session.blank?
            @scene.id_utente_portale = @request.utente_portale.id.to_s unless @request.utente_portale.blank?
        end
        
        def self.auth_forte_da_fare(request)
            url_consentiti = Spider.conf.get('portal.url_consentiti_string_auth')
            if Spider.conf.get('portal.abilita_autenticazione_forte') == true && !url_consentiti.include?(request.path) && request.session['strong_auth_valid'].blank? && request.utente_portale.attivo?
                return true
            else
                return false
            end

        end

        def self.auth_forte_da_fare_per_servizio(request)
            if Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true && request.session['strong_auth_valid'].blank? && request.utente_portale.attivo?
                return true
            else
                return false
            end

        end

        __.action
        def amministrazione(action='')
            redirect Spider::Admin.http_s_url("portal/#{action}")
        end

        __.html
        def index
            autenticazione_necessaria if @request.utente_portale
            @scene.flash_errore = @request.session.flash['errore']
            @scene.flash_warning = @request.session.flash['warning']
            @scene.flash_info = @request.session.flash['info']
            @scene.flash_conferma = @request.session.flash['conferma']
            #se arriva un errore da app esterna mostro un messaggio di errore
            if @request.params.has_key?('err')
                @scene.errore_da_app_esterna = true
            end
            if @scene.layout_web 
                if defined?(Comunicazioni) != nil && @request.utente_portale.blank?
                    numero_comunicazioni_pubbliche = Comunicazioni::ComunicazioniController.numero_comunicazioni_portale
                    redirect Comunicazioni.http_s_url if numero_comunicazioni_pubbliche > 0
                end
                #se sono nella index setto una var per mostrare il testo introduttivo
                @scene.index = true
                @scene.mostra_testo_indroduttivo = true
                render 'index'
            else #APP NATIVA
                @scene.index = true
                @scene.mostra_testo_indroduttivo = false
                render 'index'
            end
        end

        __.html :template => 'servizi_pubblici'
        def servizi_pubblici
            @scene.testo_navigatore = 'Servizi Pubblici'
            @scene.lista_servizi_pubblici = true
        end

        __.html :template => 'servizi_privati'
        def servizi_privati
            autenticazione_necessaria unless @request.utente_portale
            #se non sono loggato non mando all'autenticazione ma mostro solo il messaggio
            if @request.utente_portale
                unless controllo_utente_portale(@request.utente_portale)
                    redirect Portal.request_url+'/autenticazione?redirect='+CGI.escape(@request.env['REQUEST_URI'])
                end
            end
            @scene.testo_navigatore = 'Servizi Privati'
            @scene.lista_servizi_privati = true
        end


        #azione chiamata dal menù a sinistra quando è definito comunicazioni per vedere la lista servizi
        __.html
        def indice_servizi
            autenticazione_necessaria if @request.utente_portale
            render 'index'
        end

        __.html
        def html(page)
            raise NotFound.new("Pagina senza nome") if page.blank?
            path = ::File.join(Spider.paths[:data], 'portal', 'html', page)
            path += ".#{@request.format}"
            raise NotFound.new("Html page #{page}") unless ::File.file?(path)
            doc = Nokogiri::HTML(::File.read(path))
            doc.encoding = 'UTF-8'
            @scene.static_html = doc.at('body').inner_html
            if t = doc.at('title')
                title = doc.at('title').content
                title = page.gsub(/[-_]/, '') if title.blank?
                @scene.section_name = title
            end
            render('static_html')
        end

        __.html
        def registrazione
            #se non abilitata la autenticazione interna e vado sulla registrazione faccio redirect
            if Spider.conf.get('portal.registrazione_utenti') == false
                redirect Portal.http_s_url 
            end
            @scene.registrazione = true
            @scene.hide_user = true
            #se ho installato albo_fornitori mostro la registrazione come azienda
            if !defined?(AlboFornitori).blank?
                @scene.mode = :azienda
                @scene.registrazione_aziendale = true
            else
                #se arrivo in post devo aver settato il mode
                if @request.post? || (@request.get? && !@request.params['mode'].blank?) 
                    @scene.mode = @request.params['mode'] == 'azienda' ? :azienda : :persona
                else
                    #se arrivo in get posso essere in login
                    @scene.mode = nil
                end
            end
            
            @scene.model = @scene.mode == :azienda ? Portal::Ditta : Portal::Utente

            if @request.utente_portale && @request.utente_portale.utente_login
                @scene.utente_login = @request.utente_portale.utente_login
            end
            unless Spider.conf.get('portal.registrazione_utenti') || @scene.utente_login
                redirect Portal.http_s_url 
            end
            template = init_template 'registrazione/registrazione'
            form = template.widgets[:form_registrazione]
            form.scene.password_libera = Spider.conf.get('portal.password_libera')
            form.scene.password_normativa = Spider.conf.get('portal.abilita_password_normativa')
            form.scene.cellulare_obbligatorio = Spider.conf.get('portal.cellulare_obbligatorio')
            form.scene.pec_obbligatoria = Spider.conf.get('portal.pec_obbligatoria')
            form.scene.clausole = Spider.conf.get('portal.abilita_accettazione_clausole')
            if Spider.conf.get('portal.comuni_province_tabellate') == true
                form.scene.comuni_prov_tabellati = true
                form.scene.label_comune = 'comune_azienda_tab'
                form.scene.label_provincia = 'provincia_azienda_tab'
            elsif Spider.conf.get('portal.province_tabellate') == true
                form.scene.prov_tabellate = true
                form.scene.label_comune = 'comune_azienda'
                form.scene.label_provincia = 'provincia_azienda_tab'
            else
                form.scene.prov_tabellate = false
                form.scene.label_comune = 'comune_azienda'
                form.scene.label_provincia = 'provincia_azienda'
            end
            if form.params['submit']
                saving = true
                prosegui = false
                if form.params['submit'] == 'Conferma e prosegui'
                    prosegui = true
                end
            end
            form.scene.controllo_cf_errato = 'false'

            if Spider.conf.get('portal.abilita_gdpr_utente')
                #se ho i dati gdpr li carico in pagina
                gdpr = Portal::Gdpr.all.last
                unless gdpr.blank?
                    form.scene.informativa_gdpr = "#{gdpr.autorizzazione}"
                end
            end

            if saving

                form.scene.username = @request.params['username']
                form.scene.password = @request.params['password']
                form.scene.password2 = @request.params['password2']
                form.scene.accettazione_clausole = @request.params['accettazione_clausole']
                form.scene.autorizza_comunicazioni = @request.params['autorizza_comunicazioni']

                if Spider.conf.get('portal.abilita_gdpr_utente')
                    form.scene.accettazione_gdpr = @request.params['accettazione_gdpr'] 
                end
                #controllo generazione codice fiscale
                dati_registrazione = @request.params['_w']['form_registrazione']
                cf = dati_registrazione['codice_fiscale']
                naz_resid = dati_registrazione['stato_residenza']
                
                #caso cf presente e nazione IT
                if !cf.blank? && (!naz_resid.blank? && naz_resid.upcase == 'IT') 
                        nome = dati_registrazione['nome'].gsub(" ","")
                        nome = (nome.respond_to?(:force_encoding) ? nome.force_encoding('UTF-8') : nome)
                        cognome = dati_registrazione['cognome'].gsub(" ","")
                        cognome = (cognome.respond_to?(:force_encoding) ? cognome.force_encoding('UTF-8') : cognome)
                        data_nascita = dati_registrazione['data_nascita']
                        sesso = dati_registrazione['sesso']['value']
                        codice_fiscale = dati_registrazione['codice_fiscale'] 
                        cf = CodiceFiscale.genera_codice(nome, cognome, sesso, data_nascita)
                        #controllo se il codice fiscale è già presente, se si mostro messaggio d'errore e necessaria conferma per proseguire
                        if Portal::Utente.load{ |u| (u.codice_fiscale == codice_fiscale) & (u.email .not nil) & (u.cancellato .not true) } && !prosegui
                            msg_errore = 'Codice fiscale già presente'
                            form.add_error(msg_errore, :codice_fiscale)
                            form.scene.controllo_cf_errato = "Questo codice fiscale risulta già registrato al portale. Se è il tuo e hai dimenticato username o password puoi
                            <a href=\"#{Portal::PortalController.http_s_url('autenticazione/password_dimenticata')}\">recuperare la password</a> oppure 
                            <a href=\"#{Portal::PortalController.http_s_url('autenticazione/username_dimenticato')}\">recuperare l'username</a>."
                        elsif codice_fiscale[0,11].upcase != cf && !prosegui
                            form.add_error("I dati inseriti non corrispondono con il codice fiscale.", :codice_fiscale)
                            form.scene.controllo_cf_errato = 'Il codice fiscale non corrisponde con i dati inseriti, confermare e proseguire comunque?'
                        elsif !Portal::CodiceFiscale.check_codice_fiscale(codice_fiscale,'complete') && !prosegui
                            form.add_error("Codice fiscale non corretto.", :codice_fiscale)
                            form.scene.controllo_cf_errato = 'Il codice fiscale non contiene un codice di controllo corretto.'
                        end    
                end
                if @request.params['username'].blank?
                    form.add_error("Devi scegliere un nome utente.", :username)
                end
                if Portal::UtenteLogin.load{ username == @request.params['username'] }
                    msg_errore = "Il nome utente scelto non è disponibile. Per favore, scegli un altro nome utente oppure se hai scordato la password puoi
                            <a href=\"#{Portal::PortalController.http_s_url('autenticazione/password_dimenticata')}\">recuperarla qui</a>."
                    form.add_error(msg_errore, :username)
                end
                #se portal.azienda_univoca a true controllo che codice fiscale e partita iva dell'azienda siano univoci
                if Spider.conf.get('portal.azienda_univoca') && @request.params['mode'] == 'azienda'
                    cf_azienda = @request.params['_w']['form_registrazione']['codice_fiscale_azienda']
                    unless cf_azienda.blank?
                        #cerco in db il cf dell'azienda
                        aziende_cf = Portal::Ditta.where(:codice_fiscale_azienda => cf_azienda)
                        if aziende_cf.length > 0
                            msg_errore = "Il codice fiscale dell'azienda è già presente."
                            form.add_error(msg_errore, :codice_fiscale_azienda)
                        end
                    end
                    piva_azienda = @request.params['_w']['form_registrazione']['partita_iva']
                    unless piva_azienda.blank?
                        #cerco in db la partita iva dell'azienda
                        aziende_piva = Portal::Ditta.where(:partita_iva => piva_azienda)
                        if aziende_piva.length > 0
                            msg_errore = "La partita IVA dell'azienda è già presente."
                            form.add_error(msg_errore, :partita_iva)
                        end
                    end
                end

                if !form.params['email'].blank? 
                    #faccio il downcase della mail per salvare sempre la versione in minuscolo dell'indirizzo
                    form.params['email'] = form.params['email'].downcase
                    if Portal::Utente.load{ |u| (u.email == form.params['email']) }
                        msg_errore = "Questa e-mail risulta già registrata al portale."
                        #if Spider.conf.get('portal.password_libera')
                            #msg_errore += " Se hai scordato la password, puoi <a href=\"#{Portal.http_s_url}/recupero_password\">recuperarla da qui</a>."
                        #else
                            msg_errore += " Se hai scordato username o password puoi
                            <a href=\"#{Portal::PortalController.http_s_url('autenticazione/password_dimenticata')}\">recuperare la password</a> oppure 
                            <a href=\"#{Portal::PortalController.http_s_url('autenticazione/username_dimenticato')}\">recuperare l'username</a>."
                        #end
                        form.add_error(msg_errore, :email)
                    else
                        if RUBY_VERSION >= '2' #gemma non presente in ruby 1.9
                            #controllo che si un indirizzo email esistente, non funziona per mail yahoo
                            begin
                                esito_verifica_email = MailVerifier.verify(Spider.conf.get('portal.email_from'), form.params['email'])
                                form.add_error("Indirizzo e-mail inesistente.", :email) unless esito_verifica_email
                            rescue MailVerifier::NoMailServerException => exc_no_dominio
                                form.add_error("Il dominio di posta dell' e-mail indicata non è corretto.", :email)
                            rescue Exception => exc
                                form.add_error(exc.message, :email)
                            end
                        end
                    end    
                end
                if Spider.conf.get('portal.password_libera')
                    if @request.params['password'].blank?
                        form.add_error("Devi scegliere una password.", :password)
                    elsif @request.params['password'] != @request.params['password2']
                        form.add_error("Le due password non coincidono.", :password2)
                    elsif @request.params['password'].length < 8 && Spider.conf.get('portal.abilita_password_normativa')
                        form.add_error("La password deve essere di almeno 8 caratteri.", :password)
                    end
                end
                #accettazione clausole
                if Spider.conf.get('portal.abilita_accettazione_clausole')
                    acc_clausole = @request.params['accettazione_clausole']
                    form.add_error("Devi accettare l'Informativa sul Trattamento dei Dati Personali e sulla Privacy", :accettazione_clausole) if acc_clausole.blank?
                end

                #pec obbligatoria
                if Spider.conf.get('portal.pec_obbligatoria')
                    pec = @request.params['_w']['form_registrazione']['pec']
                    form.add_error("Il campo Pec è obbligatorio", :pec) if pec.blank?
                end
                #cellulare obbligatoria
                if Spider.conf.get('portal.cellulare_obbligatorio')
                    cellulare = @request.params['_w']['form_registrazione']['cellulare']
                    form.add_error("Il Cellulare è obbligatorio", :cellulare) if cellulare.blank?
                end
                if Spider.conf.get('portal.abilita_gdpr_utente')
                    #accettazione GDPR
                    acc_gdpr = @request.params['accettazione_gdpr']
                    form.add_error("Devi accettare l'Informativa GDPR per poterti registrare.", :accettazione_gdpr) if acc_gdpr.blank?
                end


            end
            template.exec
            #filtro con il codice della provincia
            # if Spider.conf.get('portal.comuni_province_tabellate') == true
            #     form.inputs[:comune_azienda_tab].condition = { :provincia => 823 }
            #     form.inputs[:comune_azienda_tab].run
            # end
            
            @scene.saved = form.saved?
            @scene.error = form.error?
            if form.saved?
                utente_login = Portal::UtenteLogin.new
                utente_login.username = @request.params['username']
                utente_login.password = @request.params['password'] if Spider.conf.get('portal.password_libera')
                utente_login.save
                utente = form.obj
                
                #caso cf non presente e residenza estera, carico il campo con EE_paddingzeroIDUTENTE
                if cf.blank? && (!naz_resid.blank? && naz_resid.upcase != 'IT')
                   utente.codice_fiscale = "EE_"+utente.id.to_s.rjust(13, '0')
                end

                utente.utente_login = utente_login
                #salvo il campo a true nell'accettazione clausole
                utente.accettazione_clausole = true if @request.params['accettazione_clausole'] == 'true'
                if Spider.conf.get('portal.abilita_gdpr_utente')
                    #salvo il campo a true nell'accettazione del gdpr
                    if @request.params['accettazione_gdpr'] == 'true'
                        utente.accettazione_gdpr = true 
                        utente.data_ora_accettazione_gdpr = DateTime.now
                    end
                end

                utente.registrato!
                utente.save
                #Spider::Worker.in('1d', "Portal::Utente.controllo_registrazione(#{utente.id})")
                @request.session['utente_registrato'] = utente.id if ( !utente.blank? && utente.is_a?(Portal::Utente) )
                Spider.logger.error "** salvo in sessione l'id #{utente.id.to_s}" if ( !utente.blank? && utente.is_a?(Portal::Utente) )
                @request.session['utente_registrato'] = utente.referente.id if ( !utente.blank? && utente.is_a?(Portal::Ditta) )
                Spider.logger.error "** salvo in sessione l'id ditta #{utente.referente.id.to_s}" if ( !utente.blank? && utente.is_a?(Portal::Ditta) )
                utente_autenticato(utente_login, :login, true)
                redirect(self.class.http_s_url('registrazione_eseguita'), Spider::HTTP::FOUND)
            else
                render(template)
            end
        end
        
        #metodi che servono per essere richiamati da js che fanno il render delle views con il testo delle informative
        #usate in fase di registrazione

        __.action
        def informativa_dati_personali
            render 'registrazione/trattamento_dati', :layout => nil
        end


        __.action
        def informativa_privacy
            render 'registrazione/informativa_privacy', :layout => nil
        end


        __.html
        def registrazione_eseguita
            utente = Portal::Utente.new(@request.session['utente_registrato'])
            if utente.blank?
                Spider.logger.error "++ errore registrazione eseguita sessione nulla ++" if @request.session['utente_registrato'].blank? 
                render('registrazione/conferma_contatti_errore')
            else
                @scene.utente = utente
                @scene.stato = utente.stato.id
                if utente.stato.id == 'contatti'
                    descrizioni_contatti = {
                        'cellulare' => 'il numero di cellulare',
                        'email' => "l'indirizzo e-mail"
                    }
                    @scene.contatti_pendenti = []
                    @scene.pendenti = {}
                    utente.modifiche_contatti_pendenti.each do |m|
                        @scene.contatti_pendenti << descrizioni_contatti[m.tipo]
                        @scene.pendenti[m.tipo] = m
                    end
                    render('registrazione/attesa_contatti')
                elsif utente.stato.id == 'attesa'
                    render('registrazione/conferma_attesa')
                else
                    render('registrazione/conferma_attivo')
                end
            end
            
        end


        __.html
        def conferma_email # Per compatibilità versione 1.0.1; TODO: togliere
            redirect(self.class.http_s_url("controllo_email?controllo=#{@request.get['controllo']}"))
        end
        
        __.html
        def invia_seconda_conferma
            @request.utente_portale.stato = "seconda_conferma"
            @request.utente_portale.save
            redirect self.class.http_s_url('attesa_seconda_conferma')
            done
        end


        __.html :template => 'registrazione/attesa_seconda_conferma'
        def attesa_seconda_conferma
            @scene.utente = @request.utente_portale
            descrizioni_contatti = {
                'cellulare' => 'il numero di cellulare',
                'email' => "l'indirizzo e-mail"
            }
            @scene.contatti_pendenti = []
            @scene.pendenti = {}
            @scene.utente.modifiche_contatti_pendenti.each do |m|
                @scene.contatti_pendenti << descrizioni_contatti[m.tipo]
                @scene.pendenti[m.tipo] = m
            end
        end


        def conferma_contatto_registrazione(utente, pendente)
            stato_precedente = utente.stato.id
            utente.conferma_contatto(pendente.tipo, pendente)
            @scene.utente = utente
            if utente.stato.id == 'contatti' || utente.stato.id == 'seconda_conferma'
                descrizioni_contatti = {
                    'cellulare' => 'il numero di cellulare',
                    'email' => "l'indirizzo e-mail"
                }
                @scene.contatti_pendenti = []
                @scene.modifiche = {}
                utente.modifiche_contatti_pendenti.each do |m|
                    @scene.contatti_pendenti << descrizioni_contatti[m.tipo]
                    @scene.modifiche[m.tipo] = m
                end
                @scene.modificato = case pendente.tipo
                when 'email'
                    'indirizzo e-mail'
                when 'cellulare'
                    'numero di cellulare'
                end
                @scene.modifiche_pendenti = utente.modifiche_contatti_pendenti
                render('registrazione/conferma_contatto_attesa_altri')
            elsif utente.stato.id == 'attesa'
                email_amministratore_attesa_conferma(utente.id)
                @scene.conferma_contatti = true
                render('registrazione/conferma_attesa')
            else # attivo o confermato
                email_amministratore_utente_registrato(utente.id)
                render('registrazione/conferma_attivo')
            end
        end
        
        def controllo_contatto(tipo)
            raise NotFound.new("Controllo tipo #{tipo}") unless ['email', 'cellulare'].include?(tipo)
            @scene.tipo = tipo
            #se non ho gli sms attivi non faccio il controllo dell'sms
            if tipo == 'cellulare' && Spider.conf.get('messenger.sms.backend').blank?
                @scene.controllo_non_attivo = true
                @scene.errore = true
                return render 'controllo_contatti'
            end
            utente = nil
            pendente = nil
            if @request.params['controllo']
                pendente = ModificaContatto.load(:chiave_conferma => @request.params['controllo'])
                utente = pendente.utente if pendente
            else
                #se non arriva la chiave di controllo vuol dire che ho cliccato sull'invio di un nuovo controllo
                autenticazione_necessaria(false)
                #carico l'utente in @request
                utente = @request.utente_portale
                pendente = utente.modifica_contatto_pendente(tipo) if utente
            end
            unless utente
                return render 'controllo_contatti'
            end
            if !pendente
                if tipo == 'cellulare' && !utente.cellulare_confermato 
                    cell = utente.cellulare
                    utente.cellulare = nil
                    pendente = utente.modifica_cellulare(cell)
                else
                    @scene.errore = true
                    @scene.no_pendenti = true
                    return render 'controllo_contatti'
                end
            end
            #se non sono obbligatori le conferme di mail e cellulare pendente == nil
            unless pendente.blank?
                pendente.conferme_mandate ||= 0
                @scene.pendente = pendente
            end
            
            if @request.params.key?('invia')
                if pendente.conferme_mandate > Spider.conf.get("portal.invio_conferme_max.#{tipo}")
                    @scene.max_controlli = true
                    return render 'controllo_contatti'
                end
                if tipo == 'email'
                    utente.invia_controllo_email(pendente)
                elsif tipo == 'cellulare'
                    utente.invia_sms_controllo_cellulare(pendente)
                end
                redirect Portal.http_s_url("controllo_#{tipo}")
            elsif @request.params['controllo']
                unless pendente.chiave_conferma == @request.params['controllo']
                    @scene.codice_non_corretto = true
                    return render 'controllo_contatti'
                end
                if utente.stato.id == 'contatti' || utente.stato.id == 'seconda_conferma'
                    #in questo caso devo anche cambiare lo stato in attivo se c'è l'attivazione automatica
                    #viene chiamato utente.conferma_contatto(tipo,modifica) 
                    return conferma_contatto_registrazione(utente, pendente)
                end
                utente.conferma_contatto(tipo)
                if tipo == 'email'
                    @scene.email_confermata = true
                else
                    #ho confermato il cellulare
                    #se è abilitata l'auth forte salvo una var in sessione per controllarla nel before del portal_controller
                    #se ho confermato il cellulare ho fatto anche una strong authentication
                    @request.session['cellulare_appena_modificato'] = true if (Spider.conf.get('portal.abilita_autenticazione_forte') == true || Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true )
                    @scene.cellulare_confermato = true
                end
            end
            render 'controllo_contatti'
        end
        
        __.html
        def controllo_cellulare
            controllo_contatto('cellulare')
        end
        
        __.html
        def controllo_email
            controllo_contatto('email')
        end

        #vecchio metodo logout, spostato in controller_autenticazione per problemi col CAS
        __.action
        def logout
            redirect Portal::ControllerAutenticazione.http_s_url('logout')
        end

        __.action
        def abilita_comunicazioni
            autenticazione_necessaria
            utente = @request.utente_portale
            utente.disabilita_comunicazioni = false
            utente.save
            redirect self.class.http_s_url('dettagli_utente')
        end

        __.action
        def disabilita_comunicazioni
            autenticazione_necessaria
            utente = @request.utente_portale
            utente.disabilita_comunicazioni = true
            utente.save
            redirect self.class.http_s_url('dettagli_utente')
        end
        
        __.html :template => 'utente/dettagli_utente'
        def dettagli_utente
            autenticazione_necessaria
            #se è abilitata l'autenticazione forte carico dalla sessione un messaggio d'errore se presente
            @scene.errore_strong_auth = @request.session.flash['errore_strong_auth'] if Spider.config.get('portal.abilita_autenticazione_forte') == true || Spider.config.get('portal.abilita_autenticazione_forte_per_servizio') == true
            @scene.dati_modificati = @request.session.flash[:dati_modificati]
            @scene.utente = @request.utente_portale
            @scene.autenticazioni = @request.autenticazioni
            @scene.providers = Portal.auth_providers.select{ |prov| @request.autenticazioni[prov.details[:label]] }
            @scene.cambia_password = @request.params.has_key?('cambia_password')
            mail_admin = "<a href=\"mailto:#{ Spider.conf.get('portal.email_amministratore') }\">#{Spider.conf.get('portal.email_amministratore')}</a>"
            if ( (Spider.conf.get('portal.attivazione_utenti_automatica') == 'false' && @request.utente_portale.stato != 'confermato' ) || ( Spider.conf.get('portal.attivazione_utenti_automatica') == 'true' && @request.utente_portale.stato != 'attivo' && @request.utente_portale.stato != 'confermato' ) && !@request.session.flash[:password_cambiata] ) 
                @scene.messaggio_cambia_password = "Spiacente non puoi cambiare la password, la tua utenza non è ancora stata confermata dall'amministratore.<br /> Per informazioni scrivi a #{mail_admin}." 
            end
            #se l'utente è nello stato confermato mostro il link cambia password settando link_cambio_password_attivo, se no non setto e mostro solo scritta semplice
            @scene.link_cambio_password_attivo = true if ['attivo','confermato'].include?(@request.utente_portale.stato.id)
            @scene.messaggio_cambia_password = "Password cambiata." if @request.session.flash[:password_cambiata]
            @scene.modifica = @request.params.has_key?('modifica') 
            
            @scene.servizi_utente_confermato = Servizio.where(:accesso => 'confermati')
            modifica_cellulare_pendente = @scene.utente.modifica_contatto_pendente('cellulare')
            @scene.modifica_cellulare_pendente = modifica_cellulare_pendente
            @scene.invio_sms_attivo = Spider.const_defined?(:Messenger) && Spider::Messenger.backends[:sms]
            modifica_email_pendente = @scene.utente.modifica_contatto_pendente('email')
            @scene.modifica_email_pendente = modifica_email_pendente
            
            @scene.password_libera = Spider.conf.get('portal.password_libera')
            @scene.password_cambiabile = Spider.conf.get('portal.password_cambiabile')
            #se ho installato albo_fornitori mostro la registrazione come azienda
            if !defined?(AlboFornitori).blank? && !@request.utente_portale.ditta.blank?
                @scene.mode = :azienda
                @scene.registrazione_aziendale = true
            else
                @scene.mode = @request.params['mode'] == 'azienda' ? :azienda : :persona
            end

            #se attivo modulo comuinicazioni, posso disabilitare l'invio
            if Spider.const_defined?(:Comunicazioni)
                @scene.disabilita_comunicazioni = true
            end

            #se l'utente ha inserito i dati dell'azienda mostro la modifica dei dati aziendali
            if !@request.utente_portale.ditta.blank?
                @scene.mode = :azienda
                @scene.registrazione_aziendale = true
            end
            @scene.model = @scene.mode == :azienda ? Portal::Ditta : Portal::Utente
            @scene.id_form = @scene.mode == :azienda ? @request.utente_portale.ditta.id : @request.utente_portale.id
            
            
            template = init_template 'utente/dettagli_utente'
            form = template.widgets[:form_registrazione]
            if form
                #modifica per province tabellate da civilia
                if Spider.conf.get('portal.comuni_province_tabellate') == true
                    form.scene.comuni_prov_tabellati = true
                    form.scene.label_comune = 'comune_azienda_tab'
                    form.scene.label_provincia = 'provincia_azienda_tab'
                elsif Spider.conf.get('portal.province_tabellate') == true
                    form.scene.prov_tabellate = true
                    form.scene.label_comune = 'comune_azienda'
                    form.scene.label_provincia = 'provincia_azienda_tab'                         
                else
                    form.scene.prov_tabellate = false
                    form.scene.label_comune = 'comune_azienda'
                    form.scene.label_provincia = 'provincia_azienda'
                end
                form.scene.pec_obbligatoria = Spider.conf.get('portal.pec_obbligatoria')
                form.scene.cellulare_obbligatorio = Spider.conf.get('portal.cellulare_obbligatorio')
            end
            #se sono in post e sto modificando la password
            if @request.post? && @request.params['password']
                if @scene.password_libera || @scene.password_cambiabile
                    @scene.cambia_password = true
                    if @request.params['password'].blank?
                        @scene.errore_cambia_password = "La password non può essere vuota."
                    elsif @request.params['password'] != @request.params['password2']
                        @scene.errore_cambia_password = "Le due password non coincidono."
                    elsif Spider.conf.get('portal.abilita_password_normativa') == true && Spider::DataTypes::Password.check_match(@request.utente_portale.utente_login.password,@request.params['password'])
                        @scene.errore_cambia_password = "La nuova password deve essere diversa dalla precedente."
                    elsif Spider.conf.get('portal.abilita_password_normativa') == true && @request.params['password'].length < 4 
                        @scene.errore_cambia_password = "La nuova password deve essere di almeno 8 caratteri."
                    else
                        #anche se è attiva la password normativa salvo la data di cambio password
                        utenti_trovati = Portal::Utente.where{ |utente| (utente.codice_fiscale == @request.utente_portale.codice_fiscale) & (utente.utente_login.username == @request.utente_portale.utente_login.username)}
                        utenti_trovati[0].data_cambio_password = Date.today
                        utenti_trovati[0].save
                        
                        @request.autenticazioni[:login].password = @request.params['password']
                        @request.autenticazioni[:login].save
                        @request.session.flash[:password_cambiata] = true
                        redirect(Portal.url+'/dettagli_utente')
                    end 
                end
            #se sono in post
            elsif @request.post?
                utente = nil
                template.widgets[:form_registrazione].on(:before_save) do |obj|
                    if obj.id && ( obj.class == Portal::Utente || obj.referente.class == Portal::Utente  ) 
                        utente = ( obj.class == Portal::Utente ?  Portal::Utente.load(:id => obj.id) : Portal::Utente.load(:id => obj.referente.id) ) 
                        unless obj.email.blank?
                            if obj.email != utente.email
                                qs_mail_presente = Portal::Utente.where{ |ut| (ut.email == obj.email.strip) }
                                mail_presente = (qs_mail_presente.blank? ? false : (qs_mail_presente.length > 0))
                                #se presente una sola volta la mail in db blocco, la nuova mail modificata si salva quando conferma la mail col link
                                if mail_presente
                                    form.add_error("E-Mail già presente", :email)
                                else
                                    nuova = obj.email
                                    obj.email = utente.email unless utente.email.blank? #se c'era una mail vuota non la inserisco altrimenti errore di validazione
                                    obj.modifica_email(nuova)
                                end
                            elsif modifica_email_pendente && modifica_email_pendente.prima
                                obj.email_confermata = true
                                modifica_email_pendente.delete
                            end                           
                        end
                        if obj.cellulare.blank?
                            if Spider.conf.get('portal.cellulare_obbligatorio')
                                form.add_error("Il numero di cellulare è obbligatorio", :cellulare)
                            end
                        else
                            if obj.cellulare != utente.cellulare
                                nuovo = obj.cellulare
                                obj.cellulare = (utente.cellulare.nil? ? utente.cellulare : utente.cellulare.gsub(" ",""))
                                obj.modifica_cellulare(nuovo)
                                @request.session.flash['cellulare_modificato'] = "Numero di cellulare modificato con successo, confermare il numero con il codice inviato per sms"

                            elsif modifica_cellulare_pendente && modifica_cellulare_pendente.prima
                                obj.cellulare_confermato = true
                                modifica_cellulare_pendente.delete
                            end
                        end
                    end
                end
                #AGGIUNTA PER GESTIONE MODIFICHE SEZIONI CON DATI ANAGRAFICI
                if !defined?(AlboFornitori).blank?
                    #controllo se sono cambiati dei campi
                    template.widgets[:form_registrazione].on(:save) do |obj, save_mode|
                        if utente
                            cambiati = Spider::OrderedHash.new
                            cambiato = false
                            Utente.elements_array.select{ |el| el.attributes[:conferma] }.each do |el|
                                prima = utente.get(el)
                                dopo = obj.get(el)
                                if prima != dopo
                                    cambiato = true
                                    cambiati[el.label] = [prima, dopo].convert_object
                                end
                            end
                            if cambiato
                                #se servizio albo_fornitori attivo aggiungo una modifica sezione per i dati anagrafici
                                    unless utente.richieste_albo_fornitore.blank?
                                        modifica_sezione = AlboFornitori::ModificaSezione.load(:sezione => 'dati_anagrafici')
                                        utente.richieste_albo_fornitore.each{ |richiesta|
                                            if richiesta.data_fine_validita.blank?
                                                trovato = false
                                                richiesta.modifiche_sezioni.each{ |mod_sezione|
                                                    if mod_sezione.modifica_sezione.sezione == 'dati_anagrafici'
                                                        trovato = true
                                                        mod_sezione.data_modifica = DateTime.now 
                                                    end
                                                }
                                                unless trovato
                                                    #la inserisco
                                                    richiesta.modifiche_sezioni << { :modifica_sezione => modifica_sezione, :data_modifica => DateTime.now }
                                                end
                                                richiesta.save
                                            end
                                        }
                                    end
                                utente.save
                                #mando la mail all'admin con i campi che sono stati modificati
                                email_amministratore_utente_modificato(utente, obj, cambiati)
                            end
                        end
                    end
                elsif @scene.utente.confermato? 
                    #controllo per gli utenti confermati:
                    # se l'utente è stato confermato da admin oppure se arriva da un auth esterna (che mette lo stato a confermato)
                    # controllo se ci sono dei campi (con conferma a true) che mancano -> se utente portale creato oggi, come nel caso delle auth esterne 
                    # faccio completare i dati anagrafici e tengo il 'confermato' come stato. Se non da auth esterna, era confermato ma ha cambiato dei dati
                    # che richiedono conferma gli metto lo stato 'attivo' e mando la mail all'admin
                    template.widgets[:form_registrazione].on(:save) do |obj, save_mode|
                        if utente
                            cambiati = Spider::OrderedHash.new
                            cambiato = false
                            stato_precedente = nil
                            Utente.elements_array.select{ |el| el.attributes[:conferma] }.each do |el|                              
                                prima = utente.get(el)
                                #per lo stato, se era confermato mantengo confermato..modifica del 16/09/2016 per LEGNANO
                                stato_precedente = prima if el.name.to_s == 'stato' #che deve essere sempre confermato lo stato..
                                dopo = obj.get(el)
                                if prima != dopo
                                    cambiato = true
                                    cambiati[el.label] = [prima, dopo]
                                end
                            end
                            if cambiato
                                if !cambiati['Codice fiscale'].blank?
                                    #ho cambiato il codice fiscale => stato 'attivo e mail a admin'
                                    utente.stato = 'attivo'
                                    utente.save
                                    email_amministratore_utente_modificato(utente, obj, cambiati) #serve conferma dei dati utente da admin
                                else
                                    #non ho cambiato cf, stato precedente
                                    creato_oggi = utente.obj_created.to_date == Date.today
                                    utente.stato = stato_precedente #l'utente non perde lo stato precedente con queste modifiche
                                    utente.save
                                    #se utente viene creato oggi (caso auth esterne) non mando mail a admin, altrimenti mando mail ma non serve conferma
                                    email_amministratore_utente_modificato(utente, obj, cambiati,false) unless creato_oggi
                                end
                            end
                        end
                    end
                end
                #mostro il messaggio di modifica dei dati utente
                @request.session.flash[:dati_modificati] = "Dati modificati correttamente" unless form.error?
            #se sono in get e modifica abilitata
            elsif @scene.modifica
                # Visualizza nuovi dati nella form
                email_mostrata = nil
                cellulare_mostrato = nil
                if modifica = @scene.modifica_email_pendente
                    email_mostrata = modifica.dopo 
                    @scene.email_precedente = modifica.prima
                end
                if modifica = @scene.modifica_cellulare_pendente
                    cellulare_mostrato = modifica.dopo
                    @scene.cellulare_precedente = modifica.prima
                end
                template.widgets[:form_registrazione].on(:after_load) do |obj|
                    obj.email = email_mostrata if email_mostrata
                    obj.cellulare = cellulare_mostrato if cellulare_mostrato
                end
               
            end
        end

        __.action
        def configurazione_servizio(id)
            servizio = Portal::Servizio.load(:id => id)
            raise NotFound.new("Servizio portale #{id}") unless servizio
            servizio_portale = Portal.servizi[id]
            if servizio_portale && servizio_portale.url_configurazione
                redirect Portal.http_s_url(:servizi)+servizio_portale.url_configurazione
            elsif servizio_portale
                redirect servizio_portale.http_s_url
            else
                redirect servizio.url
            end
        end
        
        def get_user_attributes
            unless @request.session[:auth] && @request.session[:auth]['Spider::Auth::LoginUser']
                raise Spider::Auth::Unauthorized
            end
            return {}
        end
        
        
        def email_amministratore_attesa_conferma(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "#{Portal.http_s_url('amministrazione')}/utenti_login/#{scene.utente.utente_login.id}"
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'), 
                Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "#{Portal.http_s_url('amministrazione')}/utenti_login/#{scene.utente.utente_login.id}"
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'), 
                Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def self.email_attivazione_utente(utente)
            scene = Spider::Scene.new
            scene.utente = utente
            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Attivazione account portale dei servizi"}
            Spider::Messenger::MessengerHelper.send_email(self, 'attivazione_account', scene, Spider.conf.get('portal.email_from'),
                utente.email, headers)
        end
        
        #mail di attivazione account all'inserimento dell'utente da Api per integrazione openweb-master con CiviliaOpen
        def self.email_attivazione_utente_da_civilia(scene)
            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Attivazione account portale dei servizi"}
            Spider::Messenger::MessengerHelper.send_email(self, 'attivazione_account_da_civilia', scene, Spider.conf.get('portal.email_from'),
                scene.utente.email, headers)
        end

        def email_amministratore_utente_modificato(utente, nuovo, cambiati,conferma=true)
            scene = Spider::Scene.new
            scene.utente = utente
            scene.cambiati = cambiati
            scene.link_amministrazione = utente.link_amministrazione
            headers = {'Subject' => "Modifica dati utente portale"}
            if conferma
                send_email('amministratore/utente_modificato', scene, Spider.conf.get('portal.email_from'),Spider.conf.get('portal.email_amministratore'), headers)
            else
                send_email('amministratore/utente_modificato_no_conferma', scene, Spider.conf.get('portal.email_from'),Spider.conf.get('portal.email_amministratore'), headers)
            end

        end

        __.html :template => 'cambia_password'
        def cambia_password
            unless @request.params['scaduta'].blank?
                @scene.msg_scaduto = "La password non è stata cambiata negli ultimi sei mesi, per la sua sicurezza si prega di cambiarla." if @request.params['scaduta'] == 't'
            end
            autenticazione_necessaria
            @scene.cambio_password_abilitato = Spider.conf.get('portal.password_libera')
            return unless Spider.conf.get('portal.password_libera')

            @scene.error_class_old_password = ""
            @scene.error_class_new_password = ""
            @scene.error_class_new_password_confirm = ""
            @scene.success_message = nil
            utente_portale = @request.utente_portale
            #controllo se la data_cambio_password è minore di sei mesi fa
            data_cambio_password = ( utente_portale.data_cambio_password.blank? ? Date.parse(utente_portale.obj_created.to_s) : utente_portale.data_cambio_password )
            if Date.today < (data_cambio_password >> 6)
                redirect PortalController.http_s_url
            end 
            if @request.post? && @request.params['cambia_password'] == 'Modifica'
                
                #controllo che tutti e tre i campi siano non vuoti
                old_password = @request.params['old_password']
                new_password = @request.params['new_password']
                new_password_confirm = @request.params['new_password_confirm']
                if !old_password.blank? && !new_password.blank? && !new_password_confirm.blank?
                    #controllo che la vecchia password sia come quella registrata
                    if Spider::DataTypes::Password.check_match(utente_portale.utente_login.password, old_password)
                        #controllo che le due nuove password coincidano, che la nuova sia diversa dalla vecchia e che la nuova password sia di almeno 8 caratteri
                            if old_password == new_password
                                @scene.error_message = "La nuova password deve essere diversa dalla precedente."
                                @scene.error_class_old_password = @scene.error_class_new_password = "error"
                            elsif new_password != new_password_confirm
                                @scene.error_message = "Le due password non coincidono."
                                @scene.error_class_new_password = @scene.error_class_new_password_confirm = "error"
                            elsif new_password.length < 8 && Spider.conf.get('portal.abilita_password_normativa')
                                @scene.error_message = "La nuova password deve essere di almeno 8 caratteri"
                                @scene.error_class_new_password = @scene.error_class_new_password_confirm = "error"
                            else
                                #ho passato i controlli, sostituisco la password e imposto la data di cambio password
                                utenti_trovati = Portal::Utente.where{ |utente| (utente.codice_fiscale == utente_portale.codice_fiscale) & (utente.utente_login.username == utente_portale.utente_login.username)}
                                utenti_trovati[0].utente_login.password = new_password
                                utenti_trovati[0].data_cambio_password = Date.today
                                utenti_trovati[0].save_all
                                @scene.success_message = "La password è stata modificata con successo. Fare il logout e poi effettuare il login con la nuova password."
                                @scene.msg_scaduto = nil
                            end
                    else
                        @scene.error_message = "La vecchia password non è corretta."
                        @scene.error_class_old_password = "error"
                    end
                else
                    @scene.error_message = "Tutti i campi sono obbligatori."
                    @scene.error_class_old_password = @scene.error_class_new_password = @scene.error_class_new_password_confirm = "error"
                end

            end
        end

        # inserisco il codice per l'autenticazione forte che arriva per sms

        __.html :template => 'codice_autenticazione'
        def codice_autenticazione
            @scene.link_cambio_dati = self.class.http_s_url('dettagli_utente')
            #controllo che almeno sia autenticato con username e password 
            autenticazione_necessaria
            utente = @request.utente_portale

            @scene.messaggio_info = @request.session.flash['messaggio_info'] unless @request.session.flash['messaggio_info'].blank?

            #se mi arriva un servizio in get
            serv_id = @request.params['servizio_id']
            if !serv_id.blank? && @request.get?
                serv = Portal::Servizio.where{ |s| s.id == serv_id}
                @request.session['strong_auth_da_servizio'] = { :id => serv_id, :url => serv[0].url}
            end    

            autenticazione_abilitata = false
            if @request.get? && self.class.auth_forte_da_fare_per_servizio(@request) && @request.params['repeat'].blank?
                # controllo se è attivo un backend per gli sms
                if Spider.conf.get('messenger.sms.backend').blank?
                    @scene.messaggio_errore = "Non è possibile procedere con l'autenticazione per problemi tecnici con l'invio di sms"
                    Spider.logger.error "Configurare messenger.sms.backend per abilitare l\'autenticazione forte sul sito"
                else
                    #se ho appena confermato il cellulare non richiedo ancora l'inserimento della chiave
                    if @request.session['cellulare_appena_modificato'] == true
                        @request.session['strong_auth_valid'] = true
                        autenticazione_abilitata = true
                        @request.session['cellulare_appena_modificato'] = nil
                        url = @request.session['strong_auth_da_servizio'][:url]
                        @request.session['strong_auth_da_servizio'] = nil
                        redirect url
                    else
                        
                        if utente.cellulare_confermato == false
                                @request.session.flash['errore_strong_auth'] = "Per completare l'autenticazione confermare il numero di cellulare inserendo il codice di verifica o inviare un nuovo codice"
                                redirect self.class.http_s_url('dettagli_utente')
                        else 
                            #cellulare presente, controllo se è confermato o no
                            if utente.cellulare.blank?
                                @request.session.flash['errore_strong_auth'] = "Spiacente non è possibile proseguire in quanto non hai inserito il tuo numero di cellulare. Inserirlo nella sezione 'Contatti' e poi confermarlo con il codice inviato per sms."
                                redirect self.class.http_s_url('dettagli_utente?modifica')
                            else
                                # il cellulare è presente e confermato, mando sms con chiave, salvo chiave in tab Utenti e mostro view
                                utente_da_db = Portal::Utente.new(utente.id)
                                invio_sms_riuscito = utente_da_db.invia_sms_autenticazione_forte
                                Spider.logger.error "Invio sms per auth forte fallito" unless invio_sms_riuscito
                                if invio_sms_riuscito
                                    autenticazione_abilitata = true
                                    @scene.messaggio_info = "Per proseguire è necessario inserire il codice di autenticazione inviato tramite sms al suo numero di cellulare ovvero al numero #{utente.cellulare}" 
                                end
                            end
                        end    

                    end

                end

            end

            


            if !@request.params['repeat'].blank? && @request.params['repeat'] == 't' && @request.get?
                if Spider.conf.get('messenger.sms.backend').blank?
                    @scene.messaggio_errore = "Non è possibile procedere con l'autenticazione per problemi tecnici con l'invio di sms"
                    Spider.logger.error "Configurare messenger.sms.backend per abilitare l\'autenticazione forte sul sito"
                else
                    invio_sms =utente.invia_sms_autenticazione_forte
                    unless invio_sms
                        Spider.logger.error "Invio sms con chiave di autenticazione fallito per utente #{utente.id}"
                    else
                        @scene.messaggio_azione = "Codice di autenticazione inviato con successo al numero #{utente.cellulare}"
                        autenticazione_abilitata = true
                    end
                end    
                
            end

            #se arrivo in post controllo la chiave che sia uguale a quella dell'utente in tabella
            if @request.post?
                autenticazione_abilitata=true
                @scene.errori = []
                if @request.params['codice_sms'].blank?
                    @scene.error_codice_sms = "Inserire il codice di autenticazione inviato per sms"
                    @scene.errori << @scene.error_codice_sms
                else
                    #controllo se sono uguali le chiavi
                    codice_inviato = @request.params['codice_sms']
                    codice_db = utente.strong_auth_key
                    if codice_inviato == codice_db
                        @request.session['strong_auth_valid'] = true
                        #se sono arrivato da un servizio rimando al servizio altrimneti alla index
                        unless @request.session['strong_auth_da_servizio'].blank?
                            url = @request.session['strong_auth_da_servizio'][:url]
                            @request.session['strong_auth_da_servizio'] = nil
                            redirect url
                        else
                            redirect self.class.http_s_url
                        end

                        
                    else
                        @scene.errori << "Il codice inserito non è corretto."
                    end
                end
            end

            @scene.autenticazione_abilitata = autenticazione_abilitata

        end
        


        __.html :template => "conferma_gdpr"
        def conferma_gdpr
            autenticazione_necessaria
            #se ho i dati gdpr li carico in pagina
            gdpr = Portal::Gdpr.all.last
            unless gdpr.blank?
                @scene.informativa_gdpr = "#{gdpr.autorizzazione}"
            end
            @scene.gdpr_accettato = @request.utente_portale.accettazione_gdpr
            @scene.comunicazioni_abilitate = @request.utente_portale.disabilita_comunicazioni.blank?
            if @request.post?
                if @request.params['abilita_comunicazioni_gdpr'] == 'on'
                    @request.utente_portale.disabilita_comunicazioni = false
                end
                cancellato = false
                #se accetto aggiorno il flag e la dataora
                unless @request.params['submit_accetto'].blank?
                    @request.utente_portale.accettazione_gdpr = true
                    @request.utente_portale.data_ora_accettazione_gdpr = DateTime.now
                    @request.utente_portale.richiesta_cancellazione_gdpr = nil
                    @request.utente_portale.data_ora_cancellazione_gdpr = nil
                end
                unless @request.params['submit_non_accetto'].blank?
                    cancellato = true
                    @request.utente_portale.accettazione_gdpr = nil
                    @request.utente_portale.data_ora_accettazione_gdpr = nil
                    @request.utente_portale.richiesta_cancellazione_gdpr = true
                    @request.utente_portale.data_ora_cancellazione_gdpr = DateTime.now
                end
                begin
                    Portal::Utente.storage.start_transaction
                        @request.utente_portale.save
                        if cancellato
                            #mando mail ad admin e a cittadino
                            scene_admin = Spider::Scene.new
                            scene_admin.data_cancellazione_gdpr = @request.utente_portale.data_ora_cancellazione_gdpr.to_date.lformat(:short)
                            scene_admin.ora_cancellazione_gdpr = @request.utente_portale.data_ora_cancellazione_gdpr.to_time.lformat(:short)
                            scene_admin.utente = @request.utente_portale
                            headers = {'Subject' => "Richiesta di cancellazione dati personali."}
                            send_email('gdpr/richiesta_cancellazione_admin', scene_admin, Spider.conf.get('portal.email_from'), 
                                Spider.conf.get('portal.email_amministratore'), headers)
                            scene_utente = Spider::Scene.new
                            scene_utente.data_cancellazione_gdpr = @request.utente_portale.data_ora_cancellazione_gdpr.to_date.lformat(:short)
                            scene_utente.ora_cancellazione_gdpr = @request.utente_portale.data_ora_cancellazione_gdpr.to_time.lformat(:short)
                            scene_utente.utente = @request.utente_portale
                            headers = {'Subject' => "Richiesta di cancellazione dati personali."}
                            send_email('gdpr/richiesta_cancellazione_utente', scene_utente, Spider.conf.get('portal.email_from'), 
                                @request.utente_portale.email, headers)
                        else
                            scene_admin = Spider::Scene.new
                            scene_admin.data_accettazione_gdpr = @request.utente_portale.data_ora_accettazione_gdpr.to_date.lformat(:short)
                            scene_admin.ora_accettazione_gdpr = @request.utente_portale.data_ora_accettazione_gdpr.to_time.lformat(:short)
                            scene_admin.utente = @request.utente_portale
                            headers = {'Subject' => "Accettazione trattamento dati personali."}
                            send_email('gdpr/accettazione_trattamento_admin', scene_admin, Spider.conf.get('portal.email_from'), 
                                Spider.conf.get('portal.email_amministratore'), headers)
                        end
                    Portal::Utente.storage.commit
                rescue Exception => exc
                    Portal::Utente.storage.rollback  
                    messaggio =  "#{exc.message}"
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
                end
                if cancellato
                    redirect self.class.http_s_url('autenticazione/logout')
                    @request.session.flash['errore'] = "Hai fatto rischiesta di essere cancellato." #NON SI VEDE
                else
                    @request.session.flash['conferma'] = "Hai autorizzato il trattamento dei tuoi dati personali"
                    redirect self.class.http_s_url
                end
                
            end
        end


        __.html :template => "informativa_gdpr"
        def informativa_gdpr
            #se ho i dati gdpr li carico in pagina
            gdpr = Portal::Gdpr.all.last
            unless gdpr.blank?
                @scene.informativa_gdpr = " #{gdpr.informativa}"
            end
        end


        __.json
        def lista_utenti
            data = @request.params['da']
            data = DateTime.parse(data) if data
            utenti = Utente.where{ |u| u.obj_modified > data }.map{ |u| u.dump_to_hash }
            $out << utenti.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }.to_json
        end

        __.json
        def lista_gruppi
            gruppi = Gruppo.all.map{ |g| g.cut({ :id => 0, :nome => 0 })}
            $out << gruppi.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }.to_json
        end


        __.json
        def lista_servizi(ws=nil)
            solo_ws = ( @request.params['ws']=='t' ? true : false )
            lingua = @request.params['lang']

            cut = {:id => 0, :nome => 0, :url => 0, :descrizione => 0, :accesso => 0, :web_service => 0 }
            servizi_pubblici = @scene.servizi_pubblici.map{ |s|
                
                servizio_pub = s.cut(cut)
                #se il servizio ha web_service a false e ho richiesto solo i servizi con ws a true non ritorno il servizio
                if servizio_pub[:web_service] == false && solo_ws
                    {}
                else
                    if (s.controller? && (s.oggetto_db? && !s.oggetto_db['web_service'].blank?))
                        #non piu usate last_items con nuovo menu
                        #servizio_pub['last_items'] = s.controller.last_items if s.controller.respond_to?(:last_items)
                        servizio_pub['url_app'] = s.controller.http_s_url
                        servizio_pub['ws_area_riservata'] = (s.controller.respond_to?('ws_area_riservata') && s.controller.ws_area_riservata == true)
                        #se il servizio ha dei dati di inizializzazione da passare li passo facendo la chiamata: dati_iniziali
                        servizio_pub['dati_iniziali'] = s.controller.dati_iniziali(lingua) if s.controller.respond_to?(:dati_iniziali)
                        servizio_pub.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                    end
                end
                }
            servizi_privati = @scene.servizi_privati.map{ |s| 
                servizio_pri = s.cut(cut)
                if servizio_pri[:web_service] == false && solo_ws
                    {}
                else
                    servizio_pri.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) } 
                end
            }
            
            $out << {
                :pubblici => servizi_pubblici.delete_if{ |elem| elem.blank? },
                :privati => servizi_privati.delete_if{ |elem| elem.blank? }
            }.convert_object.to_json
            
        end

        __.json
        def utente
            if !@request.utente_portale
                $out << {:autenticato => false}.to_json
            else
                h = @request.utente_portale.cut(:nome => 0, :cognome => 0, :codice_fiscale => 0, :id => 0)
                h[:autenticato] = true
                $out << h.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }.to_json
            end
        end
    
        #Servizi per app nativa 03/2019
        def check_sid(sessione_corrente,sid)
            if sid.blank?
                $out << {   :esito => "ko",
                    :messaggio => "Autenticazione non valida!"
                }.to_json
                done
            else #vedo se ho una sessione con una autenticazione attiva
                @request.session = Spider::Session.get(sid)
                unless @request.session.blank?
                    if @request.session[:auth].blank?
                        $out << {   :esito => "ko",
                            :messaggio => "Sessione non valida!"
                        }.to_json
                        done
                    end
                else
                    $out << {   :esito => "ko",
                        :messaggio => "Errore recupero sessione!"
                    }.to_json
                    done
                end
            end
        end


        __.json
        def info_utente
            check_sid(@request.session,@request.params['sid'])
            utente_login = Portal::UtenteLogin.new(@request.session[:auth]['Portal::UtenteLogin'][:id])
            id_utente_portale = utente_login.utente_portale.id
            h = utente_login.cut(:nome => 0, :cognome => 0, :codice_fiscale => 0)
            h[:id] = id_utente_portale
            h = h.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
            #carico tutti gli id dei servizi privati associati all'utente
            array_servizi_privati = []
            utente_login.utente_portale.servizi_privati.each{ |servizio|
                array_servizi_privati << servizio[:servizio].id if servizio.stato.id == 'attivo'
            }
            $out << { :esito => "ok",
                    :dati_utente => h,
                    :servizi_privati => array_servizi_privati
                    }.to_json
                
        end

        __.json
        def elenco_servizi
            check_sid(@request.session,@request.params['sid'])
            cut = {:id => 0, :nome => 0, :url => 0, :accesso => 0 }
            servizi_pubblici = @scene.servizi_pubblici.map{ |s|
                servizio_pub = s.cut(cut)
                if s.controller? && s.oggetto_db?
                    #non piu usate last_items con nuovo menu
                    #servizio_pub['last_items'] = s.controller.last_items if s.controller.respond_to?(:last_items)
                    servizio_pub['url'] = s.controller.http_s_url
                end
                servizio_pub.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                servizio_pub
            }
            servizi_privati = @scene.servizi_privati.map{ |s| 
                servizio_pri = s.cut(cut)
                servizio_pri.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                servizio_pri
            }
            $out << { :esito => "ok",
                      :pubblici => servizi_pubblici.delete_if{ |elem| elem.blank? },
                      :privati => servizi_privati.delete_if{ |elem| elem.blank? }
            }.convert_object.to_json
            
        end


    #registra in db i registrationId dei device per le app in android e iphone
        __.action
        def registraId
            if @request.post?
                registration_id = @request.post['registrationId'] #device token del dispositivo
                device_in_db = Portal::MobileDeviceRegistrati.load(:registrationId => registration_id)
                if device_in_db.blank?
                    #nuovo inserimento
                    device_in_db = Portal::MobileDeviceRegistrati.new
                end
                device_type = @request.post['deviceType']
                app_name = @request.post['app_name']
                #salvo il gruppo utente se passato
                gruppo = @request.post['gruppo']
                unless gruppo.blank?
                    gruppo_in_db = Portal::Gruppo.load(:id => gruppo)
                    device_in_db.gruppo = gruppo_in_db unless gruppo_in_db.blank?
                end
                #salvo l'utente se passato
                utente = @request.post['id_utente']
                unless utente.blank?
                    if utente == '0'
                        device_in_db.utente_portale = nil
                    else
                        utente_portale = Portal::Utente.where(:id => @request.post['id_utente'])
                        device_in_db.utente_portale = utente_portale[0] unless utente_portale[0].blank?
                    end
                end
                device_in_db.device_type = device_type
                device_in_db.registrationId = registration_id
                device_in_db.app_name = app_name
                #se stringa vuota non ci sono app collegate, se non presente il parametro 
                if @request.post['lista_servizi'].nil?
                    device_in_db.lista_servizi = "comunicazioni"
                elsif @request.post['lista_servizi'] == ""
                    device_in_db.lista_servizi = ""
                else
                    device_in_db.lista_servizi = @request.post['lista_servizi']
                end
                Spider.logger.debug("Registro nuovo device_token per app: #{registration_id} \n")
                device_in_db.save            
            end
        end

        
        
        # __.html :template => 'username_inviato'
        # def username_inviato
        #     @scene.messaggio = "Ti abbiamo inviato una e-mail con il tuo nome utente. Se non la ricevi entro qualche minuto controlla
        #     lo spam della tua casella di posta"    
        # end

        # __.html :template => 'password_inviata'
        # def password_inviata
        #     @scene.messaggio = "Ti abbiamo inviato una e-mail con la tua nuova password. Se non la ricevi entro qualche minuto controlla
        #     lo spam della tua casella di posta"
        # end


        __.json
        def short_reg
            #arrivano i parametri
            # nome, cognome, cf, email, cellulare, username, hash, salt
            nome        = @request.params['nome']
            cognome     = @request.params['cognome']
            cf          = @request.params['cf']
            email       = @request.params['email']
            cellulare   = @request.params['cellulare']
            username    = @request.params['username']
            hash        = @request.params['hash']
            salt        = @request.params['salt']


            utente_login_presente = Portal::UtenteLogin.where( :username => username )
            unless utente_login_presente.blank?
                $out << { :ok => "false",
                          :cod_errore => "username_presente" }.to_json
                return
            end
            cf_presente = Portal::Utente.where(:codice_fiscale => cf ) 
            unless cf_presente.blank?
                $out << { :ok => "false",
                          :cod_errore => "codice_fiscale_presente" }.to_json
                return
            end
            email_presente = Portal::Utente.where(:email => email ) 
            unless cf_presente.blank?
                $out << { :ok => "false",
                          :cod_errore => "email_presente" }.to_json
                return
            end

            begin
                utente_login = Portal::UtenteLogin.new
                utente_login.username = @request.params['username']
                password = Spider::DataTypes::Password.new("sha2$#{salt}$#{hash}")
                password.attributes[:hashed] = true
                utente_login.password = password
                utente_login.save
                
                utente = Portal::Utente.new
                utente.utente_login = utente_login
                utente.nome = nome
                utente.cognome = cognome
                utente.email = email
                utente.cellulare = cellulare
                utente.codice_fiscale = cf

                attivazione_automatica = Spider.conf.get('portal.attivazione_utenti_automatica')
                utente.stato = attivazione_automatica ? 'attivo' : 'attesa'

                #utente.registrato!
                utente.save_all
            rescue Exception => exc
                Spider.logger.error exc.message
            end
        end

        __.json :method => :POST
        def invio_sms
            qssha1_inviato = @request.params['hqs']
            stringa_datetime_inviata = @request.params['dt']
            num_cell = @request.params['dest']
            testo_sms = @request.params['testo']

            # #test
            # qssha1_inviato = "asdda"
            # stringa_datetime_inviata = '2010626160000' formato %Y%m%d%H%M%S
            # num_cell = "3465317477"
            # testo_sms = "ciao"

            risultato = false
            esito = ""
            if !qssha1_inviato.blank? && !stringa_datetime_inviata.blank? && !num_cell.blank? && !testo_sms.blank?
                datetime_inviata = nil
                begin
                    #controllo se il formato datetime è corretto
                    datetime_inviata = DateTime.strptime(stringa_datetime_inviata+DateTime.now.zone, '%Y%m%d%H%M%S%z')
                rescue Exception => exc
                    esito = "data con formato non corretto"
                    $out << {:invio => risultato, :esito => esito}.to_json
                    return
                end
                # dt_fin = (DateTime.now+(1.0/(24*6))).strftime('%Y%m%d%H%M%S')
                # dt_iniz = (DateTime.now-(1.0/(24*6))).strftime('%Y%m%d%H%M%S')
                #controllo che la datetime sia di +-10 minuti
                data_valida = ( (datetime_inviata < (DateTime.now+(1.0/(24*6))) && (datetime_inviata > DateTime.now-(1.0/(24*6)))) )
                unless data_valida
                    #se la data non è valida esce
                    esito = "data non valida"
                    $out << {:invio => risultato, :esito => esito}.to_json
                    return
                end
                qs = stringa_datetime_inviata+"3ur0s3rv1z1"
                qssha1 = OpenSSL::Digest::SHA1.new(qs)
                if qssha1 != qssha1_inviato 
                    #non valida l'autenticazione
                    esito = "autenticazione non valida"
                else    
                    #mando l'sms
                    begin
                        sms_inviato = send_sms(num_cell, testo_sms)
                        if sms_inviato
                            esito = "sms inviato"
                            risultato = true
                        end
                    rescue Exception => exc
                        esito = "errore invio sms"
                    end        
                end    
            else
                #dati mancanti
                esito ="dati mancanti"
            end

            $out << {:invio => risultato, :esito => esito}.to_json

        end


        #metodo per scaricare il documento tramite link delle pratiche edilizie
        __.action
        def scarica_doc
            #devo essere autenticato
            autenticazione_necessaria
            #leggo i parametri e creo query string
            unless @request.params.blank?
                query_string = ""
                @request.params.each{|chiave,valore|
                    query_string += "#{chiave}=#{valore}&"
                }
                #rimuovo ultimo &
                query_string = query_string[0..-2] 
            end
            unless Spider.conf.get("portal.url_get_doc").blank?
                redirect Spider.conf.get("portal.url_get_doc")+"?#{query_string}"
            else
                redirect self.class.http_s_url
            end
        end


        def invio_mail_errore_admin(app)
            scene = Spider::Scene.new
            scene.ente = Spider.conf.get('portal.nome')
            scene.site_domain = Spider.conf.get('site.domain')
            scene.applicazione = app
            headers = {'Subject' => "Problemi compilazione javascript"}
            send_email('amministratore/invio_mail_errore_admin', scene, Spider.conf.get('portal.email_from'), 
            Spider.conf.get('portal.email_amministratore'), headers)
        end


        #ricompilo i js e mando mail all'admin per segnalare i problemi javascript
        
        __.json
        def js_recompile
            begin
                app = @request.params['app']
                #DA-FARE: controllare in prod
                #invio_mail_errore_admin(app)
                #recompile_cmd = "rm -rf var/cache public/_c/* public/_c/.*"
                #Spider.logger.debug("Cancello cache, js e css")
                ##exec(recompile_cmd)
                #IO.popen(recompile_cmd) { |f| puts f.gets }
                #Spider.respawn!
                $out << { :esito => "ok" }.to_json
            rescue => exc
                #Spider.logger.error "\n\n Problemi nella ricompilazione js"
                $out << { :esito => "ko" }.to_json
            end

        end

        __.action
        def get_html_layout
            #se vuota oppure ultimo datetime vecchio di un ora
            if @@layout.blank? || ( (DateTime.now - (1.0/24)) > @@layout['datetime'] )
                begin
                    
                    html = parse_html_current_layout 
                    hash = html.hash
                    #converto in base64
                    htmlb64 = Base64.encode64(html) #2186966052902868832
                    @@layout['datetime'] = DateTime.now
                    @@layout['hash'] = hash
                    @@layout['html'] = Base64.encode64(html)
                    $out << { :esito => "ok", :html => htmlb64 }.to_json
                rescue => exc
                    messaggio = "Errore Applicativo ( #{exc.message} )"
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
                    $out << { :esito => "ko", "errore" => messaggio }.to_json
                end
            else #ritorno hash salvato nella var di classe
                $out << { :esito => "ok", :html => @@layout['html'] }.to_json
            end
        end

        __.action
        def get_hash_layout
            #se vuota oppure ultimo datetime vecchio di un ora
            if @@layout.blank? || ( (DateTime.now - (1.0/24)) > @@layout['datetime'] ) 
                begin
                    html = parse_html_current_layout
                    #ricavo hash
                    hash = html.hash #2186966052902868832
                    #salvo in var di classe
                    @@layout['datetime'] = DateTime.now
                    @@layout['hash'] = hash
                    @@layout['html'] = Base64.encode64(html)
                    $out << { :esito => "ok", :hash => hash }.to_json
                rescue => exc
                    messaggio = "Errore Applicativo ( #{exc.message} )"
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
                    $out << { :esito => "ko", "errore" => messaggio }.to_json
                end
            else #ritorno hash salvato nella var di classe
                $out << { :esito => "ok", :hash => @@layout['hash'] }.to_json
            end
        end


        private

        #questo metodo in locale non funziona se non si usa un webserver multi thread...
        def parse_html_current_layout
            dominio = self.class.http_s_url.gsub("/portal","")
            #dominio = "http://civilianext.soluzionipa.it/portal".gsub("/portal","") #TEST
            doc = Nokogiri::HTML(open(self.class.http_s_url))
            #doc = Nokogiri::HTML( open('http://civilianext.soluzionipa.it/portal/servizi_pubblici', :redirect => false ) ) #TEST
            #tolgo il portal_main
            doc.css("#portal_main").remove #tolgo corpo centrale
            #metto come assoluti i percorsi
            doc.to_html.gsub("src=\"/","src=\"#{dominio}/").gsub("href=\"/","href=\"#{dominio}/") 
        end
       

        def self.prepara_servizi(scena,utente_portale)
            controllers_servizi = Portal.servizi
            servizi_db = Portal::Servizio.all.to_indexed_hash(:id)
            id_servizi = (controllers_servizi.keys + servizi_db.keys).uniq
            servizi_utente = {}
            if utente_portale
                servizi_utente = utente_portale.servizi_privati.to_indexed_hash('servizio.id')
            end
            servizi = []
            hash_servizi = {}
            id_servizi.each do |id|
                s = Portal::ServizioPortale.new(controllers_servizi[id], servizi_utente[id] || servizi_db[id])
                servizi << s
                hash_servizi[id] = s
            end
            scena.servizi = servizi
            scena.hash_servizi = hash_servizi
            servizi
        end
        
        def self.carica_elenco_servizi(scena, request, utente_portale)
            scena.servizi_privati_utente = []
            servizi = Portal::PortalController.prepara_servizi(scena,utente_portale)

            servizi.each do |servizio|

                if servizio.pubblico?
                    if servizio.oggetto_db? || (!servizio.oggetto_db? && servizio.mostra_default? )
                        scena.servizi_pubblici << servizio
                    end
                elsif servizio.privato?
                    scena.servizi_privati << servizio
                    # devo visualizzare mell'indice anche i servizi in necessaria configurazione
                    # con accesso per abilitati
                    if servizio.attivo? || servizio.in_configurazione?
                        scena.servizi_privati_utente << servizio
                        
                        #se è attiva l'autenticazione forte cambio il link per farlo andare in codice_autenticazione e la si faranno i controlli
                        if servizio.richiede_strong_auth == true && Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true && request.session['strong_auth_valid'].blank? && request.utente_portale.attivo?
                            scena.servizi_privati_utente.last.url = "/portal/codice_autenticazione?servizio_id=#{servizio.id}"
                        end
                    end    
                elsif servizio.nascosto?
                    scena.servizi_privati_utente << servizio if servizio.attivo?

                    #se è attiva l'autenticazione forte cambio il link per farlo andare in codice_autenticazione e la si faranno i controlli
                    if servizio.attivo? && servizio.richiede_strong_auth == true && Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true && request.session['strong_auth_valid'].blank?
                        scena.servizi_privati_utente.last.url = "/portal/codice_autenticazione?servizio_id=#{servizio.id}"
                    end
                end
            end

            #ordino la lista dei servizi in base all'attributo posizione
            scena.servizi_pubblici.sort!{ |a,b| 
                a.ordina_posizione(b) 
            }

        end






    
    end
    
    module PortalRequest
        def autenticazioni=(val)
            @autenticazioni = val
        end
        def autenticazioni
            @autenticazioni
        end
        def utente_portale=(val)
            @utente_portale = val
        end
        def utente_portale
            @utente_portale
        end
    end
    
end

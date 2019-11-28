# -*- encoding : utf-8 -*-
require 'spid-es'
require 'apps/portal/lib/auth_provider'
require 'apps/portal/models/auth_providers/spid_agid/utente_spid_agid'
require 'apps/portal/models/auth_providers/spid_agid/tracciatura_spid_agid'
require 'openssl'
require "base64"
require "zlib"

require 'net/http'
require 'uri'

module Portal
    
    class SpidAgid < Spider::PageController
        include HTTPMixin
        include AuthProvider
        include Spider::Messenger::MessengerHelper 
        include Spid::Saml::Coding

        #verifica_presenza_configurazioni('federa', ['idp_metadata', 'destination_service_url', 'idp_sso_target_url', 'requester_identificator'])

        auth_provider({
            :label => 'spid',
            :nome => 'Spid',
            :link_personalizzato => true,
            :descrizione => 'Servizio di autenticazione attraverso Spid'
        })

        __.xml
        def sp_metadata
            settings = get_saml_settings
            meta = Spid::Saml::Metadata.new
            
            @response.headers['Content-Type'] = 'application/samlmetadata+xml'
            $out << meta.generate(settings)
        end



        __.action
        def index
            #ricavo dall'id passato le configurazioni impostate con hash in config.yml
            provider_id = @request.params['ProviderID']
            @request.session['ProviderID'] = provider_id
            hash_id_link_spid = Spider.conf.get('portal.spid.hash_gestori')
            if provider_id.blank? || hash_id_link_spid.blank?
                @request.session.flash['errore_auth'] = "Parametri assenti: ProviderID o configurazione Gestori Credenziali"
                redirect Portal::ControllerAutenticazione.http_s_url
            else
                begin
                    saml_settings = get_saml_settings
                #setto l'url per la chiamata e gli url per metadata e nome del gestore
                    saml_settings.destination_service_url =  hash_id_link_spid[provider_id]['url_authnrequest']
                    saml_settings.idp_sso_target_url =  hash_id_link_spid[provider_id]['url_authnrequest']
                    saml_settings.idp_metadata = hash_id_link_spid[provider_id]['idp_metadata']
                    saml_settings.idp_name_qualifier = hash_id_link_spid[provider_id]['idp_name_qualifier']
                    
                    #se ho richiesto l'accesso con EIDAS devo cambiare gli index 
                    if provider_id == "eidas"
                        saml_settings.assertion_consumer_service_index   = 100
                        saml_settings.attribute_consuming_service_index  = 100
                    end

                    #create an instance of Spid::Saml::Authrequest
                    request = Spid::Saml::Authrequest.new(saml_settings)
                    
                    auth_request = request.create
                    
                    #ricavo issue istant
                    @request.session[:issue_instant] = auth_request.issue_instant

                    #stampo la request se metto il log level debug
                    #Spider.logger.debug "\n REQUEST #{auth_request.request} \n"

                    # Based on the IdP metadata, select the appropriate binding 
                    # and return the action to perform to the controller
                    meta = Spid::Saml::Metadata.new(saml_settings)
                    signature = get_signature(auth_request.uuid,auth_request.request,"http://www.w3.org/2001/04/xmldsig-more#rsa-sha256")
                    
                    sso_request = meta.create_sso_request( auth_request.request, { :RelayState   => request.uuid,
                                                                            :SigAlg       => "http://www.w3.org/2001/04/xmldsig-more#rsa-sha256",
                                                                            :Signature    => signature } )
                    #creo una nuova istanza per la tracciatura
                    tracciatura = TracciaturaSpidAgid.new
                    tracciatura.provider_id = provider_id
                    saml_request_dec_compressa = Zlib::Deflate.deflate(auth_request.request)
                    tracciatura.authn_request = Base64.strict_encode64(saml_request_dec_compressa)
                    tracciatura.authn_req_id = auth_request.uuid
                    tracciatura.authn_req_issue_instant = auth_request.issue_instant
                    tracciatura.save
                    redirect sso_request 

                rescue Exception => exc
                    messaggio = "Errore Applicativo ( #{exc.message} )"
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
                    @request.session.flash['errore_auth'] = (Spider.runmode == 'devel' ? messaggio_log.gsub("\n","<br />") : messaggio)
                    redirect Portal::ControllerAutenticazione.http_s_url
                end
                                                          
            end

            
        end
        
        __.html
        def assertion_consumer

            provider_id = @request.session['ProviderID']
            #per TEST
            #provider_id = 'infocert'
            #provider_id = 'poste'
            errore_autenticazione "Non sono stati ricevuti dati validi l'utente" if provider_id.blank?
            hash_id_link_spid = Spider.conf.get('portal.spid.hash_gestori')
            
            saml_response = @request.params['SAMLResponse']
            #Spider.logger.error "**SAML RESPONSE: #{saml_response}"
            if !saml_response.nil? 
                #istante di ricezione della response
                ricezione_response_datetime = Time.now.utc.to_datetime #formato utc
                #read the settings
                settings = get_saml_settings
                settings.destination_service_url =  hash_id_link_spid[provider_id]['url_authnrequest']
                settings.idp_sso_target_url =  hash_id_link_spid[provider_id]['url_authnrequest']
                settings.idp_metadata = hash_id_link_spid[provider_id]['idp_metadata']
                settings.idp_name_qualifier = hash_id_link_spid[provider_id]['idp_name_qualifier']
                #creo un oggetto response
                response = Spid::Saml::Response.new(saml_response)
                if response.assertion_present?
                    #ricavo issue istant
                    issue_instant_req = @request.session[:issue_instant]
                    unless issue_instant_req.blank? #in fase di test si deve fare la login ogni volta per gli issue istant
                        issue_instant_req_datetime = DateTime.strptime(issue_instant_req, "%Y-%m-%dT%H:%M:%SZ")
                        issue_instant_resp = response.issue_instant
                        begin
                            issue_instant_resp_datetime = DateTime.strptime(issue_instant_resp.to_s, "%Y-%m-%dT%H:%M:%SZ")
                        rescue => exc
                            #provo a fare strptime con millisecondi
                            begin
                                issue_instant_resp_datetime = DateTime.strptime(issue_instant_resp.to_s, "%Y-%m-%dT%H:%M:%S.%LZ")
                            rescue => exc2
                                errore_autenticazione "La response non è valida", "Problemi nella conversione dell' issue istant anche con millisecondi" #caso 110
                            end
                        end
                        assertion_issue_instant_resp = response.assertion_issue_instant
                        begin
                            assertion_issue_instant_resp_datetime = DateTime.strptime(assertion_issue_instant_resp.to_s, "%Y-%m-%dT%H:%M:%SZ")
                        rescue => exc
                            #provo a fare strptime con millisecondi
                            begin
                                assertion_issue_instant_resp_datetime = DateTime.strptime(assertion_issue_instant_resp.to_s, "%Y-%m-%dT%H:%M:%S.%LZ")
                            rescue => exc2
                                errore_autenticazione "La response non è valida", "Problemi nella conversione dell' issue istant dell'assertion anche con millisecondi" #caso 110
                            end
                        end
                        # Spider.logger.error "\n\n issue_instant_req #{issue_instant_req}"
                        # Spider.logger.error "\n\n issue_instant_resp #{issue_instant_resp}"
                        errore_autenticazione "La response non è valida", "Problemi istanti di tempo: issue_instant_req_datetime > issue_instant_resp_datetime" if issue_instant_req_datetime > issue_instant_resp_datetime #caso spid valid 14
                        errore_autenticazione "La response non è valida", "Problemi istanti di tempo: issue_instant_resp_datetime.to_date != Date.today" if issue_instant_resp_datetime.to_date != Date.today #caso spid valid 15
                        #asserzioni
                        errore_autenticazione "La response non è valida", "Problemi istanti di tempo: issue_instant_req_datetime > assertion_issue_instant_resp_datetime" if issue_instant_req_datetime > assertion_issue_instant_resp_datetime #caso spid valid 39
                        errore_autenticazione "La response non è valida", "Problemi istanti di tempo: assertion_issue_instant_resp_datetime.to_date != Date.today" if assertion_issue_instant_resp_datetime.to_date != Date.today #caso spid valid 40
                    end

                    #controllo se Attributo NotOnOrAfter di SubjectConfirmationData precedente all'istante di ricezione della response, caso 66
                    not_on_or_after = response.assertion_subject_confirmation_data_not_on_or_after
                    unless not_on_or_after.blank?
                        #Spider.logger.error "\n\n data not_on_or_after #{not_on_or_after.to_s}"
                        begin
                            not_on_or_after_datetime = DateTime.strptime(not_on_or_after.to_s, "%Y-%m-%dT%H:%M:%SZ")
                        rescue => exc
                            #errore_autenticazione "La response non è valida", "Problemi istanti di tempo: problema parsing formato" #caso di data non valida, controlla gemma..duplicato
                            #provo a fare strptime con millisecondi
                            begin
                                not_on_or_after_datetime = DateTime.strptime(not_on_or_after.to_s, "%Y-%m-%dT%H:%M:%S.%LZ")
                            rescue => exc2
                                errore_autenticazione "La response non è valida", "Problemi nella conversione dell' assertion_subject_confirmation_data_not_on_or_after anche con millisecondi" 
                            end
                        end
                        errore_autenticazione "La response non è valida", "Problemi istanti di tempo: not_on_or_after_datetime < ricezione_response_datetime" if not_on_or_after_datetime < ricezione_response_datetime
                    end
                    
                    #controllo se Attributo NotBefore di Condition successivo all'instante di ricezione della response, caso 78
                    not_before = response.assertion_conditions_not_before
                    unless not_before.blank?
                        #Spider.logger.error "\n\n data not_on_or_after #{not_on_or_after.to_s}"
                        begin
                            not_before_datetime = DateTime.strptime(not_before.to_s, "%Y-%m-%dT%H:%M:%SZ")
                        rescue => exc
                            #errore_autenticazione "La response non è valida", "Problemi istanti di tempo: not_on_or_after_datetime < ricezione_response_datetime" #caso di data non valida, controlla gemma..duplicato
                            #provo a fare strptime con millisecondi
                            begin
                                not_before_datetime = DateTime.strptime(not_before.to_s, "%Y-%m-%dT%H:%M:%S.%LZ")
                            rescue => exc2
                                errore_autenticazione "La response non è valida", "Problemi nella conversione dell'assertion_conditions_not_before  anche con millisecondi" 
                            end
                        end
                        if not_before_datetime > ricezione_response_datetime
                            errore_autenticazione "La response non è valida", "Intervallo di validità scaduto per autenticazione SPID"
                        end 
                    end

                    #controllo se Attributo Attributo NotOnOrAfter di Condition precedente all'istante di ricezione della response #82
                    assertion_conditions_not_on_or_after = response.assertion_conditions_not_on_or_after
                    unless not_on_or_after.blank?
                        #Spider.logger.error "\n\n data not_on_or_after #{not_on_or_after.to_s}"
                        begin
                            assertion_conditions_not_on_or_after_datetime = DateTime.strptime(assertion_conditions_not_on_or_after.to_s, "%Y-%m-%dT%H:%M:%SZ")
                        rescue => exc
                            #errore_autenticazione "La response non è valida", "errore in strptime assertion_conditions_not_on_or_after"  #caso di data non valida, controlla gemma..duplicato
                            #provo a fare strptime con millisecondi
                            begin
                                assertion_conditions_not_on_or_after_datetime = DateTime.strptime(assertion_conditions_not_on_or_after.to_s, "%Y-%m-%dT%H:%M:%S.%LZ")
                            rescue => exc2
                                errore_autenticazione "La response non è valida", "Problemi nella conversione dell'assertion_conditions_not_on_or_after  anche con millisecondi" 
                            end
                        end
                        errore_autenticazione "La response non è valida", "assertion_conditions_not_on_or_after_datetime < ricezione_response_datetime" if assertion_conditions_not_on_or_after_datetime < ricezione_response_datetime
                    end
                end #fine controlli su assertion

                #se ho richiesto l'accesso con EIDAS devo cambiare gli index 
                if provider_id == "eidas"
                    settings.assertion_consumer_service_index   = 100
                    settings.attribute_consuming_service_index  = 100
                end

                #assegno alla response i settaggi
                response.settings = settings
                #estraggo dal Base64 l'xml
                saml_response_dec = Base64.decode64(saml_response)
                #Spider.logger.error "**SAML RESPONSE DECODIFICATA: #{saml_response_dec}"
                
                #Controllo nel caso che lo status della response non sia success il valore dell'errore.
                unless response.success?
                    status_message = response.get_status_message
                    unless status_message.blank?
                        case status_message.strip
                            when "ErrorCode nr19"
                                errore_autenticazione "Ripetuta sottomissione di credenziali errate"
                            when "ErrorCode nr20"
                                errore_autenticazione "Utente privo di credenziali compatibili"
                            when "ErrorCode nr21"
                                errore_autenticazione "Richiesta in Timeout"
                            when "ErrorCode nr22"
                                errore_autenticazione "Consenso negato"
                            when "ErrorCode nr23"
                                errore_autenticazione "Credenziali bloccate"
                            when "ErrorCode nr25"
                                errore_autenticazione "Processo di autenticazione annullato dall'utente"
                        end
                    else
                        #non ho status message, manca l'elemento
                        errore_autenticazione "La response non è valida"
                    end
                end
                
                #controllo validità response (firma ecc)
                #DA CAMBIARE PER VEDERE ERRORE SPECIFICO
                #response.validate! #da usare per avere info su errori
                errore_autenticazione "La response non è valida" unless response.is_valid?
                attributi_scelti_da_utente = []
               
                attributi_utente = response.attributes
                Spider.logger.debug "\n\n Attributi utente scelti: #{attributi_utente.inspect}"
               
                if provider_id != "eidas"
                    errore_autenticazione "Attributi utente non presenti" if attributi_utente.blank?
                    #caso 103, controllo se attributi che arrivano sono quelli richiesti.
                    errore_autenticazione "Attributi utente diversi da quelli richiesti" unless Spider.conf.get("portal.spid.hash_assertion_consumer")['0']['array_campi'].sort == response.attributes.keys.select{ |chiave| chiave.is_a?(String) }.sort

                    if attributi_utente[:fiscalNumber].include?("TINIT")
                        codice_fiscale = attributi_utente[:fiscalNumber].split("-")[1]
                    elsif attributi_utente[:fiscalNumber].include?("VATIT")
                        codice_fiscale = attributi_utente[:fiscalNumber].split("-")[1]
                    end                        
                    
                    unless codice_fiscale
                        Spider.logger.error "L'Idp di Spid non ha restituito un codice fiscale valido"
                        errore_autenticazione "il sistema di autenticazione remoto non ha trovato l'utente"
                    end
                    codice_fiscale = codice_fiscale.strip
                    
                else
                    #arrivo con eidas
                    #attributi: {"spidCode"=>"IT/IT/INFC0000026739", "name"=>"Andrea", "familyName"=>"Grazian", "gender"=>"M", 
                    #"dateOfBirth"=>"1971-09-16T00:00:00.000Z", "placeOfBirth"=>"Padova", "address"=>"fullCvaddress: Via Vescovo 13c 35020 Legnaro PD", 
                    #:spidCode=>"IT/IT/INFC0000026739", :name=>"Andrea", :familyName=>"Grazian", :gender=>"M", 
                    #:dateOfBirth=>"1971-09-16T00:00:00.000Z", :placeOfBirth=>"Padova", :address=>"fullCvaddress: Via Vescovo 13c 35020 Legnaro PD"}
               
                    Spider.logger.error "Fatto login con EIDAS, attributi: #{attributi_utente}"
                    #se arrivo e non ho lo spid code salvato creo un cf nuovo con EE_0000000000000
                    spid_code_from_eidas = attributi_utente['spidCode'].split("/").last
                    #fisso un codice che passa la validazione e che verrà cambiato alla fine
                    codice_fiscale = "EE_0000000000000"
                    #salvo in sessione attributi
                    hash_mappatura = { 
                        'name'          => 'nome',
                        'familyName'    => 'cognome',
                        'gender'        => 'sesso',
                        'dateOfBirth'   => 'data_nascita',
                        'placeOfBirth'  => 'comune_nascita',
                        'address'       => 'indirizzo_residenza'
                    }
                    attributi_scelti_da_utente = response.attributes.keys.select{ |chiave| chiave.is_a?(String) }.map{ |elem| hash_mappatura[elem]}.compact
                    attributi_scelti_da_utente << "stato_residenza"  
                end
                codice_fiscale_maiuscolo = codice_fiscale.upcase
                utente_qs = UtenteSpidAgid.where{ |ut_spid| ( ((ut_spid.chiave == codice_fiscale) | (ut_spid.chiave == codice_fiscale_maiuscolo)) | (ut_spid.spid_code == spid_code_from_eidas)) & (ut_spid.gestore_identita == provider_id) }
                utente_portale = nil
                creato = false
                utente = nil #conterra l'utente spid
                begin
                    Portal::Utente.storage.start_transaction
                        #Spider::Model.in_unit do |uow|
                            #se presente un utente spid lo carico
                            unless utente_qs.length == 0
                                utente = utente_qs[0]
                                utente_portale = utente.utente_portale
                                #controllo se ha una ditta collegata
                                ditta = utente_portale.ditta unless utente_portale.ditta.blank? 
                                codice_fiscale = utente_portale.codice_fiscale
                                Spider.logger.debug "** Caricato utente portale #{codice_fiscale}"
                            else
                                #creo utente spid agid
                                utente = UtenteSpidAgid.new(:chiave => codice_fiscale)
                                utente.spid_code = attributi_utente[:spidCode].split("/").last
                                utente.expiration_date = attributi_utente[:expirationDate]
                                utente.gestore_identita = provider_id
                                utente.provider = provider_id
                                #salvo in un campo a db gli attributi eidas scelti dall'utente
                                utente.info_extra = { 'attributi_scelti' => attributi_scelti_da_utente }.to_json if provider_id == 'eidas'

                                #potrebbe già esserci un utente portale con quel cf
                                utente_presente_qs = Portal::Utente.where{ |ut_portale| (ut_portale.codice_fiscale == codice_fiscale) | (ut_portale.codice_fiscale == codice_fiscale_maiuscolo) }
                                unless utente_presente_qs.length == 0
                                    #utente portale presente
                                    utente_portale = utente_presente_qs[0]
                                    ditta = utente_portale.ditta unless utente_portale.ditta.blank? 
                                    Spider.logger.debug "** Creato utente SPID collegato ad utente portale #{utente_portale.codice_fiscale}"
                                else
                                    #devo creare un nuovo utente portale
                                    utente_portale = Portal::Utente.new
                                    #se arriva una partita iva devo creare una ditta
                                    ditta = Portal::Ditta.new if !attributi_utente[:fiscalNumber].blank? && attributi_utente[:fiscalNumber].include?("VATIT")
                                    creato = true
                                end
                            end
                            #aggiorno i dati anagrafici, questi li prendo sempre da spid
                            utente_portale.nome = attributi_utente[:name] unless attributi_utente[:name].blank?
                            utente_portale.cognome = attributi_utente[:familyName] unless attributi_utente[:familyName].blank?
                            utente_portale.codice_fiscale = codice_fiscale
                            utente_portale.sesso = attributi_utente[:gender] unless attributi_utente[:gender].blank?
                            utente_portale.comune_nascita = attributi_utente[:placeOfBirth] if provider_id == "eidas" && !attributi_utente[:placeOfBirth].blank?
                            #utente_portale.stato_nascita = attributi_utente[:countyOfBirth] unless attributi_utente[:countyOfBirth].blank?
                            
                            utente_portale.stato_residenza = attributi_utente['spidCode'].split("/").first if provider_id == "eidas"
                            utente_portale.stato_residenza ||= "IT"

                            #gestione prov nascita con stato estero
                            prov_nascita = attributi_utente[:countyOfBirth]
                            unless prov_nascita.blank?
                                #if prov_nascita.strip == 'STATO ESTERO' || prov_nascita.strip == '0' #vecchio test, uso quello sotto, testato su cagliari
                                if prov_nascita.strip == 'STATO ESTERO' || !(prov_nascita.strip =~ /\d{2}/).nil?
                                    utente_portale.provincia_nascita = 'SE'
                                else
                                    utente_portale.provincia_nascita = prov_nascita
                                end
                            end              

                            utente_portale.data_nascita = Date.strptime(attributi_utente[:dateOfBirth], "%Y-%m-%d") unless attributi_utente[:dateOfBirth].blank?

                            #gestione doc di identità
                            #arriva stringa del tipo: cartaIdentita AX5200324 comuneCagliari 2016-04-28 2026-08-31
                            doc_ident = attributi_utente[:idCard]
                            if !doc_ident.blank? && doc_ident.include?("cartaIdentita")
                                @request.session['doc_ident_da_spid'] = true
                                array_info_doc = doc_ident.split(" ")
                                n_parti = array_info_doc.length
                                if array_info_doc.length == 5
                                    tipo_doc = array_info_doc[0]
                                    num_doc = array_info_doc[1]
                                    rilasciato_da = array_info_doc[2]
                                    rilasciato_il = array_info_doc[3]
                                else #se ci sono problemi di solito è sul nome del comune
                                    tipo_doc = array_info_doc[0]
                                    num_doc = array_info_doc[1]
                                    rilasciato_da = "" # parte da array_info_doc[2]
                                    cont = 2
                                    while cont < n_parti-2  do
                                    rilasciato_da += array_info_doc[cont] if (array_info_doc[cont] =~ /[0-9]/).nil?
                                    cont +=1
                                    end
                                    rilasciato_il = array_info_doc[n_parti-2] #l'ultimo pezzo dovrebbe essere la data
                                end
                                utente_portale.tipo_documento = 'CI'
                                utente_portale.numero_documento = num_doc
                                utente_portale.data_documento = Date.parse(rilasciato_il) unless rilasciato_il.blank?
                                utente_portale.documento_rilasciato = rilasciato_da
                            end

                            #gestione patente guida
                            #arriva stringa del tipo: patenteGuida U1Y491627X mitucoROMA 2017-07-15 2028-01-31
                            if !doc_ident.blank? && doc_ident.include?("patenteGuida")
                                @request.session['doc_ident_da_spid'] = true
                                array_info_doc = doc_ident.split(" ")
                                n_parti = array_info_doc.length
                                if array_info_doc.length == 5
                                    tipo_doc = array_info_doc[0]
                                    num_doc = array_info_doc[1]
                                    rilasciato_da = array_info_doc[2]
                                    rilasciato_il = array_info_doc[3]
                                else #se ci sono problemi di solito è sul nome del comune
                                    tipo_doc = array_info_doc[0]
                                    num_doc = array_info_doc[1]
                                    rilasciato_da = "" # parte da array_info_doc[2]
                                    cont = 2
                                    while cont < n_parti-2  do
                                    rilasciato_da += array_info_doc[cont] if (array_info_doc[cont] =~ /[0-9]/).nil?
                                    cont +=1
                                    end
                                    rilasciato_il = array_info_doc[n_parti-2] #l'ultimo pezzo dovrebbe essere la data
                                end
                                utente_portale.tipo_documento = 'Patente'
                                utente_portale.numero_documento = num_doc
                                utente_portale.data_documento = Date.parse(rilasciato_il) unless rilasciato_il.blank?
                                utente_portale.documento_rilasciato = rilasciato_da
                            end


                            #questi campi possono essere cambiati a livello di portale
                            utente_portale.email = attributi_utente[:email] if utente_portale.email.blank? && !attributi_utente[:email].blank?
                            if utente_portale.cellulare.blank? && !attributi_utente[:mobilePhone].blank?
                                cellulare = attributi_utente[:mobilePhone] 
                                unless cellulare.blank?
                                    if !cellulare.include?("+") && (cellulare.length == 10 || cellulare.length == 9)
                                        utente_portale.cellulare = cellulare
                                    elsif cellulare.include?("+39") && (cellulare.length == 13 || cellulare.length == 12)
                                        utente_portale.cellulare = cellulare.gsub("+39","")
                                    end
                                end
                            end
                            #utente_portale.cap_residenza = attributi_utente[:capResidenza] unless attributi_utente[:capResidenza].blank?
                            #utente_portale.comune_residenza = attributi_utente[:comuneResidenza] unless attributi_utente[:comuneResidenza].blank?
                            if utente_portale.indirizzo_residenza.blank? && !attributi_utente[:address].blank?
                                utente_portale.indirizzo_residenza = attributi_utente[:address].gsub("fullCvaddress: ","") #nel caso EIDAS ho questa stringa ulteriore
                            end
                             #utente_portale.provincia_residenza = attributi_utente[:provinciaDomicilio] unless attributi_utente[:provinciaDomicilio].blank?
                            #digitalAddress sarebbe la pec, se non presente Poste ritorna un trattino....
                            utente_portale.pec = attributi_utente[:digitalAddress] if utente_portale.pec.blank? && !attributi_utente[:digitalAddress].blank? && !attributi_utente[:digitalAddress] == '-'
                            utente_portale.stato = 'confermato'
                            utente_portale.save

                            #salvo la ditta se arriva una p iva
                            if !attributi_utente[:fiscalNumber].blank? && attributi_utente[:fiscalNumber].include?("VATIT")
                                ditta.ragione_sociale = attributi_utente[:companyName] if ditta.ragione_sociale.blank? && !attributi_utente[:companyName].blank?
                                ditta.partita_iva = attributi_utente[:ivaCode] if ditta.partita_iva.blank? && !attributi_utente[:ivaCode].blank?
                                ditta.indirizzo_azienda = attributi_utente[:registeredOffice] if ditta.indirizzo_azienda.blank? && !attributi_utente[:registeredOffice].blank?
                                ditta.referente = utente_portale
                                ditta.save
                            end        
                        
                            # if creato #creato utente_portale
                            #     utente_portale.insert
                            #     Spider.logger.debug "** CREATO UTENTE PORTALE"
                            # else
                            #     utente_portale.update
                            #     Spider.logger.debug "** MODIFICATO UTENTE PORTALE"
                            # end
                            utente.utente_portale = utente_portale
                            #utente.utente_esterno.save
                            utente.save
                        
                            #salvo nella tracciatura le info della response
                            authn_req_id = response.response_to_id
                            unless authn_req_id.blank?
                                traccia_response = Portal::TracciaturaSpidAgid.load(:authn_req_id => authn_req_id)
                                unless traccia_response.blank?
                                    saml_response_dec_compressa = Zlib::Deflate.deflate(saml_response_dec)
                                    traccia_response.response = Base64.strict_encode64(saml_response_dec_compressa)
                                    traccia_response.response_id = response.id
                                    traccia_response.response_issue_instant = response.issue_instant
                                    traccia_response.response_issuer = response.issuer
                                    traccia_response.assertion_id = response.assertion_id
                                    traccia_response.assertion_subject = response.assertion_subject
                                    traccia_response.assertion_subject_name_qualifier = response.assertion_subject_name_qualifier
                                    traccia_response.utente_tracciato = utente_portale
                                    traccia_response.save
                                else
                                    raise "Response non corrispondente ad una request effettuata"
                                end
                            else
                                raise "Response senza id request"
                            end


                            
                        #end
                    #Spider.logger.debug "**FACCIO COMMIT"
                    Portal::Utente.storage.commit
                    
                    if provider_id == "eidas"
                        #qui posso prendere l'id dell'utente portale e completare il cf se estero
                        id_utente_creato = utente_portale.id.to_s
                        utente_portale.codice_fiscale = "EE_"+id_utente_creato.to_s.rjust(13, '0')
                        utente_portale.save   
                        utente.chiave = utente_portale.codice_fiscale #risalvo anche chiave in portal__utenteesterno
                        utente.save
                    end
                    
                    if creato
                        email_amministratore_utente_registrato(utente_portale.id) unless utente_portale.email.blank?
                    end
                    #Salvo in sessione i dati
                    if (!@request.utente_portale || @request.utente_portale != utente_portale)
                        @request.utente_portale = utente_portale
                    end
                    # Spider.logger.debug("Utente portale: #{@request.utente_portale}")
                    @request.utente_portale.authenticated(:spid)
                    utente.save_to_session(@request.session)
                    #@request.utente_portale.save_to_session(@request.session)

                    #controllo se vengono passati i campi obbligatori
                    campi_obbligatori_presenti = Portal.controlla_campi_obbligatori(utente_portale)
                    if campi_obbligatori_presenti
                        redirect Portal.http_s_url
                    else
                        #lo rimando alla pagina di modifica dei dati
                        redirect Portal.http_s_url('dettagli_utente?modifica')
                    end

                    
                rescue Exception => exc
                    messaggio = "Errore Applicativo" 
                    messaggio += " ( #{exc.message} )" if Spider.runmode == 'devel'
                    messaggio_log = messaggio + " ( #{exc.message} )"
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    }
                    Spider.logger.error messaggio_log
                    Portal::Utente.storage.rollback
                    errore_autenticazione messaggio, exc.message
                end

                


            else
                errore_autenticazione "Non sono stati ricevuti dati per l'utente"
            end    
        end
        
        __.xml
        def logout_service
            #controllo indirizzo dell' identity provider e faccio logout
            settings = get_saml_settings
            # LogoutRequest accepts plain browser requests w/o paramters 
            logout_request = Spid::Saml::LogoutRequest.new( :settings => settings )

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
        def errore_autenticazione(messaggio, dett="")
            @scene.messaggio_errore = messaggio
            Spider.logger.error("Errore autenticazione Spid: #{messaggio}, #{dett}")
            render 'auth_providers/spid/autenticazione_fallita'
            done
        end
        

        def get_saml_settings
            settings = Spid::Saml::Settings.new
            if Spider.conf.get('portal.spid.https_server')
                local_portal_url = Portal.http_s_url.gsub("http:","https:")
            end

            settings.assertion_consumer_service_url     = local_portal_url+'/auth/spid/assertion_consumer'
            settings.issuer                             = Spider.conf.get('portal.spid.issuer')
            settings.sp_cert                            = (Spider.conf.get('portal.spid.cert_path').blank? ? File.join(Spider.paths[:certs],"default/cert.pem") : Spider.conf.get('portal.spid.cert_path') )
            settings.sp_external_consumer_cert          = Spider.conf.get('portal.spid.sp_external_consumer_cert') #array di path di certificati di consumer esterni
            settings.sp_private_key                     = (Spider.conf.get('portal.spid.private_key_path').blank? ? File.join(Spider.paths[:certs],"default/private/key.pem") : Spider.conf.get('portal.spid.private_key_path') ) 
            settings.single_logout_service_url          = local_portal_url + '/auth/spid/logout_service'
            settings.sp_name_qualifier                  = Spider.conf.get('portal.spid.sp_name_qualifier')
            settings.sp_name_identifier                 = Spider.conf.get('portal.spid.sp_name_identifier')
            settings.idp_name_qualifier                 = Spider.conf.get('portal.spid.name_identifier_format')
            # Optional for most SAML IdPs
            #settings.authn_context = "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport"
            #assegno ai settings un array da configurazione
            settings.name_identifier_format             = Spider.conf.get('portal.spid.name_identifier_format')
            settings.destination_service_url            = Spider.conf.get('portal.spid.destination_service_url')
            settings.single_logout_destination          = Spider.conf.get('portal.spid.single_logout_destination')
            settings.authn_context                      = Spider.conf.get('portal.spid.authn_context')
            settings.requester_identificator            = Spider.conf.get('portal.spid.requester_identificator')
            settings.skip_validation                    = Spider.conf.get('portal.spid.skip_validation')
            settings.idp_sso_target_url                 = Spider.conf.get('portal.spid.idp_sso_target_url')
            settings.idp_metadata                       = Spider.conf.get('portal.spid.idp_metadata')
            settings.requested_attribute                = Spider.conf.get('portal.spid.requested_attribute')
            settings.requested_attribute_eidas_min      = Spider.conf.get('portal.spid.requested_attribute_eidas_min')
            settings.requested_attribute_eidas_full     = Spider.conf.get('portal.spid.requested_attribute_eidas_full')
            settings.metadata_signed                    = Spider.conf.get('portal.spid.metadata_signed')
            settings.organization                       = Spider.conf.get('portal.spid.organization')
            settings.assertion_consumer_service_index   = Spider.conf.get('portal.spid.assertion_index')
            settings.attribute_consuming_service_index  = Spider.conf.get('portal.spid.attribute_index')
            #ho degli hash identificati dagli indici degli AssertionConsumerService tags nei metadata. Costruisco AssertionConsumerService e AttributeConsumingService
            settings.hash_assertion_consumer            = Spider.conf.get('portal.spid.hash_assertion_consumer')
            #se il campo settings.hash_assertion_consumer[indiceN][url_consumer] è vuoto, uso settings.assertion_consumer_service_url
            settings.hash_assertion_consumer.each_pair{ |index,hash_service|
                hash_service['url_consumer'] = settings.assertion_consumer_service_url if hash_service['url_consumer'].blank?
            }
            
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
            #querystring="RelayState=#{relayState_encoded}&SAMLRequest=#{deflate_request_B64_encoded}&SigAlg=#{sigAlg_encoded}"
            querystring="SAMLRequest=#{deflate_request_B64_encoded}&RelayState=#{relayState_encoded}&SigAlg=#{sigAlg_encoded}"
            #puts "**QUERYSTRING** = "+querystring
            #digest = OpenSSL::Digest::SHA1.new(querystring.strip) sha1
            digest = OpenSSL::Digest::SHA256.new(querystring.strip) #sha2 a 256
            chiave_privata = (Spider.conf.get('portal.spid.private_key_path').blank? ? File.join(Spider.paths[:certs],"default/private/key.pem") : Spider.conf.get('portal.spid.private_key_path') ) 
            pk = OpenSSL::PKey::RSA.new File.read(chiave_privata) #chiave privata
            qssigned = pk.sign(digest,querystring.strip)
            Base64.encode64(qssigned).gsub(/\n/, "")
        end

        
        def email_amministratore_attesa_conferma(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_spid/#{scene.utente.utente_spid_agid.id}"
            scene.auth_provider = 'Gestore Identità Spid'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)

            scene.link_amministrazione = "http://#{@request.http_host}/admin/portal/utenti_spid/#{scene.utente.utente_spid_agid.id}"
            scene.auth_provider = 'Gestore Identità Spid'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'), Spider.conf.get('portal.email_amministratore'), headers)
        end
        
       
        
    end
    
end

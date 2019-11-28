# -*- encoding : utf-8 -*-

module Spider

    config_option 'ente.nome', "Nome dell'ente", :type => String, :default => Proc.new{ Spider.conf.get('orgs.default.name') }
    config_option 'ente.tipo', "Tipo di ente, esempio: 'Comune di', 'Citta di'", :type => String, :default => ""
    config_option 'ente.indirizzo', "Indirizzo dell'ente", :type => String
    config_option 'ente.mail', "E-mail dell'amministratore", :type => String, :default => Proc.new{ Spider.conf.get('orgs.default.email') }
    config_option 'ente.telefono', "Telefono dell'ente", :type => String
    config_option 'ente.fax', "Fax dell'ente", :type => String
    config_option 'ente.partita_iva', "Partita IVA dell'ente", :type => String
    config_option 'ente.codice_istat', "Codice Istat dell'ente", :type => String
    config_option 'ente.codice_ipa', "Codice iPA o codice Amministrazione", :type => String
    config_option 'ente.codice_aoo', "Codice AOO", :type => String
    config_option 'ente.codice_ipa_dest', "Codice iPA dell'ente destinatario", :type => String, :default => Proc.new{ Spider.conf.get('ente.codice_ipa') }
    config_option 'ente.codice_aoo_dest', "Codice AOO dell'ente destinatario", :type => String, :default => Proc.new{ Spider.conf.get('ente.codice_aoo') }

    config_option 'portal.nome', "Nome dell'ente", :type => String, :default => Proc.new{ "#{Spider.conf.get('ente.tipo')} #{Spider.conf.get('ente.nome')}" }
    config_option 'portal.url', "Indirizzo da cui è raggiungibile il portale", :type => String
    config_option 'portal.no_db', "Se disabilitare la gestione interna degli utenti e dei servizi",
    :type => Spider::DataTypes::Bool, :default => false
    config_option 'portal.autenticazione_interna', "Se abilitare l'autenticazione interna",
    :type => Spider::DataTypes::Bool, :default => Proc.new{ Spider.conf.get('portal.no_db') ? false : true } 
    config_option 'portal.registrazione_utenti', "Se abilitare la registrazione utenti dal portale",
    :type => Spider::Bool, :default => Proc.new{ Spider.conf.get('portal.no_db') ? false : true } 
    
    config_option 'portal.prefisso_tabelle', "Prefisso da usare per le tabelle invece di 'Portal'", :type => String, :default => nil
    config_option 'portal.autenticazioni_esterne', "Elenco di provider di autenticazione esterna da utilizzare",
    :type => Array, :default => []
    config_option 'portal.attivazione_utenti_automatica', "Se rendere l'utente attivo quando si registra", 
    :type => Spider::DataTypes::Bool, :default => Proc.new{ !Spider.conf.get('portal.password_libera') }
    config_option 'portal.password_libera', "Se permettere all'utente di scegliere la propria password", 
    :type => Spider::DataTypes::Bool, :default => true
    config_option 'portal.password_cambiabile', "Se permettere all'utente di cambiare la propria password, anche se la password all'inizio non è libera", 
    :type => Spider::DataTypes::Bool, :default => true
    #config_option 'portal.email_from', "Indirizzo 'from' da cui provengono i messaggi del portale",
    #:type => String, :default => Proc.new{ Spider.conf.get('orgs.default.auto_from_email') || Spider.conf.get('portal.email_amministratore') }
    config_option 'portal.email_from', "Indirizzo 'from' da cui provengono i messaggi del portale", :type => String, :default => Proc.new{ Spider.conf.get('portal.email_amministratore') }
    # config_option 'portal.email_amministratore', "Indirizzo e-mail dell'amministratore del portale",
    # :type => String, :default => Proc.new{ Spider.conf.get('orgs.default.email') }
    config_option 'portal.email_amministratore', "Indirizzo e-mail dell'amministratore del portale", :type => String, :default => 'info@soluzionipa.it'
    config_option 'portal.pec_amministratore', "Indirizzo pec dell'amministratore del portale", :type => String, :default => 'info@soluzionipa.it'
    config_option 'portal.url_sito', "Indirizzo del sito dell'ente", :type => String, :default => Proc.new{ 
        Object.const_defined?(:Cms) ? Cms.http_s_url : nil 
    }
    config_option 'portal.attributi_aggiuntivi', "Attributi aggiuntivi per l'utente", :type => Hash, 
    :default => {'codice_master' => 'Codice Master'}

    config_option 'portal.richiedi_documento', "Se richiedere il documento alla registrazione", :type => Spider::Bool,
        :default => true
    config_option 'portal.conferma_email', "Se richiedere la conferma dell'indirizzo e-mail alla registrazione", 
        :type => Spider::Bool, :default => true
    config_option 'portal.conferma_cellulare', "Se richiedere la conferma del numero di cellulare alla registrazione (richiede messenger SMS)", 
        :type => Spider::Bool, :default => false
    config_option 'portal.conferma_cambio_email', "Se richiedere la conferma dell'e-mail quando viene cambiata",
        :type => Spider::Bool, :default => Proc.new{ Spider.conf.get('portal.conferma_email') ? true : false }
    config_option 'portal.conferma_cambio_cellulare', 
        "Se richiedere la conferma del cellulare quando viene cambiato (richiede messenger SMS)", 
        :type => Spider::Bool, :default => Proc.new{ Spider.conf.get('portal.conferma_cellulare') ? true : false }
    config_option 'portal.cellulare_obbligatorio', "Se richiedere il cellulare in fase di registrazione",
        :type => Spider::Bool, :default => Proc.new{ Spider.conf.get('portal.conferma_cellulare') ? true : false }
    config_option 'portal.pec_obbligatoria', "Se richiedere la pec obbligatoria in fase di registrazione",
        :type => Spider::Bool, :default => false
    config_option 'portal.invio_conferme_max.email', :type => Integer, :default => 3
    config_option 'portal.invio_conferme_max.cellulare', :type => Integer, :default => 3
    config_option 'portal.registrazione_professionista', "Abilita registrazione come professionista con Albo professionale", :type => Spider::Bool, :default => false
    
    # Auth providers
    
    config_option 'portal.regione_lombardia.assertion_consumer', "Indirizzo a cui risponde l'assertion_consumer del portale", :type => String
    config_option 'portal.regione_lombardia.test', "Se utilizzare il certificato di test", :type => Spider::DataTypes::Bool
    
    
    config_option 'portal.alto_milanese.url', "Url della pagina di autenticazione", :type => String,
        :default => 'http://www.altomilanese.mi.it/servizi/autorizzazioni/login_fase01.aspx'
    config_option 'portal.alto_milanese.wsdl', "Url del WSDL del servizio di autenticazione", :type => String, 
        :default => 'http://www.altomilanese.mi.it/servizi/autorizzazioni/ssows.asmx?WSDL'
    config_option 'portal.alto_milanese.client_key', "Client Key del servizio di autenticazione", :type => String
    
    config_option 'portal.pat_trentino.login_url', "Url della pagina di autenticazione della federazione PAT trentina", :type => String
    config_option 'portal.pat_trentino.test', "Se utilizzare ambiente di test per il pat trentino", :type => Spider::DataTypes::Bool
    config_option 'portal.pat_trentino.https_server', "Imposto se usare https", :type => Spider::DataTypes::Bool
    config_option 'portal.pat_trentino.name_identifier_format', "Array con valori di formato per l'identificatore", :type => Array,
        :default => ["urn:oasis:names:tc:SAML:2.0:nameid-format:persistent", "urn:oasis:names:tc:SAML:2.0:nameid-format:transient", "urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"]
    config_option 'portal.pat_trentino.sp_name_qualifier', "Name qualifier del service processor", :type => String, 
        :default => '/auth/pat_trentino/sp_metadata'
    config_option 'portal.pat_trentino.authn_context', "Array con tipi di autorizzazioni permesse (Smartcard, PasswordProtectedTransport)", :type => Array,
        :default => ["urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard", "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport"]
    config_option 'portal.pat_trentino.requester_identificator', "Array con id dei richiedenti", :type => Array
    config_option 'portal.pat_trentino.single_logout_destination', "Url di destinazione per la request logout", :type => String
        #,:default => 'https://idp-test.infotn.it/idp/FrontChannel/Logout'      
    config_option 'portal.pat_trentino.idp_sso_target_url', "Url target per il single sign on", :type => String
    config_option 'portal.pat_trentino.idp_metadata', "Url dei metadati dell'identity provider", :type => String  
    config_option 'portal.pat_trentino.idp_name_qualifier', "Identificativo che può essere usato in più namespace (in senso federato) per rappresentare l\'idp", :type => String  
    config_option 'portal.pat_trentino.destination_service_url', "Url del servizio per l'identity provider, usato come proxy per il sso", :type => String
    config_option 'portal.pat_trentino.idp_sso_target_url', "Url target del sso dell'identity provider", :type => String
    config_option 'portal.pat_trentino.skip_validation', "Imposto se evitare la validazione della response", :type => Spider::DataTypes::Bool,
        :default => false

    config_option 'portal.federa.login_url', "Url della pagina di autenticazione Federa Emilia Romagna", :type => String
    config_option 'portal.federa.test', "Se utilizzare ambiente di test per Federa Emilia Romagna", :type => Spider::DataTypes::Bool
    config_option 'portal.federa.https_server', "Imposto se usare https per Federa Emilia Romagna", :type => Spider::DataTypes::Bool
    config_option 'portal.federa.name_identifier_format', "Array con valori di formato per l'identificatore", :type => Array,
        :default => ["urn:oasis:names:tc:SAML:2.0:nameid-format:persistent", "urn:oasis:names:tc:SAML:2.0:nameid-format:transient", "urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"]
    config_option 'portal.federa.sp_name_qualifier', "Name qualifier del service processor", :type => String, 
        :default => '/auth/federa_emilia_romagna/sp_metadata'
    config_option 'portal.federa.authn_context', "Array con tipi di autorizzazioni permesse (Smartcard, PasswordProtectedTransport)", :type => Array,
        :default => ["urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard", "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport"]
    config_option 'portal.federa.requester_identificator', "Array con id dei richiedenti", :type => Array      
    config_option 'portal.federa.single_logout_destination', "Url di destinazione per la request logout", :type => String      
    config_option 'portal.federa.idp_sso_target_url', "Url target per il single sign on", :type => String
    config_option 'portal.federa.idp_metadata', "Url dei metadati dell'identity provider", :type => String  
    config_option 'portal.federa.idp_name_qualifier', "Identificativo che può essere usato in più namespace (in senso federato) per rappresentare l\'idp", :type => String  
    config_option 'portal.federa.destination_service_url', "Url del servizio per l'identity provider, usato come proxy per il sso", :type => String
    config_option 'portal.federa.idp_sso_target_url', "Url target del sso dell'identity provider", :type => String
    config_option 'portal.federa.skip_validation', "Imposto se evitare la validazione della response", :type => Spider::DataTypes::Bool, 
        :default => false    

    config_option 'portal.spid.login_url', "Url della pagina di autenticazione Spid ", :type => String
    config_option 'portal.spid.test', "Se utilizzare ambiente di test per Spid ", :type => Spider::DataTypes::Bool
    config_option 'portal.spid.https_server', "Imposto se usare https per Spid ", :type => Spider::DataTypes::Bool, :default => true
    config_option 'portal.spid.name_identifier_format', "Array con valori di formato per l'identificatore", :type => Array,
        :default => ["urn:oasis:names:tc:SAML:2.0:nameid-format:transient"]
    config_option 'portal.spid.sp_name_qualifier', "Name qualifier del service processor , un URI con lo schema specificato (es: http://dominio/portal)", :type => String, 
        :default => ''
    config_option 'portal.spid.sp_name_identifier', "Name identifier del service processor", :type => String, :default => Proc.new{ Spider.conf.get('portal.spid.sp_name_qualifier') }
    config_option 'portal.spid.authn_context', "Array con tipi di autorizzazioni permesse (Smartcard, PasswordProtectedTransport)", :type => Array,
        :default => ["urn:oasis:names:tc:SAML:2.0:ac:classes:Smartcard", "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport"]
    config_option 'portal.spid.requester_identificator', "Array con id dei richiedenti", :type => Array      
    config_option 'portal.spid.single_logout_destination', "Url di destinazione per la request logout", :type => String      
    #config_option 'portal.spid.idp_metadata', "Url dei metadati dell'identity provider", :type => String  
    #config_option 'portal.spid.idp_name_qualifier', "Identificativo che può essere usato in più namespace (in senso federato) per rappresentare l\'idp", :type => String  
    #config_option 'portal.spid.destination_service_url', "Url del servizio per l'identity provider, usato come proxy per il sso", :type => String
    #config_option 'portal.spid.idp_sso_target_url', "Url target del sso dell'identity provider", :type => String
    config_option 'portal.spid.skip_validation', "Imposto se evitare la validazione della response", :type => Spider::DataTypes::Bool,
        :default => false    
    config_option 'portal.spid.issuer', "SP Issuer, va nell'entityID un URI con lo schema specificato (es: https://dominio/portal)", :type => String
    config_option 'portal.spid.cert_path', "Path del certificato (formato pem) dalla root" , :type => String
    config_option 'portal.spid.sp_external_consumer_cert', "Array di Path dei certificati dei consumer esterni (formato pem)" , :type => Array, :default => []
    config_option 'portal.spid.private_key_path', "Path della chiave (formato pem) dalla root", :type => String
    config_option 'portal.spid.hash_gestori', "Hash che collega id dei gestori con il loro link per sso", :type => Hash, 
    :default => { "namirialid" => { 'url_authnrequest' => "https://idp.namirialtsp.com/idp/profile/SAML2/Redirect/SSO", 'idp_metadata' => "https://idp.namirialtsp.com/idp/metadata", 'idp_name_qualifier' => "Namirial  S.p.A."},
                  "spiditalia" => { 'url_authnrequest' => "https://spid.register.it/login/sso", 'idp_metadata' => "https://spid.register.it/login/metadata", 'idp_name_qualifier' => "SpidItalia REGISTER.IT"},
                  "arubaid" => { 'url_authnrequest' => "https://loginspid.aruba.it/ServiceLoginWelcome", 'idp_metadata' => "https://loginspid.aruba.it/metadata", 'idp_name_qualifier' => "Aruba Pec S.p.A."},
                  "infocert" => { 'url_authnrequest' => "https://identity.infocert.it/spid/samlsso", 'idp_metadata' => "https://identity.infocert.it/metadata/metadata.xml", 'idp_name_qualifier' => "Infocert S.p.A."},
                  "poste" => {'url_authnrequest' => "https://posteid.poste.it/jod-fs/ssoserviceredirect", 'idp_metadata' => "http://posteid.poste.it/jod-fs/metadata/metadata.xml", 'idp_name_qualifier' => "Poste Italiane S.p.A."}, 
                  "sielte" => {'url_authnrequest' => "https://identity.sieltecloud.it/simplesaml/saml2/idp/SSOService.php", 'idp_metadata' => "https://identity.sieltecloud.it/simplesaml/metadata.xml", 'idp_name_qualifier' => "Sielte S.p.A."},
                  "tim" => {'url_authnrequest' => "https://login.id.tim.it/affwebservices/public/saml2sso" , 'idp_metadata' => "https://login.id.tim.it/spid-services/MetadataBrowser/idp", 'idp_name_qualifier' => "TI Trust Technologies S.r.l."},
                  "intesa" => {'url_authnrequest' => "https://spid.intesa.it/Time4UserServices/services/idp/AuthnRequest" , 'idp_metadata' => "https://spid.intesa.it/metadata/metadata.xml", 'idp_name_qualifier' => "In.Te.S.A. S.p.A."},
                  "lepida" => {'url_authnrequest' => "https://id.lepida.it/idp/profile/SAML2/Redirect/SSO" , 'idp_metadata' => "https://id.lepida.it/idp/shibboleth", 'idp_name_qualifier' => "Lepida S.p.A."},
                  "eidas" => {'url_authnrequest' => "https://sp-proxy.eid.gov.it/spproxy/samlsso" , 'idp_metadata' => "https://sp-proxy.eid.gov.it/spproxy/idpitmetadata", 'idp_name_qualifier' => "Eidas"}
                  #,"eidas" => {'url_authnrequest' => "https://sp-proxy.pre.eid.gov.it/spproxy/samlsso" , 'idp_metadata' => "https://sp-proxy.pre.eid.gov.it/spproxy/idpitmetadata", 'idp_name_qualifier' => "Eidas"} #PER VALIDAZIONE EIDAS 
                  #,"spid_validator" => {'url_authnrequest' => "https://validator.spid.gov.it/samlsso" , 'idp_metadata' => "https://validator.spid.gov.it/metadata.xml", 'idp_name_qualifier' => "Spid Validator"} #PER FAR VALIDARE LO SPID AD AGID
                }
    config_option 'portal.spid.requested_attribute', "Array che contiene i nomi dei campi richiesti dal servizio nei metadata", :type => Array, :default => ['spidCode', 'name', 'familyName', 'fiscalNumber', 'email', 'gender', 'dateOfBirth', 'placeOfBirth', 'countyOfBirth', 'idCard', 'address', 'digitalAddress', 'expirationDate', 'mobilePhone', 'ivaCode', 'registeredOffice']
    config_option 'portal.spid.requested_attribute_eidas_min', "Array che contiene i nomi dei campi richiesti dal servizio nei metadata", :type => Array, :default => ['spidCode', 'name', 'familyName', 'dateOfBirth']
    config_option 'portal.spid.requested_attribute_eidas_full', "Array che contiene i nomi dei campi richiesti dal servizio nei metadata", :type => Array, :default => ['spidCode', 'name', 'familyName', 'gender', 'dateOfBirth', 'placeOfBirth', 'address']
    config_option 'portal.spid.metadata_signed', "Indico se firmare i metadata", :type => Spider::DataTypes::Bool, :default => true
    config_option 'portal.spid.organization', "Hash che contiene nome, nome lungo e url dell'ente fornitore di servizi", :type => Hash, 
        :default => Proc.new{ { "org_name" => Spider.conf.get('orgs.default.name'), "org_display_name" => Spider.conf.get('orgs.default.name'), "org_url" => Spider.conf.get('orgs.default.url') } }
    config_option 'portal.spid.assertion_index', "Index di AssertionConsumerServiceIndex nella authnrequest da usare per login su portale Openweb (potrebbero esserci ulteriori AssertionConsumerService nei metadata)", :type => String, :default => "0"
    config_option 'portal.spid.attribute_index', "Index di AttributeConsumingServiceIndex nella authnrequest da usare per login su portale Openweb (potrebbero esserci ulteriori AssertionConsumerService nei metadata)", :type => String, :default => "0"    
    config_option 'portal.spid.hash_assertion_consumer', "Hash usato per creare tag AssertionConsumerService e AttributeConsumingService nei metadata. Nel service interno lasciare vuoto il campo url_consumer", :type => Hash, 
    :default => {   "0" => { 'url_consumer' => '',
                        'external' => false,
                           'default' => true, 
                           'array_campi' => ['spidCode', 'name', 'familyName', 'fiscalNumber', 'email', 'gender', 'dateOfBirth', 'placeOfBirth', 'countyOfBirth', 'idCard', 'address', 'digitalAddress', 'expirationDate', 'mobilePhone', 'ivaCode', 'registeredOffice'],
                           'testo' => 'User Data'
                        },
                    "99" => { 'url_consumer' => '',
                            'external' => false,
                            'default' => false, 
                            'array_campi' => ['spidCode', 'name', 'familyName', 'dateOfBirth'],
                            'testo' => 'eIDAS Natural Person Minimum Attribute Set'
                        },
                    "100" => { 'url_consumer' => '',
                            'external' => false,
                            'default' => false, 
                            'array_campi' => ['spidCode', 'name', 'familyName', 'gender', 'dateOfBirth', 'placeOfBirth', 'address'],
                            'testo' => 'eIDAS Natural Person Full Attribute Set'
                         },

                }
    config_option 'portal.scarica_utenti_da', "Url da cui scricare gli utenti", :type => String
    config_option 'portal.servizi_privati_default', "Servizi privati da attivare in automatico alla registrazione",
        :type => Array, :default => []
    config_option 'portal.servizi_abilitazione_automatica', "Servizi solo per abilitati ad abilitazione automatica",
        :type => Array, :default => []
    config_option 'portal.locale_fisso', "Utilizza sempre un locale indipendentemente dalle impostazioni del client",
        :default => 'it'

    config_option 'portal.abilita_password_normativa', "Imposto se usare la normativa per la password di registrazione al portale.", :type => Spider::DataTypes::Bool,
        :default => false   

    config_option 'portal.abilita_autenticazione_forte', "Imposto se usare l\'autenticazione forte con invio di sms dopo login.", :type => Spider::DataTypes::Bool,
        :default => false 
    config_option 'portal.abilita_autenticazione_forte_per_servizio', "Imposto se usare l\'autenticazione forte con invio di sms per singolo servizio.", :type => Spider::DataTypes::Bool,
        :default => false 
    config_option 'portal.url_consentiti_string_auth', "Path del portale che non rimandano alla pagina di inserimento codice nella strong auth per evitare loop",
        :type => Array, :default => ['/portal/autenticazione', '/portal/dettagli_utente', '/portal/controllo_cellulare', '/portal/codice_autenticazione', '/portal/logout']

    config_option 'portal.abilita_dossier_cittadini', "Indica se è abilitato il dossier cittadino", :type => Spider::Bool, :default => false
    config_option 'portal.servizi_tracciati_dossier_cittadini', "Array di id dei servizi che si vogliono tracciare per i dossier dei cittadini", :type => Array, :default => []

    config_option 'portal.api.chiavi', "Client id e secret key per autenticazione delle chiamate da web service", :type => Hash, :default => {}

    config_option 'portal.abilita_accettazione_clausole', "Indica se abilitare l'accettazione delle clausole su privacy e comunicazioni", :type => Spider::DataTypes::Bool, :default => false

    config_option 'portal.comuni_province_tabellate', "Indica se vengono caricate le tabelle comuni e province fornite da civilia e caricate tramite csv", :type => Spider::DataTypes::Bool, :default => false
    config_option 'portal.province_tabellate', "Indica se vengono caricata la tabelle province fornite da civilia e caricate tramite csv", :type => Spider::DataTypes::Bool, :default => false
    config_option 'portal.azienda_univoca', "Indica se viene effettuato il controllo che il codice fiscale e la partita iva dell'azienda siano univoci", :type => Spider::DataTypes::Bool, :default => false


    config_option 'notifiche.moduli_da_disattivare', "Elenco dei moduli di notifica da disattivare", :type => Array, :default => []
    config_option 'notifiche.sms', "Se attivare l'invio delle notifiche via SMS", :type => Spider::Bool, :default => true
    config_option 'notifiche.email_mittente', "Indirizzo From per le e-mail", :type => String, :default => Proc.new{ Spider.conf.get('portal.email_from') }
    config_option 'notifiche.cron', "Hash di modulo => riga cron in formato 0 22 * * 1-5 di quando eseguire il modulo", :type => Hash, :default => {}
    config_option 'notifiche.muse.limite_credito_1', "Limite di credito CityCard per il primo avviso", :type => BigDecimal, :default => 10
    config_option 'notifiche.muse.limite_credito_2', "Limite di credito CityCard per il secondo avviso", :type => BigDecimal, :default => 5
    config_option 'notifiche.muse.notifica_onetime_per_limit', "Notifica il credito una sola volta per limite", :type => Spider::Bool, :default => false
    config_option 'portal.json_compressi', "Imposta se far scaricare il json compresso con header: Accept-Encoding: gzip, deflate", :type => Spider::Bool, :default => false

    config_option 'portal.nome_stemma', "Imposta il nome dello stemma del portale", :type => String, :default => "stemma.png"
    config_option 'portal.attiva_settori_hippo', "Imposta se attivare i settori delle tabelle dei benefici", :type => Spider::Bool, :default => false
    config_option 'portal.check_worker.attiva', "Attiva o meno la funzionalità di monitoring del worker", :type => Spider::Bool, :default => true
    config_option 'portal.check_worker.cron', "Riga cron in formato 0 22 * * 1-5 per il runtime del worker ", :type => String, :default => "0 6,12,20 * * *"
    config_option 'portal.check_worker.dest_url', "Url per il runtime del worker", :type => String, :default => "http://app.soluzionipa.it/openweb/servizi"

    config_option 'portal.url_get_doc', "Url per scaricare le pratiche con getDoc php dopo auth ruby.", :type => String
    config_option 'portal.servizi.richiesta_documenti_cittadino', "Richiede al cittadino integrazione e successiva mail all'Ente per abilitare un servizio con conferma es: (Demografici)", :type => Spider::DataTypes::Bool,
        :default => false   
    config_option 'portal.abilita_gdpr_utente', "Imposta se mostrare al cittadino l'informativa sulla privacy", :type => Spider::Bool, :default => false
    config_option 'portal.completa_campi_auth_esterne', "Imposta se devono essere completati i dati mancanti dalle auth esterne", :type => Spider::Bool, :default => true
    config_option 'portal.campi_readonly_auth_esterne', "Imposta se i campi provenienti da auth esterne sono readonly o no", :type => Spider::Bool, :default => true
    
    config_option 'portal.abilita_translate', "Imposta se abilitare Google translate sul sito", :type => Spider::Bool, :default => false
    
    config_option 'portal.secret_auth_hub', 'Secret condivisa per verifica token jwt con Auth_hub', :type => String

    config_option 'portal.client_id_oauth2', "Client id dell'applicazione che permette di fare Oauth2 Euro Servizi.", :type => String
    config_option 'portal.secret_oauth2', "Secret dell'applicazione che permette di fare Oauth2 Euro Servizi.", :type => String
    config_option 'portal.url_oauth2', "Url del servizio di Oauth2 Euro Servizi.", :type => String, :default => "https://login.soluzionipa.it"

    config_option 'api_next.tributi.tenant', "Tenant CiviliaNext per Tributi.", :type => String
    config_option 'api_next.tributi.secret', "Secret CiviliaNext per Tributi.", :type => String
    config_option 'api_next.tributi.client_id', "Client Id CiviliaNext per Tributi.", :type => String
    

end

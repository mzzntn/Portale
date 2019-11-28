# -*- encoding : utf-8 -*-
require 'apps/portal/lib/auth_provider'
# xmlsig deve essere installato da http://xmlsig.sourceforge.net/
require 'xmlsig'
require 'base64'
require "rexml/document"
require 'apps/portal/models/auth_providers/regione_lombardia/utente_regione_lombardia'
require 'openssl'


module Portal
    
    class RegioneLombardia < Spider::PageController
        include HTTPMixin
        include AuthProvider
        include Spider::Messenger::MessengerHelper 

        auth_provider({
            :label => 'regione_lombardia',
            :nome => 'C.R.S. Regione Lombardia',
            :descrizione => 'Servizio di autenticazione attraverso Carta Regionale dei Servizi della Regione Lombardia'
        })

        __.action
        def index
            #target = https_request_url+'/assertion_consumer?target='+Spider.conf.get('portal.url')
            target = Spider.conf.get('portal.regione_lombardia.assertion_consumer')+'?target='+Portal.http_s_url
            redirect("https://idpcrl.crs.lombardia.it/scauth/SSLAuthServlet?TARGET=#{target}")
        end
        
        __.html
        def assertion_consumer
            saml_response = Base64.decode64(@request.params['SAMLResponse'])
            target = @request.params['target']
            response_status = @request.params['authResponseStatus']
            # Spider.logger.debug("SAML RESPONSE: #{saml_response}")
            x = Xmlsig::XmlDoc.new
            x.loadFromString(saml_response)
            x.addIdAttr("ResponseID", "Response", "")
            xp = Xmlsig::XPath.new()
            xp.addNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#')
            xp.setXPath("(//*[local-name()='Signature'])[1]")
            v = Xmlsig::Verifier.new(x,xp)
            begin
                rc1 = v.verify
            rescue => exc
                Spider.logger.error("ERROR VERIFYING SIGNATURE 1: #{exc.message}")
                rc1 = 0
            end
            xp = Xmlsig::XPath.new()
            xp.addNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#')
            xp.setXPath("(//*[local-name()='Signature'])[2]")
            v = Xmlsig::Verifier.new(x,xp)
             begin
                 rc2 = v.verify
             rescue => exc
                 Spider.logger.warn("ERROR VERIFYING SIGNATURE 2: #{exc.message}")
            #     rc2 = 0
             end
            rc2 = 1
            unless rc1 == 1 && rc2 == 1
                errore_autenticazione "non è stato possibile verificare la validità della risposta del sistema di autenticazione."
            end
            doc = REXML::Document.new(saml_response)
            status = REXML::XPath.first(doc, '/Response/Status/StatusCode')
            status_code = nil
            status_code = status.attributes['Value'] if (status)
            unless status_code == 'samlp:Success'
                errore_autenticazione "l'autenticazione presso il sistema remoto non è andata a buon fine." 
            end
            cnt = 0
            cert_file = Spider.apps['Portal'].path+"/data/auth_providers/regione_lombardia/certs"
            if (Spider.conf.get('portal.regione_lombardia.test'))
                cert_file += "/ca_lombardia_test.pem"
            else
                cert_file += "/ca_lombardia.pem"
            end
            ca = OpenSSL::X509::Certificate.new(File.read(cert_file))
            doc.elements.each("/Response/ds:Signature/ds:KeyInfo/ds:X509Data/ds:X509Certificate") do |c|
                ctext = "-----BEGIN CERTIFICATE-----\n#{c.text.strip}\n-----END CERTIFICATE-----"
                cert = OpenSSL::X509::Certificate.new(ctext)
                unless (cert.verify(ca.public_key) && cert.issuer.to_s == ca.subject.to_s)
                    Spider.logger.warn "certificato di autenticazione non valido: #{ctext}"
                    errore_autenticazione "il certificato del sistema di autenticazione non è valido."
                end
                cnt += 1
            end
            attributi_utente = {}
            doc.elements.each("/Response/Assertion/AttributeStatement/Attribute") do |a|
                a_name = Spider::Inflector.underscore(a.attributes['AttributeName']).to_sym
                attributi_utente[a_name] = a.elements["AttributeValue"].text
            end

            unless attributi_utente[:user_id]
                Spider.logger.warn "L'IdP Regione Lombardia non ha restituito un user_id valido"
                errore_autenticazione "il sistema di autenticazione remoto non ha trovato l'utente"
            end
            utente = UtenteRegioneLombardia.load(:chiave => attributi_utente[:user_id])
            utente_portale = nil
            creato = false
            if utente
                utente_portale = utente.utente_portale
                # Spider.logger.debug("Caricato utente portale #{utente_portale}")
            else
                utente = UtenteRegioneLombardia.new(:chiave => attributi_utente[:user_id])
                utente_portale = Portal::Utente.new
                utente_portale.nome = attributi_utente[:nome]
                utente_portale.cognome = attributi_utente[:cognome]
                utente_portale.codice_fiscale = attributi_utente[:codice_fiscale]
                utente_portale.sesso = attributi_utente[:sesso]
                utente_portale.comune_nascita = attributi_utente[:luogo_nascita]
                utente_portale.provincia_nascita = attributi_utente[:provincia_nascita]
                utente_portale.stato_nascita = attributi_utente[:stato_nascita]
                utente_portale.data_nascita = Date.strptime(attributi_utente[:data_nascita], "%d/%m/%Y")
                utente_portale.email = attributi_utente[:email_address]
                utente_portale.cellulare = attributi_utente[:cellulare]
                utente_portale.stato = 'confermato'
                utente_portale.save
                # Spider.logger.debug("Salvato utente portale #{utente_portale}")
                # Spider.logger.debug("ATTRIBUTI UTENTE: #{attributi_utente.inspect}")
                utente.chiave = attributi_utente[:user_id]
                utente.origine_dati_utente = attributi_utente[:origine_dati_utente]
                utente.cns_carta_reale = attributi_utente[:cns_carta_reale] == 'TRUE' ? true : false
                utente.cns_subject = attributi_utente[:cns_subject]
                utente.cns_issuer = attributi_utente[:cns_issuer]
                utente.utente_portale = utente_portale
                utente.utente_esterno.insert
                utente.insert
                creato = true
                # Spider.logger.debug("Salvato utente #{utente}")
            end
            if (!@request.utente_portale || @request.utente_portale != utente_portale)
                @request.utente_portale = utente_portale
            end
            # Spider.logger.debug("Utente portale: #{@request.utente_portale}")
            @request.utente_portale.authenticated(:regione_lombardia)
            utente.save_to_session(@request.session)
            #@request.utente_portale.save_to_session(@request.session)
            if creato
                email_amministratore_utente_registrato(utente_portale.id)
                #VIENE IMPOSTATO A CONFERMATO SEMPRE, mod del 27/09/2016
                # if (attivazione_automatica)
                #     email_amministratore_utente_registrato(utente_portale.id)
                # else
                #     email_amministratore_attesa_conferma(utente_portale.id)
                #     @scene.utente = utente_portale
                #     render('auth_providers/regione_lombardia/attesa_attivazione')
                #     done
                # end
            end
            redirect(target)
        end
        
        def errore_autenticazione(messaggio)
            @scene.messaggio_errore = messaggio
            Spider.logger.error("Errore autenticazione Regione Lombardia: #{messaggio}")
            render 'auth_providers/regione_lombardia/autenticazione_fallita'
            done
        end
        
        def email_amministratore_attesa_conferma(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/#{Portal.url}/amministrazione/utenti_crs_regione_lombardia/#{scene.utente.utente_regione_lombardia.id}"
            scene.auth_provider = 'C.R.S. Regione Lombardia'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_attesa_conferma', scene, Spider.conf.get('portal.email_from'), 
                Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        def email_amministratore_utente_registrato(id_utente)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "http://#{@request.http_host}/#{Portal.url}/amministrazione/utenti_crs_regione_lombardia/#{scene.utente.utente_regione_lombardia.id}"
            scene.auth_provider = 'C.R.S. Regione Lombardia'
            headers = {'Subject' => "Registrazione al portale"}
            send_email('amministratore/utente_registrato', scene, Spider.conf.get('portal.email_from'), 
                Spider.conf.get('portal.email_amministratore'), headers)
        end
        
        
        
    end
    
end

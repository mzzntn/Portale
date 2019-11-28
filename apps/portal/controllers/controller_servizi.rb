# -*- encoding : utf-8 -*-
module Portal
    
    class ControllerServizi < Spider::PageController
        include AutenticazionePortale
        include Spider::Messenger::MessengerHelper rescue NameError

        def self.aggiungi_servizio(nome, controller)
            Spider.logger.debug("/portal/servizi/#{nome} -> #{controller}")
            route nome, controller
        end
        
        Portal.servizi.each do |nome, controller|
            aggiungi_servizio(nome, controller)
        end
        
        def self.http_s_url(method=nil)
            url = Portal.http_s_url('servizi')
            url += '/'+method if method
            url
        end
        
        def before(action='', *params)
            #serve per passare alla views il servizio che viene messo come classe per gestire la grafica in base al servizio che si sta usando
            if @dispatch_next[action] && @dispatch_next[action].dest && @dispatch_next[action].dest.respond_to?(:servizio_portale)
                @scene.classe_contenuto_portale = @dispatch_next[action].dest.servizio_portale[:id]
            end


            super
        end
        
        __.html :template => 'servizi_utente'
        def index
            # @scene.servizi viene caricato in PortalController#before (richiamato da PortalController#before)
            autenticazione_necessaria
        end
        
        __.html :template => 'index'
        def attiva
            autenticazione_necessaria
            redirect self.class.http_s_url unless @request.post?
            servizi_utente = @request.utente_portale.servizi_privati.to_indexed_hash('servizio.id')
            @scene.disabilitati = []
            @scene.richiesti = []
            @scene.attivati = []
            # se @request.params['servizio'] era vuoto lo inizializzo
            @request.params['servizio'] = {} if @request.params['servizio'].blank?
            @scene.servizi.each do |servizio|
                next unless servizio.privato? 
                if @request.params['servizio'][servizio.id].blank? && (!servizi_utente[servizio.id].nil? && servizi_utente[servizio.id].stato.id != 'richiesto')
                    # mi creo un finto hash di request per avere anche i servizi disabilitati nell'hash
                    @request.params['servizio'][servizio.id] = "off"
                    @scene.disabilitati << servizio
                    if servizio.servizio_utente?
                        @request.utente_portale.servizi_privati.delete_if{ |s| s.servizio.id == servizio.id }
                    elsif servizio.oggetto_db?
                        @request.utente_portale.servizi_privati << Portal::Utente::ServiziPrivati.new(
                            :servizio => servizio.oggetto_db,
                            :stato => 'disattivato'
                        )
                    end
                end
            end
            @request.params['servizio'].each_pair do |s, val|
                #se ho richiesto l'attivazione e non è tra i miei servizi o è stato disattivato
                if val == "on" && (!servizi_utente[s] || servizi_utente[s].stato == 'disattivato')
                    #servizio da attivare
                    servizio = Servizio.new(s)
                    serv_class = Portal.servizi[s]
                    stato = nil
                    #se non viene specificato l'accesso sul servizio dava errore non controllando servizio.accesso.blank?
                    if servizio.accesso.blank? || ( servizio.accesso.id == 'abilitati' || ( @request.utente_portale.stato == 'attivo' && servizio.accesso.id == 'confermati' ) ) 
                        stato = 'richiesto'
                        @scene.richiesti << servizio
                    elsif serv_class && serv_class.configurazione_necessaria?
                        stato = 'configurazione'
                        @scene.attivati << servizio
                    else
                        stato = 'attivo'
                        @scene.attivati << servizio
                    end
                    if servizio_utente = servizi_utente[s]
                        servizio_utente.stato = stato
                        servizio_utente.save
                    else
                        @request.utente_portale.servizi_privati << {
                            :servizio => servizio,
                            :stato => stato
                        }
                    end
                end
            end
            @request.utente_portale.save
            if @scene.richiesti.length > 0 && Spider.conf.get('portal.servizi.richiesta_documenti_cittadino')
                email_cittadino_integrazione_documenti(@request.utente_portale.id, @scene.richiesti)
            elsif @scene.richiesti.length > 0
                email_amministratore_richiesta_servizi(@request.utente_portale.id, @scene.richiesti)
            end
            #setto la variabile per visualizzare il navigatore dell'index
            @scene.attivazione = true
            #richiamo ora la carica_elenco_servizi per aggiornare la variabile nella scene
            carica_elenco_servizi

        end
        
        def email_cittadino_integrazione_documenti(id_utente, servizi_richiesti)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.richiesti = servizi_richiesti
            scene.doc_url = "<a href=\"www.comune.acerra.na.it/data/files/1779_mod_richiesta_PASSWORD_fax_ver2.pdf\">modello richiesta password*</a>"
            headers = {'Subject' => "Richiesta attivazione servizi portale"}
            send_email('richiesta_documenti_per_servizi_con_conferma', scene, Spider.conf.get('portal.email_from'), 
                scene.utente.email, headers)
        end
        
        def email_amministratore_richiesta_servizi(id_utente, servizi_richiesti)
            scene = Spider::Scene.new
            scene.utente = Portal::Utente.new(id_utente)
            scene.link_amministrazione = "#{scene.utente.link_amministrazione}/servizi_privati"
            scene.richiesti = servizi_richiesti
            headers = {'Subject' => "Richiesta attivazione servizi portale"}
            send_email('amministratore/richiesta_servizi', scene, Spider.conf.get('portal.email_from'), 
                Spider.conf.get('portal.email_amministratore'), headers)
            if altre_email = Spider.conf.get('portal.email_richiesta_servizi')
                servizi_richiesti.each do |serv|
                    if altra_email = altre_email[serv.id]
                        scene.richiesti = [serv]
                        send_email('amministratore/richiesta_servizi', scene, Spider.conf.get('portal.email_from'),
                        altra_email, headers)
                    end
                end
            end
        end
        
        private

        def prepara_servizi
            controllers_servizi = Portal.servizi
            servizi_db = Portal::Servizio.all.to_indexed_hash(:id)
            id_servizi = (controllers_servizi.keys + servizi_db.keys).uniq
            servizi_utente = {}
            if @request.utente_portale
                servizi_utente = @request.utente_portale.servizi_privati.to_indexed_hash('servizio.id')
            end
            servizi = []
            hash_servizi = {}
            id_servizi.each do |id|
                s = Portal::ServizioPortale.new(controllers_servizi[id], servizi_utente[id] || servizi_db[id])
                servizi << s
                hash_servizi[id] = s
            end
            @scene.servizi = servizi
            @scene.hash_servizi = hash_servizi
            servizi
        end
        
        def carica_elenco_servizi
            @scene.servizi_privati_utente = []
            servizi = prepara_servizi
            servizi.each do |servizio|
                if servizio.pubblico?
                    @scene.servizi_pubblici << servizio
                elsif servizio.privato?
                    @scene.servizi_privati << servizio
                    # devo visualizzare mell'indice anche i servizi in necessaria configurazione
                    # con accesso per abilitati
                    if servizio.attivo? || servizio.in_configurazione?
                        @scene.servizi_privati_utente << servizio
                        
                        #se è attiva l'autenticazione forte cambio il link per farlo andare in codice_autenticazione e la si faranno i controlli
                        if servizio.richiede_strong_auth == true && Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true && @request.session['strong_auth_valid'].blank? && @request.utente_portale.attivo?
                            @scene.servizi_privati_utente.last.url = "/portal/codice_autenticazione?servizio_id=#{servizio.id}"
                        end
                    end    
                elsif servizio.nascosto?
                    @scene.servizi_privati_utente << servizio if servizio.attivo?

                    #se è attiva l'autenticazione forte cambio il link per farlo andare in codice_autenticazione e la si faranno i controlli
                    if servizio.attivo? && servizio.richiede_strong_auth == true && Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true && @request.session['strong_auth_valid'].blank?
                        @scene.servizi_privati_utente.last.url = "/portal/codice_autenticazione?servizio_id=#{servizio.id}"
                    end
                end
            end

            #ordino la lista dei servizi in base all'attributo posizione
            @scene.servizi_pubblici.sort!{ |a,b| 
                a.ordina_posizione(b) 
            }

        end




    end
    
end

# -*- encoding : utf-8 -*-
#controller usato per integrazione tra openweb e civiliaopen
require 'digest/sha1'

begin
    require 'jwt'
rescue LoadError => exc
    raise "Installare gemma jwt e usare ruby > 2"
end

module Portal
    
    class APIController < Spider::Controller
        include HTTPMixin
        include Visual

        CHIAVE = Spider.conf.get('pagamenti.secret_oauth')

        route :PUT, :put
        route :DELETE, :delete

        def before(action='', *params)
            super
            if !@request.params['check'].blank? #auth di default
                #controllo se ho passato il client_id
                client_id = @request.params['client_id']
                raise Forbidden.new("client_id missing") if client_id.blank?
                key = Spider.conf.get('portal.api.chiavi')[client_id]
                raise Forbidden.new("client_id doesn't match") if key.blank?
                hash = @request.params.delete('check')
                #vecchio metodo per ricavare la str fatto da ivan
                # str = @request.params.sort.map{ |pair|
                #         pair[1] = pair[1].to_json if pair[0] == 'lista_contesti_applicativi'
                #         pair.join('=')
                #     }.join('&')

                query_string = ""
                @request.params.sort.each{ |chiave_valore|
                    query_string += "#{chiave_valore[0]}=#{chiave_valore[1]}&"
                }
                #tolgo l'ultimo carattere
                query_string = query_string[0...-1]
             
                check_hash = Digest::SHA1.hexdigest(key+query_string)
                if hash != check_hash
                    raise Forbidden.new("Checksum doesn't match")
                end
                time = @request.params.delete('req_time')
                unless time
                    raise Forbidden.new("time param missing")
                end
                origin_time = DateTime.parse(time)
                #cancello il client id dai parametri in request
                @request.params.delete('client_id')
                #commento controllo sul tempo trascorso per problemi con test e per comune di Riano che ha data sbagliata del server
                #if origin_time < (DateTime.now - 60)
                #    raise Forbidden.new("Time too skewed")
                #end
            elsif !@request.env['HTTP_AUTHORIZATION'].blank? #authorization con token jwt
                token = @request.env['HTTP_AUTHORIZATION']
                raise Forbidden.new("Formato token non corretto: Bearer <token jwt>") unless token.include?('Bearer ')
                token_jwt = token.gsub('Bearer ','')
                #verifica della firma
                begin
                    jwt_decoded = JWT.decode(token_jwt, CHIAVE,'HS256')[0]    
                rescue JWT::VerificationError => ext
                    raise "Verifica della firma fallita su token jwt"
                end
                #verifica della scadenza
                raise Forbidden.new("Autenticazione scaduta!") if Time.now.utc.to_i > jwt_decoded['exp'] 
                #verifica del client id..da implementare invio del client_id su auth_hub
                jti = jwt_decoded['jti']
                client_id = OpenSSL::Digest::SHA256.new(get_dbname+jti)
                raise "Verifica del client_id fallito" if client_id != jwt_decoded['client_id']
            else
                raise Forbidden.new("Autenticazione mancante!")
            end
                    
            
        end

        __.json :method => :GET
        def rstapp
            Spider.restart!
            $out << { :esito => "ok" }.to_json
            done
        end

        #aggiunta di una anagrafica passando tramite post i dati
        __.json :method => :POST
        def aggiungi_anagrafica
            
            if @request.post?       
                begin
                    #recupero i dati dai parametri
                    nome = @request.params['nome']
                    cognome = @request.params['cognome']
                    sesso = @request.params['sesso']
                    codice_fiscale = @request.params['codice_fiscale']
                    data_nascita = @request.params['data_nascita']
                    comune_nascita = @request.params['comune_nascita']
                    if Spider.conf.get('portal.province_tabellate') == true
                        provincia_nascita_tab = @request.params['provincia_nascita']
                        provincia_residenza_tab = @request.params['provincia_residenza']
                    else
                        provincia_nascita = @request.params['provincia_nascita']
                        provincia_residenza = @request.params['provincia_residenza']
                    end
                    comune_residenza = @request.params['comune_residenza']
                    indirizzo_residenza = @request.params['indirizzo_residenza']
                    civico_residenza = @request.params['civico_residenza']
                    email = @request.params['email']
                    pec = @request.params['pec']
                    note = @request.params['note']
                    telefono = @request.params['telefono']
                    fax = @request.params['fax']
                    cellulare = @request.params['cellulare']
                    #dati ditta
                    ragione_sociale = @request.params['ragione_sociale']
                    partita_iva = @request.params['partita_iva']
                    indirizzo_azienda = @request.params['indirizzo_azienda']
                    civico_azienda = @request.params['civico_azienda']
                    comune_azienda = @request.params['comune_azienda']
                    provincia_azienda = @request.params['provincia_azienda']
                    telefono_azienda = @request.params['telefono_azienda']
                    fax_azienda = @request.params['fax_azienda']
                    email_azienda = @request.params['email_azienda']
                    pec_azienda = @request.params['pec_azienda']
                    codice_master = @request.params['codice_master']
                    lista_contesti_applicativi = @request.params['lista_contesti_applicativi']
                    #ID UTENTE mi serve per capire se fare un nuovo inserimento o un aggiornamento
                    id_utente_portale = @request.params['id']
                    #campi professionista                    
                    albo = @request.params['albo']
                    n_albo = @request.params['n_albo']
                    p_iva = @request.params['p_iva']
                    
                    crea_ditta = !ragione_sociale.blank? || !partita_iva.blank?
                    #campo usato per capire se sto confermando da civilia
                    conferma_civilia = false

                    if id_utente_portale.blank?
                        # => NUOVO INSERIMENTO 
                        operazione = 'new'
                        utente = Portal::Utente.new
                        utente_login = Portal::UtenteLogin.new
                        ditta = Portal::Ditta.new if crea_ditta
                    else
                        # => MODIFICA
                        utente = Portal::Utente.load(:id => id_utente_portale.to_i)
                        if utente.blank?
                            $out << { :esito => "ko",
                              :cod_errore => "anagrafica non trovata" }.to_json
                            #Portal::Utente.storage.commit
                            done
                        elsif crea_ditta
                            ditta = utente.ditta
                            ditta ||= Portal::Ditta.new
                        end
                        #valorizzo il conferma_civilia se il parametro vale t
                        conferma_civilia = (@request.params['conferma_civilia'] == 't')
                        operazione = 'edit'
                    end
                    #inserisco i dati

                    Portal::Utente.storage.start_transaction  
                        
                            utente.nome = nome
                            utente.cognome = cognome
                            utente.sesso = sesso
                            utente.data_nascita = Date.strptime(data_nascita,"%d/%m/%Y") if data_nascita
                            begin
                                utente.codice_fiscale = codice_fiscale
                            rescue Spider::Model::FormatError
                                Spider.logger.error "Errore di formato codice_fiscale"
                            end
                            utente.comune_nascita = comune_nascita
                            utente.provincia_nascita = provincia_nascita  
                            utente.comune_residenza = comune_residenza unless comune_residenza.blank?
                            utente.provincia_residenza = provincia_residenza unless provincia_residenza.blank?
                            utente.indirizzo_residenza = indirizzo_residenza unless indirizzo_residenza.blank?
                            utente.civico_residenza = civico_residenza unless civico_residenza.blank?
                            utente.email = email
                            utente.pec = pec
                            utente.note = note
                            utente.telefono = telefono
                            utente.fax = fax
                            utente.cellulare = cellulare

                            #campi professionista
                            utente.albo = albo
                            utente.n_albo = n_albo
                            utente.p_iva = p_iva
                            
                            utente.data_conferma = DateTime.now unless conferma_civilia.blank? 

                            #metto lo stato a confermato dell'utente
                            utente.stato = 'confermato'
                            
                            #SALVO IL CODICE MASTER COME ATTRIBUTO UTENTE
                            unless codice_master.blank?
                                #se l'utente non ha codice master creo un attributo nuovo
                                attributi_utente = utente.attributi_aggiuntivi
                                if attributi_utente.length == 0
                                    utente.attributi_aggiuntivi << Portal::Utente::AttributiAggiuntivi.new(:attributo_utente => 'codice_master', :valore => codice_master) 
                                else
                                    #cerco il codice master e lo aggiorno
                                    cm_trovato = false
                                    attributi_utente.each{ |attributo|
                                        if attributo.desc == "Codice Master"
                                            attributo[:valore] = codice_master.to_s
                                            cm_trovato = true
                                        end
                                    }
                                    #se non ho trovato il codice master lo inserisco
                                    unless cm_trovato
                                        utente.attributi_aggiuntivi << Portal::Utente::AttributiAggiuntivi.new(:attributo_utente => 'codice_master', :valore => codice_master)
                                    end
                                end 
                            end
                            
                            
                            #SALVO I SERVIZI CHE HANNO CONTESTO UGUALE A QUELLI PASSATI
                            unless lista_contesti_applicativi.blank?
                                servizi_con_contesto = Portal::Servizio.where{ |servizio_portale| (servizio_portale.contesto .not nil) } 
                                servizi_con_contesto.each{ |servizio|
                                    if !servizio.contesto.blank? && lista_contesti_applicativi.include?(servizio.contesto.id)
                                        utente.servizi_privati << Portal::Utente::ServiziPrivati.new(
                                            :servizio => servizio,
                                            :stato => 'attivo'
                                        )
                                    end
                                }
                            
                            end
    
                            if operazione == 'new'

                                #costruzione dell'username come nome.cognome + eventuale contatore
                                new_username = (utente.nome+"."+utente.cognome).gsub(" ","")
                                
                                username_presenti = Portal::UtenteLogin.where{ |user| (user.username .ilike new_username+"%") }
                                if username_presenti.length > 0
                                    max = 0
                                    username_presenti.each{ |username_presente|
                                        valore_cnt = (username_presente.username.gsub(new_username,"")).to_i
                                        max = valore_cnt if valore_cnt > max
                                    }
                                    max+=1
                                    #aggiungo un numero all'username
                                end
                                new_username = new_username+(max.to_s)

                                nuova_password = rand(36**8).to_s(36)
                                #utente_login = Portal::UtenteLogin.new
                                utente_login.password = nuova_password
                                utente_login.username = new_username

                                # salvo nella scene username e password per passarle al template di attivazione
                                # utente da civilia
                                @scene.username = utente_login.username
                                @scene.password = nuova_password

                                utente_login.save
                                utente.utente_login = utente_login
                            end

                            utente.save
                            if crea_ditta
                                #ditta = Portal::Ditta.new
                                ditta.ragione_sociale = ragione_sociale
                                ditta.partita_iva = partita_iva
                                ditta.indirizzo_azienda = "#{indirizzo_azienda} #{civico_azienda}"
                                #ditta.civico_azienda = civico_azienda  tolto perche va in errore civilia in quanto gli manca il civico dell'azienda
                                ditta.comune_azienda = comune_azienda
                                ditta.provincia_azienda = provincia_azienda
                                ditta.telefono_azienda = telefono_azienda
                                ditta.fax_azienda = fax_azienda
                                ditta.email_azienda = email_azienda
                                ditta.pec_azienda = pec_azienda
                                ditta.referente = utente
                                ditta.save
                            end
                            
                            #mando la mail di attivazione dell'utente
                            @scene.utente = utente
                            Portal::PortalController.email_attivazione_utente_da_civilia(@scene) if operazione == 'new'
                            #done

                            $out << {   :esito => "ok",
                                        :operazione => (operazione == 'new' ? "inserimento" : "aggiornamento"),
                                        :id_utente_portale => utente.id,
                                        :codice_master => codice_master
                                    }.to_json
                        
                        
                    Portal::Utente.storage.commit
                rescue Exception => exc
                    Portal::Utente.storage.rollback
                    Spider.logger.error "** Errore inserimento da civilia: #{exc.message}, #{exc.backtrace}"
                    $out << { :esito => "ko",
                          :cod_errore => "** Errore inserimento da civilia: #{exc.message}" }.to_json
                end
            else
                $out << { :esito => "ko",
                          :cod_errore => "effettuare chiamata tramite POST http" }.to_json
                done
            end


        end


        __.json :method => :GET
        def lista_anagrafiche_variate
            from = @request.params['from']
            now = DateTime.now.strftime("%Y-%m-%dT%H:%M:%S")
            unless from.blank?
                begin
                    data_from = Date.strptime(from,"%d/%m/%Y")
                    anagrafiche_variate_qs = Portal::Utente.where{ |utente| (utente.obj_modified >= from ) & ( utente.obj_modified <= now)}
                    anagrafiche_variate_json = []
                    anagrafiche_variate_qs.each{ |anagrafica|
                        unless anagrafica.data_conferma.blank?
                            data_conf_max = (anagrafica.data_conferma.to_time+(60)).to_datetime #aggiungo un minuto alla data conferma
                            #se l'ultima modifica compresa tra data_conferma e data_conferma + 1 minuto non scarico la variazione che sarebbe il passaggio attivo->confermato di civilia
                            next if anagrafica.obj_modified >= anagrafica.data_conferma && anagrafica.obj_modified <= data_conf_max
                        end
                        hash_anagrafica = {}
                        hash_anagrafica = anagrafica.cut( :id => 0, :codice_fiscale => 0, :obj_modified => 0, :ditta => { :partita_iva => 0} )
                        #porto la partita iva al livello delle altre info
                        hash_anagrafica[:partita_iva] = hash_anagrafica[:ditta][:partita_iva] unless hash_anagrafica[:ditta].blank?
                        hash_anagrafica.delete(:ditta)
                        #ciclo su tutti gli attributi aggiuntivi e cerco il codice master
                        anagrafica.attributi_aggiuntivi.each{ |attributo|
                            if attributo.attributo_utente.id == 'codice_master'
                                hash_anagrafica[:codice_master] = attributo.valore unless attributo.valore.blank?
                            end
                        }
                        #passo un parametro per sapere se in corso una modifica della mail o del cellulare
                        mod_email = anagrafica.modifica_contatto_pendente('email')
                        if !mod_email.blank? # && !mod_email.prima.blank? mando mod_email a true anche quando mi registro la prima volta, caso prima = null
                            hash_anagrafica['mod_email'] = true
                        end
                        mod_cellulare = anagrafica.modifica_contatto_pendente('cellulare')
                        if !mod_cellulare.blank? && !mod_cellulare.prima.blank?
                            hash_anagrafica['mod_cellulare'] = true
                        end
                        
                        anagrafiche_variate_json << hash_anagrafica.each{ |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) } 
                    }
                    hash_output = { 'esito' => "ok",
                                    'anagrafiche_variate' => anagrafiche_variate_json
                                }
                    $out << hash_output.to_json
                rescue Exception => exc
                    Spider.logger.error exc.message
                    Spider.logger.error exc
                    $out << { :esito => "ko",
                          :cod_errore => exc.message }.to_json
                    done
                end
                
            else
                $out << { :esito => "ko",
                          :cod_errore => "parametro from mancante" }.to_json
                done
            end
            

        end

        __.json :method => :GET
        def dettaglio_anagrafica
            id_utente = @request.params['id_utente']
            unless id_utente.blank?
                begin
                    anagrafica_utente = Portal::Utente.load(:id => id_utente.to_i)
                    #se ho trovato l'utente
                    unless anagrafica_utente.blank?
                        if Spider.conf.get('portal.province_tabellate') == true
                            hash_anagrafica = anagrafica_utente.cut( :id => 0, :nome => 0, :cognome => 0, :sesso => 0, :codice_fiscale => 0, :comune_nascita => 0, :provincia_nascita_tab => 0, 
                            :comune_residenza => 0, :provincia_residenza_tab => 0, :indirizzo_residenza => 0, :civico_residenza => 0, :email => 0, :pec => 0, :note => 0, :telefono => 0, :fax => 0, :cellulare => 0,
                            :albo => 0, :n_albo => 0, :p_iva => 0,  
                            :ditta => { :partita_iva => 0, :ragione_sociale => 0, :indirizzo_azienda => 0, :comune_azienda => 0, :provincia_azienda => 0, 
                            :telefono_azienda => 0, :fax_azienda => 0, :email_azienda => 0, :pec_azienda => 0} )
                        else
                            hash_anagrafica = anagrafica_utente.cut( :id => 0, :nome => 0, :cognome => 0, :sesso => 0, :codice_fiscale => 0, :comune_nascita => 0, :provincia_nascita => 0, 
                            :comune_residenza => 0, :provincia_residenza => 0, :indirizzo_residenza => 0, :civico_residenza => 0, :email => 0, :pec => 0, :note => 0, :telefono => 0, :fax => 0, :cellulare => 0,
                            :albo => 0, :n_albo => 0, :p_iva => 0,  
                            :ditta => { :partita_iva => 0, :ragione_sociale => 0, :indirizzo_azienda => 0, :comune_azienda => 0, :provincia_azienda => 0, 
                            :telefono_azienda => 0, :fax_azienda => 0, :email_azienda => 0, :pec_azienda => 0} )
                        end

                        #ciclo su tutti gli attributi aggiuntivi e cerco il codice master
                        anagrafica_utente.attributi_aggiuntivi.each{ |attributo|
                            if attributo.attributo_utente.id == 'codice_master'
                                hash_anagrafica[:codice_master] = attributo.valore unless attributo.valore.blank?
                            end
                        }
                        #aggiungo i servizi privati
                        array_servizi = []
                        anagrafica_utente.servizi_privati.each{ |servizio|
                            array_servizi << servizio[:servizio].nome if servizio[:stato].id == 'attivo'
                        }
                        hash_anagrafica[:lista_servizi_attivi] = array_servizi
                        hash_output = { 'esito' => "ok",
                                        'dettaglio_anagrafica' => hash_anagrafica.each{ |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                                }
                        $out << hash_output.to_json

                    else
                        #se non presente un utente con id passato -> messaggio d'errore
                        $out << { :esito => "ko",
                                  :cod_errore => "utente con id #{id_utente} non presente" }.to_json
                        done
                    end
                        

                rescue Exception => exc
                    Spider.logger.error exc.message
                    Spider.logger.error exc
                    $out << { :esito => "ko",
                          :cod_errore => exc.message }.to_json
                    done
                end
            else
                $out << { :esito => "ko",
                          :cod_errore => "parametro id_utente mancante" }.to_json
                done
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
                data_valida = datetime_inviata < (DateTime.now+(1.0/(24*6)) && datetime_inviata > DateTime.now-(1.0/(24*6)))
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


        #Devo fare la ricerca su portal e civilia_open persona. Poi restituisco il merge dei due query_set
        # Se utente cercato ha il record solo sul portale -> id del tipo P_xxx
        # Se utente cercato ha il record solo sul civ_open -> id del tipo D_xxx
        # Se utente cercato ha il record sulle due tabelle -> id del tipo D_xxx (Sul dettaglio per quelli residenti devo cmq vedere le info di contatto del portal)
        #ricerca_utente: http get con parametri cognome, nome e data_nascita. Ci deve essere almeno un parametro, vengono concatenati in AND nel filtro.

        #metodo interno
        def self._ricerca_utente(params)
            cognome = params['cognome']
            nome = params['nome']
            data_nascita = params['data_nascita']
            codice_fiscale = params['codice_fiscale']
            codice_fiscale_maiuscolo = codice_fiscale.upcase unless codice_fiscale.blank?
            #se non sono tutti e tre vuoti
            unless cognome.blank? && nome.blank? && data_nascita.blank? && codice_fiscale.blank?
                #controllo formato data_nascita
                begin
                    data_nascita_parsed = Date::strptime(data_nascita, "%d/%m/%Y") unless data_nascita.blank?
                rescue Exception => exc
                    self.log_errore(exc)
                    return { :esito => "ko",
                             :cod_errore => '03',
                             :errore => "Problemi conversione data_nascita" }
                end 
                hash_utenti = {}
                #Effettuo query
                begin
                    #creo l'hash dai dati del civilia open
                    utenti_civilia_qs = CiviliaOpen::Persona.all
                    utenti_civilia_qs.query.condition = Spider::Model::Condition.new
                    utenti_civilia_qs.query.condition = utenti_civilia_qs.query.condition.and{ |ut_civ| (ut_civ.segreto == false) | (ut_civ.segreto == nil) } #non deve essere un utente segreto
                    utenti_civilia_qs.query.condition = utenti_civilia_qs.query.condition.and{ |ut_civ| (ut_civ.cognome .ilike cognome) } unless cognome.blank?
                    utenti_civilia_qs.query.condition = utenti_civilia_qs.query.condition.and{ |ut_civ| (ut_civ.nome .ilike nome) } unless nome.blank?
                    utenti_civilia_qs.query.condition = utenti_civilia_qs.query.condition.and{ |ut_civ| (ut_civ.data_nascita == data_nascita) }  unless data_nascita.blank? 
                    utenti_civilia_qs.query.condition = utenti_civilia_qs.query.condition.and{ |ut_civ| (ut_civ.codice_fiscale == codice_fiscale) | (ut_civ.codice_fiscale == codice_fiscale_maiuscolo) } unless codice_fiscale.blank?
                    utenti_civilia_qs.query.condition = utenti_civilia_qs.query.condition.and{ |ut_civ| (ut_civ.flag_residente == true) } 
                    if utenti_civilia_qs.length > 0 #ci sono residenti con queste info
                        utenti_civilia_qs.each{|ut|
                            hash_utenti[ut.codice_fiscale] = { #indicizzo per cf
                                                                'id_utente' => "D_#{ut.master}", 
                                                                'nome_cognome' => "#{ut.nome} #{ut.cognome}",
                                                                'data_nascita' => "#{ut.data_nascita.lformat(:short)}",
                                                                'codice_fiscale' => "#{ut.codice_fiscale}"
                                                            }.convert_object 

                        }
                    end  
                    #aggiorno l'hash coi dati del portale
                    utenti_portale_qs = Portal::Utente.all
                    utenti_portale_qs.query.condition = Spider::Model::Condition.new
                    utenti_portale_qs.query.condition = utenti_portale_qs.query.condition.and{ |ut_port| (ut_port.cognome == cognome) } unless cognome.blank?
                    utenti_portale_qs.query.condition = utenti_portale_qs.query.condition.and{ |ut_port| (ut_port.nome == nome) } unless nome.blank?
                    utenti_portale_qs.query.condition = utenti_portale_qs.query.condition.and{ |ut_port| (ut_port.data_nascita == data_nascita) }  unless data_nascita.blank?
                    utenti_portale_qs.query.condition = utenti_portale_qs.query.condition.and{ |ut_port| (ut_port.codice_fiscale == codice_fiscale) | (ut_port.codice_fiscale == codice_fiscale_maiuscolo) } unless codice_fiscale.blank?
                    if utenti_portale_qs.length > 0
                        utenti_portale_qs.each{|ut|
                            if hash_utenti[ut.codice_fiscale].blank? #se non ho dati in civilia ritorno i dati del portale
                                hash_utenti[ut.codice_fiscale] = {  'id_utente' => "P_#{ut.id}", 
                                                                    'nome_cognome' => "#{ut.nome} #{ut.cognome}",
                                                                    'data_nascita' => "#{ut.data_nascita.lformat(:short)}",
                                                                    'codice_fiscale' => "#{ut.codice_fiscale}"
                                                                }.convert_object
                            else
                                #ho i dati anche in civilia, lascio quelli in civilia
                            end
                                                    }
                    end
                    array_utenti = []

                    hash_utenti.each_value{ |val| array_utenti << val }
                    

                    return { :esito => "ok", :array_utenti => array_utenti }
                    
                rescue Exception => exc_appl
                    self.log_errore(exc_appl)
                    return { :esito => "ko",
                             :cod_errore => '01',
                             :errore => "Problema applicativo: #{exc_appl.message}" }
                end
            else
                return { :esito => "ko",
                         :cod_errore => '02',
                         :errore => "Parametri mancanti (cognome,nome,data_nascita)" }
            end

        end

        __.json :method => :GET
        def ricerca_utente
            
            $out << self.class._ricerca_utente(@request.params).to_json

        end


        # Se arriva un id_utente del tipo D_xxx controllo cmq per cf che non ci sia anche in Portal::Utente
        # Se presente uso quei dati di contatto
        # Se arriva id_utente D_xxx ed è residente aggiungo i campi di residenza
        # Se arriva id_utente P_xxx ed è una ditta aggiungo i campi ditta

        # Ritorna i dati
        #     Nome e Cognome
        #     Codice Fiscale
        #     Data nascita
        #     Comune di Nascita
        #     Comune di Residenza
        #     Via di residenza
        #     Civico di residenza
        #     Cap di residenza
        #     Sigla provincia di residenza
        #     Cellulare (se presente a portale, sempre questo)
        #     Email  (se presente a portale, sempre questo)
        #     PEC  (se presente a portale, sempre questo)
        #     Telefono  (se presente a portale, sempre questo)
        #     Fax  (se presente a portale, sempre questo)
        # Se residente (solo demografici)
        #     Numero famiglia
        #     Id utente, nome + cognome, codice fiscale per ciascun componente, ruolo
        # Se Azienda (solo portale)
        #     Nome azienda
        #     Email azienda
        #     PEC azienda
        #     Comune azienda
        #     Cap azienda
        #     Via azienda
        #     Civico azienda
        #     Partita IVA

        #metodo interno
        def self._dettaglio_utente(params)
            id_utente = params['id_utente']
            unless id_utente.blank?
                dati_utente = {}
                if id_utente[0] == "D" #utente trovato in civilia, devo cercare anche in portal per cf
                    id_master = id_utente[2..-1]
                    utente = CiviliaOpen::Residente.load(:master => id_master)
                    if utente.blank?
                        return { :esito => "ko", 
                                 :cod_errore => '04',
                                 :errore => "Utente con codice master #{id_master} non trovato." }
                    end
                    dati_utente['nome'] = utente.nome
                    dati_utente['cognome'] = utente.cognome
                    dati_utente['sesso'] = utente.sesso.id unless utente.sesso.blank?
                    dati_utente['codice_fiscale'] = utente.codice_fiscale
                    dati_utente['data_nascita'] = utente.data_nascita.lformat(:short) unless utente.data_nascita.blank?
                    dati_utente['comune_nascita'] = utente.comune_nascita.nome
                    dati_utente['comune_residenza'] = utente.indirizzo_residenza.comune.nome
                    dati_utente['via_residenza'] = utente.indirizzo_residenza.via.descrizione
                    dati_utente['civico_residenza'] = "#{utente.indirizzo_residenza.numero} #{utente.indirizzo_residenza.bis} #{utente.indirizzo_residenza.scala} #{utente.indirizzo_residenza.piano} #{utente.indirizzo_residenza.interno}"
                    dati_utente['cap_residenza'] = utente.indirizzo_residenza.comune.cap
                    dati_utente['prov_residenza'] = utente.indirizzo_residenza.comune.provincia
                    #cerco i dati su portal
                    cf = utente.codice_fiscale
                    utente_portale = Portal::Utente.load(:codice_fiscale => cf )
                    if utente_portale.blank?
                        dati_utente['cellulare'] = ''
                        dati_utente['email'] = ''
                        dati_utente['pec'] = ''
                        dati_utente['telefono'] = ''
                        dati_utente['fax'] = ''
                    else
                        #dati di contatto
                        dati_utente['cellulare'] = utente_portale.cellulare
                        dati_utente['email'] = utente_portale.email
                        dati_utente['pec'] = utente_portale.pec
                        dati_utente['telefono'] = utente_portale.telefono
                        dati_utente['fax'] = utente_portale.fax
                        #dati aziendali, se presenti li mostro
                        unless utente_portale.ditta.blank?
                            dati_utente['nome_azienda'] =  utente_portale.ditta.ragione_sociale
                            dati_utente['email_azienda'] =  utente_portale.ditta.email_azienda
                            dati_utente['pec_azienda'] =  utente_portale.ditta.pec_azienda
                            dati_utente['comune_azienda'] =  utente_portale.ditta.comune_azienda
                            dati_utente['cap_azienda'] =  utente_portale.ditta.cap_azienda
                            dati_utente['via_azienda'] =  utente_portale.ditta.indirizzo_azienda
                            dati_utente['civico_azienda'] =  utente_portale.ditta.civico_azienda
                            dati_utente['partita_iva_azienda'] =  utente_portale.ditta.partita_iva
                        end
                    end
                    # Se residente (solo demografici)
                    #     Numero famiglia
                    #     Id utente, nome + cognome, codice fiscale per ciascun componente, ruolo
                    if utente.flag_residente && !utente.codice_famiglia.blank?
                        dati_utente['num_famiglia'] = utente.codice_famiglia
                        dati_famiglia = []
                        componente_famiglia = CiviliaOpen::Residente.where(:codice_famiglia => utente.codice_famiglia, :flag_residente => true )
                        componente_famiglia.each{ |famigliare|
                            dati_famiglia << {  'id_utente' => "D_#{famigliare.master}",
                                                'nome_cognome' => "#{famigliare.nome} #{famigliare.cognome}",
                                                'codice_fiscale' => famigliare.codice_fiscale,
                                                'ruolo' => famigliare.sesso == 'M' ? famigliare.relazione_parentela.descrizione_m :  famigliare.relazione_parentela.descrizione_f
                                            } if famigliare.master != utente.master
                        }
                        dati_utente['dati_famiglia'] = dati_famiglia       
                    end

                else # caso P => portal
                    id_portale = id_utente[2..-1]
                    utente_portale = Portal::Utente.load(:id => id_portale )
                    if utente_portale.blank?
                        return { :esito => "ko", 
                                 :cod_errore => '04',
                                 :errore => "Utente con id #{id_portale} non trovato." }
                    end
                    dati_utente['nome'] = utente_portale.nome
                    dati_utente['cognome'] = utente_portale.cognome
                    dati_utente['sesso'] = utente_portale.sesso.id unless utente_portale.sesso.blank?
                    dati_utente['codice_fiscale'] = utente_portale.codice_fiscale
                    dati_utente['data_nascita'] = utente_portale.data_nascita.lformat(:short) unless utente_portale.data_nascita.blank?
                    dati_utente['comune_nascita'] = utente_portale.comune_nascita
                    dati_utente['comune_residenza'] = utente_portale.comune_residenza
                    dati_utente['via_residenza'] = utente_portale.indirizzo_residenza
                    dati_utente['civico_residenza'] = utente_portale.civico_residenza
                    dati_utente['cap_residenza'] = utente_portale.cap_residenza
                    dati_utente['prov_residenza'] = utente_portale.provincia_residenza
                    #dati di contatto
                    dati_utente['cellulare'] = utente_portale.cellulare
                    dati_utente['email'] = utente_portale.email
                    dati_utente['pec'] = utente_portale.pec
                    dati_utente['telefono'] = utente_portale.telefono
                    dati_utente['fax'] = utente_portale.fax
                    #dati aziendali, se presenti li mostro
                    unless utente_portale.ditta.blank?
                        dati_utente['nome_azienda'] =  utente_portale.ditta.ragione_sociale
                        dati_utente['email_azienda'] =  utente_portale.ditta.email_azienda
                        dati_utente['pec_azienda'] =  utente_portale.ditta.pec_azienda
                        dati_utente['comune_azienda'] =  utente_portale.ditta.comune_azienda
                        dati_utente['cap_azienda'] =  utente_portale.ditta.cap_azienda
                        dati_utente['via_azienda'] =  utente_portale.ditta.indirizzo_azienda
                        dati_utente['civico_azienda'] =  utente_portale.ditta.civico_azienda
                        dati_utente['partita_iva_azienda'] =  utente_portale.ditta.partita_iva
                    end 
                end
                return { :esito => "ok", :dati_utente => dati_utente }
                done

            else
                return { :esito => "ko",
                         :cod_errore => '02',
                         :errore => "Parametro id_utente mancante" }
            end
        end

        __.json :method => :GET
        def dettaglio_utente
            $out << self.class._dettaglio_utente(@request.params).to_json
        end

        #metodo interno
        def self._elenco_utenti_variati(params)
            str_from_data_ora = params['from_data_ora']
            now = DateTime.now.strftime("%Y-%m-%dT%H:%M:%S")
            unless str_from_data_ora.blank?
                begin
                    from_data_ora = Date.strptime(str_from_data_ora,"%d%m%Y%H%M%S")
                    anagrafiche_variate_qs = Portal::Utente.where{ |utente| (utente.obj_modified >= from_data_ora ) & ( utente.obj_modified <= now)}
                    array_utenti = []
                    anagrafiche_variate_qs.each{ |anagrafica|
                        unless anagrafica.data_conferma.blank?
                            data_conf_max = (anagrafica.data_conferma.to_time+(60)).to_datetime #aggiungo un minuto alla data conferma
                            #se l'ultima modifica compresa tra data_conferma e data_conferma + 1 minuto non scarico la variazione che sarebbe il passaggio attivo->confermato di civilia
                            next if anagrafica.obj_modified >= anagrafica.data_conferma && anagrafica.obj_modified <= data_conf_max
                        end
                        hash_anagrafica = {}
                        #cerco su civ_open se presente l'utente, se si ritorno un id del tipo D_ così il dettaglio viene ritornato a partire da civ_open
                        utente_civilia_qs = CiviliaOpen::Persona.where{ |ut_civ| (ut_civ.segreto == false) | (ut_civ.segreto == nil) & (ut_civ.codice_fiscale == anagrafica.codice_fiscale) }
                        if utente_civilia_qs.length > 0
                            hash_anagrafica['id_utente'] = "D_#{utente_civilia_qs.first.master}"
                        else
                            hash_anagrafica['id_utente'] = "P_#{anagrafica.id}"
                        end
                        hash_anagrafica['nome_cognome'] = "#{anagrafica.nome} #{anagrafica.cognome}"
                        hash_anagrafica['data_nascita'] = "#{anagrafica.data_nascita.lformat(:short)}"
                        hash_anagrafica['codice_fiscale'] = "#{anagrafica.codice_fiscale}"
                        #salvo nell'array
                        array_utenti << hash_anagrafica.convert_object 
                    }
                    hash_output = { 'esito' => "ok",
                                    'utenti_variati' => array_utenti
                                }
                    return hash_output
                rescue Exception => exc
                    Spider.logger.error exc.message
                    Spider.logger.error exc
                    return { :esito => "ko", 
                             :cod_errore => '01',
                             :errore => exc.message }
                end 
            else
                return { :esito => "ko",
                         :cod_errore => '02', 
                         :errore => "Parametro from_data_ora mancante (formato gg/mm/aaaa hh:mm:ss)" }
            end
        end


        __.json :method => :GET
        def elenco_utenti_variati
            $out << self.class._elenco_utenti_variati(@request.params).to_json 
        end

        #da id_utente
        #modifica dei dati 
        # Email
        # PEC
        # Cellulare
        # Solo se non residente (sempre e solo del portale)
        # Cap
        # Comune
        # Provincia
        # Via 
        # Civico

        #metodo interno
        def self._modifica_dati_contatto(params)
            id_utente = params['id_utente']
            unless id_utente.blank?
                #modificare sul portale
                email = params['email']
                pec = params['pec']
                cellulare =params['cellulare']
                #da modificare solo se non residente
                cap = params['cap']
                comune = params['comune']
                provincia = params['provincia']
                via = params['via']
                civico = params['civico']
                
                
                if id_utente[0] == "D" #utente trovato in civilia, devo cercare anche in portal per cf
                    id_master = id_utente[2..-1]
                    utente = CiviliaOpen::Residente.load(:master => id_master)
                    if utente.blank?
                        return { :esito => "ko", 
                                 :cod_errore => '04',
                                 :errore => "Utente con codice master #{id_master} non trovato." }
                    end
                    #cerco utente portale con stesso cf
                    utente_portale = Portal::Utente.load(:codice_fiscale => utente.codice_fiscale)
                    unless utente_portale.blank?
                        utente_portale.email = email unless email.blank?
                        utente_portale.pec = pec unless pec.blank?
                        utente_portale.cellulare = cellulare unless cellulare.blank? 
                        if utente.flag_residente.blank?
                            #se non residente posso modificare anche questi
                            utente_portale.comune_residenza = comune unless comune.blank?
                            utente_portale.provincia_residenza = provincia unless provincia.blank?
                            utente_portale.cap_residenza = cap unless cap.blank?
                            utente_portale.indirizzo_residenza = via unless via.blank?
                            utente_portale.civico_residenza = civico unless civico.blank?
                        end
                        utente_portale.save
                        return { :esito => "ok" }
                    end
                    
                else
                    #qui modifico tutti i campi, considero come non residente se ho i dati solo qui
                    id_portale = id_utente[2..-1]
                    utente_portale = Portal::Utente.load(:id => id_portale)
                    if utente_portale.blank?
                        return { :esito => "ko", 
                                 :cod_errore => '04',
                                 :errore => "Utente con id portale #{id_portale} non trovato." }
                    end
                    utente_portale.email = email unless email.blank?
                    utente_portale.pec = pec unless pec.blank?
                    utente_portale.cellulare = cellulare unless cellulare.blank? 
                    utente_portale.comune_residenza = comune unless comune.blank?
                    utente_portale.provincia_residenza = provincia unless provincia.blank?
                    utente_portale.cap_residenza = cap unless cap.blank?
                    utente_portale.indirizzo_residenza = via unless via.blank?
                    utente_portale.civico_residenza = civico unless civico.blank?
                    utente_portale.save
                    return { :esito => "ok" }

                end
            else
                return { :esito => "ko", 
                         :cod_errore => '02',
                         :errore => "Parametro id_utente mancante." }
            end
        end

        __.json :method => :POST
        def modifica_dati_contatto

            
            if @request.post?
                $out << self.class._modifica_dati_contatto(@request.params).to_json
            else
                $out << { :esito => "ko",
                          :cod_errore => '05',
                          :errore => "Effettuare chiamata in POST." }.to_json
                done
            end
        end





        #DA FARE: DEFINIRE LE ECCEZIONI
        ERRORI = {
            '01' => "Errore Applicativo.",
            '02' => "Parametri mancanti.",
            '03' => "Problemi di conversione date.",
            '04' => "Errore valore di ricerca non corretto o non valido.",
            '05' => "Chiamata HTTP errata.",
        }

        def get_dbname
            con = Spider::Model::BaseModel.get_storage
            array_valori_db = con.parse_url(con.url)
            #[@host, @user, @pass, @db_name, @port, @sock]
            db_name = array_valori_db[3]
        end



        def self.log_errore(eccezione)
            messaggio_log = "Errore API Portal: #{eccezione.message}"
            eccezione.backtrace.each{|riga_errore| 
                messaggio_log += "\n\r#{riga_errore}" 
            } 
            Spider.logger.error messaggio_log
        end


        def try_rescue(exc)
            $out << {'error' => exc.message}.to_json
            raise 
        end




    end
end

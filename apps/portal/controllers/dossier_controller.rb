# -*- encoding : utf-8 -*-
module Portal
    
    class DossierController < Spider::PageController
        include HTTPMixin, StaticContent
        #include Spider::SAML2Mixin
        include Spider::Messenger::MessengerHelper rescue NameError

        include AutenticazionePortale

        #layout '/portal/portal'
        layout 'portal'

        __.html :template => 'dossier/index'
        def index
            autenticazione_necessaria
        	utente = @request.utente_portale
        	@scene.utente = utente
        	@scene.data_creazione = utente.obj_created.strftime("%d/%m/%Y %H:%M:%S")
        	#da sostituire
        	@scene.ultimo_accesso = (DateTime.now-1).strftime("%d/%m/%Y %H:%M:%S")
        	
            servizi_tracciati = recupera_servizi_configurati
            @scene.servizi_tracciati = servizi_tracciati

        end



        __.html :template => 'dossier/vedi_dati'
        def vedi_dati
            autenticazione_necessaria
            utente = @request.utente_portale
            id_servizio = @request.params['id_srv']
            #ripasso l'id del servizio alla scene
            servizi_tracciati = recupera_servizi_configurati
            servizio_selezionato = nil
            servizi_tracciati.each{ |serv|
                servizio_selezionato = serv if serv['id'] == id_servizio
            }
            unless servizio_selezionato.blank?
                @scene.servizio_selezionato = servizio_selezionato
            else
                #se non ho selezionato un servizio corretto mostro la pagina con l'errore
                return nil
            end

            case servizio_selezionato['id']
                when "pagamenti"
                    tabella = Pagamenti::Traccia
                when "demografici"
                    tabella = Demografici::Traccia
                when "muse_iscrizione"
                    tabella =  MuSe::Traccia
                else
                    puts "Tabella non valida!"
            end

        	righe_tracce = nil
            
            @request.params.delete("id_srv")
            if @request.get? && @request.params.blank? 
                @request.session['parametri_ricerca'] = nil
                @scene.dati = {
                            'data_da' => "",
                            'data_a' => "",
                            'id_transazione_app' => "",
                            'tipologia_richiesta' => ""
                        }
            end


            #se ho filtrato la tabella o ho cliccato su una pagina dell'elenco
            if (@request.post? && @request.params['submit'] == 'cerca') || !@request.params['_w'].blank?
                    @scene.filtro_dati = true
                    
                    @request.session['parametri_ricerca'] = @request.params
                    @scene.dati = {
                        'data_da' => @request.session['parametri_ricerca']['_w']['data_da'],
                        'data_a' => @request.session['parametri_ricerca']['_w']['data_a'],
                        'id_transazione_app' => @request.session['parametri_ricerca']['id_transazione_app'],
                        'tipologia_richiesta' => @request.session['parametri_ricerca']['tipologia_richiesta']
                    }


                    righe_tracce = filtra_tabella(tabella, utente, 6, @request.session['parametri_ricerca'])
            end

            #se ho cliccato sull'esportazione delle abelle
            if @request.post? && @request.params['submit'] == 'esporta_tabella'
                
                #reinizializzo la scene per evitare l'errore sulle date quando si esportano i dati
                @scene.dati = {
                    'data_da' => "",
                    'data_a' => "",
                    'id_transazione_app' => "",
                    'tipologia_richiesta' => ""
                }
                #se ho fatto una ricerca esporto il query_set
                unless @request.session['parametri_ricerca'].blank?
                    nome_tabella = filtra_tabella(tabella, utente, 6, @request.session['parametri_ricerca'])
                else
                    #altrimenti esporto la tabella in base al servizio selezionato
                    nome_tabella = tabella.to_s
                end
                
                #setto la variabile per capire se devo mettere le stringhe tra apici
                stringhe_con_apici = !@request.params['testo_apici'].blank?
                #setto la variabile per capire se devo mettere le label dei campi nella prima riga
                label_prima_riga = !@request.params['intestazione_campi'].blank?
                #setto il separatore
                separatore = "," if @request.params['separatore'] == "virgola"
                separatore = ";" if @request.params['separatore'] == "punto_virgola"

                tabella_to_csv(nome_tabella, stringhe_con_apici, label_prima_riga, separatore, ['parametri', 'pagina'])
                done #evita di includere html della pagina se usato use Rack::Deflater
            end
            


            @scene.righe = righe_tracce

        end

        __.html :template => 'dossier/dettaglio_transazione'
        def dettaglio_transazione
            id_srv = @request.params['id_srv']
            id_transazione = @request.params['id_t']
            dati_transazione = Pagamenti::Pagamento.all.first
            @scene.dati_transazione = dati_transazione
            @scene.id_srv = id_srv
        end


        protected

        def recupera_servizi_configurati
            servizi_da_scegliere = Spider.conf.get('portal.servizi_tracciati_dossier_cittadini')
            servizi_db = Portal::Servizio.all.to_indexed_hash(:id)
            servizi_tracciati =[]
            unless servizi_da_scegliere.blank? || servizi_db.blank?
                servizi_da_scegliere.each{ |servizio|
                    if servizi_db.include?(servizio)
                        servizi_tracciati << {'nome' => servizi_db[servizio].nome, 'id' => servizi_db[servizio].id }
                    end
                }
            end
            servizi_tracciati
        end

        #Funzione valida per tutti i filtri sulle tabelle, aggiungere per ogni tabella le varie condizioni particolari

        def filtra_tabella(tabella, utente, max_mesi, parametri_ricerca)
            righe = tabella.all.order_by(:obj_created,:desc)
            #calcolo la data di n mesi fa
            datatime_mesidaoggi = DateTime.now << max_mesi.to_i
            righe.query.condition = Spider::Model::Condition.new
            #filtro per utente passato
            righe.query.condition = righe.query.condition.and{ |riga| (riga.utente == utente) }
            
            if !defined?(Demografici).blank? && tabella == Demografici::Traccia
                righe.query.condition = righe.query.condition.and{ |riga| (riga.tipologia_richiesta .ilike "%#{parametri_ricerca['tipologia_richiesta'].strip}%" ) } unless parametri_ricerca['tipologia_richiesta'].blank?
                righe.query.condition = righe.query.condition.and{ |riga| (riga.id_transazione_app == parametri_ricerca['id_transazione_app'].strip ) } unless parametri_ricerca['id_transazione_app'].blank?
                

            elsif !defined?(Pagamenti).blank? && tabella == Pagamenti::Traccia    
                righe.query.condition = righe.query.condition.and{ |riga| (riga.tipologia_richiesta .ilike "%#{parametri_ricerca['tipologia_richiesta'].strip}%" ) } unless parametri_ricerca['tipologia_richiesta'].blank?
                righe.query.condition = righe.query.condition.and{ |riga| (riga.id_transazione_app == parametri_ricerca['id_transazione_app'].strip ) } unless parametri_ricerca['id_transazione_app'].blank?

            elsif !defined?(MuSe).blank? && tabella == MuSe::Traccia    
                righe.query.condition = righe.query.condition.and{ |riga| (riga.tipologia_richiesta .ilike "%#{parametri_ricerca['tipologia_richiesta'].strip}%" ) } unless parametri_ricerca['tipologia_richiesta'].blank?
                righe.query.condition = righe.query.condition.and{ |riga| (riga.id_transazione_app == parametri_ricerca['id_transazione_app'].strip ) } unless parametri_ricerca['id_transazione_app'].blank?

            end
                    
            #filtro sulle date
            if !parametri_ricerca['_w']['data_da'].blank?
                data_fine = DateTime.strptime(parametri_ricerca['_w']['data_da'], '%d/%m/%Y')
                #-1 per comprendere anche l'ultimo giorno, sto usando datetime ed esclude l'estremo perchè mette l'ora 00:00
                data_fine = data_fine
                if data_fine > datatime_mesidaoggi
                    righe.query.condition = righe.query.condition.and{ |riga| (riga.obj_created > data_fine) }
                else
                    righe.query.condition = righe.query.condition.and{ |riga| (riga.obj_created > datatime_mesidaoggi) }
                end
            else
                righe.query.condition = righe.query.condition.and{ |riga| (riga.obj_created > datatime_mesidaoggi) }
            end

            if !parametri_ricerca['_w']['data_a'].blank?
                data_inizio = DateTime.strptime(parametri_ricerca['_w']['data_a'], '%d/%m/%Y')
                #+1 per comprendere anche l'ultimo giorno, sto usando datetime ed esclude l'estremo perchè mette l'ora 00:00
                data_inizio = data_inizio + 1
                righe.query.condition = righe.query.condition.and{ |riga| (riga.obj_created < data_inizio) }
            end

            righe            

        end


        def tabella_to_csv(nome_tabella, stringhe_con_apici, label_prima_riga, separatore, campi_esclusi, campi_collegati=nil)
            #nome_tabella è una stringa del tipo 'Portal::Utente' o un query_set
            if nome_tabella.is_a?(Spider::Model::QuerySet)
                #se è un query_set ricavo il modello per conoscere i campi e i dati sono dati dal qs stesso
                klass = nome_tabella.model
                dati_tabella = nome_tabella
            else
                #creo un oggetto in base alla stringa della classe passata
                
                nome_tab = nome_tabella.split("::").last
                array_mod = nome_tabella.split("::")
                array_mod.delete_at(array_mod.length-1)
                classe_modulo = nil
                array_mod.each{ |modulo|
                    unless classe_modulo.blank?
                        classe_modulo = classe_modulo.const_get(modulo)
                    else
                        classe_modulo = Object.const_get(modulo)
                    end
                }
                klass =  classe_modulo.const_get(nome_tab)
                #carico tutti i dati
                dati_tabella = klass.all
            end
            save_dir = Spider.paths[:data]+'/uploaded_files/moduli/esportazione_tabelle/'
            FileUtils.mkdir_p(save_dir) unless File.directory?(save_dir)            
            options = separatore if RUBY_DESCRIPTION.include?("ruby 1.8.7")
            options = { :col_sep => separatore} if RUBY_DESCRIPTION.include?("ruby 1.9.3")
            CSV.open(save_dir+"csv_#{nome_tab}.csv", 'w', options) do |row|
                array_intestazioni_riga = []
                array_valori_riga = []
                klass.elements.each_value{ |val|
                    array_intestazioni_riga << val.name.to_s.capitalize.gsub("_"," ") if ( (!val.model? || (val.model? && val.type.to_s.include?(val.definer_model.to_s)) ) && !campi_esclusi.include?(val.name.to_s) )
                }
                array_intestazioni_riga = array_intestazioni_riga.sort
                #aggiungo le label dei campi collegati alle intestazioni
                unless campi_collegati.blank?
                    campi_collegati.each_key{ |chiave|
                        array_intestazioni_riga << chiave
                    }  
                end
                row << array_intestazioni_riga if label_prima_riga #se vogliono le intestazioni
                #scrivo tutti i dati della tabella se ho passato il nome tabella
                dati_tabella.each{ |riga_tabella|
                    #reinizializzo l'array dei valori
                    array_valori_riga = []
                    array_intestazioni_riga.each{ |label_campo|
                        unless campi_collegati.blank? || campi_collegati[label_campo].blank?
                            chiavi_campi_collegati = (campi_collegati[label_campo]).split(".")
                            dato_collegato_liv_0 = riga_tabella.send(chiavi_campi_collegati[0])
                            if dato_collegato_liv_0.kind_of?(Spider::Model::QuerySet)
                                # se ho un query set con un elemento entro in quello, caso N a N in cui vado nella tabella di raccordo 
                                # e poi passo alla tabella collegata e cerco l'elemento  
                                if dato_collegato_liv_0.length == 1
                                    chiavi_campi_collegati.delete(chiavi_campi_collegati[0]) #tolgo l'elemento già usato
                                    risultato = dato_collegato_liv_0[0]
                                    chiavi_campi_collegati.each{ |chiave|
                                        risultato = risultato.send(chiave)
                                    }
                                    array_valori_riga << campo_per_csv(risultato, stringhe_con_apici)
                                else
                                    #se ho caricato un query set di oggetti
                                    campo_txt_per_qs = ""
                                    dato_collegato_liv_0.each{ |modello_collegato|
                                        campo_txt_per_qs += modello_collegato.send(chiavi_campi_collegati[1]).to_s+", "
                                    }
                                    array_valori_riga << campo_per_csv(campo_txt_per_qs.gsub(/,\s$/,""), stringhe_con_apici)
                                end
                            else
                                #sono già sul modello, chiamo il metodo per recuperare il dato 
                                unless dato_collegato_liv_0.blank?
                                    risultato = dato_collegato_liv_0.send(chiavi_campi_collegati[1])
                                    array_valori_riga << campo_per_csv(risultato, stringhe_con_apici)
                                else
                                    array_valori_riga << nil
                                end
                            end
                        end
                    if label_campo.is_a?(String) && ( (!campi_collegati.blank? && !campi_collegati.keys.include?(label_campo)) || campi_collegati.blank?)
                        risultato = riga_tabella[label_campo.downcase.gsub(" ","_").to_sym]
                        array_valori_riga << campo_per_csv(risultato, stringhe_con_apici) #if label_campo.is_a?(String) && (!campi_collegati.blank? && !campi_collegati.keys.include?(label_campo))
                    end        
                    }

                    row << array_valori_riga
                } 
            end
            #faccio scaricare il file csv
            nomefile= klass.to_s.gsub("::","_")+".csv"
            csvfile= save_dir+"csv_#{nome_tab}.csv"
            @response.headers['Content-disposition'] = "attachment; filename=#{nomefile.gsub(" ","_")}"
            output_static(csvfile)
        end

        def campo_per_csv(valore, stringhe_con_apici)
            return valore if valore.nil?
            case valore.class.to_s
                when 'DateTime'
                    valore_convertito = valore.strftime('%d/%m/%Y %H:%M:%S')
                when 'Date'
                    valore_convertito = valore.strftime('%d/%m/%Y')
                when 'BigDecimal'
                     valore_convertito = valore.to_f.to_s
                     valore_convertito.gsub!(".",",") if !(valore_convertito =~ /^[0-9]+[.][0-9]+$/).nil?
                # when 'FalseClass'
                #     valore_convertito = valore.to_s
                # when Integer
                #     valore_convertito = valore.to-s
                else
                    valore_convertito = valore.to_s
                    valore_convertito = (valore_convertito.respond_to?(:force_encoding) ? valore_convertito.force_encoding('UTF-8') : valore_convertito)
                    valore_convertito.gsub!(".",",") if !(valore_convertito =~ /^[0-9]+[.][0-9]+$/).nil?
            end
            #tolgo spazi davanti e dietro
            valore_convertito.strip!
            # TOLTO PERCHE INSERIVA DOPPIE VIRGOLETTE
            # #se ci sono virgole nel testo ci vanno gli apici
            # stringhe_con_apici = true if valore_convertito.include?(',') 
            # #metto le virgolette attorno al testo
            # if stringhe_con_apici && valore != ""
            #     valore_convertito = '"'+valore_convertito+'"'
            # end
            valore_convertito
        end
        


    end
end
        

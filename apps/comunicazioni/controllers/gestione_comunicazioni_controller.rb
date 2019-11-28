# -*- encoding : utf-8 -*-
require 'rmagick'

module Comunicazioni

	class GestioneComunicazioniController < Spider::Admin::AppAdminController
		layout ['/core/admin/admin', 'gestione/gestione'], :assets => 'comunicazioni_admin'

        include StaticContent

        route /(\d+)\/modifica_comunicazione/, :nuova_comunicazione
        route /(\d+)\/scelta_destinatari/, :scelta_destinatari
        route /(\d+)\/mostra_utenti/, :mostra_utenti
        route /(\d+)\/cancella_utente/, :cancella_utente
        route /(\d+)\/conferma_invio/, :conferma_invio
        route /(\d+)\/lista_destinatari/, :lista_destinatari
        #devo mettere questa route perchè se sono in /admin/comunicazioni/71/scelta_destinatari si prende come route 71/get_auth_nickname
        route /(\d+)\/get_auth_nickname/, :get_auth_nickname
        route /dettaglio_segnalazione\/(\d+)/, :dettaglio_segnalazione
        route /(\d+)/, :comunicazione

        

        include Spider::Auth::AuthHelper
        #tolto per usare amministratori servizi
        #require_user Spider::Auth::SuperUser

        def before(action='', *params)
            #controllo se ho fatto la chiamata in javascript per sapere il nickname
            if !@request.action.include?('get_auth_nickname')
                unless @request.session[:auth]['AuthBox::TwitterUser'].blank?
                    @scene.messaggio_login_twitter = "Connesso a "
                else
                    @scene.messaggio_login_twitter = "Connetti" 
                end
                
            end
            #se ritorno dopo aver rifiutato le autorizzazioni per una app mostro messaggio d'errore
            auth_provider = @request.session["autenticazione_rifiutata"]
            unless auth_provider.blank?
                @request.session["autenticazione_rifiutata"] = nil
                @scene.errore_autenticazione_app = "Per connettersi a #{auth_provider.capitalize} si devono concedere le autorizzazioni all'applicazione Comunicazioni sull'account."
            end
            super
            #@scene.url_gestione_comunicazioni = self.class.url+"/comunicazione"
        end

        __.json
        def get_auth_nickname(id=nil)
            #ricavo l'id del provider a cui mi sono connesso
            id_auth_box_config = @request.params["id"]
            hash_dati_autenticazione = Spider.conf.get('auth_box.'+id_auth_box_config)
            provider = hash_dati_autenticazione['provider']
            provider_user_klass = AuthBox.const_get(provider+'User')
            provider_user = provider_user_klass.restore_session_hash(@request.session[:auth]['AuthBox::'+provider+'User'])
            #$out << auth_provider_user.get_nickname
            $out << {
                :nickname => provider_user.get_nickname,
                :logout_path => AuthBox.const_get(provider+'Controller').https_url('logout?id_auth_box_config='+id_auth_box_config)
            }.to_json
        end

        __.html
        def index(param=nil)
            @scene.sezione = 'elenco'
            @scene.admin_breadcrumb << {:url => self.class.https_url, :label => 'Elenco'}
            @scene.url_nuova_comunicazione = self.class.https_url("nuova_comunicazione")
            #gestione del multilingua con lingua settata come parametro in get, altrimenti lingua di default
            @scene.multilingua_attivo = false
            lingue_traduzioni = Spider.conf.get('comunicazioni.lingue_traduzioni')
            
            unless lingue_traduzioni.blank?             
                @scene.multilingua_attivo = true
                @scene.lingue_traduzioni = lingue_traduzioni
                #la lingua di default è quella passata come parametro, altrimenti quella in sessione, altrimenti la prima specificata in configurazione
                
                lingua = @request.params['lang']
                lingua ||= @request.session['lingua']
                lingua ||= lingue_traduzioni.first

                @request.session['lingua'] = lingua 
                @scene.lingua_corrente = lingua
                #se uso la lingua di default anche tutte le comunicazioni vecchie con "" vengono caricate
                comunicazioni_all = Comunicazioni::Comunicazione.all
                #nel template cerco il crud e gli inserisco una condizione fissa
                template = init_template 'gestione/elenco'
                crud = template.widgets[:crud_comunicazione]
                crud.fixed = { :lingua => lingua }
                template.exec                
            end
            
            render 'gestione/elenco'
        end

        __.html
        def nuova_comunicazione(id=nil)
            
            #controllo se sto facendo una modifica
            if !@request.params.blank? && @request.params['m'] == 't'
                @scene.admin_breadcrumb << {:url => self.class.https_url, :label => 'Modifica comunicazione'}
                @scene.titolo_legend = "Modifica Comunicazione"
                @scene.azione = "modifica"
            else
                @scene.admin_breadcrumb << {:url => self.class.https_url, :label => 'Nuova comunicazione'}
                @scene.titolo_legend = "Nuova Comunicazione"
                @scene.azione = "nuovo"
            end

            @scene.help_message_testo = ""
            
            canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
            if canali_pubblicazione.include?('twitter') && defined?(Twitter)
                @scene.help_message_testo += "I tweet del canale Twitter consentono al massimo 140 caratteri. Il testo restante non sarà visualizzato. "
            end
            #se ho incluso le notifiche push


            #carico le newslist presenti nella view per farla scegliere all'utente se deve pubblicare sul cms
            if canali_pubblicazione.include?('cms') && defined?(Cms)
                newslist_presenti = Cms::NewsList.where{ |nl| (nl.label .not nil)}
                @scene.newslist_presenti = newslist_presenti
            end
            #se entro come amministratore di comunicazioni non permetto di inviare sms
            sms_consentiti = Spider.config.get('comunicazioni.consenti_sms_a_tutti')
            if !@request.user.is_a?(Spider::Auth::SuperUser) && sms_consentiti == false
                canali_pubblicazione.delete('sms') if canali_pubblicazione.include?('sms')
            end

            lingue_traduzioni = Spider.conf.get('comunicazioni.lingue_traduzioni')
            unless lingue_traduzioni.blank? 
                #la lingua di default è quella specificata in configurazione
                lingua = @request.session['lingua']
                lingua ||= lingue_traduzioni.first
                @scene.lingua_corrente = lingua    
            end

            #utilizzo il metodo del modulo Comunicazioni, poi nella view uso 
            #Comunicazioni.canali_comunicazione[0].dettagli_canale_comunicazione che restituisce {:immagine=>"portale.png", :id=>"portale", :nome=>"Portale Comunale"}
            
            #controllo se i canali sono attivi
            @scene.canali_pubblicazione = Comunicazioni.canali_comunicazione.select{ |canale|
                canale.canale_attivo(@request)
            }
        
            id ||= @request.params['id']
            unless id.blank?
                comunicazione = Comunicazioni::Comunicazione.load(:id => id.to_i)
                @scene.comunicazione_id = comunicazione.id 
            end


            #se arrivo in GET o è la prima volta oppure è il torna indietro dallo step 2
            if @request.get?
                #se arrivo dal torna indietro dello step 2 carico la comunicazione dato l'id
                unless id.blank?
                    canali_pubblicati = comunicazione.canali_pubblicazione.split(',')
                    nomi_canali_pubblicati = @scene.canali_pubblicazione.map{ |canale_pubblicato| 
                        canali_pubblicati.include?(canale_pubblicato.dettagli_canale_comunicazione[:id]) ? canale_pubblicato.dettagli_canale_comunicazione[:nome] : nil 
                        }.compact


                    @scene.dati = {
                        'id'            => comunicazione.id,  
                        'titolo'        => comunicazione.titolo,
                        'testo_breve'   => comunicazione.testo_breve,
                        'immagine'      => comunicazione.immagine,
                        'testo'         => comunicazione.testo,
                        'data_da'       => comunicazione.data_da,
                        'data_a'        => comunicazione.data_a,
                        'data_invio'    => comunicazione.data_invio,
                        'canali'        => nomi_canali_pubblicati
                    }
                    @scene.testo_html = comunicazione.testo
                    comunicazione.canali_pubblicazione.split(',').each{ |canale|
                        @scene.dati[canale.to_s] = true
                    }
                else    
                #inizializzo nella scene i dati 
                titolo = testo_breve = immagine = testo = data_da = data_a = ""
                    @scene.dati = {
                        'titolo'        => titolo,
                        'testo_breve'   => testo_breve,
                        'immagine'      => immagine,
                        'testo'         => testo,
                        'data_da'       => data_da,
                        'data_a'        => data_a
                    }
                end    
            end
               
            #se arrivo con i dati in POST controllo errori e inserisco
            if @request.post?
                errori = []
                dati = @request.params['dati']
                if dati['titolo'].blank?
                    errori << 'Il titolo non può essere vuoto' 
                    @scene.titolo_error = "error"
                else
                    titolo = dati['titolo'] 
                end
                #testo breve con controllo numero caratteri
                if dati['testo_breve'].blank?
                    errori << 'Il testo breve non può essere vuoto' 
                    @scene.testo_breve_error = "error"
                elsif dati['testo_breve'].length > 300
                    errori << 'Il testo_breve non può avere più di 300 caratteri' 
                    @scene.testo_breve_error = "error"
                    testo_breve = dati['testo_breve']
                else
                    testo_breve = dati['testo_breve']
                end
                #ricavo il testo html
                testo_html = @request.params['_w']['html_messaggio']
                if testo_html.blank? || strip_html(testo_html).strip.empty?
                    #se ho selezionato solo sms e non portale o mail    
                    unless !dati['sms'].nil? && (dati['portale'].nil? && dati['email'].nil?)
                        errori << "Il testo non può essere vuoto"
                        @scene.testo_error = "error"
                    end
                else
                    dati['testo'] = testo_html
                end
                if @request.params['_w']['data_da'].blank?    
                    errori << "La data di pubblicazione non può essere vuota"
                    @scene.data_da_error = "error"
                else
                    data_da = @request.params['_w']['data_da']
                    data_pubblicazione = DateTime.strptime(data_da, '%d/%m/%Y')
                end
                if @request.params['_w']['data_a'].blank?    
                    errori << "La data di scadenza non può essere vuota"
                    @scene.data_a_error = "error"
                else
                    data_a = @request.params['_w']['data_a']
                    data_scadenza = DateTime.strptime(data_a, '%d/%m/%Y')
                end
                if (!data_pubblicazione.blank? && !data_scadenza.blank?) && data_pubblicazione > data_scadenza
                    errori << "La data di scadenza non può essere antecedente alla data di pubblicazione"
                    @scene.data_a_error = "error"
                end

                #se sono in modifica e voglio cancellare l'immagine
                unless id.blank?
                    if !@request.params['dati'].blank? && @request.params['dati']['rimuovi_immagine'] == 'true'
                        save_dir = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+comunicazione.dir_immagine+"/"
                        nome_file = comunicazione.immagine
                        path_immagine = File.join(save_dir, nome_file)
                        FileUtils.remove(path_immagine) if File.exists?(path_immagine)
                        comunicazione.immagine = nil
                        comunicazione.save
                    end
                end


                @scene.dati = {
                    'titolo'        => titolo,
                    'testo_breve'   => testo_breve,
                    'immagine'      => (id.blank? ? "" : comunicazione.immagine),
                    'testo'         => testo,
                    'data_da'       => data_da,
                    'data_a'        => data_a
                }

                nessun_canale_selezionato = true
                canali_pubblicazione.each{ |canale|
                    if dati[canale.to_s] != nil
                        nessun_canale_selezionato = false
                        @scene.dati[canale.to_s] = true
                    end
                }
                if nessun_canale_selezionato == true
                    errori << "Deve essere selezionato almeno un canale di pubblicazione."
                end

                if errori.empty?
                    #se sto facendo una modifica ad una comunicazione già esistente
                    # if !dati['id'].blank?
                    #     comunicazione = Comunicazioni::Comunicazione.load(:id => dati['id'])
                    # end 
                    comunicazione ||= Comunicazioni::Comunicazione.new

                    dir_immagine = comunicazione.dir_immagine
                    dir_immagine ||= Spider::SecureRandom.hex(10)
                    
                    unless @request.params['dati']['immagine'].blank?
                        #salvataggio immagine con pulizia precedente immagine
                        save_dir = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+dir_immagine+"/"
                        #creo la cartella per il salvataggio dell'immagine con l'hash e la cartella img_resized per le immagini piccole
                        unless File.directory?(save_dir)
                            FileUtils.mkdir_p(save_dir) 
                        end
                        nome_file = @request.params['dati']['immagine'].filename.to_s
                        path_immagine = File.join(save_dir, nome_file)
                        #se era già presente un file lo cancello
                        unless comunicazione.immagine.blank?
                            begin
                                FileUtils.remove(File.join(save_dir, comunicazione.immagine))
                                FileUtils.remove(File.join(save_dir, 'img_resized', comunicazione.immagine))
                            rescue Exception => e
                                Spider.logger.error "Immagine da cancellare non presente #{comunicazione.immagine}"
                            end
                        end
                        #scrivo il file
                        path_immagine_mini = File.join(save_dir, 'img_resized', nome_file)
                        File.open(path_immagine, "wb") { |f| f.write(@request.params['dati']['immagine'].read) }
                        #creo la cartella img_resized e copio il file che se serve viene ridimensionato
                        FileUtils.mkdir_p(save_dir+'img_resized') unless File.directory?(save_dir+'img_resized')
                        FileUtils.copy(path_immagine,path_immagine_mini)
                        #ridimensiono le immagini e le salvo nella cartella img_resized
                        img = Magick::Image::read(path_immagine_mini).first
                        x_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[0].to_i
                        y_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[1].to_i
                        if img.columns > x_resolution || img.rows > y_resolution
                            mini = img.resize_to_fit(x_resolution, y_resolution)
                        else 
                            mini = img
                        end
                        mini.write path_immagine_mini
                        comunicazione.immagine = nome_file
                        comunicazione.dir_immagine = dir_immagine

                    end

                    #cancello tutti i collegamenti con i canali di pubblicazione e poi li risalvo
                    comunicazione.canali_pubblicazione = ""
                    #aggiungo il collegamento per i canali di comunicazione

                    #se sono in nuovo inserimento inizializzo, altrimenti modifico
                    if @scene.azione == "nuovo"
                        hash_extra_params = {}
                    else
                        hash_extra_params = JSON.parse(comunicazione.extra_params)
                    end

                    canali_pubblicazione.each{ |canale|    
                        #salvo i parametri extra legati ad un particolare canale
                        unless dati["#{canale}"].blank?
                            comunicazione.canali_pubblicazione = comunicazione.canali_pubblicazione+"#{canale}," 
                            #carico i dati extra (es newsletter per cms)
                            if hash_extra_params[canale].blank?
                                hash_extra_params[canale] = dati["extra_params_#{canale}"]
                            else
                                hash_extra_params[canale].merge(dati["extra_params_#{canale}"])
                            end
                        end
                        
                    }
                    
                    comunicazione.merge_hash({
                        :titolo => (dati['titolo'].respond_to?(:force_encoding) ? dati['titolo'].force_encoding('UTF-8') : dati['titolo']),
                        :testo_breve => (dati['testo_breve'].respond_to?(:force_encoding) ? dati['testo_breve'].force_encoding('UTF-8') : dati['testo_breve']),
                        :testo => (dati['testo'].respond_to?(:force_encoding) ? dati['testo'].force_encoding('UTF-8') : dati['testo']),
                        :data_da => @request.params['_w']['data_da'],
                        :data_a => @request.params['_w']['data_a'],
                        :extra_params => hash_extra_params.to_json
                    })

                    #gestione multilingua
                    comunicazione.lingua = lingua

                    comunicazione.save_all
                    redirect self.class.https_url("#{comunicazione.id}/scelta_destinatari")
                    return
                else
                    #rimango in pagina e mostro gli errori
                    @scene.errori = errori
                end
            end
            #mostro la pagina
            render 'gestione/nuova_comunicazione'
        end

        __.html
        def scelta_destinatari(id)
            id ||= @request.get['id']
            unless id.blank?
                @scene.admin_breadcrumb << {:url => self.class.https_url, :label => 'Scelta destinatari'}
                #carico gli stati possibili in cui può essere l'utente
                @scene.stati_utente = Portal::Utente::Stato.all
                #carico i gruppi degli utenti tra cui si può selezionare un utente
                gruppi_utente = Portal::Gruppo.all
                #raggruppo i gruppi a tre a tre
                @scene.gruppi_utente = {}
                array_gruppo = []
                gruppi_utente.each_with_index{ |gruppo,i|
                    j = i / 3
                    array_gruppo << gruppo
                    @scene.gruppi_utente[j] = array_gruppo
                    array_gruppo = [] if array_gruppo.length == 3
                }
                

                comunicazione = Comunicazioni::Comunicazione.load(:id => id)
                @scene.comunicazione = comunicazione

                #controllo se è stato selezionato facebook
                @scene.pubblica_in_fb = false
                comunicazione.canali_pubblicazione.split(',').each{ |canale|
                    @scene.pubblica_in_fb = true if canale == "facebook"
                }
                
                servizi_privati = Portal::Servizio.all
                servizi_privati.query.condition = servizi_privati.query.condition.or{|srv| (srv.accesso == 'confermati')}
                servizi_privati.query.condition = servizi_privati.query.condition.or{|srv| (srv.accesso == 'abilitati')}
                servizi_privati.query.condition = servizi_privati.query.condition.or{|srv| (srv.accesso == 'registrati')}
                servizi_privati.query.condition = servizi_privati.query.condition.or{|srv| (srv.accesso == 'nascosto')}
                @scene.servizi_privati = servizi_privati

                #se ho attivato i gruppi per le comunicazioni pubbliche mostro la maschera per i gruppi
                attiva_gruppi_com_pubblica = Spider.conf.get('comunicazioni.pubbliche_per_gruppi')
                if attiva_gruppi_com_pubblica
                    @scene.attiva_gruppi = true
                end

                #cancello l'array degli utenti da cancellare
                @request.session[:utenti_cancellati] = nil


                #se effettuo una ricerca entro in questo metodo in post
                if @request.post?
                    #se torno indietro dal mostra_utenti carico i dati dalla sessione
                    if @request.params['torna_indietro'] == "Indietro"
                        dati_post = @request.session[:post]
                    elsif @request.params['cerca']
                        #altrimenti carico i dati dal post della ricerca
                        dati_post = @request.params
                        #salvo in sessione i parametri di ricerca per filtrare gli utenti nella chiamata ajax
                        @request.session[:post] = dati_post
                    elsif @request.params['submit_invia'] == 'Invia'
                        dati_post = @request.session[:post] || {}
                        dati_post['scelta_tipo_comunicazione'] = @request.params['scelta_tipo_comunicazione']
                        #aggiungo anche il forza_invio ai dati post, caso con gruppi in comunicazione pubblica
                        dati_post['forza_invio'] = ( @request.params['forza_invio'] == 'true' )
                    end


                    if dati_post['scelta_tipo_comunicazione'] == "pubblica"
                        comunicazione.pubblica = true
                        #se sono attivi i gruppi, salvo nella comunicazione i gruppi
                        if attiva_gruppi_com_pubblica
                            gruppi = @request.params['gruppo_utente']
                            unless gruppi.blank?
                                comunicazione.gruppi = []
                                gruppi.each_pair{ |nome_gruppo, valore|
                                    #cerco il gruppo nella tabella gruppi del portal
                                    gruppo = Portal::Gruppo.load(:nome => nome_gruppo)
                                    #salvo il gruppo nella comunicazione                    
                                    comunicazione.gruppi << gruppo unless gruppo.blank?
                                }
                            else
                                comunicazione.gruppi = []
                            end
                        end

                    elsif dati_post['scelta_tipo_comunicazione'] == "privata"
                        #visualizzo i vari utenti cercati
                        comunicazione.pubblica = false

                        
                        #chiamo il metodo che filtra gli utenti con i dati in post e setta la @scene
                        filtra_utenti(dati_post)
                       
                        #VECCHIO METODO
                        # if @request.post['mostra_risultati'] == "Mostra risultati"
                        #     #salvo in sessione i parametri di ricerca per filtrare gli utenti
                        #     @request.session[:post] = dati_post
                        #     #chiamo il metodo mostra_utenti che mi porta in una pagina dove vengono mostrati gli utenti filtrati
                        #     redirect self.class.https_url("#{comunicazione.id}/mostra_utenti")
                        # end
                        
                        #FATTO ALL'INIZIO
                        # if @request.params['cerca']
                        #     #salvo in sessione i parametri di ricerca per filtrare gli utenti nella chiamata ajax
                        #     @request.session[:post] = dati_post
                        # end

                    end

                    @scene.tipo_comunicazione = dati_post['scelta_tipo_comunicazione']
                    if @request.params['submit_invia'] == "Invia" 
                        #se ho fatto una cancellazione e il qs è stato aggiornato prima lo carico dalla scene
                        unless @request.session[:array_ids_da_cancellazione].blank?
                            #converto un array di id in un queryset
                            array_ids = @request.session[:array_ids_da_cancellazione]
                            @scene.utenti = Portal::Utente.where(:id => array_ids)
                        end
                        #trucco per caricare i dati del queryset
                        n_utenti = @scene.utenti.length unless @scene.utenti.blank?
                        #se comunicazione privata associo i portal::utente nella tabella di raccordo comunicazioni__comunicazione__utenti
                        if comunicazione.pubblica == false
                            #comunicazione.utenti va sulla tabella di raccordo, per vedere gli utenti singoli bisogna andare su 
                            #comunicazione.utenti[0].utente, comunicazione.utenti[1].utente ecc
                            comunicazione.utenti = @scene.utenti
                        end

                        #pubblico la comunicazione tramite i vari canali di pubblicazione
                        comunicazione.canali_pubblicazione.split(',').each{ |canale|
                            unless Spider.conf.get('comunicazioni.canali_comunicazione').include?(canale)
                                comunicazione.canali_pubblicazione = comunicazione.canali_pubblicazione.gsub(canale+',',"")
                                next
                            end
                            #se mi servono dei dati da passare senza la request passo i dati in user_in_session, un hash per passare i vari dati
                            user_in_session = ( @request.session[:auth]['AuthBox::'+canale.capitalize+'User'].blank? ? nil : @request.session[:auth]['AuthBox::'+canale.capitalize+'User'])
                            user_in_session ||= {}
                            user_in_session['forza_invio'] = true unless dati_post["forza_invio"].blank?
                            Comunicazioni.canale_comunicazione(canale).pubblica_comunicazione(comunicazione, user_in_session)
                        }
                        comunicazione.stato = "pubblicata"
                        comunicazione.data_invio = Date.today
                        comunicazione.save
                        #se ho inviato sul portale ed è definita la notifica push oppure ho usato come canale la notifica push invio le notifiche
                        canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
                        if ( (comunicazione.canali_pubblicazione.include?('portale') && !Spider.conf.get('comunicazioni.db_push_connection').blank?)  || canali_pubblicazione.include?('push_notification') )
                            Rpush.push
                        end

                        @request.session[:post] = {}
                        #metto a nil la sessione altrimenti nel file_session da errore: f.puts(Marshal.dump(data))
                        @request.session[:qs_da_cancellazione] = nil
                        redirect self.class.https_url("#{comunicazione.id}/conferma_invio")
                        done
                    end
                else
                    #cancello i parametri di ricerca in sessione se sono in GET
                    @request.session[:post] = {}
                end
                render 'gestione/scelta_destinatari'
            else
                redirect self.class.https_url
            end
        end

        __.html
        def mostra_utenti
            comunicazione_id = @request.params['comunicazione_id'].strip unless @request.params['comunicazione_id'].blank?
            dati_post = @request.session[:post]
            #carico gli stati possibili in cui può essere l'utente
            @scene.stati_utente = Portal::Utente::Stato.all
            #carico i gruppi degli utenti tra cui si può selezionare un utente
            @scene.gruppi_utente = Portal::Gruppo.all
            #chiamo il metodo che filtra gli utenti con i dati in post e setta la @scene
            #se passo un id utente da cancellare lo tolgo poi dalla filtra utenti
            id_utente_da_cancellare = @request.params['id_utente_da_cancellare']
            unless id_utente_da_cancellare.blank?
                @request.session[:utenti_cancellati] ||= []
                @request.session[:utenti_cancellati] << id_utente_da_cancellare.to_i unless @request.session[:utenti_cancellati].include?(id_utente_da_cancellare.to_i)
            end
            filtra_utenti(dati_post)
            @scene.comunicazione_id = comunicazione_id
            render 'gestione/mostra_utenti', :layout => nil
        end

        __.json
        def cancella_utente
            id_utente_da_cancellare = @request.params['id_utente_da_cancellare']
            @request.session[:utenti_cancellati] ||= []
            @request.session[:utenti_cancellati] << id_utente_da_cancellare.to_i unless @request.session[:utenti_cancellati].include?(id_utente_da_cancellare.to_i)
            #redirect self.class.https_url('mostra_utenti?comunicazione_id='+comunicazione_id)
            $out << { 'esito' => true }.to_json 
        end


        def filtra_utenti(dati_post)
            utenti = Portal::Utente.all
            #carico i servizi escludendo quelli con accesso pubblico
            c1_or = Spider::Model::Condition.new
            c2_or = Spider::Model::Condition.new
            #condizione sullo stato utente
            unless dati_post["stato_utente"].blank?
                #salvo nella scene gli stati utente per mostrarli dopo aver fatto una ricerca
                @scene.stato_utente = dati_post["stato_utente"]
                @scene.stati_utente.each{ |stato|
                    c1_or = c1_or.or{ |u| u.stato == stato } if dati_post["stato_utente"][stato.id] == "true"
                }
            end
            #condizione sul gruppo
            unless dati_post["gruppo_utente"].blank?
                gruppi_utente = Portal::Gruppo.all
                #salvo nella scene gli stati utente per mostrarli dopo aver fatto una ricerca
                @scene.gruppo_utente = dati_post["gruppo_utente"]
                gruppi_utente.each{ |gruppo|
                    c2_or = c2_or.or{ |u| u.gruppi.nome == gruppo.nome } if dati_post["gruppo_utente"][gruppo.nome] == "true"
                }    
            end
            #faccio l'and delle due condizioni che hanno l'or
            utenti.query.condition = Spider::Model::Condition.and(c1_or, c2_or)
            #controllo che sia abilitato l'invio delle comunicazioni
            if dati_post["forza_invio"].blank?
                utenti.query.condition = utenti.query.condition.and{ |u| (u.disabilita_comunicazioni == nil) | (u.disabilita_comunicazioni == false) } unless Portal::Utente.elements[:disabilita_comunicazioni].blank?
            else
                @scene.forza_invio = true
            end
            utenti.query.condition = utenti.query.condition.and{ |u| u.nome .ilike "%#{dati_post['nome_utente'].strip}%" } unless dati_post['nome_utente'].blank?
            @scene.nome_utente = dati_post['nome_utente'].strip
            utenti.query.condition = utenti.query.condition.and{ |u| u.cognome .ilike "%#{dati_post['cognome_utente'].strip}%" } unless dati_post['cognome_utente'].blank?
            @scene.cognome_utente = dati_post['cognome_utente'].strip
            #conversione date per confronto
            unless dati_post['_w']['data_nascita_utente_dal'].blank? 
                data_nascita_utente = DateTime.strptime(dati_post['_w']['data_nascita_utente_dal'], '%d/%m/%Y')
                utenti.query.condition = utenti.query.condition.and{ |u| u.data_nascita >= data_nascita_utente }
            end
            unless dati_post['_w']['data_nascita_utente_al'].blank? 
                data_nascita_utente = DateTime.strptime(dati_post['_w']['data_nascita_utente_al'], '%d/%m/%Y')
                utenti.query.condition = utenti.query.condition.and{ |u| u.data_nascita <= data_nascita_utente }
            end 
            @scene.data_nascita_utente = dati_post['_w']['data_nascita_utente']
            #condizione sul sesso
            utenti.query.condition = utenti.query.condition.and{ |u| u.sesso == "#{dati_post['sesso_utente']}" } unless dati_post['sesso_utente'].blank?
            @scene.sesso_utente = dati_post['sesso_utente']
            #condizione su cancellato o no
            utenti.query.condition = utenti.query.condition.and{ |u| u.cancellato .not true }
            
            #codizione su email che tempo fa veniva messa come cancellato-mailutente o mailutente-cancellato
            utenti.query.condition = utenti.query.condition.and{ |u| (( u.email .not nil) & (u.email .not "")) & ( u.email .nlike "cancellato-%") & ( u.email .nlike "-cancellato%") }

            post_stato_servizi_utente = dati_post["servizio_utente"]                    
            unless post_stato_servizi_utente.blank?
                #salvo nella scene i servizi degli utenti per vederli dopo aver fatto una ricerca
                @scene.servizio_utente = dati_post["servizio_utente"]
                #NB:carico il query-set degli utenti per poterlo ciclare dopo aver eseguito le varie query
                utenti_filtrati = utenti.select do |utente|
                                        hash_servizi_utente = genera_copia_hash(utente.servizi_privati)
                                        cond = true
                                        #ciclo su tutti i servizi che che mi arrivano dal post
                                        post_stato_servizi_utente.each_pair{ |chiave,valore|
                                            
                                            if valore == "true" && hash_servizi_utente[chiave] != "attivo"
                                                cond = false
                                            end
                                            if valore == "false" && (hash_servizi_utente[chiave] != "disattivato" && hash_servizi_utente[chiave] != nil)
                                                cond = false
                                            end
                                            break if !cond    
                                        }                       
                                        cond
                                    end
                utenti = utenti_filtrati                    
            end
            #se ho cancellato degli utenti dalla maschera 'mostra_utenti' popolo il queryset con solo le righe rimaste
            unless @request.session[:utenti_cancellati].blank?
                utenti_meno_cancellati = utenti.select do |utente|
                                        !@request.session[:utenti_cancellati].include?(utente.id.to_i)
                                    end
                #se ho cancellato via ajax degli utenti salvo in sessione il query_set aggiornato
                @request.session[:qs_da_cancellazione] = utenti_meno_cancellati.to_hash_array
                utenti = utenti_meno_cancellati
            end
            @scene.utenti = utenti
            
        end
     

        __.html
        def conferma_invio(id)
            unless id.blank?
                comunicazione = Comunicazioni::Comunicazione.load(:id => id, :stato => 'pubblicata')
                #controllo se la comunicazione è stata davvero inviata
                redirect self.class.https_url if comunicazione.nil?
                @scene.comunicazione = comunicazione
                if comunicazione[:canali_pubblicazione].include?('cms')
                    if Spider.conf.get('comunicazioni.pubblica_home_cms') == true
                        @scene.pubblicata_su_cms = "La comunicazione è anche diventata una notizia del sito ed è stata pubblicata la home page"
                    else
                        @scene.pubblicata_su_cms = "La comunicazione è anche diventata una notizia del sito, per renderla visibile è necessario procedere alla pubblicazione della home page"
                    end
                    
                end
                render 'gestione/conferma_invio'       
            else
                redirect self.class.https_url
            end    
        end

        __.html
        def lista_destinatari(id_comunicazione)
            unless id_comunicazione.blank?
                comunicazione = Comunicazioni::Comunicazione.load(:id => id_comunicazione)
                utenti = []
                comunicazioni_utente = Comunicazioni::Comunicazione::Utenti.where{ |c| (c.comunicazione == comunicazione) }
                comunicazioni_utente.each{ |com_ut|
                    utenti << com_ut.utente
                }
                @scene.id_comunicazione = id_comunicazione
                @scene.utenti = utenti
                render 'gestione/lista_destinatari'
            end
        end

        __.html
        def comunicazione(id)
            comunicazione = Comunicazioni::Comunicazione.load(:id => id)
            pubblicabile = true
            comunicazione.canali_pubblicazione.split(',').each{ |canale|
                    next unless Spider.conf.get('comunicazioni.canali_comunicazione').include?(canale)
                    #controllo se i canali di pubblicazione sono attivi
                    unless Comunicazioni.canale_comunicazione(canale).canale_attivo(@request)
                        pubblicabile =false
                        break
                    end
                }
            @scene.pubblicabile = pubblicabile
            redirect self.class.https_url if comunicazione.nil?
            @scene.comunicazione = comunicazione
            @scene.admin_breadcrumb << {:url => self.class.https_url, :label => 'Dettaglio comunicazione'}
            #output_format_headers(:html)
            render 'gestione/dettaglio_comunicazione'
        end

        __.html
        def channel
            render 'gestione/channel'
        end

        #genera un hash per i servizi privati di un utente 
        def genera_copia_hash(array_orig)
            my_hash = Hash.new
            array_orig.each { |element| 
                my_hash[element.nome] = element.stato.id
            }
            my_hash
        end

        def strip_html(html)
            html.gsub(%r{</?[^>]+?>}, '').gsub('&nbsp;', '')
        end

        __.action
        def download_immagine
            tipo_img = @request.params['t_img']
            id_comunicazione = @request.params['id_com'].to_i
            comunicazione = Comunicazioni::Comunicazione.load(:id => id_comunicazione)
            dir_immagine = comunicazione.dir_immagine
            if !tipo_img.blank? && tipo_img == 'mini'
                path_file = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+dir_immagine+"/img_resized"
            else
                path_file = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+dir_immagine+"/"
            end
            
            unless path_file.blank?
                file = File.join(path_file,comunicazione.immagine)
                @response.headers['Content-disposition'] = "inline; filename=#{comunicazione.immagine.gsub(' ','_')}"
                output_static(file)
            else
               return ""
            end
        end


        #elenco delle varie segnalazioni, filtro per vari stati, compaiono i vari gestori di segnalazione
        __.html :template => 'gestione/elenco_segnalazioni'
        def elenco_segnalazioni
            segnalazioni = Comunicazioni::Segnalazione.all
            #ricavo gli stati in cui può essere la segnalazione
            stati = Comunicazioni::Segnalazione.elements[:stato].model.all
            if @request.post?
                stato_filtro = @request.params['tipo_stato']
                unless stato_filtro.blank?
                    @scene.filtro = stato_filtro
                    #filtro il qs con questa condizione
                    segnalazioni.query.condition = segnalazioni.query.condition.and{ |segnalazione| (segnalazione.stato == stato_filtro)}
                end
                nome_stato = Comunicazioni::Segnalazione.elements[:stato].model.where(:id => stato_filtro)
                @scene.nome_stato = nome_stato[0] unless nome_stato.blank?
            end
            #setto la scene
            @scene.segnalazioni = segnalazioni
            @scene.stati = stati
            
        end

        __.html :template => 'gestione/dettaglio_segnalazione'
        def dettaglio_segnalazione(id_segnalazione)
            if id_segnalazione.blank?
                @request.session.flash['errore'] = 'Segnalazione non presente'
                redirect self.class.https_url('elenco_segnalazioni') 
            else
                segnalazione = Comunicazioni::Segnalazione.where(:id => id_segnalazione.to_i)
                unless segnalazione.blank?
                    @scene.segnalazione = segnalazione[0]
                    @scene.extra_params = JSON.parse(segnalazione[0].extra_params)
                end
                stati = Segnalazione.elements[:stato].model.all
                @scene.stati = stati
            end
        end


	end

end

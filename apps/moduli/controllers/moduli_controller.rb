# -*- encoding : utf-8 -*-
require 'json'
require 'digest/md5'
require 'nokogiri'
require 'crack'
require 'pdfkit'
require 'combine_pdf'
require 'active_support/all'

begin
    require 'ftools'
rescue LoadError
end

module Moduli
    
    class ModuliController < Spider::PageController

        include Portal::ControllerServizio

        include Spider::Messenger::MessengerHelper rescue NameError

        #route nil, :modulo #route di fallback, quando si mette un percorso non inserito nelle route prende questa.
        #route '', :index
        route /(\d+)\/modulo/, :modulo
        route /(\d+)\/stampa/, :stampa
        route /(\d+)\/invio_mail/, :invio_mail
        route /(\d+)\/cancella$/, :elimina_modulo

        #route 'modulo', :modulo

        include Portal::HelperAutenticazione

        begin   
            servizio_portale :id => 'moduli', :accesso => 'registrati', :nome => 'Moduli', :eccetto => [:download_pdf_embed, :download_iscrizione]
        rescue NameError
        end
    
        self.parent_module.verifica_presenza_configurazioni(['mail_invio_moduli'])

        layout ['/portal/portal', 'moduli'], :assets => 'modulo' 

        __.html 
        def index
            autenticazione_necessaria
            utente = @request.utente_portale
            # non uso utente.moduli perchè non riesco ad ordinarlo
            moduli_compilati = ModuloSalvato.where{ |modulo| (modulo.utente == utente) & (modulo.tipo_modulo .not nil) & ((modulo.tipo_modulo.solo_pratiche == false) | (modulo.tipo_modulo.solo_pratiche == nil)) & ((modulo.tipo_modulo.per_iscrizioni_scolastiche == false) | (modulo.tipo_modulo.per_iscrizioni_scolastiche == nil)) & ( (modulo.stato == 'inviato') | ( (modulo.stato .not 'inviato') & ((modulo.tipo_modulo.disponibile_dal <= DateTime.now) | (modulo.tipo_modulo.disponibile_dal == nil)) & ((modulo.tipo_modulo.disponibile_al >= DateTime.now) | (modulo.tipo_modulo.disponibile_al == nil)) ) ) }.order_by(:obj_modified, :desc)
            #Se presente Pagamenti mostro avviso se ci sono pagamenti da completare per inviare moduli
            if defined?(Pagamenti) != nil
                #ciclo sui moduli per vedere se i pagamenti sono pendenti per mostrare avviso di pagamenti da completare per inviare i moduli
                moduli_da_pagare = false
                moduli_compilati.each{ |modulo_compilato|
                    if modulo_compilato.respond_to?(:pagamenti_collegati) && !modulo_compilato.pagamenti_collegati.blank?
                        modulo_compilato.pagamenti_collegati.each{ |pagamento_collegato|
                            moduli_da_pagare = true if pagamento_collegato.stato == 'pendente' || pagamento_collegato.stato == 'non_eseguito'
                            break if moduli_da_pagare
                        }
                    end
                    break if moduli_da_pagare
                }
                @scene.moduli_da_pagare = moduli_da_pagare
            end
            #calcolo i moduli in bozza per mostrare un messaggio che dice che ci sono dei moduli in bozza da confermare
            moduli_in_bozza = ModuloSalvato.where{ |modulo| ( ((modulo.tipo_modulo.disponibile_dal <= DateTime.now) | (modulo.tipo_modulo.disponibile_dal == nil)) & ((modulo.tipo_modulo.disponibile_al >= DateTime.now) | (modulo.tipo_modulo.disponibile_al == nil)) & ((modulo.tipo_modulo.solo_pratiche == false) | (modulo.tipo_modulo.solo_pratiche == nil)) & ((modulo.tipo_modulo.per_iscrizioni_scolastiche == false) | (modulo.tipo_modulo.per_iscrizioni_scolastiche == nil)) & (modulo.stato == 'bozza') & (modulo.utente == utente)  ) }
            @scene.moduli_compilati = moduli_compilati
            @scene.moduli_in_bozza = moduli_in_bozza
            num_moduli_compilati = moduli_compilati.blank? ? 0 : moduli_compilati.length
            num_moduli_in_bozza = moduli_in_bozza.blank? ? 0 : moduli_in_bozza.length
            if (num_moduli_compilati + num_moduli_in_bozza) == 0
                #mi ripasso l'errore se devo fare un redirect
                @request.session.flash['errore_azione'] = @request.session.flash['errore'] unless @request.session.flash['errore'].blank?
                redirect self.class.http_s_url('scegli_modulo') 
            end
            #mostro i moduli precedentemente compilati con il link
            @scene.link_nuovo = self.class.http_s_url('scegli_modulo')
            @scene.esito_azione = @request.session.flash['esito_azione'] unless @request.session.flash['esito_azione'].blank?
            @scene.errore = @request.session.flash['errore'] unless @request.session.flash['errore'].blank? 
            render 'index'
        end

        def get_moduli_utente_attivi(utente,scena,request)
            #carico gli id dei moduli compilati dall'utente per escludere quelli one shot che ha già compilato
            tipi_modulo_utente = []
            utente.moduli.each{ |modulo| 
                tipi_modulo_utente << modulo.tipo_modulo.id if modulo.tipo_modulo.tipo_compilazione == 'uno' && modulo.stato != 'bozza' 
            }
            #carico i vari moduli che si possono compilare in base alle date
            tipi_modulo_utente = tipi_modulo_utente.uniq
            moduli_compilabili = Moduli::TipoModulo.all.order_by(:nome,:asc)
            moduli_compilabili.query.condition = Spider::Model::Condition.new
            moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.disponibile_dal <= DateTime.now) | (modulo.disponibile_dal == nil) }
            moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.disponibile_al >= DateTime.now) | (modulo.disponibile_al == nil) } #unless request.utente_portale.id == 1 || request.utente_portale.id == 6 Abilita solo certi utenti
            #se non vengo da pratiche escludo i moduli compilabili solo da pratiche edilizie
            if request.params['id_pratica'].blank? && request.params['url_ritorno'].blank?
                #questo controllo è indipendente dallo stato di pubblicvazione del modulo (test o no)
                moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.solo_pratiche == false) | (modulo.solo_pratiche == nil) }
            end
            if request.params['id_iscrizione'].blank? && request.params['url_ritorno'].blank?
                #questo controllo è indipendente dallo stato di pubblicazione del modulo (test o no)
                moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.per_iscrizioni_scolastiche == false) | (modulo.per_iscrizioni_scolastiche == nil) }
            end
            #mostro tutti i moduli che hanno stato_visualizzazione a nil o pubblico
            #se l'utente ha come servizio moduli_test allora gli mostro quelli in fase di test
            #creo array con id dei servizi
            nomi_servizi = []
            scena.servizi_privati_utente.each{ |obj_servizio|
                nomi_servizi << obj_servizio.id
            }
            unless nomi_servizi.include?('moduli_test')
                moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.stato_visualizzazione .not 'test') }
            end

            if (request.post? && request.params['cerca_parole'] == 'true') || (request.get? && !request.params['settore'].blank?)
                #filtro le schede se effettuo la ricerca o seleziono un settore
                query_ricerca = request.params['query_ricerca']
                if !query_ricerca.blank?
                    parole = query_ricerca.split(" ")
                    c1_or = Spider::Model::Condition.new
                    parole.each{ |str_ricerca|
                        c1_or = c1_or.or{|tip_mod| (tip_mod.nome .ilike "%"+str_ricerca+"%") }

                    }    
                    moduli_compilabili.query.condition = moduli_compilabili.query.condition.and(c1_or)
                    scena.query_ricerca = query_ricerca
                end

                #carico l'id del settore selezionato
                id_settore_selezionato = request.params['settore']
                scena.settore_selezionato = id_settore_selezionato
                #cond su settore
                if id_settore_selezionato == '0'
                    moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.settore == nil) }
                elsif !id_settore_selezionato.nil? && id_settore_selezionato != 'tutti'
                    moduli_compilabili.query.condition = moduli_compilabili.query.condition.and{ |modulo| (modulo.settore == id_settore_selezionato) } 
                end
            end


            #controllo se un modulo è compilabile una sola volta che l'utente non ne abbia già fatto uno
            moduli_utente_attivi = []
            moduli_compilabili.each{ |tipo_modulo|
                if tipo_modulo.tipo_compilazione.id == "uno"
                    moduli_utente_attivi << tipo_modulo if !tipi_modulo_utente.include?(tipo_modulo.id)
                else
                    moduli_utente_attivi << tipo_modulo
                end
            }
            moduli_utente_attivi
        end
     
        __.html :template => 'scegli_modulo'
        def scegli_modulo
            autenticazione_necessaria
            @scene.link_indietro = self.class.http_s_url
            #pulisco la sessione con la dir degli allegati
            @request.session["hashdir_moduli_allegati"] = nil
            #popola la select dei settori
            settori = Portal::Hippo::Settore.all
            @scene.settori_modulo = settori           

            #se ci sono degli errori
            @scene.errore_azione = @request.session.flash['errore_azione'] unless @request.session.flash['errore_azione'].blank?

            utente = @request.utente_portale

            #carico solo i moduli compilabili
            moduli_utente_attivi = get_moduli_utente_attivi(utente,@scene,@request)
            @scene.moduli_utente_attivi = moduli_utente_attivi
        end

        def codifica_hash_utf8(hash)
            return hash if hash.blank?
            return ( hash.is_a?(String) && hash.respond_to?(:force_encoding) ? hash.force_encoding('UTF-8').encode('UTF-8') : hash ) if hash.is_a?(String)
            hash.each_pair { |k, v|
                if v.class == Hash
                    hash[k] = codifica_hash_utf8(v)
                else
                    #se ho un file non posso fargli il force_encoding('UTF-8') perchè viene letto da rack con encoding ASCII-8BIT (BINARY)
                    #se ho passato un UploadedFile lo tolgo dai parametri per non dover fare il to_json che da errore
                    if v.is_a?(Spider::UploadedFile)
                        hash[k] = (!v.filename.blank? && v.filename.respond_to?(:force_encoding) && v.filename.is_a?(String) ? v.filename.force_encoding('UTF-8').encode('UTF-8') : v.filename)
                    else
                        hash[k] = (v.respond_to?(:force_encoding) && v.is_a?(String) ? v.force_encoding('UTF-8').encode('UTF-8') : v)
                    end
                end
            }
            hash
        end

        def json_titolo_studio_necessario(categoria)
            #se in moduli.rb il metodo hash_titoli non ha il caso con chiave titolo_necessario ritorno un hash vuoto
            return {} if Moduli.hash_titoli(categoria)['titolo_necessario'].blank?
            hash_titolo_necessario = {'1' => {}}
            hash_titolo_necessario['1'] = {
                                            'tipo_titolo' => 'titolo_necessario',
                                            'universita' => "",
                                            'anno_accademico' => "",
                                            'titolo_descrizione' => Moduli.hash_titoli(categoria)['titolo_necessario']['nome'],
                                            'id_inc' => 1 
            }
            hash_titolo_necessario.to_json
        end


        def modulo_scaduto?(id_tipo_modulo)
            tipo_modulo = Moduli::TipoModulo.load(:id => id_tipo_modulo)
            unless tipo_modulo.blank?
                #controllo le date di scadenza
                valido = ( (!tipo_modulo.disponibile_dal.blank? && tipo_modulo.disponibile_dal <= DateTime.now )  || tipo_modulo.disponibile_dal == nil) && ( (!tipo_modulo.disponibile_al.blank? && tipo_modulo.disponibile_al >= DateTime.now) || tipo_modulo.disponibile_al == nil) 
            end
            !valido
        end


        __.html
        def modulo(id_modulo, id_tipo_mod=nil)
            autenticazione_necessaria         
            utente = @request.utente_portale
            #se ho un solo parametro è l'id del tipo modulo
            id_tipo_modulo = ( id_tipo_mod.nil? ? id_modulo.to_i : id_tipo_mod.to_i)
            #controllo se sto compilando un modulo compilabile in base a date scadenza, tipo di compilazione e test
            moduli_utente_attivi = get_moduli_utente_attivi(utente,@scene,@request)
            unless moduli_utente_attivi.map{ |m| m.id }.include?(id_tipo_modulo)
                @request.session.flash['esito_azione'] = "Modulo non compilabile! Il modulo potrebbe essere stato disattivato o scaduto."
                redirect self.class.http_s_url
            end
            #se ho un solo parametro id_modulo lo metto a nil perchè ho solo l'id del tipo modulo
            id_modulo = nil if id_tipo_mod.blank?
            #carico il tipo di modulo per avere il percorso del template per la widget
            #controllo se il modulo è compilabile
            tipo_modulo_qs = TipoModulo.where{ |tipo_modulo| ( (tipo_modulo.id == id_tipo_modulo) & ((tipo_modulo.disponibile_dal <= DateTime.now) | (tipo_modulo.disponibile_dal == nil)) & ((tipo_modulo.disponibile_al >= DateTime.now) | (tipo_modulo.disponibile_al == nil)) ) }
            #rimando all index con un messaggio se il modulo non è compilabile
            if tipo_modulo_qs.blank?
                @request.session.flash['esito_azione'] = "Modulo non compilabile"
                redirect self.class.http_s_url
            end
            #carico nella scene un errore che viene visualizzato nel layout per problemi allegati non caricati
            @scene.errore_allegati = @request.session.flash['errore'] unless @request.session.flash.blank?

            #carico in tipo_modulo il primo risultato
            tipo_modulo = tipo_modulo_qs[0]
            percorso = tipo_modulo.tipo
            modulo = nil
            #metto in sessione un hash per creare la cartella degli allegati se non è stata inizializzata
            @request.session["hashdir_moduli_allegati"] = Spider::SecureRandom.hex(10) if @request.get? && @request.params['_wt'].blank? && @request.params['_we'].blank?
            unless id_modulo.blank? 
                modulo = ModuloSalvato.load(:id => id_modulo.to_i, :utente => utente)
                if modulo.nil?
                    @request.session.flash['esito_azione'] = "Modulo non presente"
                    redirect self.class.http_s_url
                end
                #carico in sessione la cartella degli allegati
                @request.session["hashdir_moduli_allegati"] = modulo.hashdir_allegati  
            end
            # nascondo il menu laterale per mostrare il modulo a tutto schermo
            @scene.no_left_column = true
            
            begin
                #carico il template del modulo
                tpl = carica_template(percorso)
                widget = tpl.widgets[:modulo] || tpl.widgets.first[1]
                widget_template = widget.template
                widget.sub_widgets = []
            rescue Exception => exc
                #qui catturo il raise fatto su allegato.rb
                if exc.message == 'Sessione scaduta'
                    Spider.logger.error "\n Sessione per allegati non presente, utente #{@request.utente_portale}, sessione #{@request.session["hashdir_moduli_allegati"]}"
                    @request.session.flash['errore'] = "Sessione dati compromessa!"
                    redirect self.class.http_s_url
                else
                    messaggio =  "#{exc.message}"
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
                    redirect self.class.http_s_url
                end

            end
            
            widget.scene.stato = 'bozza'
            
            #flag per capire se dopo il modulo vado a completare esperienze lavorative e titoli studio
            widget.scene.da_completare = !tipo_modulo.categoria_giuridica.blank?

            #se ci sono delle dimensioni salvate le carico e metto in una variabile della scene della widget
            unless modulo.nil?
                widget.scene.dimensioni_da_db = modulo.dimensioni.gsub("\"","'") unless modulo.dimensioni.blank?
                #carico gli importi salvati con il rispettivo valore, sono quelli selezionati da cittadino
                widget.scene.importi_selezionati = widget.scene.importi_ceccati = JSON.parse(modulo.importi) unless modulo.importi.blank?
                #carico la data per i campi data odierna
                widget.scene.data_conferma_modulo = modulo.confermato.to_date.lformat(:short) unless modulo.confermato.blank?
                #carico lo stato del modulo nella sua scene per mostrare o no i tasti di salvataggio
                widget.scene.stato = modulo.stato.id
                 #carico i dati degli extra_dati
                unless modulo.extra_dati.blank?
                    hash_extra_dati_modulo = JSON.parse(modulo.extra_dati)
                    widget.scene.riquadri_opzionali = hash_extra_dati_modulo['riquadri_opzionali'].to_json.gsub("\"","'")
                
                end
            end
           
            if defined?(Pagamenti) != nil
                #raggruppo gli importi configurati lato admin per tipo obbligatorieta per mostare la tabella con gli importi 
                #raggruppati per obbligatorio, almeno_uno e solo_uno  
                importi_collegati = Moduli::Importo.where{|imp| (imp.tipo_modulo == tipo_modulo)}.group_by{|imp_col| imp_col[:tipo_obbligatorieta]}

                unless importi_collegati.blank?
                    widget.scene.importi_collegati = importi_collegati
                end

                #se ci sono importi obbligatori o di tipo almeno_uno è di sicuro da pagare
                modulo_da_pagare_sicuramente = false
                importi_collegati_presenti = false
                importi_collegati = tipo_modulo.importi
                unless importi_collegati.blank?
                    widget.scene.importi_collegati_presenti = true #so che ci sono degli importi comunque, potrebbe essere da pagare
                    importi_collegati.each{ |importo_coll|
                        modulo_da_pagare_sicuramente = true if importo_coll.tipo_obbligatorieta == 'obbligatorio' || importo_coll.tipo_obbligatorieta == 'almeno_uno'
                        break if modulo_da_pagare_sicuramente
                    }
                end
            end
            #se nel tipo modulo ho impostato che la firma sia diversa da null cambio il submit con queste var in scene
            #se il modulo ha il baffo solo da procedimenti amministrativi, fa tutto pratiche edilizie (pagamento, firma ecc)
            #da firmare se nel tipo modulo ho impostato che la firma sia diversa da null
            #e non è stato selezionato solo da procedimenti amministrativi
            widget.scene.da_firmare = (!tipo_modulo.tipo_firma.blank? && !tipo_modulo.tipo_firma.id.blank? && tipo_modulo.solo_pratiche.blank? && tipo_modulo.per_iscrizioni_scolastiche.blank? )
            
            #il modulo è da pagare se il totale degli importi configurati > 0 o se presente campo libero e non gratis (importo a 0 e )
            #e non è stato selezionato solo da procedimenti amministrativi
            widget.scene.da_pagare = ( widget.scene.da_firmare.blank? && modulo_da_pagare_sicuramente && tipo_modulo.solo_pratiche.blank? && tipo_modulo.per_iscrizioni_scolastiche.blank? )

            #se ho caricato un modulo da db carico i dati nella widget
            widget.dati(modulo.dati, modulo.dimensioni) if @request.get? && !id_modulo.blank?




            if @request.post?
                #salvo in sessione i dati del modulo passati in post per poterli caricare se devo visualizzarli
                @request.session['dati_post_sessione_params'] = @request.session['dati_post_sessione_widget_serializzata'] = nil 
                @request.session['dati_post_sessione_params'] = codifica_hash_utf8(@request.params['_w']['modulo']).to_json if !@request.params['_w'].blank? && !@request.params['_w']['modulo'].blank?
                dati_widget_no_utf8 = widget.serializza
                array_chiavi_allegati = widget.serializza.keys.reject{ |k| k.to_s !~ /allegato_\d+/  }
                if array_chiavi_allegati.length > 0
                    array_chiavi_allegati.each{ |chiave_hash|
                        next if chiave_hash == 'allegato_8211' #path per moduli vecchi che hanno nome del campo con allegato_ 
                        hash_file_request = @request.params['_w']['modulo'][chiave_hash.to_s][chiave_hash.to_s]
                        if hash_file_request['file'].blank?
                            
                            #se la chiave file_name è blank metto nil
                            if !hash_file_request['file_name'].blank? && hash_file_request['clear'] != 'on'
                                dati_widget_no_utf8[chiave_hash] = Pathname.new(hash_file_request['file_name']).basename.to_s
                            else
                                dati_widget_no_utf8[chiave_hash] = nil
                            end
                        else
                            #il nome del file lo trovo in @request.params['_w']['modulo'][chiave_hash.to_s][chiave_hash.to_s]['file']
                            dati_widget_no_utf8[chiave_hash] = @request.params['_w']['modulo'][chiave_hash.to_s][chiave_hash.to_s]['file']
                        end
                    }
                
                end
                #in dati_post metto i dati in utf-8 puliti da usare nella widget e da salvare in sessione
                dati_post = codifica_hash_utf8(dati_widget_no_utf8)
                @request.session['dati_post_sessione_widget_serializzata'] = dati_post  
                #imposto tipo di salvataggio
                tipo_salvataggio = 'bozza' if @request.params['bozza'] == 'true'
                tipo_salvataggio = 'confermato' if @request.params['confermato'] == 'true'
                #caso pratiche
                tipo_salvataggio = 'da_pratiche' if @request.params['proseguo'] == 'true'
                #caso iscrizioni on line Muse
                tipo_salvataggio = 'da_iscrizioni' if @request.params['per_iscrizioni'] == 'true'
                
                tipo_salvataggio_iniziale = tipo_salvataggio.dup #poi viene cambiato se da pagare o da firmare, mi serve sapere
                #se vengo da iscrizioni o da pratiche

                #riassocio il tipomodulo e salvo/sovrascrivo i dati
                modulo ||= ModuloSalvato.new
                
                #se sono in bozza salvo gli importi
                if !tipo_salvataggio.blank? && tipo_salvataggio == 'bozza'
                    unless @request.params['importi_collegati'].blank?
                        importi_da_salvare = {}
                        @request.params['importi'].each_pair{ |indice_importo,valore|
                            if !valore.blank? && BigDecimal.new(valore.gsub(",",".")) > 0 && @request.params['importi_collegati'][indice_importo] == 'on'
                                importi_da_salvare[indice_importo] = BigDecimal.new(valore.gsub(",",".")).lformat
                            end
                        }
                        modulo.importi = importi_da_salvare.to_json
                    end
                end

                #se arriva in post il dato sugli importi selezionati lo risalvo in una var per mostrarli se ho errori
                #controllo le condizioni su importi se salvo definitivamente (e devo firmare o pagare)
                #anche da iscrizioni potrei avere passato un modulo che ha degli importi
                if !tipo_salvataggio.blank? && ['confermato','da_iscrizioni'].include?(tipo_salvataggio)   
                    errori_importi = {}
                    errore_importi_solo_uno = ""
                    errore_importi_almeno_uno = ""
                    #se ci sono importi ciclo sugli importi e controllo se sono > 0
                    unless @request.params['importi_collegati'].blank?
                        modulo_da_pagare = true #se non ho importi obbligatori o almeno uno potevo anche non dover pagare, se ci sono check però devo pagare di sicuro
                        #sono stati ceccati degli importi, modulo da pagare. Controllo prima se anche da firmare
                        if (!tipo_modulo.tipo_firma.blank? && !tipo_modulo.tipo_firma.id.blank? )
                            #firma richiesta, controllo se sono entrato con spid
                            if (!@request.session[:auth].blank? && !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?)     
                                #controllo se il totale è > di 0
                                if BigDecimal.new(@request.params['totale_importi'].gsub(",",".")) > 0
                                    tipo_salvataggio = 'da_pagare' #sono con spid, devo solo pagare
                                else
                                    tipo_salvataggio = 'confermato' #sono con spid e non ci sono cose da pagare
                                end
                            else
                                tipo_salvataggio = 'da_firmare' #non ho spid -> devo firmare
                            end
                        else #non richiesta la firma
                            #controllo se il totale è > di 0
                            if BigDecimal.new(@request.params['totale_importi'].gsub(",",".")) > 0
                                tipo_salvataggio = 'da_pagare' #devo pagare qlcs
                            else
                                tipo_salvataggio = 'confermato' #non devo pagare e non richiesta firma
                            end
                        end
                    else
                        #non sono arrivati importi ceccati, controllo se da firmare oppure invio il modulo direttamente
                        #se non è da pagare sicuramente e non ci sono importi ceccati è di sicuro a false
                        if !modulo_da_pagare_sicuramente
                            modulo_da_pagare = false 
                        else
                            #considero che sia da pagare, dopo controllo i valori passati
                            modulo_da_pagare = true 
                        end
                        
                        if (!tipo_modulo.tipo_firma.blank? && !tipo_modulo.tipo_firma.id.blank? )
                            #firma richiesta, controllo se sono entrato con spid
                            if (!@request.session[:auth].blank? && !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?)
                                tipo_salvataggio = 'confermato' #sono con spid, invio diretto    
                            else
                                tipo_salvataggio = 'da_firmare' #non ho spid -> devo firmare
                            end
                        else
                            #non ci sono importi e non è richiesta la firma, invio il modulo
                            tipo_salvataggio = 'confermato'
                        end
                    end
            
                    #se il modulo è da pagare e ci sono importi valorizzati
                    if modulo_da_pagare && !@request.params['importi'].blank?
                        importi_solo_uno = nil
                        importi_almeno_uno = nil
                        importi_da_salvare = {}
                        @request.params['importi'].each_pair{ |indice_importo,valore|
                            imp = Moduli::Importo.load(:id => indice_importo)
                            unless imp.blank?
                                #passo all'importo successivo se non ho ceccato l'importo e l'importo non è libero e non è un importo di tipo almeno_uno
                                next if !@request.params['importi_collegati'].blank? && @request.params['importi_collegati'][indice_importo] != 'on' && imp.tipo_obbligatorieta != 'almeno_uno'  && !imp.importo_utente
                                if imp.tipo_obbligatorieta == 'solo_uno'
                                   importi_solo_uno ||= 0 
                                   importi_solo_uno += 1 if ( imp.importo_utente == false || (imp.importo_utente == true && BigDecimal.new(valore) > 0) )
                                end
                                if imp.tipo_obbligatorieta == 'almeno_uno'
                                    importi_almeno_uno ||= 0
                                    importi_almeno_uno += 1 if !@request.params['importi_collegati'].blank? && @request.params['importi_collegati'][indice_importo] == 'on' && ( imp.importo_utente == false || (imp.importo_utente == true && BigDecimal.new(valore) > 0) )
                                end
                            end
            
                            #se l'importo è a zero ed è un importo libero metto errore
                            if BigDecimal.new(valore) == 0 && imp.importo_utente
                                errori_importi[indice_importo] = 'errore_importo error danger' if !@request.params['importi_collegati'].blank? && @request.params['importi_collegati'][indice_importo] == 'on'
                            else
                                #salvo gli importi se ceccato
                                importi_da_salvare[indice_importo] = BigDecimal.new(valore.gsub(",",".")).lformat if !@request.params['importi_collegati'].blank? && @request.params['importi_collegati'][indice_importo] == 'on'
                            end
                        }
                        #salvo nella scene i parametri per mostrare sul modulo i checkbox checked nella pagina di errore
                        widget.scene.importi_selezionati = importi_da_salvare
                        widget.scene.importi_ceccati = @request.params['importi_collegati']
                        errore_importi_solo_uno = 'errore_importi_solo_uno error danger' if !importi_solo_uno.blank? && importi_solo_uno > 1
                        errore_importi_almeno_uno = 'errore_importi_almeno_uno error danger' if !importi_almeno_uno.blank?  && importi_almeno_uno == 0
                        #se non ci sono problemi sugli importi salvo in db
                        if errori_importi.blank?
                            #se non ci sono errori controllo se ci sono importi
                            if importi_da_salvare.blank?
                                #se non ci sono importi non è da pagare
                                modulo_da_pagare = false
                            else
                                modulo.importi = importi_da_salvare.to_json
                                modulo_da_pagare = true #era già a true, per chiarezza..
                            end
                            
                        end
                        
                    end


                end

                #carico l'hash delle dimensioni dei campi auto adattativi
                #hsh_dimensioni = JSON.parse(@request.params['dimensioni'])
                modulo.dimensioni = @request.params['dimensioni'] unless @request.params['dimensioni'].blank?

                modulo.tipo_modulo = tipo_modulo
                unless tipo_salvataggio.blank?
                    modulo.stato = tipo_salvataggio 
                    modulo.confermato = DateTime.now if tipo_salvataggio != "bozza"
                end
                modulo.utente = utente if @request.respond_to?(:utente_portale)
                #se la sessione è vuota la ricreo per non avere errore sulla join con un valore nil
                modulo.hashdir_allegati = @request.session["hashdir_moduli_allegati"].blank? ? Spider::SecureRandom.hex(10) : @request.session["hashdir_moduli_allegati"]
                
                #ricavo il json dei riquadri opzionali se ci sono
                #arriva un json che viene convertito in un hash del tipo
                # {"modulo-fieldset_riquadro_opzionale_1"=>"attivo", "modulo-fieldset_riquadro_opzionale_2"=>"nascosto"}
                unless @request.params["array_id_riquadri_opzionali"].blank?
                    hash_riquadri_opzionali = JSON.parse( @request.params["array_id_riquadri_opzionali"].gsub("'","\"") )
                    #ho riquadri che potrebbero essere nascosti, creo array con solo id riquadri attivi
                    arr_id_riquadri_attivi = []
                    hash_riquadri_opzionali.each_pair{|id,valore| arr_id_riquadri_attivi << id.gsub('modulo-','') if valore == 'attivo'}
                    
                    #salvo nel campo extra_dati il valore json per i riquadri
                    hash_extra_dati = ( modulo.extra_dati.blank? ? {} : JSON.parse(modulo.extra_dati) )
                    hash_extra_dati['riquadri_opzionali'] = hash_riquadri_opzionali
                    modulo.extra_dati = hash_extra_dati.to_json
                end


                #Gestione errori in scene: 
                if ['confermato','da_firmare','da_pagare','da_pratiche','da_iscrizioni'].include?(tipo_salvataggio)
                #if tipo_salvataggio == 'confermato' || tipo_salvataggio == 'da_firmare' || tipo_salvataggio == 'da_pagare' || @request.params['proseguo'] == 'true'
                    #caso pratiche
                    if @request.params['proseguo'] == 'true'
                        id_pratica = @request.params['id_pratica']
                        nome_classe_gestore = 'Gestore'+tipo_modulo.tipo.split('_').each{|v| v.capitalize! }.join
                        errori = Moduli.const_get(nome_classe_gestore).controlla_dati_modulo(dati_post) if Moduli.const_defined?(nome_classe_gestore)
                        #se non presente un gestore apposito controllo i campi con editor
                        if errori.blank?
                            #controllo errori di un modulo inserito da editor, passando anche i dati della pratica se presente
                            hash_conf_pratica = get_hash_config(id_pratica.to_s)
                            allegati_associati = tipo_modulo.allegati_associati
                            hash_dati_utente = Moduli::Funzioni.carica_dati_modulo_editor(@request.params, hash_conf_pratica, allegati_associati)
                            dati_post['array_id_riquadri_opzionali'] = arr_id_riquadri_attivi unless dati_post.blank?
                            errori = Moduli::Funzioni.controlla_campi_obbligatori_editor(tipo_modulo,dati_post,hash_dati_utente) unless tipo_modulo.campi_obbligatori.blank?
                        end
                    #caso iscrizioni
                    elsif @request.params['per_iscrizioni'] == 'true'
                        id_iscrizione = @request.params['id_iscrizione']
                        #controllo errori di un modulo inserito da editor + formato di allegati
                        allegati_associati = tipo_modulo.allegati_associati
                        hash_dati_utente = Moduli::Funzioni.carica_dati_modulo_editor_iscrizioni(@request.params, allegati_associati)
                        dati_post['array_id_riquadri_opzionali'] = arr_id_riquadri_attivi unless dati_post.blank?
                        errori = Moduli::Funzioni.controlla_campi_obbligatori_editor(tipo_modulo,dati_post,hash_dati_utente) unless tipo_modulo.campi_obbligatori.blank?
                    else
                        #caso di default
                        #controllo errori di un modulo inserito da editor + formato di allegati
                        dati_post['array_id_riquadri_opzionali'] = arr_id_riquadri_attivi unless dati_post.blank?
                        errori = Moduli::Funzioni.controlla_campi_obbligatori_editor(tipo_modulo,dati_post) unless tipo_modulo.campi_obbligatori.blank?
                    end

                    #aggiungo errori legati agli importi per mantenere la grafica
                    if !errori_importi.blank? || !errore_importi_solo_uno.blank? || !errore_importi_almeno_uno.blank?
                        errori ||= []
                        errori << { 'campo' => 'importi', 'msg' => 'Importi non validi', 'input_type' => 'text' }
                    end
                    
                end

                #GESTIONE ALLEGATI E CONTROLLO ALLEGATO VUOTO: ricarico gli allegati nella widget
                widget.aggiorna_dati = dati_post unless dati_post.blank?

                #allegati presenti nella widget, sono quelli che ci devono effettivamente essere nella dir
                allegati_in_widget = []
                errori ||= []
                widget.allegati.each{ |f|
                    if f.input.file_vuoto? 
                        #carico errori
                        f.input.value = nil
                        errori <<  { 'campo' => f.id, 'msg' => 'File caricato vuoto!', 'input_type' => 'text' }
                    elsif f.input.has_value?
                        allegati_in_widget << File.basename(f.input.value).force_encoding('UTF-8')
                    else
                        #niente
                    end
                }
                
                #costruisco array di allegati presenti nella dir
                unless allegati_in_widget.blank?
                    folder = File.join(Spider.paths[:data],'/uploaded_files/moduli', modulo.hashdir_allegati)
                    if File.directory? folder
                        allegati_presenti = Dir.entries(folder).select{|f| !File.directory?(folder+"/"+f)}
                        #controllo se gli allegati sono diversi da quelli presenti nella dir
                        allegati_presenti.each{ |allegato| 
                            if !allegati_in_widget.include?(allegato) && !allegato.blank?
                                FileUtils.remove(File.join(Spider.paths[:data],'/uploaded_files/moduli', modulo.hashdir_allegati, allegato)) 
                            end        
                        }
                    end
                end

                



                #se non ci sono errori per campi obbligatori e non ci sono errori negli importi salvo e redirect
                if errori.blank? #&& errori_importi.blank? && errore_importi_solo_uno.blank? && errore_importi_almeno_uno.blank?
                    #se arrivo da pratiche salvo l'id della pratica
                    modulo.id_pratica = id_pratica unless id_pratica.blank?
                    #se arrivo da iscrizioni salvo l'id dell'iscrizione
                    modulo.id_iscrizione = id_iscrizione unless id_iscrizione.blank?
                    #carico i dati dei campi
                    modulo.dati = @request.session['dati_post_sessione_widget_serializzata'].to_json.gsub('\"',"&quot\;")
                    #in questi casi creo numero del modulo, inserisco pagamenti e invio modulo se configurato
                    
                    if ['confermato','da_firmare','da_pagare','da_iscrizioni'].include?(tipo_salvataggio)

                        if !modulo.tipo_modulo.categoria_giuridica.blank? && tipo_salvataggio != 'bozza'
                            modulo.completare_servizi_titoli = true #devo completare i servizi e titoli. Se chiudo il browser posso riprendere la compilazione                 
                            #salvo un titolo di studio in base alla cat del modulo
                            modulo.titoli_studio = json_titolo_studio_necessario(modulo.tipo_modulo.categoria_giuridica.nome)
                            #modulo.save -> salva dopo
                        end
                        
                        #calcolo il numero del modulo se salvo come confermato
                        max_codice_modulo = nil
                        begin
                            Moduli::ModuloSalvato.storage.start_transaction
                            ##DA RIVEDERE CALCOLO DEL MAX
                            # cond_max = Spider::Model::Condition.new
                            # cond_max = cond_max.and{ |mod| ((mod.stato == 'confermato') | (mod.stato == 'inviato') | (mod.stato == 'da_firmare')) & (mod.tipo_modulo == modulo.tipo_modulo) }
                            # #calcolo il max passando la condition
                            # request_max_num_modulo = Spider::Model::Request.strict([Spider::QueryFuncs::Max(:numero_modulo).as(:max_numero_modulo)])
                            # query_max_num_modulo = Spider::Model::Query.new(cond_max, request_max_num_modulo)
                            # max_num_modulo_qs =  Moduli::ModuloSalvato.mapper.find(query_max_num_modulo, nil, {:no_expand_request => true}) 
                            # max_num_modulo = max_num_modulo_qs[0][:max_numero_modulo].blank? ? 1 : max_num_modulo_qs[0][:max_numero_modulo]+1
                            # #vecchio calcolo
                            # # max_codice_modulo_qs = Moduli::ModuloSalvato.where{ |mod| ((mod.stato == 'confermato') | (mod.stato == 'inviato') | (mod.stato == 'da_firmare')) & (mod.tipo_modulo == Moduli::TipoModulo.new(modulo.tipo_modulo.id.to_i)) }.order_by(:numero_modulo, :asc) 
                            # # max_codice_modulo = max_codice_modulo_qs.blank? ? 0 : max_codice_modulo_qs.last.numero_modulo
                            # # modulo.numero_modulo = max_codice_modulo+1
                            # modulo.numero_modulo = max_num_modulo


                            #Determino il tipo di autenticazione ai fini della registrazione dello SpidCode
                            spid_code = nil
                            spid_auth = false
                            if (!@request.session[:auth].blank? && (!@request.session[:auth]["Portal::UtenteSpidAgid"].blank? || !@request.session[:auth]["Portal::UtenteFederaEmiliaRomagna"].blank?))
                                if !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?
                                    spid_id = @request.session[:auth]["Portal::UtenteSpidAgid"][:id]                            
                                    user_agid = Portal::UtenteSpidAgid.load(:id => spid_id)
                                    spid_code = user_agid.spid_code
                                else
                                    spid_id = @request.session[:auth]["Portal::UtenteFederaEmiliaRomagna"][:id]
                                    user_agid = Portal::UtenteFederaEmiliaRomagna.load(:id => spid_id)
                                    spid_code = user_agid.tracciature_federa.last.spid_code
                                end    
                                spid_auth = true
                            end
                            modulo.spid_code = spid_code


                            modulo.save
                            Moduli::ModuloSalvato.storage.commit
                            #controllo che nella cartella hashdir ci siano gli allegati presenti nella widget
                            array_allegati_da_trovare = []
                            @request.session['dati_post_sessione_widget_serializzata'].each_pair{ |k,v| array_allegati_da_trovare << "#{v}" if k.to_s =~ /allegato_\d+/  }
                            unless array_allegati_da_trovare.blank?
                                folder_allegati = File.join(Spider.paths[:data],'/uploaded_files/moduli', modulo.hashdir_allegati)
                                array_allegati_da_trovare.each{ |allegato| 
                                    unless File.exists?(File.join(folder_allegati,allegato))
                                        #salvo in bozza per poterlo far ricaricare all'utente
                                        modulo.stato = 'bozza'
                                        modulo.confermato = nil
                                        modulo.save
                                        Spider.logger.error "\n\n Errore caricamento allegato, utente #{@request.utente_portale.id.to_s}, #{@request.utente_portale.nome} #{@request.utente_portale.cognome}. Manca l'allegato #{allegato} in dir #{modulo.hashdir_allegati} " 
                                        @request.session.flash['errore'] = "Errore in salvataggio allegati. Ricaricarli usando il link 'Cambia' a fianco del nome del file."
                                        #se ho già l'id nell'action uso quella
                                        redirect (@request.action =~ /\d$/).nil?  ? @request.action+"/"+modulo.id.to_s : @request.action
                                        done
                                    end 
                                }
                            end
                            # invia sempre la mail se non serve firma,non da pagare e non si devono completare servizi e titoli
                            if ( modulo.tipo_modulo.tipo_firma.blank? || ( !modulo.tipo_modulo.tipo_firma.blank? && modulo.tipo_modulo.tipo_firma.id.blank?) || ( !modulo.tipo_modulo.tipo_firma.blank? && !modulo.tipo_modulo.tipo_firma.id.blank? && (!@request.session[:auth].blank? && \
                                !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?) ) ) && !modulo_da_pagare && (modulo.completare_servizi_titoli == false || modulo.completare_servizi_titoli == nil) 
                                invio_modulo_email(modulo.id)
                            end
                        rescue Exception => exc
                            #salvo in bozza per poterlo far correggere all'utente
                            modulo.stato = 'bozza'
                            modulo.confermato = nil
                            modulo.save
                            messaggio =  "#{exc.message}"
                            messaggio_log = messaggio
                            exc.backtrace.each{|riga_errore| 
                                messaggio_log += "\n\r#{riga_errore}" 
                            } 
                            Spider.logger.error("Errore in salvataggio o invio modulo: "+messaggio_log)
                            Moduli::ModuloSalvato.storage.rollback if Moduli::ModuloSalvato.storage.in_transaction?
                            @request.session.flash['errore'] = "Errore in salvataggio o invio modulo."
                            redirect self.class.http_s_url
                        end
                        
                        id_modulo = modulo.id
                        #inserisco gli importi per il pagamento
                        if defined?(Pagamenti) != nil && !modulo.importi.blank?
                            importi_collegati = JSON.parse(modulo.importi)
                            importi_collegati_raggruppati = {}
                            #raggruppo gli importi per stesso tipo dovuto
                            importi_collegati.each_pair{ |indice_importo,valore|
                                next if valore == "0,00"
                                importo_tipo_modulo = Moduli::Importo.where(:id => indice_importo).first
                                next if importo_tipo_modulo.blank? #se non trovo in db un importo con questo id faccio il next
                                id_tipo_dov = importo_tipo_modulo.tipo_dovuto.id.to_s
                                importi_collegati_raggruppati[id_tipo_dov] ||= {}
                                importi_collegati_raggruppati[id_tipo_dov]['array_importi'] ||= []
                                importi_collegati_raggruppati[id_tipo_dov]['array_importi'] << importo_tipo_modulo
                                importi_collegati_raggruppati[id_tipo_dov]['totale_importi_stesso_tipo_dovuto'] ||= BigDecimal.new(0)
                                importi_collegati_raggruppati[id_tipo_dov]['totale_importi_stesso_tipo_dovuto'] += BigDecimal.new(valore.gsub(".","").gsub(",","."))
                                importi_collegati_raggruppati[id_tipo_dov]['codici_stesso_tipo_dovuto'] ||= ""
                                importi_collegati_raggruppati[id_tipo_dov]['codici_stesso_tipo_dovuto'] += importo_tipo_modulo.codice+" "
                            }
                            
                            begin 
                                Pagamenti::Pagamento.storage.start_transaction
                                Spider::Model.in_unit do |uow|
                                    importi_collegati_raggruppati.each_pair{ |id_tipo_dovuto,importi_stesso_tipo_dovuto|
                                        #se da pagare salvo una riga in pagamenti
                                        params = {
                                            'utente' => @request.utente_portale,
                                            'cancellabile' => false,
                                            #parametri per anagrafica versante
                                            'tipo_anagrafica' => @request.utente_portale.ditta.blank? ? 'F' : 'G',
                                            'nome' => @request.utente_portale.nome,
                                            'cognome' => @request.utente_portale.cognome,
                                            'codice_fiscale' => @request.utente_portale.codice_fiscale,
                                            'via_residenza' => @request.utente_portale.indirizzo_residenza,
                                            'civico_residenza' => @request.utente_portale.civico_residenza,
                                            'comune_residenza' => @request.utente_portale.comune_residenza,
                                            'cap_residenza' => @request.utente_portale.cap_residenza,
                                            'prov_residenza' => @request.utente_portale.provincia_residenza,
                                            'nazione_residenza' => 'IT',
                                            'email' => @request.utente_portale.email,
                                            #fine parametri per anagrafica versante
                                            #parametri per anagrafica pagatore
                                            'tipo_persona_pagatore' => @request.utente_portale.ditta.blank? ? 'F' : 'G',
                                            'nome_pagatore' => @request.utente_portale.nome,
                                            'cognome_pagatore' => @request.utente_portale.cognome,
                                            'codice_fiscale_pagatore' => @request.utente_portale.codice_fiscale,
                                            'via_residenza_pagatore' => @request.utente_portale.indirizzo_residenza,
                                            'civico_residenza_pagatore' => @request.utente_portale.civico_residenza,
                                            'comune_residenza_pagatore' => @request.utente_portale.comune_residenza,
                                            'cap_residenza_pagatore' => @request.utente_portale.cap_residenza,
                                            'prov_residenza_pagatore' => @request.utente_portale.provincia_residenza,
                                            'nazione_residenza_pagatore' => 'IT',
                                            'email_pagatore' => @request.utente_portale.email,
                                            #fine parametri per anagrafica pagatore
                                            'iuv' => nil,
                                            'descrizione' => "Istanza #{id_modulo},Codici: #{importi_stesso_tipo_dovuto['codici_stesso_tipo_dovuto']}",
                                            'importo' => importi_stesso_tipo_dovuto['totale_importi_stesso_tipo_dovuto'],
                                            'id_elemento' => "istanza_#{id_modulo}_#{id_tipo_dovuto}",
                                            
                                            'tipo_elemento' =>  importi_stesso_tipo_dovuto['array_importi'].first.tipo_dovuto.tipo_elemento,
                                            'codice_applicazione' => importi_stesso_tipo_dovuto['array_importi'].first.tipo_dovuto.applicazione.codice,
                                            'cod_procedura' => importi_stesso_tipo_dovuto['array_importi'].first.tipo_dovuto.applicazione.codice_calcolo_iuv, 
                                            
                                            'tipo_dovuto' => id_tipo_dovuto,
                                            'data_scadenza' => nil
                                            
                                        }
                                        id_pagamento,iuv = Pagamenti.inserisci_pagamento_pagopa(params)
                                        
                                    }
                                    Pagamenti::Pagamento.storage.commit
                                end
                                
                                pagamenti_modulo_corrente = Pagamenti::Pagamento.where{|pag| (pag.id_elemento .ilike "istanza_#{id_modulo}_%")  }
                                #SALVATAGGIO STRANO, CAMPO MANY CHE DA ERRORE: *** NoMethodError Exception: undefined method `[]' for nil:NilClass
                                unless pagamenti_modulo_corrente.blank?
                                    modulo.pagamenti_collegati = pagamenti_modulo_corrente.to_a
                                    modulo.save
                                end
                            rescue Exception => exc
                                messaggio =  "#{exc.message}"
                                messaggio_log = messaggio
                                exc.backtrace.each{|riga_errore| 
                                    messaggio_log += "\n\r#{riga_errore}" 
                                } 
                                Spider.logger.error("Errore in salvataggio pagamenti collegati a modulo #{id_modulo}: #{messaggio_log}")
                                Pagamenti::Pagamento.storage.rollback
                                #cancello il modulo precedentemente salvato
                                modulo.delete
                                @request.session.flash['errore'] = "Errore in salvataggio pagamenti collegati al modulo."
                                redirect self.class.http_s_url
                            end

                        end
                            
                    else
                        modulo.save
                    end
                    #GESTIONE REDIRECT FINALE IN BASE AL TIPO DI SALVATAGGIO
                    if tipo_salvataggio_iniziale != tipo_salvataggio && tipo_salvataggio_iniziale == 'da_iscrizioni'
                        #stampo il modulo e torno su muse
                        stampa("iscrizioni",modulo.id.to_s)
                        redirect @request.params['url_ritorno']+"/iscrizione_eseguita"
                    else
                        #se sto usando pratiche edilizie stampo il modulo
                        if tipo_salvataggio == 'da_pratiche'
                            #stampo il modulo 
                            stampa("pratiche",modulo.id.to_s)
                            redirect @request.params['url_ritorno']
                        elsif tipo_salvataggio == 'da_iscrizioni'
                            #stampo il modulo 
                            stampa("iscrizioni",modulo.id.to_s)
                            redirect @request.params['url_ritorno']+"/iscrizione_eseguita"
                        elsif !modulo.tipo_modulo.categoria_giuridica.blank? && tipo_salvataggio != 'bozza'
                            modulo.completare_servizi_titoli = true #devo completare i servizi e titoli. Se chiudo il browser posso riprendere la compilazione
                            modulo.save
                            redirect self.class.http_s_url("servizi_svolti?id_modulo=#{modulo.id}")
                        elsif tipo_salvataggio == 'da_firmare' #devo apporre la firma sul modulo
                            redirect self.class.http_s_url("firma_modulo?id_modulo=#{modulo.id}")
                        else  #tipo_salvataggio == 'da_pagare' o confermato o bozza
                            #@request.session["modulo_#{percorso}"] = modulo.chiave
                            @request.session.flash['esito_azione'] = "Il modulo è stato salvato con successo."
                            redirect self.class.http_s_url
                        end
                    end
                else
                    #carico in un campo che poi controllo da js
                    widget.scene.errori = errori.to_json
                    #ho gli errori di obbligatorieta o negli importi inseriti dal cittadino, ricarico i dati nella widget
                    widget.scene.errori_importi = errori_importi
                    widget.scene.errore_importi_solo_uno = errore_importi_solo_uno
                    widget.scene.errore_importi_almeno_uno = errore_importi_almeno_uno

                    #carico i riquadri opzionali che erano stati attivati
                    widget.scene.riquadri_opzionali = hash_riquadri_opzionali.to_json.gsub("\"","'") unless hash_riquadri_opzionali.blank?

                    #ricarico gli allegati
                    widget.aggiorna_dati = @request.session['dati_post_sessione_params']
                    
                    widget.dati(dati_post, modulo.dimensioni)
                    
                    #ricarico le scene
                    #CONSIDERO DI ESSERE NEL CASO PRATICHE
                    if @request.params['proseguo'] == 'true' && !@request.params['id_pratica'].blank?
                        #carico nella scene i valori per ritornare a pratiche e per le verifiche
                        url_ritorno = @request.params['url_ritorno']
                        widget.scene.url_ritorno = url_ritorno+'&completo=no'
                        widget.scene.da_pratiche = true
                        widget.scene.id_pratica = @request.params['id_pratica']
                        #aggiungo i dati nella scene della sezione attiva, passando i l'hash_dati, e dello stato
                        #ricavo il nome del gestore che POTREBBE essere presente
                        nome_classe_gestore = 'Gestore'+tipo_modulo.tipo.split('_').each{|v| v.capitalize! }.join
                        #recupero le configurazioni della pratica dal file config.xml
                        hash_conf_pratica = get_hash_config(@request.params['id_pratica'])
                        #se non riesco a fare il caricamento dei dati dall'xml scrivo un log di errore e ritorno a pratiche
                        begin
                            hash_dati_utente = Moduli.const_get(nome_classe_gestore).carica_dati_modulo(@request.params, hash_conf_pratica) if Moduli.const_defined?(nome_classe_gestore)
                            unless tipo_modulo.contenuto_modulo.blank?
                                allegati_associati = tipo_modulo.allegati_associati
                                eventi_associati = tipo_modulo.eventi_associati
                                interventi_associati = tipo_modulo.interventi_associati
                                #metto in pagina hash per eventi e interventi
                                widget.scene.eventi_associati_sezioni = eventi_associati
                                widget.scene.interventi_associati_sezioni = interventi_associati
                                hash_dati_utente = Moduli::Funzioni.carica_dati_modulo_editor(@request.params, hash_conf_pratica, allegati_associati)
                                #passo il codice dell'evento e dell'intervento
                                widget.scene.codice_evento_da_pratiche = hash_dati_utente['codice_evento']
                                widget.scene.codice_intervento_da_pratiche = hash_dati_utente['codice_intervento']
                            end
                            #Carico nella scene della variabili che cambiano da modulo a modulo, con condizioni sui dati da pratiche eventualmente
                            widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene, hash_dati_utente) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)
                        rescue Exception => e
                            Spider.logger.error "Caricamento dati da xml in errore: #{e.message}, #{e.backtrace}"
                            redirect url_ritorno+'&errore=caricamento_dati_xml'
                        end
                        #carico i path degli allegati
                        if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:get_url_allegati)
                            widget.scene.url_allegati_pdf = Moduli.const_get(nome_classe_gestore).get_url_allegati(id_pratica) 
                        else
                            widget.scene.url_allegati_pdf = Moduli::Funzioni.url_allegati(id_pratica)
                        end

                    end
                    #CASO ISCRIZIONI
                    if @request.params['per_iscrizioni'] == 'true' && !@request.params['id_iscrizione'].blank?
                        #carico nella scene i valori per ritornare a pratiche e per le verifiche
                        pk = @request.params['pk']
                        url_ritorno = @request.params['url_ritorno']
                        widget.scene.url_ritorno = url_ritorno+"?pk=#{pk}&completo=no"
                        widget.scene.da_iscrizioni = true
                        widget.scene.id_iscrizione = @request.params['id_iscrizione']
                        #aggiungo i dati nella scene della sezione attiva, passando i l'hash_dati, e dello stato
                        #se non riesco a fare il caricamento dei dati dall'xml scrivo un log di errore e ritorno a pratiche
                        begin
                            unless tipo_modulo.contenuto_modulo.blank?
                                allegati_associati = tipo_modulo.allegati_associati
                                hash_dati_utente = Moduli::Funzioni.carica_dati_modulo_editor_iscrizioni(@request.params, allegati_associati)
                            end
                        rescue Exception => e
                            Spider.logger.error "Caricamento dati da xml in errore: #{e.message}, #{e.backtrace}"
                            redirect url_ritorno+"?pk=#{pk}&errore=caricamento_dati_xml"
                        end
                        #carico i path degli allegati
                        widget.scene.url_allegati_pdf = Moduli::Funzioni.url_allegati(id_iscrizione)

                    end

                    #standard se tutte e due gli altri sono false
                    widget.scene.standard = !(widget.scene.da_iscrizioni || widget.scene.da_pratiche)


                end

            elsif modulo
                #STO CARICANDO UN FILE SALVATO E POSSO AVER AVUTO DEGLI ERRORI AL SALVATAGGIO CONFERMATO
                #creo l'hash con i dati del modulo
                hash_widget_dati = JSON.parse(modulo.dati)
                
                #ricavo il nome del gestore che POTREBBE essere presente
                nome_classe_gestore = 'Gestore'+tipo_modulo.tipo.split('_').each{|v| v.capitalize! }.join
                #Carico nella scene della variabili che cambiano da modulo a modulo
                widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)
                
                #caso in cui voglio scaricare l'allegato
                if !@request.params['_wt'].blank? && @request.params['_we'] == 'view_file'               
                    widget.aggiorna_dati = @request.session['dati_post_sessione_params']
                    widget.dati(@request.session['dati_post_sessione_widget_serializzata'], nil)
                end

                #standard a true, con iscrizioni e pratiche non posso caricare un modulo salvato in bozza
                widget.scene.standard = true

            else
                #SONO IN GET
                #nuovo modulo, carico i dati dell'utente nella widget

                #ricavo il nome del gestore che POTREBBE essere presente
                nome_classe_gestore = 'Gestore'+tipo_modulo.tipo.split('_').each{|v| v.capitalize! }.join
                #può arrivare un id_pratica da pratiche edilizie o un id_iscrizione per iscrizioni on line da Muse
                id_pratica = @request.params['id_pratica']
                id_iscrizione = @request.params['id_iscrizione']
                url_ritorno = @request.params['url_ritorno']
                #caso in cui chiamo un modulo da pratiche edilizie
                if !id_pratica.blank? && !url_ritorno.blank?
                                    
                    hash_conf_pratica = get_hash_config(id_pratica.to_s)
                    #se non riesco a fare il caricamento dei dati dall'xml scrivo un log di errore e ritorno a pratiche
                    begin
                        #caso con template del modulo creato da programmatore
                        hash_dati_utente = Moduli.const_get(nome_classe_gestore).carica_dati_modulo(@request.params, hash_conf_pratica) if Moduli.const_defined?(nome_classe_gestore)
                        #caso con template del modulo creato con editor
                        unless tipo_modulo.contenuto_modulo.blank?
                            allegati_associati = tipo_modulo.allegati_associati
                            eventi_associati = tipo_modulo.eventi_associati
                            interventi_associati = tipo_modulo.interventi_associati
                            #metto in pagina hash per eventi e interventi
                            widget.scene.eventi_associati_sezioni = eventi_associati
                            widget.scene.interventi_associati_sezioni = interventi_associati
                            hash_dati_utente = Moduli::Funzioni.carica_dati_modulo_editor(@request.params, hash_conf_pratica, allegati_associati)
                            #passo il codice dell'evento e dell'intervento
                            widget.scene.codice_evento_da_pratiche = hash_dati_utente['codice_evento']
                            widget.scene.codice_intervento_da_pratiche = hash_dati_utente['codice_intervento']

                        end
                        #Carico nella scene della variabili che cambiano da modulo a modulo, con condizioni sui dati da pratiche eventualmente
                        widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene, hash_dati_utente) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)

                    rescue Exception => e
                        Spider.logger.error "Caricamento dati da xml in errore: #{e.message}, #{e.backtrace}"
                        redirect url_ritorno+'&errore=caricamento_dati_xml'
                    end
                    
                    #carico i path degli allegati
                    if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:get_url_allegati)
                        widget.scene.url_allegati_pdf = Moduli.const_get(nome_classe_gestore).get_url_allegati(id_pratica)  #CARICO DA GESTORE
                    else 
                        widget.scene.url_allegati_pdf = Moduli::Funzioni.url_allegati(id_pratica) #CARICO DA FUNZIONI, PER EDITOR
                    end

                    #carico nella scene i valori per ritornare a pratiche e per le verifiche
                    widget.scene.url_ritorno = url_ritorno+'&completo=no'
                    widget.scene.da_pratiche = true
                    widget.scene.id_pratica = id_pratica
                
                elsif !id_iscrizione.blank? && !url_ritorno.blank?
                    begin
                        #caso con template del modulo creato con editor
                        unless tipo_modulo.contenuto_modulo.blank?
                            allegati_associati = tipo_modulo.allegati_associati
                            hash_dati_utente = Moduli::Funzioni.carica_dati_modulo_editor_iscrizioni(@request.params, allegati_associati)
                        end
                        #Carico nella scene della variabili che cambiano da modulo a modulo, con condizioni sui dati da pratiche eventualmente
                        #widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene, hash_dati_utente) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)

                    rescue Exception => e
                        Spider.logger.error "Caricamento dati da xml in errore: #{e.message}, #{e.backtrace}"
                        redirect url_ritorno+'&errore=caricamento_dati_xml'
                    end
                    #carico i path degli allegati
                    if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:get_url_allegati)
                        widget.scene.url_allegati_pdf = Moduli.const_get(nome_classe_gestore).get_url_allegati(id_iscrizione)  #CARICO DA GESTORE
                    else 
                        widget.scene.url_allegati_pdf = Moduli::Funzioni.url_allegati(id_iscrizione) #CARICO DA FUNZIONI, PER EDITOR
                    end

                    #carico nella scene i valori per ritornare a iscrizioni on line e per le verifiche
                    widget.scene.url_ritorno = url_ritorno+"?pk=#{id_iscrizione}&completo=no"
                    widget.scene.da_iscrizioni = true
                    widget.scene.id_iscrizione = id_iscrizione


                else
                    #sono in un get di Moduli, non da Pratiche o da Iscrizioni
                    widget.scene.standard = true
                    if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:carica_dati_registrazione) 
                       #carico i dati di registrazione 
                        hash_dati_utente = Moduli.const_get(nome_classe_gestore).carica_dati_registrazione(utente)
                    else
                        hash_dati_utente = 
                            { "dati_utente"=>
                                  { "data_nascita" => utente.data_nascita.lformat(:short),
                                    "luogo_residenza" => utente.comune_residenza,
                                    "prov_residenza" => utente.provincia_residenza,
                                    "prov_nascita" => utente.provincia_nascita,
                                    "luogo_nascita" => utente.comune_nascita,
                                    "stato_nascita" => utente.stato_nascita,
                                    "codice_fiscale" =>  (utente.codice_fiscale.include?('EE_') ? "NON DISPONIBILE" :  utente.codice_fiscale),
                                    "indirizzo_residenza" => utente.indirizzo_residenza,
                                    "civico_residenza" => utente.civico_residenza,
                                    "nome_cognome" => utente.cognome+" "+utente.nome,
                                    "nome" => utente.nome,
                                    "cognome" => utente.cognome,
                                    "numero_telefono" => utente.telefono, # prima era ( utente.telefono.blank? ? utente.cellulare : utente.telefono ), dopo modificato per CHIAMPO
                                    "cap" => utente.cap_residenza,
                                    "email" => utente.email,
                                    "cellulare" => utente.cellulare,
                                    "doc_rilasciato_da" => utente.documento_rilasciato,
                                    "tipo_documento" => utente.tipo_documento.to_s,
                                    "num_documento" => utente.numero_documento.to_s,
                                    "data_documento" => utente.data_documento.lformat(:short),
                                    "telefono" => utente.telefono,
                                    "pec" => utente.pec,
                                    "sesso" => utente.sesso.blank? ? '' : utente.sesso.id
                                  },
                                "dati_ditta" => {  
                                    "ragione_sociale_azienda" => utente.ditta.blank? ? '' : utente.ditta.ragione_sociale,
                                    "piva_azienda" => utente.ditta.blank? ? '' : utente.ditta.partita_iva,
                                    "cf_azienda" => utente.ditta.blank? ? '' : utente.ditta.codice_fiscale_azienda,
                                    "indirizzo_azienda" => utente.ditta.blank? ? '' : utente.ditta.indirizzo_azienda,
                                    "civico_azienda" => utente.ditta.blank? ? '' : utente.ditta.civico_azienda,
                                    "cap_azienda" => utente.ditta.blank? ? '' : utente.ditta.cap_azienda,
                                    "comune_azienda" => utente.ditta.blank? ? '' : utente.ditta.comune_azienda,
                                    "prov_azienda" => utente.ditta.blank? ? '' : utente.ditta.provincia_azienda,
                                    "tel_azienda" => utente.ditta.blank? ? '' : utente.ditta.telefono_azienda,
                                    "fax_azienda" => utente.ditta.blank? ? '' : utente.ditta.fax_azienda,
                                    "email_azienda" => utente.ditta.blank? ? '' : utente.ditta.email_azienda,
                                    "pec_azienda" => utente.ditta.blank? ? '' : utente.ditta.pec_azienda
                                    
                                }
                                #,
                                # "nominativo_firma" => {
                                #     "nome_cognome" => "#{utente.nome} #{utente.cognome}"
                                # }
                            }
                    end
                    #Carico nella scene della variabili che cambiano da modulo a modulo, con condizioni sui dati da pratiche eventualmente
                    widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene, hash_dati_utente) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)

                end
                hash_dati_utente['nominativo_firma'] = {"nome_cognome" => "#{utente.nome} #{utente.cognome}"}
                #carico le dimensioni salvate, se esistono
                dimensioni = modulo.dimensioni unless modulo.blank?
                #caso in cui voglio scaricare l'allegato
                if !@request.params['_wt'].blank? && @request.params['_we'] == 'view_file'               
                    widget.aggiorna_dati = @request.session['dati_post_sessione_params']
                    widget.dati(@request.session['dati_post_sessione_widget_serializzata'], nil)
                else
                    #carico i dati dell'utente portale
                    widget.dati(codifica_hash_utf8(hash_dati_utente).to_json, dimensioni)
                end
                
            end            
            render(tpl)
        end

        #ritorno l'html di una view con layout e scene
        def html_from_view(path_view, scene, tipo_layout,stylesheets_layout)
            tpl = Spider::Template.new(path_view)
            tpl.owner_class = self.class
            tpl.definer_class = self.class
            init_template(tpl)
            tpl.exec
            layout = init_layout(tipo_layout)
            layout.template = tpl
            layout.init(scene)
            html = StringIO.new
            $out.output_to(html) do
                layout.render
            end

            seen_assets = {}
            #layout.assets recupera i css del layout, layout.all_assets recupera anche i css dei moduli inclusi 
            #nel layout
            layout.assets = layout.assets.uniq { |item| item[:path] } #metto negli assets solo una copia degli assets
            layout.assets.select{ |a| 
                a[:type] == :css && File.extname(a[:src]) != '.scss' }.each do |ass|
                next if seen_assets[ass[:path]]
                stylesheets_layout << ass[:path]
                seen_assets[ass[:path]] = true
            end

            html.rewind
            html = html.read
            html
        end

        #controllo se ci sono dei totali negativi, ci deve essere un errore nel calcolo
        def punteggi_totali_negativi?(scena)
            return (!scena.totale_punteggio_servizi.blank? && scena.totale_punteggio_servizi < 0) || (!scena.totale_punteggio_titoli.blank? && scena.totale_punteggio_titoli < 0) || \
                (!scene.totale_punteggio_titoli_vari.blank? && scene.totale_punteggio_titoli_vari < 0)
        end


        __.html :template => 'servizi_svolti'
        def servizi_svolti
            @scene.link_indietro = self.class.http_s_url
            id_modulo = @request.params['id_modulo']
            @scene.id_modulo = id_modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            
            unless modulo.blank?
                id_tipo_modulo = modulo.tipo_modulo.id
                if modulo_scaduto?(id_tipo_modulo)
                    @request.session.flash['esito_azione'] = "Il modulo non è più compilabile perchè sono scaduti i termini di presentazione."
                    redirect self.class.http_s_url
                end
                #evito che vada a modificare i dati se il modulo inviato o non previsto che completi questi dati
                if modulo.stato == 'inviato' || !modulo.completare_servizi_titoli
                    @request.session.flash['esito_azione'] = "Il modulo non è più compilabile."
                    redirect self.class.http_s_url
                end

                #ricavo cat giuridica del modulo (se presente)
                @scene.cat_giuridica = modulo.tipo_modulo.categoria_giuridica.nome
                #mi passo nella scene la data di pubblicazione
                @scene.data_pubblicazione_bando = modulo.tipo_modulo.disponibile_dal
                @scene.data_fine_pubblicazione_bando = modulo.tipo_modulo.disponibile_al

                #vedo se ci sono servizi svolti salvati
                @scene.servizi_svolti = JSON.parse(modulo.servizi_svolti) unless modulo.servizi_svolti.blank?
                #calcolo il totale dei servizi presso pa e lo mette in @scene.totale_punteggio_servizi
                calcola_punteggio_servizi(@scene,@scene.servizi_svolti) unless @scene.servizi_svolti.blank?

                #vedo se ci sono titoli di studio salvati
                @scene.titoli_studio = JSON.parse(modulo.titoli_studio) unless modulo.titoli_studio.blank?
                #calcolo il totale dei titoli di studio e lo mette in @scene.totale_punteggio_titoli
                calcola_punteggio_titoli(@scene,@scene.titoli_studio) unless @scene.titoli_studio.blank?

                #vedo se ci sono titoli vari salvati
                @scene.titoli_vari = JSON.parse(modulo.titoli_vari) unless modulo.titoli_vari.blank?
                #calcolo il totale dei titoli vari e lo mette in @scene.totale_punteggio_titoli_vari
                calcola_punteggio_titoli_vari(@scene,@scene.titoli_vari) unless @scene.titoli_vari.blank?
                @scene.errori = []
                @scene.errori << "Sono presenti delle esperienze lavorative contemporanee.<br />
                Si può inserire solo un esperienza lavorativa per un determinato periodo.Se hai lavorato contemporaneamente per più amministrazioni,\
                inserisci solo quella che ai fini della valutazione ha il migliore gradimento, anche spezzando il periodo di riferimento facendo attenzione\
                alla data di inizio e fine. Il periodo completo inseriscilo nel campo note." unless verifica_intervalli_esperienze_lavorative(id_modulo).blank?

                @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)

                @scene.da_firmare = modulo.stato == 'da_firmare'
                if @request.post?
                    #salvo i punteggi
                    modulo.punteggio_servizi = @request.params['totale_punteggio_servizi'].to_f unless @request.params['totale_punteggio_servizi'].blank?
                    modulo.punteggio_titoli = @request.params['totale_punteggio_titoli'].to_f unless @request.params['totale_punteggio_titoli'].blank?
                    modulo.punteggio_titoli_vari = @request.params['totale_punteggio_titoli_vari'].to_f unless @request.params['totale_punteggio_titoli_vari'].blank?
                    modulo.punteggio_totale = (modulo.punteggio_servizi.to_d + modulo.punteggio_titoli.to_d + modulo.punteggio_titoli_vari.to_d).round(6)
                    #cambio la data di conferma se dovevo completare i bandi
                    modulo.confermato = DateTime.now
                    #cambio il flag perchè ora non devo più completare per i bandi
                    modulo.completare_servizi_titoli = false
                    #devo creare due pdf, uno con titoli e uno con servizi
                    
                    hashdir_moduli_allegati = modulo.hashdir_allegati
                    tpl_path_servizi = File.join(Spider.paths[:apps],'moduli/views/_box_servizi_pa.shtml')
                    tpl_path_titoli = File.join(Spider.paths[:apps],'moduli/views/_box_titoli.shtml')
                    tpl_path_titoli_vari = File.join(Spider.paths[:apps],'moduli/views/_box_titoli_vari.shtml')
                    @stylesheet_layout = []
                    html_servizi = html_from_view(tpl_path_servizi, @scene, 'stampa', @stylesheet_layout)
                    html_titoli = html_from_view(tpl_path_titoli, @scene, 'stampa', @stylesheet_layout)
                    html_titoli_vari = html_from_view(tpl_path_titoli_vari, @scene, 'stampa', @stylesheet_layout)

                    path_wkhtmltopdf = `which wkhtmltopdf`.gsub(/\n/, '')
                    path_wkhtmltopdf = "/usr/local/bin/wkhtmltopdf" if path_wkhtmltopdf.blank?

                    str_spid_code = (modulo.spid_code.blank? ? "" : "Token SPID #{modulo.spid_code}")

                    #trasformazioni per dimensioni fogli e pixel
                    #http://www.format-papier-a0-a1-a2-a3-a4-a5.fr/format-a4/dimensions-a4-en-pixels-par-resolutions.php
                    PDFKit.configure do |config|
                        config.wkhtmltopdf = path_wkhtmltopdf
                        config.default_options = {
                            :page_size => 'A4',
                            :print_media_type => true,
                            #:disable_smart_shrinking => false,
                            #:quiet => true,
                            #:margin_top => '0.75in',
                            #:margin_right => '0.75in',
                            #:margin_bottom => '0.75in',
                            #:margin_left => '0.75in',
                            :footer_spacing => '4',
                            :footer_font_size => '9',
                            :encoding => 'UTF-8',
                            :footer_left => "Documento n. #{id_modulo}, chiave di controllo #{modulo.chiave}. #{str_spid_code}"
                            #:user_style_sheet => @stylesheet_layout.uniq
                        }
                        #config.root_url = Spider.conf.get('site.domain') #use only if your external hostname is unavailable on the server.
                    end
                    #kit = PDFKit.new(html, :page_size => 'A4', :minimum_font_size => 14, :encoding => 'UTF-8')
                    kit_servizi = PDFKit.new(html_servizi)
                    kit_titoli = PDFKit.new(html_titoli)
                    kit_titoli_vari = PDFKit.new(html_titoli_vari)

                    kit_servizi.stylesheets = @stylesheet_layout.uniq
                    kit_titoli.stylesheets = @stylesheet_layout.uniq
                    kit_titoli_vari.stylesheets = @stylesheet_layout.uniq

                    path_servizi_titoli_pdf  = File.join(Spider.paths[:data],'/uploaded_files/moduli/',hashdir_moduli_allegati,'stampa_modulo')
                    FileUtils.mkdir_p(path_servizi_titoli_pdf) unless File.directory?(path_servizi_titoli_pdf)
                    
                    allegato_a = File.join(path_servizi_titoli_pdf,"allegato_a.pdf")
                    allegato_b = File.join(path_servizi_titoli_pdf,"allegato_b.pdf")
                    allegato_c = File.join(path_servizi_titoli_pdf,"allegato_c.pdf")
                    #scrivo gli allegati
                    pdf_servizi = kit_servizi.to_file(allegato_a)
                    pdf_titoli = kit_titoli.to_file(allegato_b)
                    pdf_titoli_vari = kit_titoli_vari.to_file(allegato_c)
                    
                    modulo.save
                    if modulo.stato == 'da_firmare'
                        redirect self.class.http_s_url("firma_modulo?id_modulo=#{modulo.id}")
                    elsif modulo.stato == 'da_pagare' #tipo_salvataggio == 'da_pagare'
                        #se non 
                        @request.session.flash['esito_azione'] = "Il modulo è stato salvato con successo."
                        redirect self.class.http_s_url
                    else #modulo confermato, non devo pagare o firmare, invio il modulo
                        invio_modulo_email(modulo.id)
                        @request.session.flash['esito_azione'] = "Il modulo è stato salvato e inviato con successo."
                        redirect self.class.http_s_url
                    end
                end

            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end

        end


        #arrivanoi dati dei servizi, li inserisco e visualizzo la lista dei servizi
        #@request.params
#{"tipo_amministrazione"=>"ammin_prov", "amministrazione"=>"asd asdasd", "cat_giuridica"=>"A", "inizio_servizio"=>"23/08/2017", "fine_servizio"=>"30/08/2017", "id_modulo"=>"111"}

        __.html 
        def box_servizi_pa
            id_modulo = @request.params['id_modulo']
            @scene.id_modulo = id_modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            
            unless modulo.blank?
                begin
                    cat_modulo = modulo.tipo_modulo.categoria_giuridica.nome
                    #tolgo dai params l'id_modulo
                    hash_servizio = @request.params
                    hash_servizio.delete("id_modulo")
                    #calcolo i giorni da inizio a fine del servizio
                    data_inizio = DateTime.strptime(@request.params["inizio_servizio"], '%d/%m/%Y')
                    data_fine = DateTime.strptime(@request.params["fine_servizio"], '%d/%m/%Y')
                    #uso metodo mjd
                    #http://ruby-doc.org/stdlib-2.3.1/libdoc/date/rdoc/Date.html#method-i-mjd
                    giorni_di_servizio = (data_fine.to_date.mjd - data_inizio.to_date.mjd)+1 #considero i giorni compresi 
                    hash_servizio['giorni_di_servizio'] = giorni_di_servizio
                    #se non ho servizi già salvati salvo questo
                    if modulo.servizi_svolti.blank?
                        hash_servizi = {}
                        hash_servizio['id_inc'] = 1
                    else
                        #carico i servizi presenti e aggiungo questo
                        hash_servizi = JSON.parse(modulo.servizi_svolti)
                        hash_servizio['id_inc'] = hash_servizi.keys.collect{|key| key.to_i }.max.to_i+1
                    end
                    hash_servizio['punteggio'] = Moduli.calcola_punteggio_esperienza_lavorativa(cat_modulo,hash_servizio['cat_giuridica'],giorni_di_servizio,hash_servizio['tipo_amministrazione'],hash_servizio['rid_lavorativa'])
                    hash_servizi[hash_servizio['id_inc']] = hash_servizio
                    #calcolo il totale dei servizi svolti
                    calcola_punteggio_servizi(@scene,hash_servizi)
                    modulo.servizi_svolti = hash_servizi.convert_object.to_json
                    modulo.save
                    @scene.errori = []
                    @scene.errori << "Sono presenti delle esperienze lavorative contemporanee.<br />
            Si può inserire solo un esperienza lavorativa per un determinato periodo.Se hai lavorato contemporaneamente per più amministrazioni,\
             inserisci solo quella che ai fini della valutazione ha il migliore gradimento, anche spezzando il periodo di riferimento facendo attenzione\
              alla data di inizio e fine. Il periodo completo inseriscilo nel campo note." unless verifica_intervalli_esperienze_lavorative(id_modulo).blank?

                    @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)

                    @scene.servizi_svolti = JSON.parse(modulo.servizi_svolti) unless modulo.servizi_svolti.blank?
                rescue Exception => exc
                    messaggio_log = "#{exc.message}"
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    }
                    @scene.errori ||= [] 
                    @scene.errori << (Spider.runmode == 'devel') ?  messaggio_log : "Errore nell'inserimento: #{exc.message}"  
                    @scene.servizi_svolti = JSON.parse(modulo.servizi_svolti) unless modulo.servizi_svolti.blank?
                end
                
                
                render '_box_servizi_pa', :layout => nil

            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end


        def verifica_intervalli_esperienze_lavorative(id_modulo)
            errori = {}
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            esperienze_lavorative = JSON.parse(modulo.servizi_svolti) unless modulo.servizi_svolti.blank?
            unless esperienze_lavorative.blank?
                esperienze_lavorative.each_pair{ |id_esperienza, hash_dati|
                    data_inizio_corrente = Date.strptime(hash_dati['inizio_servizio'], '%d/%m/%Y')
                    data_fine_corrente = Date.strptime(hash_dati['fine_servizio'], '%d/%m/%Y')
                    #controllo le esp da id successivo
                    for indice in (id_esperienza.to_i+1)..esperienze_lavorative.keys.collect{|key| key.to_i }.max.to_i do
                        unless esperienze_lavorative[indice.to_s].blank?
                            data_inizio_n = Date.strptime(esperienze_lavorative[indice.to_s]['inizio_servizio'], '%d/%m/%Y')
                            data_fine_n = Date.strptime(esperienze_lavorative[indice.to_s]['fine_servizio'], '%d/%m/%Y')
                            #funzione che confronta due range di date e ritorna se sovrapposti
                            if (data_inizio_corrente..data_fine_corrente).overlaps?(data_inizio_n..data_fine_n)
                                errori[id_esperienza] = indice.to_s
                                errori[indice.to_s] = id_esperienza
                            end
                        end
                    end
                }
            end
            return errori
        end


        __.html 
        def box_titoli_studio
            id_modulo = @request.params['id_modulo']
            @scene.id_modulo = id_modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            unless modulo.blank?
                #tolgo dai params l'id_modulo
                hash_titolo = @request.params
                hash_titolo.delete("id_modulo")
                
                #se non ho servizi già salvati salvo questo
                if modulo.titoli_studio.blank?
                    hash_titoli = {}
                    hash_titolo['id_inc'] = 1
                else
                    #carico i servizi presenti e aggiungo questo
                    hash_titoli = JSON.parse(modulo.titoli_studio)
                    hash_titolo['id_inc'] = hash_titoli.keys.collect{|key| key.to_i }.max.to_i+1
                end
                hash_titoli[hash_titolo['id_inc']] = hash_titolo
                @scene.cat_giuridica = modulo.tipo_modulo.categoria_giuridica.nome
                #calcolo il totale dei titoli di studio
                calcola_punteggio_titoli(@scene,hash_titoli)

                @scene.errori = []
                @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)

                modulo.titoli_studio = hash_titoli.convert_object.to_json
                modulo.save
                @scene.titoli_studio = JSON.parse(modulo.titoli_studio) unless modulo.titoli_studio.blank?
                render '_box_titoli', :layout => nil
            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end

        __.html 
        def box_titoli_vari
            id_modulo = @request.params['id_modulo']
            @scene.id_modulo = id_modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            unless modulo.blank?
                #tolgo dai params l'id_modulo
                hash_titolo = @request.params
                hash_titolo.delete("id_modulo")
                #se non ho servizi già salvati salvo questo
                if modulo.titoli_vari.blank?
                    hash_titoli = {}
                    hash_titolo['id_inc'] = 1
                else
                    #carico i servizi presenti e aggiungo questo
                    hash_titoli = JSON.parse(modulo.titoli_vari)
                    hash_titolo['id_inc'] = hash_titoli.keys.collect{|key| key.to_i }.max.to_i+1
                end
                hash_titoli[hash_titolo['id_inc']] = hash_titolo
                #calcolo il totale dei titoli di studio
                calcola_punteggio_titoli_vari(@scene,hash_titoli)

                @scene.errori = []
                @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)
                
                modulo.titoli_vari = hash_titoli.convert_object.to_json
                modulo.save
                @scene.titoli_vari = JSON.parse(modulo.titoli_vari) unless modulo.titoli_vari.blank?
                render '_box_titoli_vari', :layout => nil
            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end

        __.html 
        def cancella_servizio
            id_modulo = @request.params['id_modulo']
            id_servizio = @request.params['id_servizio']
            @scene.id_modulo = id_modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            unless modulo.blank?
                hash_servizi = JSON.parse(modulo.servizi_svolti)
                hash_servizi.delete(id_servizio)
                #calcolo il totale dei servizi svolti
                calcola_punteggio_servizi(@scene,hash_servizi)
                modulo.servizi_svolti = hash_servizi.convert_object.to_json
                modulo.punteggio_servizi = @scene.totale_punteggio_servizi
                modulo.punteggio_totale = (modulo.punteggio_servizi.to_d + modulo.punteggio_titoli.to_d + modulo.punteggio_titoli_vari.to_d).round(6)
                modulo.save
                @scene.errori = []
                @scene.errori << "Sono presenti delle esperienze lavorative contemporanee.<br />
                Si può inserire solo un esperienza lavorativa per un determinato periodo.Se hai lavorato contemporaneamente per più amministrazioni,\
                inserisci solo quella che ai fini della valutazione ha il migliore gradimento, anche spezzando il periodo di riferimento facendo attenzione\
                alla data di inizio e fine. Il periodo completo inseriscilo nel campo note." unless verifica_intervalli_esperienze_lavorative(id_modulo).blank?
                
                @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)

                @scene.servizi_svolti = JSON.parse(modulo.servizi_svolti) unless modulo.servizi_svolti.blank?
                render '_box_servizi_pa', :layout => nil
            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end

        __.html 
        def cancella_titolo
            id_modulo = @request.params['id_modulo']
            id_titolo = @request.params['id_titolo']
            @scene.id_titolo = id_titolo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            unless modulo.blank?
                hash_titoli = JSON.parse(modulo.titoli_studio)
                hash_titoli.delete(id_titolo)
                #calcolo il totale dei titoli di studio
                @scene.cat_giuridica = modulo.tipo_modulo.categoria_giuridica.nome
                calcola_punteggio_titoli(@scene,hash_titoli)

                @scene.errori = []
                @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)

                modulo.titoli_studio = hash_titoli.convert_object.to_json
                modulo.punteggio_titoli = @scene.totale_punteggio_titoli
                modulo.punteggio_totale = (modulo.punteggio_servizi.to_d + modulo.punteggio_titoli.to_d + modulo.punteggio_titoli_vari.to_d).round(6)
                modulo.save
                @scene.titoli_studio = JSON.parse(modulo.titoli_studio) unless modulo.titoli_studio.blank?
                render '_box_titoli', :layout => nil
            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end

        __.html 
        def cancella_titolo_vario
            id_modulo = @request.params['id_modulo']
            id_titolo_vario = @request.params['id_titolo_vario']
            @scene.id_titolo_vario = id_titolo_vario
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            unless modulo.blank?
                hash_titoli_vari = JSON.parse(modulo.titoli_vari)
                hash_titoli_vari.delete(id_titolo_vario)
                #calcolo il totale dei titoli di studio
                calcola_punteggio_titoli_vari(@scene,hash_titoli_vari)

                @scene.errori = []
                @scene.errori << "Ci sono dei punteggi negativi! Controllare i dati inseriti."  if punteggi_totali_negativi?(@scene)

                modulo.titoli_vari = hash_titoli_vari.convert_object.to_json
                modulo.punteggio_titoli_vari = @scene.totale_punteggio_titoli_vari
                modulo.punteggio_totale = (modulo.punteggio_servizi.to_d + modulo.punteggio_titoli.to_d + modulo.punteggio_titoli_vari.to_d).round(6)
                modulo.save
                @scene.titoli_vari = JSON.parse(modulo.titoli_vari) unless modulo.titoli_vari.blank?
                render '_box_titoli_vari', :layout => nil
            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end

        #setta già la scene
        def calcola_punteggio_titoli(scena,hash_titoli)
            scena.totale_punteggio_titoli = 0.00
            
            #n_master = 0
            # hash_titoli.each_value{ |titolo|
            #     if titolo['tipo_titolo'] == 'master'
            #         #posso conteggiare solo 2 master
            #         n_master += 1
            #         if n_master <= 2
            #             scena.totale_punteggio_titoli += Moduli.tipo_titolo(titolo['tipo_titolo'])['punteggio']
            #         end
            #     else
            #         scena.totale_punteggio_titoli += Moduli.tipo_titolo(titolo['tipo_titolo'])['punteggio']
            #     end                
            # }


            #hash per i vari punteggi in base al tipo titolo
            punt_titoli = {}
            hash_titoli.each_value{ |titolo|
                punt_titoli[titolo['tipo_titolo']] ||= 0.00
                if Moduli.tipo_titolo(titolo['tipo_titolo'],scena.cat_giuridica)['max_punti'].blank? || punt_titoli[titolo['tipo_titolo']] < Moduli.tipo_titolo(titolo['tipo_titolo'],scena.cat_giuridica)['max_punti']
                    punt_titoli[titolo['tipo_titolo']] += Moduli.tipo_titolo(titolo['tipo_titolo'],scena.cat_giuridica)['punteggio']
                end
            }
            #calcolo il totale ciclando sui valori dell'hash
            punt_titoli.each_value{ |punt_per_tipo| scena.totale_punteggio_titoli += punt_per_tipo }
            
            scena.totale_punteggio_titoli = Moduli.hash_punteggi_bandi[Spider.conf.get('moduli.tipologia_bando')]['max_punti_titoli'] if scena.totale_punteggio_titoli > Moduli.hash_punteggi_bandi[Spider.conf.get('moduli.tipologia_bando')]['max_punti_titoli'] #max 8 punti funzionari, 6 dirigenti
        end

        #setta già la scene
        def calcola_punteggio_titoli_vari(scena,hash_titoli_vari)
            scena.totale_punteggio_titoli_vari = 0.00
            
            # n_master = 0
            # hash_titoli_vari.each_value{ |titolo|
            #     if titolo['tipo_titolo'] == 'master' 
            #         #posso conteggiare solo 2 master o dottorati
            #         n_master += 0.5
            #         if n_master <= 1
            #             scena.totale_punteggio_titoli_vari += Moduli.tipo_titolo_vario(titolo['tipo_titolo'])['punteggio']
            #         end
            #     elsif titolo['tipo_titolo'] == 'pubblicazione' 
            #         #posso conteggiare solo 2 master o dottorati
            #         n_master += 0.5
            #         if n_master <= 2
            #             scena.totale_punteggio_titoli_vari += Moduli.tipo_titolo_vario(titolo['tipo_titolo'])['punteggio']
            #         end
            #     else
            #         scena.totale_punteggio_titoli_vari += Moduli.tipo_titolo_vario(titolo['tipo_titolo'])['punteggio']
            #     end
                
            # }
            # scena.totale_punteggio_titoli_vari = 2 if @scene.totale_punteggio_titoli_vari > 4 #max 2 punti funzionari, 4 dirigenti
        
            #hash per i vari punteggi in base al tipo titolo
            punt_titoli = {}
            hash_titoli_vari.each_value{ |titolo|
                punt_titoli[titolo['tipo_titolo']] ||= 0.00
                if Moduli.tipo_titolo_vario(titolo['tipo_titolo'])['max_punti'].blank? || punt_titoli[titolo['tipo_titolo']] < Moduli.tipo_titolo_vario(titolo['tipo_titolo'])['max_punti']
                    punt_titoli[titolo['tipo_titolo']] += Moduli.tipo_titolo_vario(titolo['tipo_titolo'])['punteggio']
                end
            }
            #calcolo il totale ciclando sui valori dell'hash
            punt_titoli.each_value{ |punt_per_tipo| scena.totale_punteggio_titoli_vari += punt_per_tipo }
            
            scena.totale_punteggio_titoli_vari = Moduli.hash_punteggi_bandi[Spider.conf.get('moduli.tipologia_bando')]['max_punti_titoli_vari'] if scena.totale_punteggio_titoli_vari > Moduli.hash_punteggi_bandi[Spider.conf.get('moduli.tipologia_bando')]['max_punti_titoli_vari'] #max 8 punti funzionari, 6 dirigenti

        end



        #setta già la scene
        def calcola_punteggio_servizi(scena,hash_servizi)
            scena.totale_punteggio_servizi = BigDecimal.new(0.00,5)
            #carico il modulo per capire che categoria ha il tipo modulo
            modulo = Moduli::ModuloSalvato.load(:id => scena.id_modulo)
            unless modulo.blank?
                cat_modulo = modulo.tipo_modulo.categoria_giuridica.nome
                hash_servizi.each_value{ |servizio|
                    valore_servizio = Moduli.calcola_punteggio_esperienza_lavorativa(cat_modulo,servizio['cat_giuridica'],servizio['giorni_di_servizio'],servizio['tipo_amministrazione'],servizio['rid_lavorativa'])
                    scena.totale_punteggio_servizi += valore_servizio
                    
                }
            end
            #arrotondo a due decimali
            scena.totale_punteggio_servizi = scena.totale_punteggio_servizi.to_f.round(6) #to_f per non avere EO alla fine...
            
            scena.totale_punteggio_servizi = 20 if scena.totale_punteggio_servizi > 20 #max 20 punti
        end


        __.html :template => 'firma_modulo'
        def firma_modulo
            @scene.link_indietro = self.class.http_s_url
            id_modulo = @request.params['id_modulo']
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            unless modulo.blank?
                id_tipo_modulo = modulo.tipo_modulo.id
                if modulo_scaduto?(id_tipo_modulo)
                    @request.session.flash['esito_azione'] = "Il modulo non è più compilabile perchè sono scaduti i termini di presentazione."
                    redirect self.class.http_s_url
                end
                #stampo il modulo per mostare l'anteprima                
                @scene.nome_file_temp = stampa('preview',id_modulo)
                #carico i dati del tipo modulo
                @scene.tipo_firma = (!modulo.tipo_modulo.tipo_firma.blank? ? modulo.tipo_modulo.tipo_firma.id : nil)
                if @request.post?
                    #controlli su file caricati
                    unless @request.params['upload_modulo_firmato'].blank?
                        #salvataggio file con pulizia del precedente
                        save_dir = File.join(Spider.paths[:data],'/uploaded_files/moduli/',modulo.hashdir_allegati,'modulo_firmato')
                        #creo la cartella per il salvataggio del file salvato
                        unless File.directory?(save_dir)
                            FileUtils.mkdir_p(save_dir) 
                        end
                        nome_file = @request.params['upload_modulo_firmato'].filename.to_s
                        path_file_firmato = File.join(save_dir, nome_file)
                        # #se era già presente un file lo cancello
                        # unless modulo.modulo_firmato.blank?
                        #     begin
                        #         FileUtils.remove(File.join(save_dir, modulo.modulo_firmato))
                        #     rescue Exception => e
                        #         Spider.logger.error "Errore in cancellazione doc firmato presente!"
                        #     end
                        # end
                        #scrivo il file
                        File.open(path_file_firmato, "wb") { |f| f.write(@request.params['upload_modulo_firmato'].read) }
                        modulo.modulo_firmato = nome_file
                        modulo.save
                    else
                        @scene.errore = "File firmato mancante."
                    end
                    #controllo se ci sono importi da pagare
                    if modulo.importi.blank?
                        invio_modulo_email(modulo.id)
                        @request.session.flash['esito_azione'] = "Il modulo è stato inviato."
                    else
                        hash_importi = JSON.parse(modulo.importi)
                        totale_importi = hash_importi.values.inject(0){|sum,x| sum + BigDecimal.new(x.gsub(".","").gsub(",",".")) }
                        if totale_importi > 0
                            modulo.stato = 'da_pagare'
                            modulo.save
                        else #ho solo importi gratis, mando il modulo
                            invio_modulo_email(modulo.id)
                            @request.session.flash['esito_azione'] = "Il modulo è stato inviato."
                        end
                    end
                    redirect self.class.http_s_url
                end
            else
                @request.session.flash['errore'] = "Il modulo indicato non è presente."
                redirect self.class.http_s_url
            end
        end



        

        __.html :template => 'invio_mail'
        def invio_mail(id_modulo)
            modulo = ModuloSalvato.new(:id => id_modulo.to_i)
            numero_modulo = modulo.numero_modulo.to_s
            @scene.modulo = modulo
            if @request.params.key?('conferma')
                invio_modulo_email(id_modulo)
                @request.session.flash['esito_azione'] = "Il modulo numero #{numero_modulo} è stato inviato tramite e-mail."
                redirect self.class.http_s_url
            elsif @request.params.key?('annulla')
                redirect self.class.http_s_url
            end
        end

        __.html :template => 'elimina_modulo'
        def elimina_modulo(id_modulo)
            autenticazione_necessaria
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            @scene.modulo = modulo
            @scene.nome_tipo_modulo = modulo.tipo_modulo.nome
            if @request.post?
                if @request.params.key?('conferma')
                    modulo.delete
                    @request.session.flash['esito_azione'] = "Il modulo con id #{id_modulo} è stato cancellato con successo."
                end
            redirect self.class.http_s_url
            end
        end 

        #fa il download inline del pdf allegato alla pratica
        __.action
        def download_pdf_embed
            id_pratica = @request.params['id']
            nome_file = @request.params['nome_file']
            file = File.join(Spider.paths[:data], 'uploaded_files/moduli/xml_pratiche', id_pratica.to_s, nome_file)
            @response.headers['Content-disposition'] = "inline; filename=#{nome_file}"
            output_static(file)
        end

        __.action
        def download_img
            path = @request.params['path']
            nomefile= Pathname.new(path).basename.to_s
            file = File.join(Spider.paths[:root], path)
            @response.headers['Content-disposition'] = "inline; filename=#{nomefile}"
            output_static(file) 
        end

        __.action
        def self.download_img_b64(nome_img)
            require 'base64' 
            nomefile = Pathname.new(nome_img).basename
            b64string = "data:image/"+nomefile.extname.gsub('.','')+";base64,"

            file_path = File.join(Spider.paths[:root],"/public/img/moduli/", nome_img)
            if File.exist?(file_path)
                File.open(file_path, 'r') do |image_file| 
                    b64string += Base64.encode64(image_file.read)
                end 
            end
            return b64string
        end

        #scarica il file indicato dal parametro nome_file
        __.action
        def download_pdf_preview
            nome_file_temp = @request.params['nome_file']
            @response.headers['Content-disposition'] = "inline; filename=#{nome_file_temp}"
            output_static(nome_file_temp)
            done
        end        
        
        __.action
        def download_modulo
            nome_file_temp = @request.params['nome_file']
            @response.headers['Content-disposition'] = "attachments; filename=modulo_da_firmare.pdf"
            output_static(nome_file_temp)
            done
        end   

        #metodo di classe che ritorna il path del file zip o nil
        def self.crea_zip_iscrizione(id_iscrizione)
            #ricavo la dir degli allegati
            modulo_iscrizione = ModuloSalvato.load(:id_iscrizione => id_iscrizione)
            unless modulo_iscrizione.blank?
                dir_allegati = modulo_iscrizione.hashdir_allegati
                path_dir_allegati = File.join(Spider.paths[:data],"uploaded_files","moduli",dir_allegati)
                #cartella in temp delle iscrizioni
                dir_zip_iscr = File.join(Spider.paths[:tmp],"zip_iscrizioni",id_iscrizione.to_s)
                FileUtils.mkdir_p(dir_zip_iscr) unless File.directory?(dir_zip_iscr)
                zip_iscrizione = File.join(dir_zip_iscr,"iscrizione_#{id_iscrizione}.zip")
                #se non è già stato creato il file lo genero
                unless File.exists?(zip_iscrizione)
                    if Zip.const_defined?('ZipFile')
                        zip_const = Zip::ZipFile
                        zip_const_create = Zip::ZipFile::CREATE
                    elsif Zip.const_defined?('File')
                        zip_const = Zip::File
                        zip_const_create = Zip::File::CREATE
                    end
                    zip_const.open(zip_iscrizione,zip_const_create) do |zipfile|  
                        #aggiungo il modulo dell'iscrizione
                        path_file_iscrizione = File.join(Spider.paths[:data],"uploaded_files","moduli","xml_iscrizioni",id_iscrizione.to_s)
                        zipfile.add("iscrizione_#{id_iscrizione}.pdf", File.join(path_file_iscrizione,"iscrizione_#{id_iscrizione}.pdf"))
                        #se nella dir_allegati ci sono allegati li aggiungo allo zip
                        unless Dir["#{path_dir_allegati}/*"].empty?
                            Dir.foreach(path_dir_allegati) do |item|
                                #salto le cartelle nascoste e la cartella stampa modulo dove vengono salvati i pdf dalla modulistica o dai bandi
                                next if item[0] == '.' || item == 'stampa_modulo'
                                zipfile.add(item, File.join(path_dir_allegati,item) )    
                            end
                        end
                    end
                end

                return zip_iscrizione
            else                
                return nil
            end
        end


        #parametro mode per scaricare lo zip in base64 o come allegato
        __.action
        def download_iscrizione
            id_iscrizione = @request.params['id_iscr']
            if !id_iscrizione.blank?
                zip_iscrizione = self.class.crea_zip_iscrizione(id_iscrizione)
                if zip_iscrizione.blank?
                    return "Errore, problemi nella creazione dello zip!"
                else
                    @response.headers['Content-disposition'] = "attachments; filename=\"iscrizione_#{id_iscrizione}.zip\""
                    output_static(zip_iscrizione)
                end
            else
                return "Errore, inviare ID iscrizione e directory degli allegati"
            end
        end

        def stampa_allegati_bando(request,id_modulo)
            #carico il modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)

            @scene = Spider::Scene.new
            @scene.id_modulo = id_modulo
            @scene.data_pubblicazione_bando = modulo.tipo_modulo.disponibile_dal
            @scene.data_fine_pubblicazione_bando = modulo.tipo_modulo.disponibile_al

            #vedo se ci sono servizi svolti salvati
            @scene.servizi_svolti = JSON.parse(modulo.servizi_svolti) unless modulo.servizi_svolti.blank?
            #calcolo il totale dei servizi presso pa e lo mette in scene.totale_punteggio_servizi
            calcola_punteggio_servizi(@scene,@scene.servizi_svolti) unless @scene.servizi_svolti.blank?

            #vedo se ci sono titoli di studio salvati
            @scene.titoli_studio = JSON.parse(modulo.titoli_studio) unless modulo.titoli_studio.blank?
            #calcolo il totale dei titoli di studio e lo mette in scene.totale_punteggio_titoli
            calcola_punteggio_titoli(@scene,@scene.titoli_studio) unless @scene.titoli_studio.blank?

            #vedo se ci sono titoli vari salvati
            @scene.titoli_vari = JSON.parse(modulo.titoli_vari) unless modulo.titoli_vari.blank?
            #calcolo il totale dei titoli vari e lo mette in scene.totale_punteggio_titoli_vari
            calcola_punteggio_titoli_vari(@scene,@scene.titoli_vari) unless @scene.titoli_vari.blank?

            hashdir_moduli_allegati = modulo.hashdir_allegati
            tpl_path_servizi = File.join(Spider.paths[:apps],'moduli/views/_box_servizi_pa.shtml')
            tpl_path_titoli = File.join(Spider.paths[:apps],'moduli/views/_box_titoli.shtml')
            tpl_path_titoli_vari = File.join(Spider.paths[:apps],'moduli/views/_box_titoli_vari.shtml')
            @stylesheet_layout = []
            html_servizi = html_from_view(tpl_path_servizi, scene, 'stampa', @stylesheet_layout)
            html_titoli = html_from_view(tpl_path_titoli, scene, 'stampa', @stylesheet_layout)
            html_titoli_vari = html_from_view(tpl_path_titoli_vari, scene, 'stampa', @stylesheet_layout)

            path_wkhtmltopdf = `which wkhtmltopdf`.gsub(/\n/, '')
            path_wkhtmltopdf = "/usr/local/bin/wkhtmltopdf" if path_wkhtmltopdf.blank?

            str_spid_code = (modulo.spid_code.blank? ? "" : "Token SPID #{modulo.spid_code}")

            #trasformazioni per dimensioni fogli e pixel
            #http://www.format-papier-a0-a1-a2-a3-a4-a5.fr/format-a4/dimensions-a4-en-pixels-par-resolutions.php
            PDFKit.configure do |config|
                config.wkhtmltopdf = path_wkhtmltopdf
                config.default_options = {
                    :page_size => 'A4',
                    :print_media_type => true,
                    #:disable_smart_shrinking => false,
                    #:quiet => true,
                    #:margin_top => '0.75in',
                    #:margin_right => '0.75in',
                    #:margin_bottom => '0.75in',
                    #:margin_left => '0.75in',
                    :footer_spacing => '4',
                    :footer_font_size => '9',
                    :encoding => 'UTF-8',
                    :footer_left => "Documento n. #{modulo.id}, chiave di controllo #{modulo.chiave}. #{str_spid_code}"
                }
                #config.root_url = Spider.conf.get('site.domain') #use only if your external hostname is unavailable on the server.
            end
            #kit = PDFKit.new(html, :page_size => 'A4', :minimum_font_size => 14, :encoding => 'UTF-8')
            kit_servizi = PDFKit.new(html_servizi)
            kit_titoli = PDFKit.new(html_titoli)
            kit_titoli_vari = PDFKit.new(html_titoli_vari)

            kit_servizi.stylesheets = @stylesheet_layout
            kit_titoli.stylesheets = @stylesheet_layout
            kit_titoli_vari.stylesheets = @stylesheet_layout

            path_servizi_titoli_pdf  = File.join(Spider.paths[:data],'/uploaded_files/moduli/',hashdir_moduli_allegati,'stampa_modulo')
            FileUtils.mkdir_p(path_servizi_titoli_pdf) unless File.directory?(path_servizi_titoli_pdf)

            allegato_a = File.join(path_servizi_titoli_pdf,"allegato_a.pdf")
            allegato_b = File.join(path_servizi_titoli_pdf,"allegato_b.pdf")
            allegato_c = File.join(path_servizi_titoli_pdf,"allegato_c.pdf")
            #scrivo gli allegati
            pdf_servizi = kit_servizi.to_file(allegato_a)
            pdf_titoli = kit_titoli.to_file(allegato_b)
            pdf_titoli_vari = kit_titoli_vari.to_file(allegato_c)

        end


        __.action
        def stampa(tipo_stampa, id_modulo)
            #carico il modulo
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            #carico il percorso degli allegati
            hashdir_moduli_allegati = modulo.hashdir_allegati
            #variabile usata nella widget allegato
            @request.session["hashdir_moduli_allegati"] = hashdir_moduli_allegati
            tpl = carica_template(modulo.tipo_modulo.tipo)
            widget = tpl.widgets[:modulo] || tpl.widgets.first[1]
            #ricavo il nome del gestore che POTREBBE essere presente
            nome_classe_gestore = 'Gestore'+modulo.tipo_modulo.tipo.split('_').each{|v| v.capitalize! }.join
            #se faccio la stampa di un modulo inserito in pratiche carico anche i dati per la scene
            if modulo.stato.id == "da_pratiche"
                id_pratica = modulo.id_pratica    
                hash_conf_pratica = get_hash_config(id_pratica.to_s)
                #se non riesco a fare il caricamento dei dati dall'xml scrivo un log di errore e ritorno a pratiche
                begin
                    hash_dati_utente = Moduli.const_get(nome_classe_gestore).carica_dati_modulo({'id_pratica' => id_pratica.to_s}, hash_conf_pratica) if Moduli.const_defined?(nome_classe_gestore)
                    #Carico nella scene della variabili che cambiano da modulo a modulo, con condizioni sui dati da pratiche eventualmente
                    widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene, hash_dati_utente) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)

                rescue Exception => e
                    Spider.logger.error "Caricamento dati da xml in errore: #{e.message}, #{e.backtrace}"
                    redirect url_ritorno+'&errore=caricamento_dati_xml'
                end
            end

            #FORSE NON SERVE
            # if modulo.stato.id == "da_iscrizioni"
            #     id_iscrizione = modulo.id_iscrizione    
            #     begin
            #         hash_dati_utente = Moduli.const_get(nome_classe_gestore).carica_dati_modulo({'id_pratica' => id_pratica.to_s}, hash_conf_pratica) if Moduli.const_defined?(nome_classe_gestore)
            #         #Carico nella scene della variabili che cambiano da modulo a modulo, con condizioni sui dati da pratiche eventualmente
            #         widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene, hash_dati_utente) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)

            #     rescue Exception => e
            #         Spider.logger.error "Caricamento dati da xml in errore: #{e.message}, #{e.backtrace}"
            #         redirect url_ritorno+'&errore=caricamento_dati_xml'
            #     end
            # end

            widget.dati(modulo.dati, modulo.dimensioni)
            
            #Carico nella scene della variabili che cambiano da modulo a modulo
            widget.scene = Moduli.const_get(nome_classe_gestore).set_scene_widget(widget.scene) if Moduli.const_defined?(nome_classe_gestore) && Moduli.const_get(nome_classe_gestore).respond_to?(:set_scene_widget)

            #carico gli importi selezionati
            unless modulo.importi.blank?
                importi_selezionati = JSON.parse(modulo.importi)
                widget.scene.importi_selezionati = widget.scene.importi_ceccati = importi_selezionati
                #calcolo il totale convertendo gli importi da 5.000,30 in 5000.30 per fare le somme
                totale_importi_selezionati = importi_selezionati.values.inject(0){|sum,x| sum + BigDecimal.new(x.gsub(".","").gsub(",",".")) }
                widget.scene.totale_importi_selezionati = totale_importi_selezionati
            end
            #raggruppo per tipo obbligatorieta per mostare la tabella con gli importi raggruppati per obbligatorio, almeno_uno e solo_uno 
            importi_collegati = Moduli::Importo.where{|imp| (imp.tipo_modulo == modulo.tipo_modulo)}.group_by{|imp_col| imp_col[:tipo_obbligatorieta]}
            
            unless importi_collegati.blank?
                widget.scene.importi_collegati = importi_collegati
            end


            layout = init_layout('stampa')
            layout.template = tpl
            layout.init(@scene)
            html = StringIO.new
            $out.output_to(html) do
                layout.render
            end
            html.rewind
            html = html.read
            path_wkhtmltopdf = `which wkhtmltopdf`.gsub(/\n/, '')
            path_wkhtmltopdf = "/usr/local/bin/wkhtmltopdf" if path_wkhtmltopdf.blank?

            str_spid_code = (modulo.spid_code.blank? ? "" : "Token SPID #{modulo.spid_code}")

            #trasformazioni per dimensioni fogli e pixel
            #http://www.format-papier-a0-a1-a2-a3-a4-a5.fr/format-a4/dimensions-a4-en-pixels-par-resolutions.php
            PDFKit.configure do |config|
                config.wkhtmltopdf = path_wkhtmltopdf
                config.default_options = {
                    :page_size => 'A4',
                    :print_media_type => true,
                    #:disable_smart_shrinking => false,
                    #:quiet => true,
                    #:margin_top => '0.75in',
                    #:margin_right => '0.75in',
                    #:margin_bottom => '0.75in',
                    #:margin_left => '0.75in',
                    :footer_spacing => '4',
                    :footer_font_size => '9',
                    :encoding => 'UTF-8',
                    :footer_right => "Page [page] of [toPage]",
                    :footer_left => "Documento n. #{id_modulo}, chiave di controllo #{modulo.chiave}. #{str_spid_code}"
                }
                #config.root_url = Spider.conf.get('site.domain') #use only if your external hostname is unavailable on the server.
            end
            #kit = PDFKit.new(html, :page_size => 'A4', :minimum_font_size => 14, :encoding => 'UTF-8')
            kit = PDFKit.new(html)
            seen_assets = {}
            #layout.assets recupera i css del layout, layout.all_assets recupera anche i css dei moduli inclusi 
            #nel layout

            layout.assets = layout.assets.uniq { |item| item[:path] } #metto negli assets solo una copia degli assets
            layout.assets.select{ |a| 
                a[:type] == :css && File.extname(a[:src]) != '.scss' }.each do |ass|
                next if seen_assets[ass[:path]]
                kit.stylesheets << ass[:path]
                seen_assets[ass[:path]] = true
            end

            nome_modulo_stampato = modulo.tipo_modulo.nome.gsub(/[^a-z^A-Z]/,"_").gsub(/_{2,}/,'_')

            #aggiunta per bandi cagliari
            path_allegati_bandi = File.join(Spider.paths[:data],'/uploaded_files/moduli/',hashdir_moduli_allegati,'stampa_modulo')

            #se chiamo il metodo stampa con parametro 'download' allora devo far scaricare il file pdf all'utente
            if tipo_stampa == "download"
                #se ho cliccato sullo 'scarica' dell'elenco ma ho il modulo firmato faccio scaricare quello
                if modulo.modulo_firmato.blank? || @request.params['rig'] == 't'
                    pdf = kit.to_pdf
                    unless modulo.tipo_modulo.categoria_giuridica.blank? #se un bando con cat giuridica unisco allegati
                        #stampa_allegati_bando(@request,id_modulo) #ricrea gli allegati
                        pdf = unisci_pdf(pdf,path_allegati_bandi,nome_modulo_stampato) 
                    end
                    @response.headers['Content-Disposition'] = "attachment; filename=#{nome_modulo_stampato}.pdf"
                    @response.headers['Content-Type'] = 'application/pdf'
                    @response.headers['Content-Length'] = pdf.size
                    @response.headers['Last-Modified'] = Time.now.httpdate
                    @request.session["hashdir_moduli_allegati"] = nil
                    if RUBY_VERSION =~ /1.8/
                            $out << pdf
                    else
                            $out << pdf.force_encoding("BINARY")
                    end
                else
                    file_firmato_path = File.join(Spider.paths[:data],'/uploaded_files/moduli/',hashdir_moduli_allegati,'modulo_firmato',modulo.modulo_firmato)
                    @response.headers['Content-disposition'] = "attachments; filename=#{modulo.modulo_firmato}"
                    output_static(file_firmato_path)
                    done
                end
            elsif tipo_stampa == "allega"
                #se chiamo il metodo stampa con parametro 'allega' allora devo allegare il file pdf alla mail
                #salvo il pdf nella cartella degli allegati
                dir_uploaded_path = File.join(Spider.paths[:data],'/uploaded_files/moduli/',hashdir_moduli_allegati,'stampa_modulo')
                FileUtils.mkdir_p(dir_uploaded_path) unless File.directory?(dir_uploaded_path)
                file_uploaded_path = File.join(Spider.paths[:data],'/uploaded_files/moduli/',hashdir_moduli_allegati,'stampa_modulo',"/#{nome_modulo_stampato}.pdf")
                @request.session["hashdir_moduli_allegati"] = nil
                pdf = kit.to_file(file_uploaded_path)
                unless modulo.tipo_modulo.categoria_giuridica.blank? #se un bando con cat giuridica unisco allegati
                    #stampa_allegati_bando(@request,id_modulo) #ricrea gli allegati
                    pdf = unisci_pdf(pdf.read,path_allegati_bandi,nome_modulo_stampato)
                end 
            elsif tipo_stampa == "pratiche"
                dir_uploaded_path = File.join(Spider.paths[:data],'/uploaded_files/moduli/xml_pratiche',modulo.id_pratica.to_s)
                FileUtils.mkdir_p(dir_uploaded_path) unless File.directory?(dir_uploaded_path)
                file_uploaded_path = File.join(dir_uploaded_path,'allegato_modulo-'+modulo.id_pratica.to_s+'.pdf')
                @request.session["hashdir_moduli_allegati"] = nil
                pdf = kit.to_file(file_uploaded_path)
            elsif tipo_stampa == "iscrizioni"
                dir_uploaded_path = File.join(Spider.paths[:data],'/uploaded_files/moduli/xml_iscrizioni',modulo.id_iscrizione.to_s)
                FileUtils.mkdir_p(dir_uploaded_path) unless File.directory?(dir_uploaded_path)
                file_uploaded_path = File.join(dir_uploaded_path,'iscrizione_'+modulo.id_iscrizione.to_s+'.pdf')
                @request.session["hashdir_moduli_allegati"] = nil
                pdf = kit.to_file(file_uploaded_path)
            elsif tipo_stampa == "preview" #usata per fare la preview del file da firmare
                #la pdfkit+wkhtmltopdf non permette di fare un pdf usando un Tempfile, uso il nome di un tempfile e creo File
                file_temporaneo = Tempfile.new('preview_modulo_firma',Spider.paths[:tmp])
                nome_file_temp = Pathname.new(file_temporaneo.path).basename.to_s
                file_temporaneo.delete
                file_temp = File.join(Spider.paths[:tmp],nome_file_temp+'.pdf')
                pdf = kit.to_file(file_temp)
                unless modulo.tipo_modulo.categoria_giuridica.blank? #se un bando con cat giuridica unisco allegati
                    #stampa_allegati_bando(@request,id_modulo) #ricrea gli allegati
                    pdf_unito = unisci_pdf(pdf.read,path_allegati_bandi,nome_file_temp)
                end
                unless pdf_unito.blank?
                    path_file = File.join(path_allegati_bandi,nome_file_temp+".pdf")
                else 
                    path_file = file_temp
                end
                path_file
            end
        end

        #unisco il modulo agli allegati con combine_pdf
        def unisci_pdf(pdf,path_allegati_bandi,nome_modulo_stampato)
            allegato_a = File.join(path_allegati_bandi,'allegato_a.pdf')
            allegato_b = File.join(path_allegati_bandi,'allegato_b.pdf')
            allegato_c = File.join(path_allegati_bandi,'allegato_c.pdf')
            pdf_unito = CombinePDF.new
            pdf_unito << CombinePDF.parse(pdf) 
            pdf_unito << CombinePDF.load(allegato_a) if File.exists?(allegato_a)
            pdf_unito << CombinePDF.load(allegato_b) if File.exists?(allegato_b)
            pdf_unito << CombinePDF.load(allegato_c) if File.exists?(allegato_c)
            #pdf_unito.number_pages numera le pagine ma il numero è centrato e sopra il testo
            pdf_unito.save File.join(path_allegati_bandi,nome_modulo_stampato+".pdf")
            pdf_unito.to_pdf
        end



        #protected tolto per poter mandare mail con modulo da Pagamenti

        def get_hash_config(id_prat)
            #cerco nella cartella in data/xml_pratiche/[id_pratica] l'xml di configurazione per un modulo dinamico
            path_pratica_dir = File.join(Spider.paths[:data], 'uploaded_files/moduli/xml_pratiche', id_prat)
            nome_file_conf = 'config.xml'
            full_path_file_conf = File.join(path_pratica_dir, nome_file_conf)
            xml_file_conf = File.read(full_path_file_conf)
            #carico l'hash per la configurazione
            hash_conf_pratica = Crack::XML.parse(xml_file_conf)
            return hash_conf_pratica
        end

        def carica_template(nome_modulo, path=nil)
            if path
                nome = File.basename(nome_modulo)
                path ||= File.join(Spider.paths[:root], 'moduli', nome) 
                tpl_path = File.join(path, "#{nome}.shtml")
            else
                shtmlfiles = File.join("moduli/**", "#{nome_modulo}.shtml")
                suffix = Dir.glob(shtmlfiles)
                #se per errore ho lo stesso nome di modulo in più cartelle (create con portal.nomefile) ho un array di template
                tpl_da_usare = nil
                if suffix.is_a?(Array)
                    suffix.each{ |nome_template|
                        #cerco in base al nome dell'ente
                        tpl_da_usare = nome_template if nome_template.include?(Spider.conf.get('portal.nome').gsub(/[^a-z^A-Z]/,"_"))  
                    }
                    tpl_da_usare ||= suffix[0] #se non lo trovo metto il primo
                else
                    tpl_da_usare = suffix
                end
                if tpl_da_usare.blank?
                    @request.session.flash['errore_azione'] = "Il modulo selezionato non è presente."
                    redirect self.class.http_s_url('scegli_modulo') 
                else
                    tpl_path = File.join(Spider.paths[:root], tpl_da_usare)
                end
            end
            tpl = Spider::Template.new(tpl_path)
            tpl.owner_class = self.class
            tpl.definer_class = self.class
            init_template(tpl)
            tpl.exec
            return tpl
        end

        def invio_modulo_email(id_modulo)
            modulo = ModuloSalvato.load(:id => id_modulo.to_i)
            @request.session["hashdir_moduli_allegati"] = modulo.hashdir_allegati
            percorso = modulo.tipo_modulo.tipo
            #carico il template passando il percorso del modulo corrente 
            tpl = carica_template(percorso)
            #assegno a widget la widgetModulo del template o la prima
            widget = tpl.widgets[:modulo] || tpl.widgets.first[1]
            #imposto i dati sugli allegati della widget
            
            widget.aggiorna_allegati(JSON.parse(modulo.dati)) unless modulo.dati.blank? 
            allegati_caricati = widget.allegati
            attachments=[]
            allegati_caricati.each { |allegato|
                unless allegato.input.value.blank?
                    nome_file_stringa = allegato.input.value.respond_to?(:force_encoding) ? allegato.input.value.force_encoding('utf-8').encode('utf-8') : allegato.input.value
                    nome_file = Spider::DataTypes::FilePath.new(nome_file_stringa)
                    path_allegati = File.join(Spider.paths[:data],'/uploaded_files/moduli/',modulo.hashdir_allegati)
                    attachments <<  {
                            :filename => nome_file_stringa,
                            :filetype => nome_file.extname, 
                            :mime_type => 'application/octet-stream',  
                            :content => File.read(File.join(path_allegati,nome_file)) 
                    }   
                end    
            }

            #se viene caricato un modulo firmato lo invio al comune
            allegato_modulo_firmato = nil
            unless modulo.modulo_firmato.blank?
                path_modulo_firmato = File.join(Spider.paths[:data],'/uploaded_files/moduli/',modulo.hashdir_allegati,'modulo_firmato',modulo.modulo_firmato)
                modulo_firmato = Spider::DataTypes::FilePath.new(path_modulo_firmato)
                modulo_firmato = modulo_firmato.respond_to?(:force_encoding) ? modulo_firmato.force_encoding('utf-8').encode('UTF-8') : modulo_firmato
                
                allegato_modulo_firmato = { :filename => modulo_firmato.basename.to_s,
                             :filetype => modulo_firmato.extname,
                             :mime_type => 'application/x-pdf', #mime type generico, può essere pdf, p7m o altro file firmato..
                             :content => File.read(modulo_firmato) }
            end
            
            #salvo nella cartella degli allegati il modulo in pdf
            stampa("allega",modulo.id.to_s)
            #aggiungo agli allegati il modulo in pdf
            path_modulo_caricato_versione_pdf = File.join(Spider.paths[:data],'/uploaded_files/moduli/',modulo.hashdir_allegati,'stampa_modulo',"/#{modulo.tipo_modulo.nome.gsub(/[^a-z^A-Z]/,'_').gsub(/_{2,}/,'_')}.pdf")
            modulo_caricato = Spider::DataTypes::FilePath.new(path_modulo_caricato_versione_pdf)
            modulo_caricato = modulo_caricato.respond_to?(:force_encoding) ? modulo_caricato.force_encoding('utf-8').encode('UTF-8') : modulo_caricato
            allegato_modulo_non_firmato = { :filename => modulo_caricato.basename.to_s,
                         :filetype => modulo_caricato.extname,
                         :mime_type => 'application/x-pdf',
                         :content => File.read(modulo_caricato) }
            
            scene = Spider::Scene.new
            nome_compilatore = modulo.utente.nome
            cognome_compilatore = modulo.utente.cognome
            headers = {'Subject' =>  "#{Spider.conf.get('portal.nome')} - Invio modulo on-line da #{cognome_compilatore} #{nome_compilatore}"}
            nome_ente = Spider.conf.get('portal.email_from')

            #mando la mail in copia al compilatore se configurato da admin
            if modulo.tipo_modulo.mail_a_compilatore
                allegati_da_inviare = []

                unless modulo.tipo_modulo.tipo_firma.id.blank? #se tipo firma impostato 
                    #se sono entrato con spid non serve la firma
                    if ((!@request.session[:auth].blank? && !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?) || !modulo.spid_code.blank? )
                        allegati_da_inviare << allegato_modulo_non_firmato
                    else #includo il modulo firmato
                        allegati_da_inviare << allegato_modulo_firmato
                    end
                else #non ho impostato la firma, sempre modulo non firmato
                    allegati_da_inviare << allegato_modulo_non_firmato
                end
                allegati_utente = attachments + allegati_da_inviare
                
                headers_utente = { 'Subject' => "#{Spider.conf.get('portal.nome')} - Inviato modulo: #{modulo.tipo_modulo.nome}"}
                send_email('invio_modulo_cittadino', scene, Spider.conf.get('portal.email_from'), modulo.utente.email, headers_utente ,allegati_utente)
            end

            #se il tipo modulo ha il campo mail non vuoto mando mail a quegli indirizzi
            mail_invio_moduli = []
            unless modulo.tipo_modulo.mail_destinatari.blank?
                mail_invio_moduli = modulo.tipo_modulo.mail_destinatari.split("\;")
            else
                #altrimenti all'indirizzo settato in configurazione
                mail_invio_moduli << Spider.conf.get('moduli.mail_invio_moduli')
            end
            if mail_invio_moduli.blank?
                #Spider.logger.error("Inserire configurazione per mail invio moduli on line")
                mail_invio_moduli << Spider.conf.get('portal.email_from')
            end
            
            allegati_ente = []
            #aggiungo all'array degli allegati il modulo firmato per fare l'invio al comune..
            unless modulo.modulo_firmato.blank?
                allegati_ente << allegato_modulo_firmato
            else
                allegati_ente << allegato_modulo_non_firmato
            end
            allegati_ente += attachments
            
            modulo.inviato = DateTime.now

            #Determino il tipo di autenticazione ai fini dell'invio della mail. Ho salvato precedentemente lo spid code in db
            spid_code = nil
            spid_auth = false
            if (!modulo.spid_code.blank? ||  (!@request.session[:auth].blank? && (!@request.session[:auth]["Portal::UtenteSpidAgid"].blank? || !@request.session[:auth]["Portal::UtenteFederaEmiliaRomagna"].blank?)) )
                if !@request.session[:auth].blank? && !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?
                    spid_id = @request.session[:auth]["Portal::UtenteSpidAgid"][:id]                            
                    user_agid = Portal::UtenteSpidAgid.load(:id => spid_id)
                    spid_code = user_agid.spid_code
                elsif !@request.session[:auth].blank? && !@request.session[:auth]["Portal::UtenteFederaEmiliaRomagna"].blank?
                    spid_id = @request.session[:auth]["Portal::UtenteFederaEmiliaRomagna"][:id]
                    user_agid = Portal::UtenteFederaEmiliaRomagna.load(:id => spid_id)
                    spid_code = user_agid.tracciature_federa.last.spid_code
                else
                    spid_code = modulo.spid_code
                end    
                spid_auth = true
            end 

            #Protocollo?
            protocollo = Moduli::Protocollo.new(modulo, allegati_ente)
            esito_protocollazione = nil
            data_ora_invio = nil
            if protocollo.invio_tramite_email? #Se il protocollo impostato richiede interoperabilità tramite webservice (tipo IRIDE WEB di Maggioli) allora non verranno inviate le email ai 
                                              #destinatari definiti nel model tipo_modulo
                proto_doc_xml = protocollo.genera

                if proto_doc_xml
                    proto_attachment = {
                        :filename => 'segnatura.xml',
                        :filetype => 'xml',
                        :mime_type => 'text/xml',
                        :content => proto_doc_xml
                    }
                    allegati_ente << proto_attachment
                end
                mail_invio_moduli.each{ |ind_email_destinatari|
                    #controllo se il compilatore è una persona fisica o giuridica
                    if !modulo.utente.ditta.blank? && !modulo.utente.ditta.ragione_sociale.blank?
                        scene.ditta_presente = true
                        nominativo = modulo.utente.ditta.ragione_sociale
                        scene.nominativo = nominativo
                        scene.partita_iva = modulo.utente.ditta.partita_iva
                        scene.pec_azienda = modulo.utente.ditta.pec_azienda
                        scene.email_azienda = modulo.utente.ditta.email_azienda
                    else
                        nominativo = "#{nome_compilatore} #{cognome_compilatore}"
                    end
                    if modulo.respond_to?(:pagamenti_collegati) && !modulo.pagamenti_collegati.blank?
                        totale_pagamenti_collegati = BigDecimal.new("0.0")
                        array_pagamenti_collegati = []
                        modulo.pagamenti_collegati.each{ |pagamento_collegato|
                            totale_pagamenti_collegati += pagamento_collegato.importo_decimale
                            hash_pagamento_collegato = {}
                            hash_pagamento_collegato['tipo_dovuto'] = pagamento_collegato.dovuto.tipo_dovuto.titolo
                            hash_pagamento_collegato['importo'] = pagamento_collegato.importo_decimale.lformat
                            hash_pagamento_collegato['iuv'] = pagamento_collegato.iuv
                            hash_pagamento_collegato['data_pagamento'] = pagamento_collegato.data_pagamento.lformat unless pagamento_collegato.data_pagamento.blank?
                            array_pagamenti_collegati << hash_pagamento_collegato
                        }
                        scene.totale_pagamenti = totale_pagamenti_collegati.lformat
                        scene.array_pagamenti_collegati = array_pagamenti_collegati
                    end
                    scene.nome_compilatore = modulo.utente.nome
                    scene.cognome_compilatore = modulo.utente.cognome
                    scene.cf_compilatore = modulo.utente.codice_fiscale
                    scene.email_compilatore = modulo.utente.email
                    scene.telefono_compilatore = modulo.utente.telefono
                    scene.cellulare_compilatore = modulo.utente.cellulare
                    scene.pec_compilatore = modulo.utente.pec
                    scene.codice_controllo = modulo.chiave
                    scene.nome_modulo = modulo.tipo_modulo.nome
                    scene.nome_del_procedimento = modulo.tipo_modulo.procedimento.nome unless modulo.tipo_modulo.procedimento.blank?
                    scene.nome_del_responsabile = modulo.tipo_modulo.responsabile.nome unless modulo.tipo_modulo.responsabile.blank?
                    scene.nome_del_settore = modulo.tipo_modulo.settore.nome unless modulo.tipo_modulo.settore.blank?
                    scene.spid_code = spid_code
                    scene.spid_auth = spid_auth
                    #Questa parte sotto non dovrebbe servire..(Fabiano)
                    if (!@request.session[:auth].blank? && !@request.session[:auth]["Portal::UtenteSpidAgid"].blank?)
                        spid_id = @request.session[:auth]["Portal::UtenteSpidAgid"][:id]
                        user_agid = Portal::UtenteSpidAgid.load(:id => spid_id)
                        unless user_agid.blank?
                            scene.spid_code = user_agid.spid_code 
                            scene.spid_auth = true
                        end 
                    end
                    data_ora_invio = DateTime.now
                    headers_comune = {'Subject' =>  "Istanza per: #{modulo.tipo_modulo.nome} nr. #{modulo.id} del #{data_ora_invio.lformat(:short)} inviata da #{nominativo}"}
                    #Verifico quale deve essere il sender della mail, se dinamico in base alla mail dell'utente oppure prefissato
                    if Spider.conf.get('moduli.protocollo_mittente')
                        from_mail = Spider.conf.get('moduli.protocollo_mittente')
                    else
                        from_mail = modulo.utente.email
                    end
                    send_email('invio_modulo_ente', scene, from_mail, ind_email_destinatari, headers_comune ,allegati_ente)
                }
            else
                #Qui fa generazione e invio tramite webservice del protocollo
                esito_protocollazione = protocollo.genera
            end
            if !esito_protocollazione.blank? && esito_protocollazione != 'ok' #Significa che ci sono stati problemi durante l'invio ai fini della protocollazione
                    #aggiorno il campo modulo.protocollo_numero una stringa x identificare che non è stato inviato al protocollo via WS x errori vari
                    modulo.protocollo_numero ||= 'errore_durante_protocollazione'
                    modulo.save
                    raise Exception.new('Problemi di invio tramite WS durante protocollazione modulo')
            else
                modulo.stato = 'inviato'
                modulo.inviato = data_ora_invio || DateTime.now
                #se sul tipo modulo presente procedimento amministrativo + responsabile + settore, inviamo la ricevuta telematica al compilatore.
                if !modulo.tipo_modulo.settore.blank? && !modulo.tipo_modulo.settore.nome.blank? && !modulo.tipo_modulo.procedimento.blank? && !modulo.tipo_modulo.procedimento.nome.blank? && !modulo.tipo_modulo.responsabile.blank? && !modulo.tipo_modulo.responsabile.nome.blank? 
                    scene_ricevuta_telematica = Spider::Scene.new
                    scene_ricevuta_telematica.nome_del_procedimento = modulo.tipo_modulo.procedimento.nome
                    scene_ricevuta_telematica.nome_del_responsabile = modulo.tipo_modulo.responsabile.nome
                    scene_ricevuta_telematica.nome_del_settore = modulo.tipo_modulo.settore.nome
                    scene_ricevuta_telematica.termine_procedimento = modulo.tipo_modulo.procedimento.termine
                    headers_compilatore = { 'Subject' =>  "#{modulo.tipo_modulo.nome} nr #{modulo.id} del #{modulo.confermato.lformat(:short)}" }
                    send_email('ricevuta_telematica', scene_ricevuta_telematica, Spider.conf.get('portal.email_from'), modulo.utente.email, headers_compilatore , nil)
                end
            end
            modulo.save

        end

        

    end

end

# -*- encoding : utf-8 -*-
require 'apps/comunicazioni/controllers/gestione_comunicazioni_controller'

module Comunicazioni
    
    class ComunicazioniController < Spider::PageController
        
        include Portal::ControllerServizio
        include StaticContent

        Comunicazioni.verifica_presenza_configurazioni

        route /(\d+)\/items_da_leggere/, :items_da_leggere
        route /(\d+)\/pubblica/, :comunicazione_pubblica
        route /(\d+)\/privata/, :comunicazione_privata
        route /(\d+)/, :comunicazione_pubblica
        route /elenco_pubblico/, :index
        route /download_immagine/, :download_immagine
        route /feed_rss/, :feed_rss

        #se integro comunicazioni nel sito aziendale con prisma uso il layout di prisma
        if Spider.conf.get('comunicazioni.integra_in_prisma') == true
	        layout ['/prisma/prisma_principale', 'comunicazioni'], :assets => 'comunicazioni'
        else
            begin
                if Spider.conf.get('comunicazioni.servizio_privato') == true
                    servizio_portale :id => "comunicazioni", :nome => "Comunicazioni", :accesso => "registrati", :web_service => true 
                else
                    servizio_portale :id => "comunicazioni", :nome => "Comunicazioni", :accesso => "pubblico", :web_service => true
                end 
                include Spider::Messenger::MessengerHelper
                include Portal::HelperAutenticazione
            rescue NameError
            end 
            layout ['/portal/portal', 'comunicazioni'], :assets => 'comunicazioni' 
        end

        

        def before(action='', *params)
            super
            #carico la scene da prisma per avere i dati nel top e nel bottom del layout del sito
            if Spider.conf.get('comunicazioni.integra_in_prisma') == true && !defined?(Prisma).blank?
                Prisma.carica_scene_sito_aziendale(@scene)
            end
        end


        __.html
        __.json
        def index
            numero_comunicazioni = Spider.conf.get('comunicazioni.numero_comunicazioni_index')
            if Spider.conf.get('comunicazioni.integra_in_prisma') == true && !defined?(Prisma).blank?
                comunicazioni_portale = self.class.scarico_comunicazioni_prisma(numero_comunicazioni)
            else
                comunicazioni_portale = self.class.scarico_comunicazioni_portale(numero_comunicazioni)
            end
            #gestione dimensioni delle immagini
            x_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[0].to_i
            @scene.x_resolution = x_resolution

            #gestione multilingua
            lingue_traduzioni = Spider.conf.get('comunicazioni.lingue_traduzioni')
            unless lingue_traduzioni.blank? 
                lingua_param = @request.params['lang']
                lingua_param ||= lingue_traduzioni.first
                unless lingua_param.blank?
                    comunicazioni_portale.query.condition = comunicazioni_portale.query.condition.and{ |com| (com.lingua == lingua_param) | (com.lingua == nil)}
                end
            end
            #TO-DO: PAGINAZIONE DA FARE....
            # if p = @request.params['page']
            #     comunicazioni_portale.offset((p.to_i-1)*10 + 1)
            # end
            if @request.format == :json
                comunicazioni_json = []
                comunicazioni_portale.each{ |com|
                    hash_comunicazione = {}
                    hash_comunicazione = com.cut( :id => 0, :titolo => 0, :data_da => 0, :data_a => 0, :gruppi => { :nome => 0 })
                    comunicazioni_json << hash_comunicazione.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) } 
                } 

                hash_output = { 'type' => "lista_comunicazione",
                                'schede' => comunicazioni_json 
                                }
                $out << hash_output.to_json
            else
                @scene.tipologia_comunicazione = 'pubblica'
                @scene.comunicazioni = comunicazioni_portale
                render 'index'
            end
        end

         __.html :template => 'archivio'
        def archivio
            #scarico tutte le comunicazioni, anche quelle scadute
            if Spider.conf.get('comunicazioni.integra_in_prisma') == true && !defined?(Prisma).blank?
                comunicazioni = Comunicazione.where{ |com| (com.canali_pubblicazione .ilike "%prisma%") & (com.pubblica == true) & (com.stato == 'pubblicata') }.order_by(:data_da, :desc)
            else
                comunicazioni = Comunicazione.where{ |com| (com.mostra_in_portal == true) & (com.pubblica == true) & (com.stato == 'pubblicata') }.order_by(:data_da, :desc)
            end
            @scene.comunicazioni = comunicazioni
        end

        __.action
        def gestione(action)
            redirect GestioneComunicazioniController.http_s_url(action)
        end

        __.html
        __.json
        def elenco_privato
            autenticazione_necessaria
            utente = @request.utente_portale
            #gestione multilingua
            lingua_param = @request.params['lang']
            unless lingua_param.blank?
                #mostro tutte le news private, con la lingua voluta, pubblicate, ordinate per data di creazione.
                comunicazioni_utente = Comunicazione::Utenti.where{ |com_u| (com_u.comunicazione.mostra_in_portal == true) & (com_u.comunicazione.lingua == lingua_param.downcase) & (com_u.utente == utente) & (com_u.comunicazione.pubblica .not true) & (com_u.comunicazione.stato == 'pubblicata') & ((com_u.comunicazione.data_da <= Date.today) & (com_u.comunicazione.data_a >= Date.today )) }.order_by('comunicazione.data_da', :desc)
            else
                #mostro tutte le news private e pubblicate, ordinate per data di creazione.
                comunicazioni_utente = Comunicazione::Utenti.where{ |com_u| (com_u.comunicazione.mostra_in_portal == true) & (com_u.utente == utente) & (com_u.comunicazione.pubblica .not true) & (com_u.comunicazione.stato == 'pubblicata') & ((com_u.comunicazione.data_da <= Date.today) & (com_u.comunicazione.data_a >= Date.today )) }.order_by('comunicazione.data_da', :desc)
            end

            #gestione dimensioni delle immagini
            x_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[0].to_i
            @scene.x_resolution = x_resolution

            if @request.format == :json
                comunicazioni_json = []
                comunicazioni_utente.each{ |com|
                    hash_comunicazione = {}
                    hash_comunicazione = com.comunicazione.cut( :id => 0, :titolo => 0, :data_da => 0, :data_a => 0)
                    hash_comunicazione[:letta] = com.letta
                    comunicazioni_json << hash_comunicazione.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) } 
                } 

                hash_output = { 'type' => "lista_comunicazione",
                                'schede' => comunicazioni_json
                                     }
                $out << hash_output.to_json
            else
                @scene.tipologia_comunicazione = 'privata'
                @scene.comunicazioni_utente = comunicazioni_utente
                render 'elenco_privato'
            end
        end 

        __.html
        __.json
        def comunicazione_privata(id)
            #autenticazione_necessaria
            comunicazione = Comunicazioni::Comunicazione.load(:id => id)
            if @request.format == :json
                if comunicazione.nil? 
                    $out << { :ok => "false",
                              :cod_errore => "comunicazione_inesistente" }.to_json 
                    done
                end
            else
                redirect self.class.http_s_url if comunicazione.nil?   
            end
            utente = @request.utente_portale
            #carico le comunicazioni associate all'utente
            com = Comunicazioni::Comunicazione::Utenti.where{ |c| (c.utente == utente) & (c.comunicazione == comunicazione) }
            unless com[0].blank?
                com[0].letta = true
                com[0].save
            else
                #sto provando a vedere una comunicazione privata che non è mia
                if @request.format == :json
                    $out << { :ok => "false",
                              :cod_errore => "comunicazione_non_personale" }.to_json
                    done
                else
                    redirect self.class.http_s_url 
                end    
            end

            #gestione dimensioni delle immagini
            x_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[0].to_i
            @scene.x_resolution = x_resolution

            if @request.format == :json
                com_utf8 = comunicazione.cut(:id => 0, :titolo => 0, :testo => 0, :data_da => 0, :data_a => 0, :lingua => 0).each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                #se è presente un tag img estraggo l'immagine
                uuid_image = com_utf8[:testo].gsub("\"","'")[/.<img.+src='\/spider\/images\/([\w|-]+)'/,1]
                unless uuid_image.blank?
                    img = Spider::Images::Image.load(:uuid => uuid_image) 
                    com_utf8[:url_immagine] = img.url 
                end
                com_utf8[:testo_html] = com_utf8[:testo]
                #tolgo l'html dal testo
                com_utf8[:testo] = Hpricot.uxs(com_utf8[:testo].gsub(/<\/?[^>]*>/, "")).strip.gsub(/\\r\\n/," ").gsub(/\\t/," ")
                $out << {:scheda => com_utf8,
                         :type => 'comunicazione'
                        }.to_json
            else
                @scene.tipo_elenco = 'elenco_privato'
                @scene.comunicazione = comunicazione
                render 'dettaglio_comunicazione'
            end
        end

        __.html
        __.json
        def comunicazione_pubblica(id)
            comunicazione = Comunicazioni::Comunicazione.load(:id => id, :pubblica => 'true')
            #comunicazione = Comunicazioni::Comunicazione.where{ |com| (com == Comunicazioni::Comunicazione.new(id)) & (com.pubblica == true)}
            if @request.format == :json
                if comunicazione.nil?
                    $out << { :ok => "false",
                              :cod_errore => 1,
                              :msg_errore => "comunicazione_inesistente" }.to_json
                    done
                end
            else
                redirect self.class.http_s_url if comunicazione.nil?   
            end
            #gestione dimensioni delle immagini
            x_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[0].to_i
            @scene.x_resolution = x_resolution

            #setto i dati per open_graph, usato da facebook
            @scene.og_url = Comunicazioni::ComunicazioniController.http_s_url(comunicazione.id.to_s+"/pubblica/?fbrefresh=CAN_BE_ANYTHING")
            @scene.og_title = comunicazione.titolo   
            @scene.og_description = comunicazione.testo_breve.blank? ? '' : comunicazione.testo_breve 
            @scene.og_image = Comunicazioni::ComunicazioniController.https_url('download_immagine?id_com='+comunicazione.id.to_s)
            @scene.og_site_name = Spider.conf.get('ente.nome') || Spider.conf.get('portal.nome') 
            @scene.og_type = "article"
            @scene.og_url = @request.env["rack.url_scheme"]+"://"+@request.env["HTTP_HOST"]+@request.env["REQUEST_URI"]

	       @scene.og_url = @scene.og_url.gsub('https', 'http')
	       @scene.og_image = @scene.og_image.gsub('https', 'http')           


            if @request.format == :json
                com_utf8 = comunicazione.cut(:id => 0, :titolo => 0, :testo_breve => 0, :testo => 0, :data_da => 0, :data_a => 0, :lingua => 0, :gruppi => { :nome => 0 }).each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v) }
                #se ho caricato una immagine come campo separato mostro quella
                unless comunicazione.immagine.blank?
                    com_utf8[:url_immagine] = comunicazione.immagine
                else
                    #se è presente un tag img estraggo l'immagine
                    uuid_image ||= com_utf8[:testo].gsub("\"","'")[/.<img.+src='\/spider\/images\/([\w|-]+)'/,1]
                    unless uuid_image.blank?
                        img = Spider::Images::Image.load(:uuid => uuid_image) 
                        com_utf8[:url_immagine] = img.url 
                    end
                end
                com_utf8[:testo_html] = com_utf8[:testo]
                #tolgo l'html dal testo
                com_utf8[:testo] = Hpricot.uxs(com_utf8[:testo].gsub(/<\/?[^>]*>/, "")).strip.gsub(/\\r\\n/," ").gsub(/\\t/," ")
                #output json senza conversione nel formato uXXXX, possibile xss!
                #$out << JSON.generate({:scheda => com_utf8, :type => 'comunicazione'})
                $out << {:scheda => com_utf8,
                         :type => 'comunicazione'
                        }.to_json
            else
                @scene.tipo_elenco = 'elenco_pubblico'
                @scene.comunicazione = comunicazione
                render 'dettaglio_comunicazione'
            end
        end


        __.json
        def items_da_leggere(id_utente)
            num_com = self.class.comunicazioni_private_non_lette(id_utente)
            $out << {:num_com => num_com}.to_json
        end

        def self.numero_comunicazioni_portale
            comunicazioni = self.scarico_comunicazioni_portale 
            comunicazioni.length 
        end

        def self.scarico_comunicazioni_portale(limit=nil)
            #scarico tutte le comunicazioni
            comunicazioni = Comunicazione.where{ |com| (com.mostra_in_portal == true) & (com.pubblica == true) & (com.stato == 'pubblicata') & (com.data_da <= Date.today) & (com.data_a >= Date.today ) }.order_by(:data_da, :desc)
            comunicazioni.limit(limit.to_i) unless limit.blank?
            comunicazioni                      
        end

        def self.scarico_comunicazioni_prisma(limit=nil)
            #scarico tutte le comunicazioni
            comunicazioni = Comunicazione.where{ |com| (com.canali_pubblicazione .ilike "%prisma%") & (com.pubblica == true) & (com.stato == 'pubblicata') & (com.data_da <= Date.today) & (com.data_a >= Date.today ) }.order_by(:data_da, :desc)
            comunicazioni.limit(limit.to_i) unless limit.blank?
            comunicazioni                      
        end

        #metodo che restituisce le ultime tre comunicazioni pubblicate e valide sul portale, usata per l'app ios
        def self.last_items
            ultime_comunicazioni = Comunicazione.where{ |com| (com.mostra_in_portal == true) & (com.pubblica == true) & (com.stato == 'pubblicata') & (com.data_da <= Date.today) & (com.data_a >= Date.today ) }.order_by(:data_da, :desc)
            ultime_comunicazioni.limit(3)
            cut = {:titolo => 0, :data_da => 0 }
            last_items = []
            comunicazioni = ultime_comunicazioni.map { |comunicazione|
                com = comunicazione.cut(cut)
                item = {
                    'titolo' => com[:titolo],
                    'data' => com[:data_da]
                }.each { |k, v| v = (v.respond_to?(:force_encoding) ? v.force_encoding('UTF-8') : v)}
                last_items << item 
            }
            last_items
        end

        #metodo che chiamato da un template restituisce il numero di comunicazioni private, non lette, non scadute e in corso di pubblicazione
        def self.comunicazioni_private_non_lette(id_utente)
            cont = 0
            unless id_utente.blank?
                utente = Portal::Utente.new(id_utente)
                non_lette = Comunicazioni::Comunicazione::Utenti.where{ |c| (c.letta == nil) & (c.comunicazione.mostra_in_portal == true) & (c.comunicazione.pubblica .not true) & (c.comunicazione.stato == 'pubblicata') & (c.utente == utente) & ((c.comunicazione.data_da <= Date.today) & (c.comunicazione.data_a >= Date.today )) }
                cont = non_lette.length
            end
            cont
        end

        #ritorno true se il servizio mostra qualcosa nell'area riservata dell'app
        def self.ws_area_riservata
            Spider.conf.get('comunicazioni.ws_area_riservata_abilitata') == true
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
                path_file = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+dir_immagine+"/img_resized/"
            else
                path_file = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+dir_immagine+"/"
            end
            
            unless path_file.blank?
                file = File.join(path_file,comunicazione.immagine)
                @response.headers['Content-disposition'] = "inline; filename=#{comunicazione.immagine.gsub(' ','_')}"
                @response.headers['Content-Length'] = file.size
                @response.headers['Last-Modified'] = Time.now.httpdate
                output_static(file)
            else
               return ""
            end
        end


        __.action
        def aggiungi_segnalazione
            #autenticazione_necessaria
            gestore_segnalazione = @request.params['gestore_segnalazione']
            if @request.post? && !gestore_segnalazione.blank?
                #controllo il gestore segnalazioni
                gestore_klass = nil
                Comunicazioni.gestori_segnalazioni.each{ |gestore|
                    if gestore.dettagli_gestore_segnalazioni[:id] == gestore_segnalazione
                        gestore_klass = gestore
                        break
                    end
                }
                unless gestore_klass.blank?
                    gestore_istanza = gestore_klass.new
                    esito_ins_segnalazione = gestore_istanza.inserisci_segnalazione(@request.params)
                    if esito_ins_segnalazione
                        $out << {:ok => 'true'}.to_json
                    else
                        $out << {:ok => 'false', :cod_errore => 'Errore in inserimento segnalazione'}.to_json
                    end
                else #gestore non trovato, ritorno errore
                    $out << {:ok => 'false', :cod_errore => 'Gestore non configurato'}.to_json
                    
                end
            else 
                #chiamata in get, ritorno errore
                $out << {:ok => 'false', :cod_errore => 'Gestore non configurato'}.to_json
            end
        end


        __.html :template => 'test_invio'
        def test_invio
        end


        __.action
        def feed_rss
            path_file_feed_xml = File.join(Spider.paths[:root],"public/rss_comunicazioni",'feed.xml')
            $out << File.open(path_file_feed_xml, "r") { |file| file.read }
        end


    end
    
end

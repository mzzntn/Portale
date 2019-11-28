# -*- encoding : utf-8 -*-
require 'csv'

module Moduli
    
    class GestioneModuliController < Spider::Admin::AppAdminController
        layout ['/core/admin/admin', 'gestione/gestione'], :assets => 'moduli_admin'
    
        include StaticContent
        
        #includo il messenger helper per mandare gli sms
        include Spider::Messenger::MessengerHelper rescue NameError

        include Spider::Auth::AuthHelper
        #tolto per usare amministratori servizi
        #require_user Spider::Auth::SuperUser
        
    
        #route /(\d+)\/vedi_titolo/, :vedi_titolo
        #route /(\d+)\/mostra_utenti/, :mostra_utenti
        route /(\d+)\/crea_modulo/, :crea_modulo
        route /(\d+)\/elenco_moduli/, :elenco_moduli
        route /(\w+)\/elenco_moduli/, :elenco_moduli    
        route /(\d+)\/importa_modulo/, :importa_modulo
        route /(\d+)\/associa_importi/, :associa_importi
        route /(\d+)\/nuovo_importo/, :nuovo_importo
        route /(\d+)\/nuovo_importo\/(\d+)/, :nuovo_importo
        #/


        def before(action='', *params)
            super
            #nuove modifiche per settori da hippo/inseriti da admin
            settori = Portal::Hippo::Settore.all
            @scene.settori_presenti = !settori.blank?
            #se l'admin servizio moduli non ha settori associati li vede e gestisce tutti, come l'admin generale
            if @request.user.is_a?(Spider::Auth::SuperUser)
                @scene.admin_portal = true
            end
            if (@request.user.is_a?(Portal::Amministratore) && @request.user.settore.blank?)
                @scene.admin_tutti_servizi = true
            end
            #se l'admin dei servizi ha un settore associato gestice solo quello
            if @request.user.is_a?(Portal::Amministratore) && !@request.user.settore.blank?
                @scene.settore_abilitato = @request.user.settore #serve per filtrare solo il settore assegnato
                @scene.admin_servizi = true
            end
        end


        __.html
        def index(param=nil)
            #se arrivo qui dal link del menu in get cancello la sessione
            unless @request.params['r'].blank?
                @request.session['id_settore_modulo'] = nil
            else
                unless @request.session['id_settore_modulo'].blank?
                    redirect self.class.http_s_url("#{@request.session['id_settore_modulo']}/elenco_moduli")
                    done
                end
            end
            @scene.sezione = 'gestione_moduli'
            @scene.url_nuovo_tipo_modulo = self.class.http_s_url('nuovo_tipo_modulo')
            @scene.url_gestione_tipo_modulo = self.class.http_s_url('nuovo_tipo_modulo')
            @scene.esito_azione = @request.session.flash['esito_azione']
            @scene.errore = @request.session.flash['errore']
            if @scene.admin_portal || @scene.admin_tutti_servizi
                @scene.settori_modulo = Portal::Hippo::Settore.all
            else
                @scene.settori_modulo = [@scene.settore_abilitato]
            end
            #se non ci sono settori mostro il crud, e se c'è un admin con un servizio impostato filtro il crud
            if @scene.settori_presenti.blank? && @scene.admin_servizi
                template = init_template 'gestione/index'
                crud = template.widgets[:crud_tipi_modulo]
                #faccio vedere i settori abilitati e i moduli che non hanno settore (generico o nullo)
                crud.fixed = { :settore => [@scene.settore_abilitato.id.to_i, 0, nil] }
                template.exec
            end
            if @request.post? && ( @request.post["_w"].blank? || (!@request.post["_w"].blank? && @request.post["_w"]["crud_tipi_modulo"]['delete'].blank?) )
                settore_selezionato = @request.params['settore']
                if settore_selezionato.blank?
                    @scene.errore = "Selezionare un settore o il 'Settore Generico'"
                else
                    #salvo in sessione il settore selezionato
                    @request.session['id_settore_modulo'] = settore_selezionato
                    redirect self.class.http_s_url("#{settore_selezionato}/elenco_moduli")
                    done
                end
            end
            render 'gestione/index'
        end

        __.html :template => 'gestione/elenco_moduli'
        def elenco_moduli(id_settore, id_settore_azione_new=nil)
            #sono nel caso in cui col crud, popolato in base ad un settore, voglio creare un nuovo modulo
            @scene.settore_selezionato = @request.session['id_settore_modulo']
            @scene.esito_azione = @request.session.flash['esito_azione']
            @scene.errore = @request.session.flash['errore']
            @request.session.flash['esito_azione'] = nil
            if id_settore == 'new'
                @request.session['id_settore_modulo'] = id_settore_azione_new
                redirect self.class.http_s_url('nuovo_tipo_modulo')
            end
            @scene.url_gestione_tipo_modulo = self.class.http_s_url('nuovo_tipo_modulo')
        
            tipi_modulo = Moduli::TipoModulo.all
            @scene.tipi_moduli_presenti = tipi_modulo.length > 0

            unless id_settore == 'tutti'
                #controllo se ci sono moduli col settore selezionato
                settore = Portal::Hippo::Settore.load(:id => id_settore)
                #se passo un id settore diverso da zero e non ce ne sono in db
                if settore.blank? && id_settore != '0'
                    @request.session.flash['errore'] = "Settore non presente"
                    redirect self.class.http_s_url
                    done
                end
                @scene.settore_abilitato = (id_settore == '0' ? 'Generico' : settore )
                if id_settore == '0'
                    tipi_modulo = Moduli::TipoModulo.where{ |tipo_modulo| (tipo_modulo.settore == nil) }
                else
                    tipi_modulo = Moduli::TipoModulo.where{ |tipo_modulo| tipo_modulo.settore == settore  }
                end
                
                @scene.errore = "Non sono presenti moduli con il settore selezionato" if tipi_modulo.length == 0
                @scene.tipi_moduli_presenti = tipi_modulo.length > 0
                template = init_template 'gestione/elenco_moduli'
                crud = template.widgets[:elenco_tipi_modulo]
                #faccio vedere i settori abilitati e i moduli che non hanno settore
                if id_settore == '0'
                    crud.fixed = { :settore => [id_settore,nil] }
                else
                    crud.fixed = { :settore => [id_settore] }
                end
                
                template.exec
            end
           
        end



        __.html :template => 'gestione/nuovo_tipo_modulo'
        def nuovo_tipo_modulo(id_tipo_modulo=nil)
            #se Pagamenti attivi mostro il bottone per gestire gli importi
            @scene.pagamenti_attivi = true if defined?(Pagamenti) != nil
            #Versione con modulo presente
            #carico i vari tipi di template per i moduli
            shtmlfiles = File.join("moduli/**", "*.shtml")
            @scene.tipi_modulo = {}
            Dir.glob(shtmlfiles).map{ |modulo|
                next if modulo[0] == '_'
                cliente = modulo.gsub(/moduli\/(.*)\/.*\/.*.shtml/) { $1 }
                nome = modulo.gsub(/moduli\/.*\/(.*).shtml/) { $1 }
                next if nome[0] == '_'
                nome = nome.capitalize.gsub('_',' ')
                path = modulo.gsub(/moduli\/(.*)\/(.*).shtml/) { $2 }
                @scene.tipi_modulo[path] = nome+" ("+cliente+")"
            }            

            #creo array coi settori che l'utente può modificare
            if @scene.admin_portal || @scene.admin_tutti_servizi
                settori = Portal::Hippo::Settore.all.to_a
                settori << Portal::Hippo::Settore.new({ :id => 0, :nome => 'Settore Generico' }) #aggiungo settore generico
            else
                settori = [@request.user.settore]
            end
            @scene.settori_modulo = settori
            #se ho in sessione un id di un settore lo metto nella scene
            @scene.settore_da_crud = @request.session['id_settore_modulo']
            @scene.settore_abilitato = true if Spider.conf.get('portal.attiva_settori_hippo')

            #se ho la protocollazione via IRIDE_WEB o via WEBSERVICES in generale attivo i campi parametrici da impostare a livello di singolo modulo
            @scene.webservice_abilitato = !Spider.conf.get('moduli.protocollo').blank?

            procedimenti = Portal::Hippo::Procedimento.all
            @scene.procedimenti_modulo = procedimenti

            responsabili = Portal::Hippo::Responsabile.all
            @scene.responsabili_modulo = responsabili
            
            cat_giuridiche = Moduli::CategoriaGiuridica.all
            @scene.cat_giuridiche = cat_giuridiche

            if defined?(MuSe) != nil
                @scene.servizi_su_richiesta = MuSe::Iscrizione::Prestazione.all
            end
            errori = []
            @scene.campo_errore = {
                'nome'                  => "",
                'tipo'                  => "",
                'stato_visualizzazione' => "",
                'descrizione'           => "",
                'date'                  => "",
                'mail_destinatari'      => "",
                'classifica'            => "",
                'tipo_documento'        => "",
                'in_carico_a'           => "",
                'ore_dal'               => "",
                'ore_al'                => ""
            }

            #inizializzo così dati['nome'] ecc ritorna nil e non ho più errore    
            dati = {}
            dati['disponibile_dal'] = nil
            dati['disponibile_al'] = nil

            @scene.azione = "Nuova"
            unless id_tipo_modulo.blank?
                @scene.azione = "Modifica"
                #sono in modifica, carico i dati
                tipo_modulo = Moduli::TipoModulo.new(id_tipo_modulo.to_i)

                #mostro l'url del modulo lato utente
                @scene.url_modulo_utente = Moduli::ModuliController.http_s_url(id_tipo_modulo+'/modulo')

                #controllo di poter modificare il modulo in base al settore
                #if @scene.settore_abilitato && (!tipo_modulo.settore.blank? && tipo_modulo.settore != @scene.settore_abilitato)
                    
                if settori.blank? || (!settori.blank? && !tipo_modulo.settore.blank? && !settori.include?(tipo_modulo.settore)) 
                    @request.session.flash['errore'] = "Il modulo non appartiene al tuo settore di competenza."
                    @scene.dati = dati
                    redirect self.class.http_s_url
                    done
                end

                dati.merge!({
                    'nome'                          => tipo_modulo.nome,
                    'tipo'                          => tipo_modulo.tipo,
                    'stato_visualizzazione'         => tipo_modulo.stato_visualizzazione,
                    'descrizione'                   => tipo_modulo.descrizione,
                    'disponibile_dal'               => (tipo_modulo.disponibile_dal.blank? ? "" : tipo_modulo.disponibile_dal.to_date.lformat(:short)),
                    'disponibile_al'                => (tipo_modulo.disponibile_al.blank? ? "" : tipo_modulo.disponibile_al.to_date.lformat(:short)),
                    'ore_dal'                       => (tipo_modulo.disponibile_dal.blank? ? "00:00" : tipo_modulo.disponibile_dal.to_time.lformat(:short)),
                    'ore_al'                        => (tipo_modulo.disponibile_al.blank? ? "00:00" : tipo_modulo.disponibile_al.to_time.lformat(:short)),
                    'tipo_compilazione'             => tipo_modulo.tipo_compilazione,
                    'mail_destinatari'              => tipo_modulo.mail_destinatari, 
                    'mail_a_compilatore'            => tipo_modulo.mail_a_compilatore,
                    'solo_pratiche'                 => tipo_modulo.solo_pratiche,
                    'per_iscrizioni_scolastiche'    => tipo_modulo.per_iscrizioni_scolastiche,
                    'settore'                       => tipo_modulo.settore,
                    'procedimento'                  => tipo_modulo.procedimento,
                    'responsabile'                  => tipo_modulo.responsabile,
                    'tipo_firma'                    => (tipo_modulo.tipo_firma.blank? ? nil : tipo_modulo.tipo_firma.id),
                    'cat_giuridica'                 => tipo_modulo.categoria_giuridica.blank? ? nil : tipo_modulo.categoria_giuridica.id.to_s,
                    'servizio'                      => tipo_modulo.servizio
                })

                if @scene.webservice_abilitato
                    campi_ws = {
                        'classifica'            => tipo_modulo.classifica,
                        'tipo_documento'        => tipo_modulo.tipo_documento,
                        'in_carico_a'           => tipo_modulo.in_carico_a
                    }
                    dati.merge!(campi_ws)
                end
            end    

            if @request.post? && !chiamata_ajax?(@request)
                dati = @request.params['dati']
                if dati['nome'].blank?
                    @scene.campo_errore['nome'] = 'error'
                    errori << "Il campo Nome non può essere vuoto"
                else
                    if id_tipo_modulo.blank? #caso nuovo modulo
                        #controllo che il nome del modulo sia univoco
                        nome_modulo_uguale = TipoModulo.load(:nome => dati['nome'])
                        unless nome_modulo_uguale.blank?
                            @scene.campo_errore['nome'] = 'error'
                            errori << "Il nome deve essere univoco, esiste un altro modulo con stesso nome."
                        end
                    end
                end

                # if dati['tipo'].blank?
                #     @scene.campo_errore['tipo'] = 'error'
                #     errori << "Selezionare un Template di modulo"
                # end
                if dati['tipo_compilazione'].blank?
                    @scene.campo_errore['tipo_compilazione'] = 'error'
                    errori << "Selezionare la tipologia di compilazione"
                end

                if @scene.webservice_abilitato
                    if dati['classifica'].blank?
                        @scene.campo_errore['classifica'] = 'error'
                        errori << "Inserire la classificazione ai fini della protocollazione"
                    end
                    if dati['tipo_documento'].blank?
                        @scene.campo_errore['tipo_documento'] = 'error'
                        errori << "Inserire la catalogazione del tipo di documento ai fini della protocollazione"
                    end
                    if dati['in_carico_a'].blank?
                        @scene.campo_errore['in_carico_a'] = 'error'
                        errori << "Inserire il codice dell'Unità Operativa ai fini della protocollazione"
                    end
                end
                
                #controllo sulle ore di inizio e fine validità
                unless dati['ore_dal'].blank?
                    if (dati['ore_dal'] =~ /^(2[0-3]|[01]?[0-9]):([0-5]?[0-9])$/).nil?
                        @scene.campo_errore['ore_dal'] = 'error'
                        errori << "Inserire gli orari in formato HH:MM"
                    end
                end

                unless dati['ore_al'].blank?
                    if (dati['ore_al'] =~ /^(2[0-3]|[01]?[0-9]):([0-5]?[0-9])$/).nil?
                        @scene.campo_errore['ore_al'] = 'error'
                        errori << "Inserire gli orari in formato HH:MM"
                    end
                end

                unless @request.params['_w'].blank?
                    # controllo che se ci sono le due dati la data di inizio disponibilità deve venire prima della data di fine disponibilità
                    unless @request.params['_w']['disponibile_dal'].blank?
                        disponibile_dal_str = dati['ore_dal'].blank? || !@scene.campo_errore['ore_dal'].blank? ? @request.params['_w']['disponibile_dal']+" 00:00" : @request.params['_w']['disponibile_dal']+" "+dati['ore_dal']
                        disponibile_dal = DateTime.strptime(disponibile_dal_str, '%d/%m/%Y %H:%M') unless disponibile_dal_str.blank?
                    end
                    unless @request.params['_w']['disponibile_al'].blank?
                        disponibile_al_str = dati['ore_al'].blank? || !@scene.campo_errore['ore_al'].blank? ? @request.params['_w']['disponibile_al']+" 00:00" : @request.params['_w']['disponibile_al']+" "+dati['ore_al']
                        disponibile_al = DateTime.strptime(disponibile_al_str, '%d/%m/%Y %H:%M') unless disponibile_al_str.blank?
                    end
                    if (!disponibile_dal.blank? && !disponibile_al.blank?) && disponibile_dal > disponibile_al
                        errori << "La data di fine disponibilità del modulo non può essere antecedente alla data di inizio disponibilità"
                        @scene.campo_errore['date'] = "error"
                    else
                        dati['disponibile_dal'] = disponibile_dal
                        dati['disponibile_al'] = disponibile_al
                        @scene.dati = dati
                    end
                end


                if errori.empty?
                    #se non è valorizzato l'id sono in un nuovo inserimento
                    tipo_modulo = TipoModulo.new if id_tipo_modulo.blank?

                    nuovo_settore = ( dati['settore'] == '0' ? nil : dati['settore'] )
                    hash_dati = {
                            :nome => dati['nome'],
                            :tipo => dati['tipo'] || tipo_modulo.tipo,
                            :stato_visualizzazione => dati['stato_visualizzazione'],
                            :descrizione => dati['descrizione'],
                            :disponibile_dal => disponibile_dal,
                            :disponibile_al => disponibile_al,
                            :tipo_compilazione => dati['tipo_compilazione'],
                            :mail_destinatari => dati['mail_destinatari'],
                            :mail_a_compilatore => dati['mail_a_compilatore'],
                            :solo_pratiche => dati['solo_pratiche'],
                            :per_iscrizioni_scolastiche => dati['per_iscrizioni_scolastiche'],
                            :settore => nuovo_settore,
                            :procedimento => dati['procedimento'],
                            :responsabile => dati['responsabile'],
                            :tipo_firma => dati['tipo_firma'],
                            :categoria_giuridica => !dati['cat_giuridica'].blank? ? dati['cat_giuridica'] : nil,
                            :servizio => dati['servizio']
                        }

                    if @scene.webservice_abilitato    
                        hash_dati_webservice = {
                            :classifica => dati['classifica'],
                            :tipo_documento => dati['tipo_documento'],
                            :in_carico_a => dati['in_carico_a']
                        }
                        hash_dati.merge!(hash_dati_webservice)
                    end
                    tipo_modulo.merge_hash(hash_dati)

                    #salvo un testo vuoto per non avere problemi se uno completa solo gli importi
                    contenuto_modulo_default = "<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>Inserire il testo<br><br></body></html>"

                    #se il contenuto dell'editor diverso da null procedo verso la pagina con l'editor
                    if tipo_modulo.contenuto_modulo.blank? 
                        tipo_modulo.contenuto_modulo = contenuto_modulo_default
                    end

                    tipo_modulo.save
                    @request.session.flash['esito_azione'] = "Il modulo è stato salvato con successo."
                    if dati['tipo'].blank? || !tipo_modulo.contenuto_modulo.blank?
                        #creare cartella e file per modulo
                        
                        nome_cartella_modulo = dati['nome'].gsub(/[^a-z^A-Z^0-9]/," ").strip.gsub(/\s+/,"_")

                        crea_cartelle_moduli(nome_cartella_modulo)

                        tipo_modulo.tipo = nome_cartella_modulo #per funzionamento esistente con file caricato
                        tipo_modulo.nome_file = nome_cartella_modulo
                        tipo_modulo.save


                        if !@request.params['submit_importi'].blank?
                            #vado sulla pagina per associare degli importi
                            redirect self.class.http_s_url(tipo_modulo.id.to_s+'/associa_importi')
                        elsif !@request.params['submit_editor'].blank?
                            #vado sulla pagina per editare/importare un modulo se nuovo
                            if tipo_modulo.contenuto_modulo.blank?
                                redirect self.class.http_s_url(tipo_modulo.id.to_s+'/importa_modulo')
                            else
                                redirect self.class.http_s_url(tipo_modulo.id.to_s+'/crea_modulo')
                            end
                        elsif !@request.params['submit_salva'].blank? #se clicco sul 'Salva' salvo solo i dati del modulo
                            redirect self.class.http_s_url
                        else
                            redirect self.class.http_s_url(tipo_modulo.id.to_s+'/crea_modulo')
                        end
                        done
                        
                        
                    else  #versione con modulo :categoria_giuridica => !dati['cat_giuridica'].blank? ? dati['cat_giuridica'] : nilpresente
                        redirect self.class.http_s_url
                        done
                    end
                else
                    #rimango in pagina e mostro gli errori
                    @scene.errori = errori

                end

            end       
            @scene.dati = dati

        end

        
        
        __.html :template => 'gestione/associa_importi'
        def associa_importi(id_tipo_modulo)
            unless id_tipo_modulo.blank?
                #se arrivo in pagina che sto cancellando un importo non mostro i tasti in basso
                if !@request.params['_w'].blank? && !@request.params['_w']['crud_importi']['delete'].blank?
                    @scene.no_bottoni = true
                end
                
                #carico crud con importi associati
                @scene.url_new_importo = self.class.http_s_url(id_tipo_modulo.to_s+'/nuovo_importo')
                @scene.url_view_importo = self.class.http_s_url(id_tipo_modulo.to_s+'/nuovo_importo')
                @scene.url_indietro = self.class.http_s_url('nuovo_tipo_modulo/'+id_tipo_modulo.to_s)

                tipo_modulo = Moduli::TipoModulo.load(:id => id_tipo_modulo.to_i)
                unless tipo_modulo.blank?
                    @scene.id_tipo_modulo = id_tipo_modulo
                    #sono in modifica, carico i dati
                    
                    @scene.nome_tipo_modulo = tipo_modulo.nome
                    #controllo se settore del modulo è gestibile da admin
                    #creo array coi settori che l'utente può modificare
                    if @scene.admin_portal || @scene.admin_tutti_servizi
                        settori = Portal::Hippo::Settore.all.to_a
                        settori << Portal::Hippo::Settore.new({ :id => 0 }) #aggiungo settore generico
                    else
                        settori = [@request.user.settore]
                    end
                    if settori.blank? || (!settori.blank? && !tipo_modulo.settore.blank? && !settori.include?(tipo_modulo.settore)) 
                        @request.session.flash['errore'] = "Il modulo non appartiene al tuo settore di competenza."
                        redirect self.class.http_s_url
                        done
                    end

                    template = init_template 'gestione/associa_importi'
                    crud = template.widgets[:crud_importi]
                    crud.scene.url_new_importo = self.class.http_s_url(id_tipo_modulo.to_s+'/nuovo_importo')
                    crud.fixed = { :tipo_modulo => id_tipo_modulo } 

                    contenuto_modulo_default = "<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>Inserire il testo<br><br></body></html>"

                    #se il contenuto dell'editor diverso da null procedo verso la pagina con l'editor
                    if tipo_modulo.contenuto_modulo.blank? || tipo_modulo.contenuto_modulo == contenuto_modulo_default
                        @scene.url_avanti = self.class.http_s_url('importa_modulo/'+id_tipo_modulo.to_s)
                    else
                        @scene.url_avanti = self.class.http_s_url('crea_modulo/'+id_tipo_modulo.to_s)
                    end
                    
                else
                    @request.session.flash['errore'] = "Id Tipo modulo non corrisponde ad una Tipologia di Modulo presente"
                    redirect self.class.http_s_url
                end
                
            else
                @request.session.flash['errore'] = "Tipo modulo non presente"
                redirect self.class.http_s_url
            end
        end


        #new e edit
        __.html :template => 'gestione/nuovo_importo' 
        def nuovo_importo(id_tipo_modulo,id_importo=nil)
            #se id_importo non nullo allora devo scambiare gli id
            unless id_importo.blank?
                scambio = id_importo
                id_importo = id_tipo_modulo
                id_tipo_modulo = scambio
            end

            tipo_modulo = Moduli::TipoModulo.load(:id => id_tipo_modulo.to_i)
            @scene.nome_tipo_modulo = tipo_modulo.nome

            @scene.url_redirect = self.class.http_s_url(id_tipo_modulo.to_s+'/associa_importi')
            @scene.pk = nil
            if id_importo.blank?
                @scene.azione = "Nuovo"
            else
                @scene.azione = "Modifica"
                @scene.pk = id_importo
            end
            template = init_template 'gestione/nuovo_importo'
            form = template.widgets[:form_nuovo_importo]
            form.scene.id_tipo_modulo = id_tipo_modulo
            form.fixed = { :tipo_modulo => id_tipo_modulo }
            
        end 

        
        __.html :template => 'gestione/importa_modulo'
        def importa_modulo(id_tipo_modulo)
            @scene.moduli_da_caricare = TipoModulo.where{ |tipo_mod| (tipo_mod.id .not id_tipo_modulo) & (tipo_mod.contenuto_modulo .not nil) }
            @scene.id_tipo_modulo = id_tipo_modulo
            tipo_modulo_corrente = TipoModulo.load(:id => id_tipo_modulo.to_i)

            if @request.post?
                id_modulo_da_importare = @request.params['modulo_da_caricare']
                unless id_modulo_da_importare.blank?
                    tipo_modulo_da_importare = TipoModulo.load(:id => id_modulo_da_importare.to_i)
                    tipo_modulo_corrente.contenuto_modulo = tipo_modulo_da_importare.contenuto_modulo
                    tipo_modulo_corrente.campi_obbligatori = tipo_modulo_da_importare.campi_obbligatori
                    tipo_modulo_corrente.allegati_associati = tipo_modulo_da_importare.allegati_associati
                    tipo_modulo_corrente.eventi_associati = tipo_modulo_da_importare.eventi_associati
                    tipo_modulo_corrente.interventi_associati = tipo_modulo_da_importare.interventi_associati
                    tipo_modulo_corrente.save
                end
                @request.session['no_import_modulo'] = true
                #procedo verso la pagina con l'editor
                redirect self.class.http_s_url('crea_modulo/'+id_tipo_modulo.to_s)
                done
            end
        end

        __.html :template => 'gestione/ricompila_moduli'
        def ricompila_moduli
            if @request.post?
                begin
                    Moduli::TipoModulo.all.each{ |tip_modulo|
                        crea_cartelle_moduli(tip_modulo.nome_file)
                        compila_modulo(tip_modulo.contenuto_modulo, tip_modulo)
                    }
                    @request.session.flash['esito_azione'] = "Moduli ricompilati con successo!"
                rescue => exc
                    messaggio =  exc.message
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
                    @request.session.flash['errore'] = messaggio
                end
                redirect self.class.http_s_url
            end

        end


        __.html :template => 'gestione/crea_modulo'
        def crea_modulo(id_tipo_modulo)
            redirect self.class.http_s_url if id_tipo_modulo.blank?
            #fa abilitare o no la scelta della famiglia di font e della dimensione.
            @scene.abilita_scelta_font = Spider.conf.get('moduli.abilita_scelta_font')
            @scene.pagamenti_attivi = true if defined?(Pagamenti) != nil
            tipo_modulo = TipoModulo.load(:id => id_tipo_modulo.to_i)
            unless tipo_modulo.blank?
                #Se il modulo ha il flag per_iscrizioni_scolastiche ed esiste MuSe, abilito il template
                @scene.attiva_template_iscrizioni = true

                if @request.get?
                    @scene.id_tipo_modulo = id_tipo_modulo
                    @scene.nome_tipo_modulo = tipo_modulo.nome
                    @scene.contenuto_tipo_modulo = tipo_modulo.contenuto_modulo.blank? ? nil : tipo_modulo.contenuto_modulo.encode('utf-8')
                    #se contenuto modulo vuoto o uguale al default chiedo se vuole importare
                    contenuto_modulo_default = "<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>Inserire il testo<br><br></body></html>"

                    if (@scene.contenuto_tipo_modulo.blank? || @scene.contenuto_tipo_modulo == contenuto_modulo_default) && @request.session['no_import_modulo'].blank?
                        redirect self.class.http_s_url('importa_modulo/'+id_tipo_modulo.to_s)
                        done
                    else
                        @request.session['no_import_modulo'] = nil
                    end

                    @scene.allegati_associati = tipo_modulo.allegati_associati unless tipo_modulo.allegati_associati.blank?
                    @scene.eventi_associati = tipo_modulo.eventi_associati unless tipo_modulo.eventi_associati.blank?
                    @scene.interventi_associati = tipo_modulo.interventi_associati unless tipo_modulo.interventi_associati.blank?
                    
                    #raggruppo per tipo obbligatorieta per mostare la tabella con gli importi raggruppati per obbligatorio, almeno_uno e solo_uno 
                    importi_collegati = Moduli::Importo.where{|imp| (imp.tipo_modulo == tipo_modulo)}.group_by{|imp_col| imp_col[:tipo_obbligatorieta]}
                    
                    @scene.importi_collegati = importi_collegati unless importi_collegati.blank?
                end
                if @request.post?

                    contenuto_editor = @request.params['textarea_tinymce']#.gsub("&nbsp;"," ") tolta per usare gli spazi per posizionamenti
                    compila_modulo(contenuto_editor,tipo_modulo)

                    @request.session.flash['esito_azione'] = "Il modulo è stato salvato con successo."
                    redirect self.class.http_s_url
                end
                
            else
                @request.session.flash['errore'] = "Tipo modulo non presente"
                redirect self.class.http_s_url if id_tipo_modulo.blank?
            end
            
        end


    #action ajax che sostituisce alla select dei tipi dovuto quelli che hanno il settore selezionato
        __.action
        def popola_select_tipo_modulo
            @scene.tipo_modulo_settore_selezionato = @request.params['tipo_modulo_tipo_dovuto_selezionato']
            settore = @request.params['settore']
            #filtro i tipi modulo in base al settore
            tipi_modulo_da_settore = []
            unless settore.blank?
                tipo_modulo = Moduli::TipoModulo.where{ |tip_mod| tip_mod.settore == settore}
            else
                tipo_modulo = Moduli::TipoModulo.all.order_by(:nome)
            end
            unless tipo_modulo.blank?
                tipo_modulo.each{ |tipo_modulo| 
                    tipi_modulo_da_settore << { 'id' => tipo_modulo.id.to_s, 
                                                'nome' => tipo_modulo.nome }
                }
            end
            @scene.tipi_modulo_da_settore = tipi_modulo_da_settore
            render "gestione/_popola_select_tipo_modulo", :layout => nil 
        end        


        __.html :template => 'gestione/ricerca_moduli'
        def ricerca_moduli
            @scene.sezione = 'ricerca_moduli'
            
            @scene.stati_modulo = {
                'bozza'         => 'Salvato come bozza',
                'confermato'    => 'Salvataggio confermato, non inviato',
                'inviato'       => 'Inviato'
            }
            righe_moduli = nil

            if @scene.admin_portal || @scene.admin_tutti_servizi
                settori = Portal::Hippo::Settore.all
            else
                settori = [@request.user.settore]
            end
            
            #se arriva un settore nella ricerca filtro i tipo modulo che hanno quel settore
            unless @request.params['settore'].blank?
                tipi_modulo = Moduli::TipoModulo.where(:settore => @request.params['settore'])
            else
                tipi_modulo = Moduli::TipoModulo.all
            end
            @scene.tipi_modulo = tipi_modulo


            @scene.settori_modulo = settori

            #se arrivo in get e non ho usato la widget paginata cancello la sessione e i campi di ricerca
            if @request.get? && @request.params.blank? 
                @request.session['parametri_ricerca'] = nil
                @scene.dati = {
                                'cognome' => "",
                                'nome' => "",
                                'id_utente' => "",
                                'id_modulo' => "",
                                'nome_modulo' => "",
                                'stato_modulo' => "",
                                'data_da' => "",
                                'data_a' => "",
                                'spid_code' => "",
                                'settore' => ""
                            }
            end
            #se ho filtrato la tabella o ho cliccato su una pagina dell'elenco
            if (@request.post? && @request.params['submit'] == 'cerca') || !@request.params['_w'].blank?
                @scene.filtro_moduli = true
                @request.session['parametri_ricerca'] = @request.params
                
                @scene.dati = {
                                'cognome' => @request.session['parametri_ricerca']['cognome'],
                                'nome' => @request.session['parametri_ricerca']['nome'],
                                'id_utente' => @request.session['parametri_ricerca']['id_utente'],
                                'id_modulo' => @request.session['parametri_ricerca']['id_modulo'],
                                'nome_modulo' => @request.session['parametri_ricerca']['nome_modulo'],
                                'stato_modulo' => @request.session['parametri_ricerca']['stato_modulo'],
                                'data_da' => @request.session['parametri_ricerca']['_w']['data_da'],
                                'data_a' => @request.session['parametri_ricerca']['_w']['data_a'],
                                'spid_code' => @request.session['parametri_ricerca']['_w']['spid_code'],
                                'settore' => @request.session['parametri_ricerca']['settore']
                                }
                    
                
                righe_moduli = filtra_tabella(Moduli::ModuloSalvato, @request.session['parametri_ricerca'])
                #calcolo totale righe
                @scene.totale_righe = righe_moduli.length
                
            end
            #se ho cliccato sull'esportazione delle abelle
            if @request.post? && @request.params['submit'] == 'esporta_tabella'
                
                #reinizializzo la scene con i dati altrimenti in prod rompe con le date della widget
                @scene.dati = {
                                'cognome' => "",
                                'nome' => "",
                                'id_utente' => "",
                                'id_modulo' => "",
                                'nome_modulo' => "",
                                'stato_modulo' => "",
                                'data_da' => "",
                                'data_a' => "",
                                'spid_code' => "",
                                'settore' => ""
                            }

                #se ho fatto una ricerca esporto il query_set
                unless @request.session['parametri_ricerca'].blank?
                    nome_tabella = filtra_tabella(Moduli::ModuloSalvato, @request.session['parametri_ricerca'])
                else
                    #altrimenti esporto la tabella ModuloSalvato
                    nome_tabella = "Moduli::ModuloSalvato"
                end
                
                #setto la variabile per capire se devo mettere le stringhe tra apici
                stringhe_con_apici = !@request.params['testo_apici'].blank?
                #setto la variabile per capire se devo mettere le label dei campi nella prima riga
                label_prima_riga = !@request.params['intestazione_campi'].blank?
                #setto il separatore
                separatore = "," if @request.params['separatore'] == "virgola"
                separatore = ";" if @request.params['separatore'] == "punto_virgola"

                tabella_to_csv(nome_tabella, stringhe_con_apici, label_prima_riga, separatore,['dati','chiave','hashdir_allegati','stampato','dimensioni','id_pratica'], {"Nome modulo" => "tipo_modulo.nome", "Cognome" => "utente.cognome", "Nome" => "utente.nome", "Settore" => "tipo_modulo.settore"} )
                done #evita di includere html della pagina se usato use Rack::Deflater
            end
            
            
            @scene.righe_moduli = righe_moduli
        end

        __.html :template => 'gestione/graduatoria_moduli'
        def graduatoria_moduli
            @scene.errori = []

            if @scene.admin_portal || @scene.admin_tutti_servizi
                tipi_modulo = Moduli::TipoModulo.all
            else
                settori = [@request.user.settore, 0]
                tipi_modulo = Moduli::TipoModulo.where(:settore => settori)
            end            
            @scene.tipi_modulo = tipi_modulo


            #se arrivo dalla widget table ho @request.params['_w']['lista_moduli']['page'], devo passare il queryset
            if (@request.post? && ( @request.params['submit'] == 'mostra_graduatoria' ||  @request.params['submit'] == 'esporta_tabella') ) || ( !@request.params['_w'].blank? && !@request.params['_w']['lista_moduli']['page'].blank? )
                if @request.params['nome_modulo'] == "seleziona"
                @scene.errori << "Selezionare un nome modulo per vedere la graduatoria"
                @request.session['nome_modulo'] = nil
                end
                tipo_modulo_id = @request.params['nome_modulo'].blank? ? @request.session['nome_modulo'] : @request.params['nome_modulo']
                    
                if !tipo_modulo_id.blank?
                    @request.session['nome_modulo'] = tipo_modulo_id
                    moduli_confermati = Moduli::ModuloSalvato.where{ |modulo| ((modulo.stato == 'confermato') | (modulo.stato == 'inviato')) & (modulo.tipo_modulo == Moduli::TipoModulo.new(tipo_modulo_id.to_i)) }.order_by(:punteggio_totale,:desc)
                    moduli_lista_completa = Moduli::ModuloSalvato.where{ |modulo| ((modulo.stato == 'confermato') | (modulo.stato == 'inviato')) & (modulo.tipo_modulo == Moduli::TipoModulo.new(tipo_modulo_id.to_i)) }.order_by(:punteggio_totale,:desc)
                    @scene.moduli_confermati = moduli_confermati
                    @scene.moduli_lista_completa = moduli_lista_completa
                    @scene.ricerca_effettuata = true
                    @scene.nome_modulo = tipo_modulo_id

                    if @request.params['submit'] == 'esporta_tabella'
                        #setto la variabile per capire se devo mettere le stringhe tra apici
                        stringhe_con_apici = !@request.params['testo_apici'].blank?
                        #setto la variabile per capire se devo mettere le label dei campi nella prima riga
                        label_prima_riga = !@request.params['intestazione_campi'].blank?
                        #setto il separatore
                        separatore = "," if @request.params['separatore'] == "virgola"
                        separatore = ";" if @request.params['separatore'] == "punto_virgola"

                        tabella_to_csv(moduli_confermati, stringhe_con_apici, label_prima_riga, separatore, ['dati','chiave','hashdir_allegati','stampato','dimensioni','id_pratica'], {"Nome modulo" => "tipo_modulo.nome", "Cognome" => "utente.cognome", "Nome" => "utente.nome"} )
                        done #evita di includere html della pagina se usato use Rack::Deflater
                    end

                end
            end

        end

        __.html :template => 'gestione/dettagli_modulo'
        def dettagli_modulo
            id_modulo = @request.params['id']
            unless id_modulo.blank?
                pag_provenienza = @request.params['current_page']
                modulo = ModuloSalvato.new(id_modulo.to_i)
                @scene.allegati = modulo.allegati_salvati
                @scene.modulo = modulo
                @scene.pag_provenienza = pag_provenienza
            else
                redirect self.class.http_s_url
            end
            
        end

        __.action
        def download_allegato
            id_modulo = @request.params['id'].to_i
            nome_allegato = @request.params['na']
            modulo = ModuloSalvato.load(:id => id_modulo)
            file = File.join(Spider.paths[:data],'/uploaded_files/moduli', modulo.hashdir_allegati, nome_allegato)
            @response.headers['Content-disposition'] = "attachment; filename=#{nome_allegato.gsub(' ','_')}"
            output_static(file)
        end


        protected

        def crea_cartelle_moduli(nome_cartella_modulo)
            #creo la cartella moduli se non esiste
            path_file = File.join(Spider.paths[:root],'moduli')
            File.chmod(0775, path_file) if File.exists?(path_file)
            Dir.mkdir(path_file, 0775) unless File.exists?(path_file)
            #creo la cartella con il nome del comune
            nome_ente = Spider.conf.get('portal.nome').gsub(/[^a-z^A-Z]/,"_")
            path_file = File.join(path_file, nome_ente)
            File.chmod(0775, path_file) if File.exists?(path_file)
            Dir.mkdir(path_file, 0775) unless File.exists?(path_file)
            #creo la cartella con la convenzione per il nuovo modulo
            
            Dir.mkdir(File.join(path_file, nome_cartella_modulo), 0775) unless File.exists?(File.join(path_file, nome_cartella_modulo))
            #creo la cartella per la testata
            Dir.mkdir(File.join(path_file, 'testata_modulo'), 0775) unless File.exists?(File.join(path_file, 'testata_modulo'))
            #creo la cartella per il piede
            Dir.mkdir(File.join(path_file, 'piede_modulo'), 0775) unless File.exists?(File.join(path_file, 'piede_modulo'))
            #copio i file
            unless File.exists?(File.join(path_file, nome_cartella_modulo, nome_cartella_modulo+".shtml"))
                #FileUtils.cp File.join(Spider.paths[:apps],'moduli','templates','moduli', 'modulo_base.shtml'), File.join(path_file, nome_cartella_modulo, nome_cartella_modulo+".shtml")
                IO.copy_stream(File.join(Spider.paths[:apps],'moduli','templates','moduli', 'modulo_base.shtml'), File.join(path_file, nome_cartella_modulo, nome_cartella_modulo+".shtml"))
            end
            
            unless File.exists?(File.join(path_file, 'testata_modulo', '_testata_modulo.shtml'))
                #FileUtils.cp File.join(Spider.paths[:apps],'moduli','templates','moduli', 'testata_base.shtml'), File.join(path_file, 'testata_modulo', '_testata_modulo.shtml')
                IO.copy_stream(File.join(Spider.paths[:apps],'moduli','templates','moduli', 'testata_base.shtml'), File.join(path_file, 'testata_modulo', '_testata_modulo.shtml'))
            end

            unless File.exists?(File.join(path_file, 'piede_modulo', '_piede_modulo.shtml'))
                #FileUtils.cp File.join(Spider.paths[:apps],'moduli','templates','moduli', 'piede_base.shtml'), File.join(path_file, 'piede_modulo', '_piede_modulo.shtml')
                IO.copy_stream(File.join(Spider.paths[:apps],'moduli','templates','moduli', 'piede_base.shtml'), File.join(path_file, 'piede_modulo', '_piede_modulo.shtml'))
            end
        end

        def compila_modulo(contenuto_editor,tipo_modulo)
            html_editor = Nokogiri::HTML(contenuto_editor, nil, 'utf-8')

            #html_editor = Nokogiri::HTML(contenuto_editor)
            contenuto_editor_corretto = html_editor.to_s

            #uso per le conversioni la tabella http://www.i18nqa.com/debug/utf8-debug.html

            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Ã¨","è")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Ã","à")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â€œ","\"")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â€","\"")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â€™","'")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â€˜","'")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â€“","-")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â€”","-")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("â‚¬","€")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Ã¹","ù")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("à¹","ù")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Ã©","é")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("à©","é")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Ã¬","ì")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("à¬","ì")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Ã²","ò")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("à²","ò")
            contenuto_editor_corretto = contenuto_editor_corretto.gsub("Â","")
        

            contenuto_editor_corretto = contenuto_editor_corretto.force_encoding('UTF-8').encode('utf-8')

            body_editor = html_editor.at('body')
            
            #sostituisco gli input text con moduli:dato
            body_editor.css("input[type='text']:not(.input_dinamico):not(.textarea)").each do |input_text|
                moduli_dato_node = Nokogiri::XML::Node.new('moduli:dato',body_editor) 
                moduli_dato_node['id'] = input_text['id'].to_i > 0 ? 'input_text_'+input_text['id'] : input_text['id']
                moduli_dato_node['class'] = input_text['class']
                if input_text['obbligatorio'] == 'true'
                    moduli_dato_node['class'] = moduli_dato_node['class']+" obbligatorio"
                end
                input_text.replace moduli_dato_node
            end
            body_editor.css("input[type='checkbox']:not(.checkbox_riquadro_opzionale)").each do |input_checkbox|
                moduli_opzione_node = Nokogiri::XML::Node.new('moduli:opzione',body_editor)
                unless input_checkbox['class'].blank?
                    moduli_opzione_node['class'] = input_checkbox['class'] if input_checkbox['class'].split(" ").include?('associato_allegato') 
                    moduli_opzione_node['id'] = (input_checkbox['class'].split(" ").include?('associato_allegato') ?  input_checkbox['id'] : 'input_checkbox_'+input_checkbox['id'] )
                else
                    moduli_opzione_node['id'] = 'input_checkbox_'+input_checkbox['id']
                end
                
                input_checkbox.replace moduli_opzione_node
            end
            #checkbox dei riquadri opzionali da portare nei dati json del modulo per poterlo stampare
            body_editor.css("input[type='checkbox'].checkbox_riquadro_opzionale").each do |checkbox_riquadro_opzionale|
                moduli_opzione_node = Nokogiri::XML::Node.new('moduli:opzione',body_editor)
                moduli_opzione_node['class'] = checkbox_riquadro_opzionale['class'] 
                moduli_opzione_node['id'] = checkbox_riquadro_opzionale['id']
                checkbox_riquadro_opzionale.replace moduli_opzione_node
            end

            body_editor.css("fieldset:not(.riquadro_opzionale_visibile)").each do |fieldset|
                moduli_gruppo_node = Nokogiri::XML::Node.new('moduli:gruppo',body_editor) 
                moduli_gruppo_node['id'] = fieldset['id'].to_i > 0 ? 'gruppo_'+fieldset['id'] : fieldset['id']
                moduli_gruppo_node['class'] = fieldset['class']
                moduli_gruppo_node << fieldset.children              
                fieldset.replace moduli_gruppo_node
            end
            body_editor.css("div.contenitore").each do |gruppo_checkbox|
                moduli_opzioni_node = Nokogiri::XML::Node.new('moduli:opzioni',body_editor)   
                moduli_opzioni_node['id'] = gruppo_checkbox['id'].to_i > 0 ? 'gruppo_checkbox_'+gruppo_checkbox['id'] : gruppo_checkbox['id']
                #se ho anche la classe contenitore_checkbox_esclusivi allora metto la classe per l'esclusività
                moduli_opzioni_node['class'] ||= ""
                if gruppo_checkbox['class'].include?('contenitore_checkbox_esclusivi')
                    moduli_opzioni_node['class'] += ' checkbox_esclusivi'+gruppo_checkbox['id']
                end
                if gruppo_checkbox['class'].include?('contenitore_checkbox_obbligatori')
                    moduli_opzioni_node['class'] += ' checkbox_obbligatori'
                end
                moduli_opzioni_node['class'] += " contenitore" #serve per avere la width come nell'editor
                moduli_opzioni_node << gruppo_checkbox.children
                gruppo_checkbox.replace moduli_opzioni_node
            end

            body_editor.css("div.contenitore_select").wrap("<div class='ui-widget inline_block'></div>")
            
            body_editor.css("div.contenitore_select").each do |contenitore_select|
                
                cont_dato = Nokogiri::XML::Node.new('moduli:dato',body_editor)   
                cont_dato['id'] = contenitore_select['id'].to_i > 0 ? 'scelta_tipologia_'+contenitore_select['id'] : contenitore_select['id']
                cont_dato['class'] = "hide mostra_in_stampa input_nascosto_select"
                moduli_select = Nokogiri::XML::Node.new('select',body_editor)   
                moduli_select['id'] = contenitore_select['id'].to_i > 0 ? 'select_'+contenitore_select['id'] : contenitore_select['id']
                moduli_select['class'] = 'input_combobox'+contenitore_select['id']
                #entro nel contenitore select e converto le option
                contenitore_select.children.each{ |nodo_opzione|
                        nodo_opzione.remove if nodo_opzione['class'] != 'opzione'
                    }
                contenitore_select.children.css("div.opzione").each do |opzione_select|
                    moduli_opzione = Nokogiri::XML::Node.new('option',body_editor)
                    opzione_select.children.each{ |nodo_figlio|
                        nodo_figlio.remove if nodo_figlio.class != Nokogiri::XML::Text
                    }
                    moduli_opzione['value'] = opzione_select.children.text.strip
                    moduli_opzione << opzione_select.children
                    opzione_select.replace moduli_opzione
                end
                
                #primo elemento per valore vuoto
                moduli_opzione_primo = Nokogiri::XML::Node.new('option',body_editor)
                #controllo se obbligatoria la scelta della combobox
                moduli_opzione_primo['value'] = ""
                moduli_select << moduli_opzione_primo

                moduli_select << contenitore_select.children
                contenitore_select.add_previous_sibling(cont_dato)
                contenitore_select.replace moduli_select 
            end
            

            body_editor.css("input[type='date'].sel_data").each do |selettore_data|
                moduli_data_node = Nokogiri::XML::Node.new('moduli:dato',body_editor)   
                moduli_data_node['id'] = selettore_data['id'].to_i > 0 ? 'selettore_data_'+selettore_data['id'] : selettore_data['id']
                moduli_data_node['tipo'] = 'data'
                moduli_data_node['class'] = "input_smallwidth "
                moduli_data_node['class'] += "obbligatorio" if selettore_data['class'].include?('obbligatorio')
                moduli_data_node['class'] += "readonly_js" if selettore_data['class'].include?('readonly_js')
                selettore_data.replace moduli_data_node
            end

            body_editor.css("input[type='date'].data_oggi").each do |data_oggi|
                moduli_data_oggi = Nokogiri::XML::Node.new('moduli:dato',body_editor)   
                moduli_data_oggi['id'] = data_oggi['id'].to_i > 0 ? 'data_oggi_'+data_oggi['id'] : data_oggi['id']
                moduli_data_oggi['class'] = "input_smallwidth data_oggi"
                moduli_data_oggi['class'] += "readonly_js" if data_oggi['class'].include?('readonly_js')
                data_oggi.replace moduli_data_oggi
            end
            #input autoadattativo
            body_editor.css("input.input_dinamico").each do |input_dinamico|
                moduli_input_dinamico_node = Nokogiri::XML::Node.new('moduli:dato',body_editor)   
                moduli_input_dinamico_node['id'] = input_dinamico['id'].to_i > 0 ? 'textarea_autoadattativa'+input_dinamico['id'] : input_dinamico['id']
                moduli_input_dinamico_node['tipo'] = 'longText'
                moduli_input_dinamico_node['class'] = 'adaptive_input no_float '+input_dinamico['class']
                moduli_input_dinamico_node['class'] = moduli_input_dinamico_node['class']+" obbligatorio" if input_dinamico['obbligatorio'] == 'true'
                input_dinamico.replace moduli_input_dinamico_node
            end
            #textarea normale
            body_editor.css("input.textarea").each do |textarea|
                moduli_textarea_node = Nokogiri::XML::Node.new('moduli:dato',body_editor)   
                moduli_textarea_node['id'] = textarea['id'].to_i > 0 ? 'textarea_'+textarea['id'] : textarea['id']
                moduli_textarea_node['tipo'] = 'longText'
                moduli_textarea_node['class'] = (textarea['class'].include?('obbligatorio') ? "obbligatorio" : "")
                moduli_textarea_node['class'] += "readonly_js" if textarea['class'].include?('readonly_js')
                textarea.replace moduli_textarea_node
            end

            body_editor.css("input[type='file']").each do |input_file|
                moduli_dato_node = Nokogiri::XML::Node.new('moduli:allegato',body_editor)   
                moduli_dato_node['id'] = input_file['id'].to_i > 0 ? 'allegato_'+input_file['id'] : input_file['id']
                moduli_dato_node['class'] = input_file['class']
                moduli_dato_node['autohide'] = "true"
                moduli_dato_node['save_path'] = "moduli/" 
                input_file.replace moduli_dato_node
            end

            #controllo obbligatorietà dei campi
            
            hash_input_obbligatori = {}
            hash_input_obbligatori['text'], hash_input_obbligatori['checkbox'], hash_input_obbligatori['textarea'], array_id_presenti  = [],[],[],[]
            body_editor.css(".obbligatorio, .checkbox_obbligatori").each do |input_obbligatorio|
                padre = nil
                array_id_padre = []
                esci = false
                #controllo se il campo è contenuto in un contenitore con un id
                
                begin
                    padre ||= input_obbligatorio
                    if padre.respond_to?(:parent)
                        padre = padre.parent
                    else
                        padre = nil
                    end
                    if !padre.blank? && !padre['id'].blank?
                        array_id_padre << padre['id']
                        esci = true
                    end

                end while padre.respond_to?(:parent) && !esci
                
                if padre == input_obbligatorio
                    unless array_id_presenti.include?(input_obbligatorio['id'])
                        hash_input_obbligatori['text'] << input_obbligatorio['id'] if input_obbligatorio.name == "moduli:dato" || input_obbligatorio.name == "moduli:allegato"
                        hash_input_obbligatori['checkbox'] << input_obbligatorio['id'] if input_obbligatorio.name == "moduli:opzioni"
                        hash_input_obbligatori['textarea'] << input_obbligatorio['id'] if input_obbligatorio.attributes.has_key?('tipo') && input_obbligatorio.attributes['tipo'].value == "longText"
                        array_id_presenti << input_obbligatorio['id']
                    end
                else
                    array_id_padre << input_obbligatorio['id']
                    unless array_id_presenti.include?(array_id_padre)
                        hash_input_obbligatori['text'] << array_id_padre if input_obbligatorio.name == "moduli:dato" || input_obbligatorio.name == "moduli:allegato"
                        hash_input_obbligatori['checkbox'] << array_id_padre if input_obbligatorio.name == "moduli:opzioni"
                        hash_input_obbligatori['textarea'] << array_id_padre if input_obbligatorio.attributes.has_key?('tipo') && input_obbligatorio.attributes['tipo'].value == "longText"
                        array_id_presenti << array_id_padre
                        array_id_padre = []
                    end
                end
                #salvo l'id in un array per non inserire due volte lo stesso id
                 
            end
            
            #salvo questo hash usando l'html presente e non l'hash json per problemi con id che cambiano dinamicamente
            hash_allegati_associati = {}
            body_editor.css(".associato_allegato").each do |input_con_allegato|
                padre = nil
                array_id_padre_allegati = []
                esci = false
                #controllo se il campo è contenuto in un contenitore con un id
                
                array_id_padre_allegati << input_con_allegato['id']
                begin
                    padre ||= input_con_allegato
                    if padre.respond_to?(:parent)
                        padre = padre.parent
                    end
                    if !padre['id'].blank?
                        array_id_padre_allegati << padre['id']
                    end
                end while padre.respond_to?(:parent)
                if input_con_allegato['class'].include?('presente')
                    hash_allegati_associati[input_con_allegato['id']] = { "codice" => input_con_allegato['name'], 
                        "presente" => array_id_padre_allegati.reverse.map {|a| %Q("#{a}")}.join(", ").gsub("\"","") }
                elsif input_con_allegato['class'].include?('assente')
                    hash_allegati_associati[input_con_allegato['id']] = { "codice" => input_con_allegato['name'], 
                        "assente" => array_id_padre_allegati.reverse.map {|a| %Q("#{a}")}.join(", ").gsub("\"","") }
                else
                    #niente
                end
            end
            tipo_modulo.allegati_associati = hash_allegati_associati.convert_object.to_json

            #salvo questo hash usando l'html presente e non l'hash json per problemi con id che cambiano dinamicamente EVENTI
            hash_eventi_associati = {}
            no_eventi = true
            body_editor.css(".associato_evento").each do |riquadro_con_evento|
                no_eventi = false
                id_riquadro = riquadro_con_evento['id'].gsub("gruppo_","")
                padre = nil
                array_id_padre_eventi = []
                esci = false
                #controllo se il campo è contenuto in un contenitore con un id
                array_id_padre_eventi << id_riquadro
                begin
                    padre ||= riquadro_con_evento
                    if padre.respond_to?(:parent)
                        padre = padre.parent
                    end
                    if !padre['id'].blank?
                        array_id_padre_eventi << padre['id']
                    end
                end while padre.respond_to?(:parent)
                #ricavo il codice dell'evento salvato nello span nascosto
                hash_eventi_associati[id_riquadro] = { "codice" => riquadro_con_evento.css("#codice_evento").text }
            end
            if no_eventi
                tipo_modulo.eventi_associati = nil
            else
                tipo_modulo.eventi_associati = hash_eventi_associati.convert_object.to_json
            end
            

            #salvo questo hash usando l'html presente e non l'hash json per problemi con id che cambiano dinamicamente INTERVENTI
            hash_interventi_associati = {}
            no_interventi = true
            body_editor.css(".associato_intervento").each do |riquadro_con_intervento|
                no_interventi = false
                id_riquadro = riquadro_con_intervento['id'].gsub("gruppo_","")
                padre = nil
                array_id_padre_interventi = []
                esci = false
                #controllo se il campo è contenuto in un contenitore con un id
                array_id_padre_interventi << id_riquadro
                begin
                    padre ||= riquadro_con_intervento
                    if padre.respond_to?(:parent)
                        padre = padre.parent
                    end
                    if !padre['id'].blank?
                        array_id_padre_interventi << padre['id']
                    end
                end while padre.respond_to?(:parent)
                #ricavo il codice dell'evento salvato nello span nascosto
                hash_interventi_associati[id_riquadro] = { "codice" => riquadro_con_intervento.css("#codice_intervento").text }
            end
            if no_interventi
                tipo_modulo.interventi_associati = nil
            else
                tipo_modulo.interventi_associati = hash_interventi_associati.convert_object.to_json
            end

            #rimuovo i layers 
            body_editor.css(".layer").each{ |layer|
                layer.remove
            }

            #rimuovo le barre contenitrici per i gruppi di checkbox
            body_editor.css(".barra_contenitore_opzioni").each{ |gruppo_cont_checkbox|
                gruppo_cont_checkbox.remove
            }

            #rimuovo le barre contenitrici per i riquadri con eventi interventi
            body_editor.css(".barra_contenitore_evento_intervento").each{ |gruppo_cont_eventi_interventi|
                gruppo_cont_eventi_interventi.remove
            }

            #rimuovo le barre contenitrici per i riquadri opzionali
            body_editor.css(".barra_riquadro_opzionale").each{ |barra_riquadro_opzionale|
                barra_riquadro_opzionale.remove
            }
            
            #salvo in db l'array a due livelli degli id obbligatori
            tipo_modulo.campi_obbligatori = hash_input_obbligatori.to_json

            #path del modulo salvato
            nome_ente = Spider.conf.get('portal.nome').gsub(/[^a-z^A-Z]/,"_")
            path_file = File.join(Spider.paths[:root],'moduli', nome_ente, tipo_modulo.nome_file.gsub(/[^a-z^A-Z^0-9]/," ").strip.gsub(/\s+/,"_"), tipo_modulo.nome_file.gsub(/[^a-z^A-Z^0-9]/," ").strip.gsub(/\s+/,"_")+".shtml")
            html_modulo = File.open(path_file) { |f| Nokogiri::XML(f) }
            div_corpo_modulo = html_modulo.at('#corpo_documento')
            #sovrascrivo il contenuto del modulo

            #div_corpo_modulo.remove
            nuovo_corpo_modulo = Nokogiri::XML::Node.new("div", html_modulo)
            nuovo_corpo_modulo['id'] = 'corpo_documento'
            #html_modulo.child.children.last.add_next_sibling(nuovo_corpo_modulo)
                

            div_corpo_modulo.replace nuovo_corpo_modulo
            #salvo il contenuto dell'editor nel file salvato
            html_modulo.at("#corpo_documento") << body_editor.children #salvo i nodi contenuti nel nodo body
            #scrivo il file in utf-8
            File.open(path_file, "w:UTF-8") do |f| 
                #rimuovo caratteri che inseriscono spazi e ritorni a capo
                f.write (html_modulo.inner_html.blank? ? '' : html_modulo.inner_html.gsub(/(\r\n|\r|\n|&#xA0;|&#13;)+/, '') )
            end 
            tipo_modulo.contenuto_modulo = contenuto_editor_corretto
            tipo_modulo.save
            #cancello la cache che crea spider
            if Spider.runmode == 'production'
                FileUtils.remove_dir(File.join(Spider.paths[:root],'var/cache/templates/ROOT/moduli'),true)
                FileUtils.remove_dir(File.join(Spider.paths[:root],'var/cache/templates/ROOT/apps/moduli'),true)
            end

        end


        def tabella_to_csv(nome_tabella, stringhe_con_apici, label_prima_riga, separatore, campi_esclusi, campi_collegati=nil)
            #nome_tabella è una stringa del tipo 'Portal::Utente' o un query_set
            if nome_tabella.is_a?(Spider::Model::QuerySet)
                #se è un query_set ricavo il modello per conoscere i campi e i dati sono dati dal qs stesso
                klass = nome_tabella.model
                dati_tabella = nome_tabella
            else
                modulo_tab, nome_tab = nome_tabella.split("::")
                klass =  Object.const_get(modulo_tab).const_get(nome_tab)
                #carico tutti i dati
                dati_tabella = klass.all
            end
            save_dir = Spider.paths[:data]+'/uploaded_files/moduli/esportazione_tabelle/'
            FileUtils.mkdir_p(save_dir) unless File.directory?(save_dir)            
            options = separatore if RUBY_VERSION =~ /^1.8.7/
            options = { :col_sep => separatore} if RUBY_VERSION =~ /^1.9.3/ || RUBY_VERSION =~ /^2./
            if File.exists?(File.join(Spider.paths[:config],"label_moduli_csv.yml"))
                labels_csv = YAML.load_file(File.join(Spider.paths[:config],"label_moduli_csv.yml"))
            elsif File.exists?(File.join(Spider.paths[:apps],"moduli/config","label_moduli_csv.yml"))
                labels_csv = YAML.load_file(File.join(Spider.paths[:apps],"moduli/config","label_moduli_csv.yml"))
            end

            CSV.open(save_dir+"csv_#{nome_tab}.csv", 'w', options) do |row|
                
                array_intestazioni_riga = []
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
                
                
                
                prima_riga = true
                #se devo convertire le intestazioni uso questo array che vado a popolare quando parserizzo la prima riga
                array_intestazioni_riga_convertite = []
                #scrivo tutti i dati della tabella se ho passato il nome tabella
                dati_tabella.each{ |riga_tabella|
                    array_label = []
                    valori_campo_dati_convertiti = []
                    #estrazione del csv dal json del campo dati
                    if Spider.conf.get('moduli.aggiungi_dati_campo_dati') && !riga_tabella[:dati].blank?
                        aggiungi_valori_campo_dati = true
                        valori_campo_dati = []
                        json = JSON.parse(riga_tabella[:dati])
                        json.each_pair do |chiave,valore|    
                            valori_campo_dati << converti_dati(nil,chiave,valore,array_label)
                        end
                        #MOSTRA LE CHIAVI DEL SINGOLO JSON
                        #puts "\n\n #{array_label}"
                        valori_campo_dati.flatten.each{ |valore_da_aggiungere|
                            valori_campo_dati_convertiti << campo_per_csv(valore_da_aggiungere, stringhe_con_apici)
                        }
                    end
                    
                    #reinizializzo l'array dei valori
                    array_valori_riga = []
                    #ciclo su array_intestazioni_riga che ha solo le label dei campi del modello
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
                            elsif dato_collegato_liv_0.blank? #metto stringa vuota nel caso che il dato no ci sia, esempio l'utente in un pagamento pagopa
                                array_valori_riga << ""
                            else
                                #sono già sul modello, chiamo il metodo per recuperare il dato 
                                array_valori_riga << campo_per_csv(dato_collegato_liv_0.send(chiavi_campi_collegati[1]), stringhe_con_apici)
                            end
                        end
                        if label_campo.is_a?(String) && ( (!campi_collegati.blank? && !campi_collegati.keys.include?(label_campo)) || campi_collegati.blank?)
                            array_valori_riga << campo_per_csv(riga_tabella[label_campo.downcase.gsub(" ","_").to_sym], stringhe_con_apici) #if label_campo.is_a?(String) && (!campi_collegati.blank? && !campi_collegati.keys.include?(label_campo))
                        end
                    }
                
                    #se devo mostrare anche i valori del del campo Dati aggiungo i valori all'array
                    if Spider.conf.get('moduli.aggiungi_dati_campo_dati') && !valori_campo_dati_convertiti.blank?
                        array_valori_riga << valori_campo_dati_convertiti
                    end

                    
                

                    if label_prima_riga && prima_riga
                        #se devo mostrare anche i dati del campo Dati aggiungo le label
                        if Spider.conf.get('moduli.aggiungi_dati_campo_dati')
                            array_intestazioni_riga_campo_dati = array_intestazioni_riga.flatten + array_label
                            #converto le intestazioni che potrebbero essere anche camel case
                        
                            array_intestazioni_riga_campo_dati.each{ |label_da_convertire|
                                #mappa element con yml
                                
                                id_tipo_modulo = riga_tabella.tipo_modulo.id.to_s
                                if !labels_csv.blank? && !labels_csv[id_tipo_modulo].blank? && !labels_csv[id_tipo_modulo][label_da_convertire].blank?
                                    #estraggo il tipo di modulo per avere la chiave per andare sul file yml
                                    label_configurata = labels_csv[id_tipo_modulo][label_da_convertire]
                                end
                                unless label_configurata.blank?
                                    array_intestazioni_riga_convertite << label_configurata
                                else
                                    #converto stringa in camelCase con la versione con underscore
                                    array_intestazioni_riga_convertite << Spider::Inflector.underscore(label_da_convertire).capitalize.gsub("_"," ")
                                end

                            }

                            row << array_intestazioni_riga_convertite.flatten
                        else
                            row << array_intestazioni_riga.flatten
                        end
                        
                        prima_riga = false  
                    end
                    

                    
                    #USATO PER ELENCO DI TRATTRICI ARGEA
                    # #se vogliono le intestazioni nella prima riga le aggiungo
                    # array_label_per_csv = array_intestazioni_riga_convertite
                    # if label_prima_riga && prima_riga                     
                    #     #tolgo dalle label le etichette dell'elenco lungo del tipo voceXX
                    #     array_label_per_csv_pulito = []

                    #     if Spider.conf.get('moduli.aggiungi_dati_campo_dati')
                    #         label_per_elenco = false
                    #         array_label_per_csv.each_index{ |index|
                    #             if array_label_per_csv[index] !~ /voce/
                    #                 #cancello dalle label e dai valori la voce
                    #                 array_label_per_csv_pulito << array_label_per_csv[index] 
                    #             else
                    #                 label_per_elenco = true
                    #             end
                    #         }
                    #         array_label_per_csv_pulito << Spider.conf.get('moduli.label_per_elenco_in_csv') if label_per_elenco
                    #         row << array_label_per_csv_pulito
                    #     else
                    #         row << array_intestazioni_riga_convertite.flatten
                    #     end
                        
                    #     prima_riga = false  
                    # end

                    


                    #USATO PER ELENCO DI TRATTRICI ARGEA
                    # if Spider.conf.get('moduli.aggiungi_dati_campo_dati')
                    #     #tolgo tutti i valori nil o 1 per l'elenco lungo e aggiungo alla fine solo il valore a 1
                    #     array_valori_per_csv = array_valori_riga.flatten
                    #     array_valori_per_csv_pulito = []
                    #     ultimo_valore = ""
                    #     array_valori_per_csv.each_index{ |index|
                    #         if array_label_per_csv[index] !~ /voce/
                    #             #cancello dalle label e dai valori la voce
                    #             array_valori_per_csv_pulito << array_valori_per_csv[index] 
                    #         else
                    #             if array_valori_per_csv[index] == "1"
                    #                 ultimo_valore = Moduli.labels(array_label_per_csv[index])
                    #             end
                    #         end
                    #     }
                    #     array_valori_per_csv_pulito << ultimo_valore

                    #     row << array_valori_per_csv_pulito
                    # else
                    #     row << array_valori_riga.flatten
                    # end

                    
                    
                    # puts riga_tabella.id.to_s
                    # puts "array_intestazioni_riga"+array_intestazioni_riga.length.to_s
                    # puts "array_intestazioni_riga_convertite"+array_intestazioni_riga_convertite.length.to_s
                    # #puts array_intestazioni_riga_convertite
                    # puts "valori_campo_dati"+valori_campo_dati.length.to_s
                    # puts "valori_campo_dati_convertiti"+valori_campo_dati_convertiti.length.to_s
                    # puts "\n\n"

                    row << array_valori_riga.flatten
                } 

            end
            #faccio scaricare il file csv
            nomefile= klass.to_s.gsub("::","_")+".csv"
            csvfile= save_dir+"csv_#{nome_tab}.csv"
            @response.headers['Content-disposition'] = "attachment; filename=#{nomefile.gsub(' ','_')}"
            output_static(csvfile)

        end
        
        #Funzione valida per tutti i filtri sulle tabelle, aggiungere per ogni tabella le varie condizioni particolari

        def filtra_tabella(tabella, parametri_ricerca)
            righe = tabella.all.order_by(:confermato, :desc)
            
            righe.query.condition = Spider::Model::Condition.new
            
            righe.query.condition = righe.query.condition.and{ |riga| (riga.utente.cognome .ilike "%"+parametri_ricerca['cognome'].strip+"%") } unless parametri_ricerca['cognome'].blank?
            righe.query.condition = righe.query.condition.and{ |riga| (riga.utente.nome .ilike "%"+parametri_ricerca['nome'].strip+"%") } unless parametri_ricerca['nome'].blank?
            righe.query.condition = righe.query.condition.and{ |riga| (riga.utente == Portal::Utente.new(parametri_ricerca['id_utente'].strip.to_i)) } unless parametri_ricerca['id_utente'].blank?
            righe.query.condition = righe.query.condition.and{ |riga| (riga.tipo_modulo == Moduli::TipoModulo.new(parametri_ricerca['nome_modulo'].to_i)) } unless parametri_ricerca['nome_modulo'].blank?
            righe.query.condition = righe.query.condition.and{ |riga| (riga.stato == parametri_ricerca['stato_modulo']) } unless parametri_ricerca['stato_modulo'].blank?
            righe.query.condition = righe.query.condition.and{ |riga| (riga.id == parametri_ricerca['id_modulo']) } unless parametri_ricerca['id_modulo'].blank?
            righe.query.condition = righe.query.condition.and{ |riga| (riga.spid_code == parametri_ricerca['spid_code']) } unless parametri_ricerca['spid_code'].blank?

            if parametri_ricerca['settore'] == '0'
                righe.query.condition = righe.query.condition.and{ |riga| (riga.tipo_modulo.settore == nil) }
            elsif !parametri_ricerca['settore'].blank?
                righe.query.condition = righe.query.condition.and{ |riga| (riga.tipo_modulo.settore == parametri_ricerca['settore']) }
            end
                    

            #filtro sulle date
            if !parametri_ricerca['_w']['data_da'].blank?
                data_fine = DateTime.strptime(parametri_ricerca['_w']['data_da'], '%d/%m/%Y')
                righe.query.condition = righe.query.condition.and{ |riga| (riga.obj_modified > data_fine) }
            end

            if !parametri_ricerca['_w']['data_a'].blank?
                data_inizio = DateTime.strptime(parametri_ricerca['_w']['data_a'], '%d/%m/%Y')
                #+1 per comprendere anche l'ultimo giorno, sto usando datetime ed esclude l'estremo perchè mette l'ora 00:00
                data_inizio = data_inizio + 1
                righe.query.condition = righe.query.condition.and{ |riga| (riga.obj_modified < data_inizio) }
            end

            righe            

        end

        def campo_per_csv(valore, stringhe_con_apici)
            return "" if valore.nil?
            case valore.class.to_s
                when 'DateTime'
                    valore_convertito = valore.strftime('%d/%m/%Y %H:%M:%S')
                when 'Date'
                    valore_convertito = valore.strftime('%d/%m/%Y')
                when 'BigDecimal', 'Spider::DataTypes::Decimal', 'Moduli::Decimal'
                    valore_convertito = valore.to_s('F').strip
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

        def converti_dati(chiave_padre,chiave_corrente,valore_da_convertire,array_label)
            valori = []
            chiave_padre = chiave_corrente if chiave_padre.blank?
            case valore_da_convertire.class.to_s
                when "Hash"
                    unless valore_da_convertire.blank?
                        valore_da_convertire.sort.map do |k, v|
                            valori << converti_dati(chiave_corrente,k,v,array_label)
                        end
                    end
                    return valori
                when "Array"
                  
                    # valore_da_convertire.each_index do |i|
                    #     valori,label = converti_dati(chiave_padre,i,valore_da_convertire[i])
                    # end
                when "NilClass"
                    if chiave_corrente == 'check'
                        array_label << chiave_padre
                    else
                        array_label << chiave_corrente
                    end
                    return nil
                else
                    if chiave_corrente == 'check'
                        array_label << chiave_padre
                    else
                        array_label << chiave_corrente
                    end
                    return valore_da_convertire 
            end
        end

        def chiamata_ajax?(request)
            !request.env.blank? && request.env['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
        end

    end
end


# -*- encoding : utf-8 -*-
module Portal

    module AutenticazionePortale
        include Annotations
        
        def self.included(klass)
            super
            klass.send(:include, Spider::Auth::AuthHelper)
        end

        def before(action, *params)
            @request.extend(PortalRequest)
            carica_utenti
            super
            @scene.utente_portale = @request.utente_portale
        end

        def autenticazione_necessaria(controllo=true)
            #se ho fatto una chiamata json
            if @request.format == :json
                #se non ho una sessione attiva mando messaggio d'errore ed esco
                if @request.utente_portale.blank?
                    $out << { :ok => "false",
                              :cod_errore => "autenticazione_necessaria" }.to_json 
                    done
                else
                    id_utente = @request.params['id_utente']
                    sid = @request.params['sid']
                    if id_utente.blank? || sid.blank?
                        $out << { :ok => "false",
                              :cod_errore => "inviare parametri id_utente e sid" }.to_json 
                        done
                    elsif @request.utente_portale.id.to_s != id_utente || @request.session.sid != sid
                        $out << { :ok => "false",
                              :cod_errore => "sessione_non_valida" }.to_json 
                        done
                    end

                end
            else
                unless controllo
                    return @request.utente_portale ? true : false
                end
                unless controllo_utente_portale(@request.utente_portale)
                    redirect Portal.request_url+'/autenticazione?redirect='+CGI.escape(@request.env['REQUEST_URI'])
                end
                url_consentiti = Spider.conf.get('portal.url_consentiti_string_auth')
                #se è abilitata l'autenticazione forte a livello di login controllo se in sessione c'è la variabile
                if Spider.conf.get('portal.abilita_autenticazione_forte') == true && Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == false &&\
                 Portal::PortalController.auth_forte_da_fare(@request)
                    if Spider.conf.get('messenger.sms.backend').blank?
                        Spider.logger.error "Configurare il backend per gli sms per avere l'autenticazione forte."
                    else
                        redirect Portal.request_url+'/codice_autenticazione?redirect='+CGI.escape(@request.env['REQUEST_URI'])
                    end
                    
                end
            end
        end

        def non_autorizzato!
            raise Portal::NonAutorizzato
        end
        
        __.html :template => 'non_autorizzato'
        def non_autorizzato
            if na = @request.session.flash[:servizio_non_autorizzato] && @request.utente_portale
                @scene.servizio = @request.utente_portale.servizio_privato(na)
            end
        end
        
        
        def carica_utente(classe, tipo)
            utente = classe.restore(@request)
            return false unless utente && utente.utente_portale
            utente_autenticato(utente, tipo)
            return utente
        end

        def utente_autenticato(utente, tipo=:login, salva_sessione=false)
            raise "Utente portale non trovato" unless utente && utente.utente_portale
            @request.autenticazioni[tipo] = utente
            @request.utente_portale = utente.utente_portale
            @request.user = utente
            utente.save_to_session(@request.session) if salva_sessione
        end

        
        def carica_utenti
            
            @request.autenticazioni = {}
            if Spider.conf.get('portal.autenticazione_interna')
                carica_utente(UtenteLogin, :login)
            end
            Portal.auth_providers.each do |provider|
                next if !provider.details[:user_model] && !provider.details[:no_model]
                if provider.details[:no_model]
                    carica_utente(UtenteLogin, :login)
                else
                    carica_utente(provider.details[:user_model], provider.details[:label])
                end
            end
        end

        
        def controllo_utente_portale(utente)
            if utente
                return true if utente.attivo?
                msg = ""
                if utente.disabilitato?
                    msg = "Questo account è stato disabilitato dall'amministratore, per chiarimenti e informazioni contatta l'amministratore all'indirizzo
                            <a href=\"mailto:#{Spider.conf.get('portal.email_amministratore')}\">#{Spider.conf.get('portal.email_amministratore')}</a>."                    
                else
                    if utente.stato == 'contatti' || utente.stato == 'seconda_conferma'
                        if (Spider.conf.get('portal.conferma_email') && !utente.email_confermata) || (Spider.conf.get('portal.conferma_email') && utente.stato == 'seconda_conferma')
                            msg = "Questo account è in attesa di conferma dell' indirizzo e-mail. Dovresti ricevere a breve un' e-mail con le 
                            istruzioni per confermare l'indirizzo. Se non ricevi l' e-mail per più di 30 minuti, controlla nella cartella della 
                            posta indesiderata. <br />
                            Se sei sicuro di non aver ricevuto l' e-mail, puoi richiedere un nuovo invio <a href=\"#{Portal::PortalController.url('controllo_cellulare')}\">cliccando qui</a><br />
                            Se hai già ripetuto l' operazione e continui a non ricevere le e-mail, per favore contatta l' amministratore all' indirizzo <a href=\"mailto:#{Spider.conf.get('portal.email_amministratore')}\">
                            #{Spider.conf.get('portal.email_amministratore')}</a>"                 
                        elsif (Spider.conf.get('portal.conferma_cellulare') && !utente.cellulare_confermato) || (Spider.conf.get('portal.conferma_cellulare') && utente.stato == 'seconda_conferma') 
                            msg ="Questo account è in attesa di conferma del numero di cellulare. Dovresti aver ricevuto un SMS con il 
                            codice da inserire per attivare l'accout. Vai alla pagina di 
                            <a href=\"#{Portal::PortalController.url('controllo_cellulare')}\">controllo numero di cellulare</a>
                            per inserire il codice o richiederne nuovamente l'invio."
                        else
                            metodo_avviso = ""
                            metodo_avviso = 'via e-mail' if Spider.conf.get('portal.conferma_email')
                            metodo_avviso = 'via sms' if Spider.conf.get('portal.conferma_cellulare')
                            msg = "Questo account è in attesa di attivazione da parte dell'amministratore. Verrai avvisato #{metodo_avviso} non appena 
                            l'account verrà attivato."
                        end
                    else
                        msg = "Questo account è in attesa di attivazione da parte dell'amministratore. Verrai avvisato via e-mail non appena 
                        l'account verrà attivato."
                    end
                end
                @request.session.flash[:attesa_attivazione_account] = msg
            end
            return nil
        end
        
        def try_rescue(exc)
            if exc.is_a?(Forbidden)
                return redirect(Portal.request_url+'/non_autorizzato')
            end
            super
        end

    end

end

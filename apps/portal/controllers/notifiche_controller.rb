# -*- encoding : utf-8 -*-
module Portal

    module Notifiche

        class NotificheController < Spider::PageController
            include HTTPMixin, StaticContent
            include Spider::Messenger::MessengerHelper rescue NameError
            include AutenticazionePortale

            layout 'portal'

            def before(action='', *params)
                super
                app_list = Portal::Notifiche::Applicazione.all
                @active_apps = []
                @active_apps_present = false
                #Verifico quali applicazioni sono attive della lista delle applicazioni con notifiche
                app_list.each do |row|
                    if Spider.apps_by_short_name.include?(row.codice) && !Spider.conf.get('notifiche.moduli_da_disattivare').include?(row.codice)
                        @active_apps << row
                        @active_apps_present = true
                    end
                end
            end

            __.html  :template => 'notifiche/index'
            def index
                autenticazione_necessaria
            	utente = @request.utente_portale
                errori = []
                if @request.post? && !@request.params["salva_notifiche"].blank?
                    @active_apps.each do |app|
                        notifica = Portal::Notifiche::Notifica.load(:utente => utente.id, :applicazione => app.codice)
                        unless @request.params[app.codice].blank?                        
                            if !@request.params[app.codice]["email"].blank? && @request.params[app.codice]["email"].to_bool == true
                                notifica.notifica_email = true 
                            else
                                notifica.notifica_email = false
                            end
                            if !@request.params[app.codice]["sms"].blank? && @request.params[app.codice]["sms"].to_bool == true
                                notifica.notifica_sms = true 
                            else
                                notifica.notifica_sms = false
                            end
                        else
                            notifica.notifica_email = false
                            notifica.notifica_sms = false
                        end
                        notifica.save
                    end
                    if (!@request.params['email_utente'].blank? && (@request.params['email_utente'].downcase =~ /\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}\b/) == nil) || @request.params['email_utente'].blank?
                        errori << "L'indirizzo email non è corretto"
                        @scene.email_error = "error"
                    end

                    if (!@request.params['cellulare_utente'].blank? && (@request.params['cellulare_utente'].downcase =~ /^[0-9]+$/) == nil) || @request.params['cellulare_utente'].blank?
                        errori << "Il numero di cellulare non è corretto"
                        @scene.cellulare_error = "error"
                    end
                    if errori.empty?
                        utente.email = @request.params['email_utente'].downcase
                        utente.cellulare = @request.params['cellulare_utente']
                        utente.save
                    else
                        @scene.errori = errori
                    end    
                end    
                #carico l'elenco delle impostazioni delle notifiche presenti per l'utente loggato
                #altrimenti creo i records di ogni app attiva per il medesimo utente
                notifiche = []
                @active_apps.each do |app|
                    app_found = Portal::Notifiche::Notifica.load(:utente => utente.id, :applicazione => app.codice)
                    unless app_found
                        notifica = Portal::Notifiche::Notifica.new 
                        notifica.utente = utente
                        notifica.applicazione = app.codice
                        notifica.notifica_email = true
                        notifica.notifica_sms = true
                        notifica.notifica_push = false
                        notifica.save
                        notifiche << notifica
                    end
                    notifiche << app_found if app_found
                end
                #notifiche = Portal::Notifiche::Notifica.where{ utente_portale == utente.id }
            	@scene.utente = utente
                @scene.notifiche = notifiche
                @scene.active_apps_present = @active_apps_present
            end
        end
    end
end

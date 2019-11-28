# -*- encoding : utf-8 -*-
require 'apps/portal/controllers/mixins/autenticazione_portale'

module Portal

    module ControllerServizio
        include Annotations
        
        def self.included(klass)
            super
            klass.send(:include, Portal::AutenticazionePortale)
            klass.extend(ClassMethods)
        end
        
        def before(action='', *params)
            return super unless self.class.servizio_portale
            if sp = self.class.servizio_portale
                parti = action.to_s.split('/')
                if !parti[0].blank? && sp[:eccetto] && sp[:eccetto].include?(parti[0].to_sym)
                    return super
                end
                unless @scene.portale
                    red = Portal.http_s_url(:servizi)+'/'+self.class.id_servizio_portale
                    red += '/'+action unless action.blank?
                    redirect red
                end 
            end
            @scene.utente_portale = @request.utente_portale
            if self.class.servizio_portale && Portal.const_defined?(:Servizio)
                @scene.servizio_portale = @scene.hash_servizi[self.class.id_servizio_portale]
            end
            return super if @dispatch_next[action]
            parametri = self.class.servizio_portale || {}
            return super if (parametri[:eccetto] || []).include?(action)

            unless self.class.pubblico?
                autenticazione_necessaria
                
                #faccio l'autenticazione forte a livello di servizio
                # if auth_forte_da_fare_per_servizio
                    
                #     @request.utente_portale.servizi_privati.each{ |servizio|
                #         if servizio[:servizio][:id] == self.class.servizio_portale[:id] 
                #             if servizio[:servizio].richiede_strong_auth == true
                #                 if Spider.conf.get('messenger.sms.backend').blank?
                #                     Spider.logger.error "Configurare messenger.sms.backend per abilitare l\'autenticazione forte sul sito"
                #                 else
                #                     utente = @request.utente_portale
                #                     if utente.cellulare.blank?
                #                         @request.session.flash['errore_strong_auth'] = "Spiacente non è possibile proseguire in quanto non hai inserito il tuo numero di cellulare. Inserirlo nella sezione 'Contatti'"
                #                         redirect Portal::PortalController.http_s_url('dettagli_utente?modifica')
                #                     else 
                #                         #cellulare presente, controllo se è confermato o no
                #                         if utente.cellulare_confermato.blank?
                #                             @request.session.flash['errore_strong_auth'] = "Per completare l'autenticazione confermare il numero di cellulare inserendo il codice di verifica o inviare un nuovo codice"
                #                             redirect Portal::PortalController.http_s_url('dettagli_utente')
                #                         else
                #                             # il cellulare è presente e confermato, mando sms con chiave, salvo chiave in tab Utenti e mostro view
                #                             utente_da_db = Portal::Utente.new(utente.id)
                #                             invio_sms_riuscito = utente_da_db.invia_sms_autenticazione_forte
                #                             Spider.logger.error "Invio sms per auth forte fallito" unless invio_sms_riuscito
                #                             if invio_sms_riuscito
                #                                 @request.session.flash['messaggio_info'] = "Per proseguire è necessario inserire il codice di autenticazione inviato tramite sms al suo numero di cellulare ovvero al numero #{utente.cellulare}"
                #                                 @request.session['strong_auth_da_servizio'] = { :id => servizio[:servizio][:id], :url => self.class.http_s_url}
                #                                 redirect Portal::PortalController.http_s_url('codice_autenticazione') 
                #                             end
                #                         end

                #                     end
                #                 end    
                #             end
                            
                #         end

                #     }
                    
                #     a=3
                # end 


                unless sp[:mostra_default]
                    metodo = nil
                    metodo = action.split('/')[0].to_sym unless action.empty?
                    unless parametri[:except] && parametri[:except].include?(metodo)
                        servizio = @request.utente_portale.servizio_privato(self.class.servizio_portale[:id])
                        unless servizio && servizio.stato &&
                                ((servizio.stato.id == 'configurazione' && !self.class.url_configurazione) || servizio.stato.id == 'attivo')
                            @request.session.flash[:servizio_non_autorizzato] = self.class.servizio_portale
                            @request.session.flash[:stato_servizio_non_autorizzato] = @req
                            non_autorizzato!
                        end
                        @servizio_privato = servizio
                    end
                end    
            end
            super
        end
        
        def auth_forte_da_fare_per_servizio
            url_consentiti = Spider.conf.get('portal.url_consentiti_string_auth')
            if Spider.conf.get('portal.abilita_autenticazione_forte_per_servizio') == true && !url_consentiti.include?(request.path) && request.session['strong_auth_valid'].blank? && request.utente_portale.attivo?
                return true
            else
                return false
            end

        end
        
        module ClassMethods

            def route_path(azione=nil)
                sp = self.servizio_portale
                return super unless sp
                unless sp[:id]
                    Spider.logger.error("ID MANCANTE:")
                    Spider.logger.error(self)
                end
                parti = azione.to_s.split('/')
                if !parti[0].blank? && sp[:eccetto] && sp[:eccetto].include?(parti[0].to_sym)
                    return super
                else
                    url = Portal::PortalController.route_path(:servizi)+'/'+sp[:id]
                end
                url += '/'+azione.to_s if azione
                url 
            end

            # Definisce i parametri del servizio portale
            # Parametri:
            # * :id:            identificativo univoco del servizio
            # * :nome:          nome descrittivo per il servizio
            # * :accesso:       tipo di accesso (vedi Portal::Servizio.accesso)
            # * :descrizione    descrizione servizio
            # * :eccetto        metodi da escludere
            def servizio_portale(params=nil, depr_params=nil)
                if params.is_a?(String) # deprecato, vecchia api
                    Spider.logger.warn("#{self}: Il metodo 'servizio_portale' ora accetta un hash")
                    id = params
                    params = depr_params || {}
                    params[:id] = id
                    params[:accesso] ||= 'pubblico'
                    params[:accesso] = params[:accesso].to_s
                end
                if params
                    @servizio_portale = params
                    Portal.aggiungi_servizio(params[:id], self)
                end
                @servizio_portale
            end

            def url_servizio(parametri)
                self.url(parametri)
            end

            def parametri_servizio_portale
                @servizio_portale
            end
            
            def url_configurazione
                @servizio_portale[:url_configurazione]
            end

            def servizio_privato(nome=nil, parametri = {})
                Spider.logger.warn("#{self}: Il metodo 'servizio_privato' è deprecato; usare servizio_portale")
                parametri[:id] = nome
                servizio_portale(parametri)
            end


            def nome_servizio_portale
                @servizio_portale[:nome]
            end

            def id_servizio_portale
                @servizio_portale[:id]
            end
            
            def configurazione_necessaria?
                @servizio_portale[:configurazione_necessaria]
            end

            def pubblico?
                @servizio_portale[:accesso] == 'pubblico'
            end
    
        end

    end

    HelperAutenticazione = ControllerServizio # Deprecato
    
    class NonAutorizzato < Forbidden
    end

end

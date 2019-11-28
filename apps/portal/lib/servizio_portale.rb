# -*- encoding : utf-8 -*-
module Portal

    class ServizioPortale
        attr_reader :controller, :oggetto_db
        attr_accessor :url

        def initialize(controller=nil, oggetto_db=nil)
            @controller = controller
            @oggetto_db = oggetto_db
        end

        def controller?
            !!@controller
        end

        def oggetto_db?
            !!@oggetto_db
        end

        def servizio_utente?
            @oggetto_db && @oggetto_db.is_a?(Portal::Utente::ServiziPrivati)
        end

        def id
            if @oggetto_db
                if @oggetto_db.respond_to?(:servizio)
                    @oggetto_db.servizio.id
                else
                    @oggetto_db.id
                end
            elsif @controller
                @controller.servizio_portale[:id]
            end
        end

        def nome
            get_val(:nome) || get_val(:id)
        end

        def descrizione
            get_val(:descrizione).to_s
        end

        def accesso
            val = get_val(:accesso)
            val = val.id if val.is_a?(Spider::Model::BaseModel)
            val ? val.to_s : val
        end

        def privato?
            self.accesso == 'registrati' || (!pubblico? && !nascosto?)
        end

        def pubblico?
            self.accesso == 'pubblico'
        end

        def nascosto?
            self.accesso == 'nascosto' || (!pubblico? && !oggetto_db?)
        end

        def abilitati?
            self.accesso == 'abilitati'
        end

        # True se il servizio viene mostrato di default agli utenti che non l'hanno richiesto esplicitamente
        # (solo per servizi ad accesso 'registrati')
        def mostra_default?
            if ['registrati', 'confermati'].include?(self.accesso)
                !!get_val(:mostra_default)
            else
                false
            end
        end

        def stato
            get_val(:stato)
        end

        def url
            return @url unless @url.blank?
            @url = get_val(:url)
            @url.blank? && !@controller.blank? ? @controller.url : @url
        end

        def url=(val)
            @url = val
        end

        def attivo?
            if servizio_utente?
                get_val(:stato) == 'attivo'
            else
                mostra_default?
            end
        end

        def in_configurazione?
            if servizio_utente?
                get_val(:stato) == 'configurazione'
            else
                false
            end
        end

        def servizio_utente
            servizio_utente? ? @oggetto_db : nil
        end


        def cut(h)
            res = {}
            h.keys.each do |k|
                res[k] = self.send(k)
            end
            res
        end

        #ritorna true se è settato a true il campo nel servizio da codice o in db
        def web_service?
            web_service = get_val(:web_service)
            web_service == true
        end

        def web_service
            get_val(:web_service) == true
        end

        #ritorna true se è settato a true il campo nel servizio da codice o in db
        def richiede_strong_auth?
            richiede_strong_auth = get_val(:richiede_strong_auth)
            richiede_strong_auth == true
        end

        def richiede_strong_auth
            get_val(:richiede_strong_auth) == true
        end
        
        #ritorna true se questo servizio viene usato con oauth2
        def usa_oauth?
            usa_oauth = get_val(:usa_oauth)
            usa_oauth == true
        end

        def ordina_posizione(other)
            if self.oggetto_db? && (self.oggetto_db.posizione.blank? || self.oggetto_db.posizione == 0)
                self.oggetto_db.posizione = 100
            end
            if other.oggetto_db? && (other.oggetto_db.posizione.blank? || other.oggetto_db.posizione == 0)
                other.oggetto_db.posizione = 100
            end
            if self.oggetto_db? && other.oggetto_db?
                self.oggetto_db.posizione <=> other.oggetto_db.posizione
            else
                0
            end
        end



        private

        def get_val(key)
            val = @oggetto_db.send(key) if @oggetto_db && @oggetto_db.respond_to?(key)
            val ||= @controller.servizio_portale[key] if @controller
            val
        end



    end

end

# -*- encoding : utf-8 -*-

module Portal
    
    class Servizio < Spider::Model::Managed
        label 'Servizio', 'Servizi'
        remove_element :id
        element :id, String, :primary_key => true, :required => true, :unique => true, :check => { "L'identificativo può contenere solo lettere, slash (/) e underscore (_)" => /^[\w_\/]+$/
        }
        element :nome, String
        element :url, String
        element :descrizione, Text
        #indica se il servizio espone dei metodi per le app ios e android
        element :web_service, Spider::Bool, :label => 'Imposta come web service per mobile-apps', :hidden => true
        #indica se il servizio è gestibile da un amministratore specifico
        element :gestibile, Spider::Bool, :label => 'Gestibile da amministratore'
        choice :accesso, Spider::OrderedHash[
            'pubblico', 'Pubblico',
            'registrati', 'Solo utenti registrati',
            'confermati', 'Solo utenti registrati e confermati',
            'abilitati', 'Solo utenti registrati e abilitati al servizio',
            'nascosto', 'Servizio nascosto, solo per utenti abilitati'
        ]
        #contesti applicativi usati per integrazione con Civilia Open
        choice :contesto, Spider::OrderedHash[
            'PRAT', 'Pratiche ed Istanze',
            'DEMO', 'Demografia',
            'IMUT', 'IMU / TASI',
            'TARI', 'Tari/Tares/Tarsu/Tia',
            'FORNMUSE', 'Fornitore',
            'MUSE', 'Servizi su richiesta'
        ]
        #element :prima_pagina, Spider::Bool, :description => 'Mostra il servizio (non pubblico) in prima pagina di default'
        element :posizione, Integer, :label => 'Posizione in menu del servizio', :default => 0, :check => { "Deve essere inserito un numero compreso tra 1 e 100, lasciare campo vuoto per ordinamento automatico" => Proc.new{ |val| val!=0 ? val <= 100 && val >0 : true  } }
        element :richiede_strong_auth, Spider::Bool, :label => 'Conferma accesso con SMS'
        element :usa_oauth, Spider::Bool, :label => 'Utilizza Oauth2'
        
        def url
            u = super
            if u.blank?
                s_id, rest = self.id.split('/', 2)
                if s = Portal.servizi[s_id]
                    u = s.url_servizio(rest)
                    @url = u
                end
            end
            u
        end

        def nome
            super
            if @nome.blank? && self.controller
                @nome = self.controller.servizio_portale[:nome]
            end
            @nome
        end

        def descrizione
            super
            if @descrizione.blank? && self.controller
                @descrizione = self.controller.servizio_portale[:descrizione]
            end
            @descrizione
        end

        def accesso
            super
            if @accesso.blank? && self.controller
                @accesso = Portal::Servizio::Accesso.new(self.controller.servizio_portale[:accesso])
            end
            @accesso
        end

        def abilitati?
            self.accesso == 'abilitati'
        end

        def pubblico?
            self.accesso == 'pubblico'
        end

        def privato?
            !pubblico?
        end

        def nascosto?
            self.accesso == 'nascosto'
        end

        def riservato?
            nascosto? || abilitati?
        end

        def controller
            Portal.servizi[self.id]
        end
        
        def web_service?
            self.web_service == true
        end

    end
    
end

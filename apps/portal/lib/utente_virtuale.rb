# -*- encoding : utf-8 -*-
module Portal
    
    module UtenteVirtuale
        # campi = [:nome, :cognome, :codice_fiscale, :sesso, :comune_nascita, :provincia_nascita, :stato_nascita,
        #         :data_nascita, :comune_residenza, :provincia_residenza, :indirizzo_residenza,
        #         :tipo_documento, :numero_documento, :data_documento, :documento_rilasciato, :email,
        #         :telefono, :fax, :cellulare]
        # campi.each{ |c| attr_accessor c }
        
        def stato
            'attivo'
        end
        
        def servizi_privati
            []
        end
        
        def servizio_privato(id)
            nil
        end
        
        def to_s
            "#{self.nome} #{self.cognome}"
        end
        
        def attivo?
            true
        end
        
        def servizio_privato?(id)
            false
        end
        
        
    end
    
end

# -*- encoding : utf-8 -*-
module Portal
    
    class UtentePatTrentino < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente CRS Pat Trentino', 'Utenti CRS Pat Trentino'
        include Autenticazione
        register_authentication :pat_trentino
        element_attributes :provider, :fixed => 'pat_trentino', :hidden => true

        def to_s
            "#{self.nome} #{self.cognome}"
        end
           
    end
    
end

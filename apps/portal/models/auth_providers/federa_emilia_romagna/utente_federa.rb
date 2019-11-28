# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteFederaEmiliaRomagna < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente Federa Emilia Romagna', 'Utenti Federa Emilia Romagna'
        include Autenticazione
        register_authentication :federa_emilia_romagana
        element_attributes :provider, :fixed => 'federa_emilia_romagana', :hidden => true

        def to_s
            "#{self.nome} #{self.cognome}"
        end
           
    end
    
end

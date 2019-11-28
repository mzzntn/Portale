# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteShibbolethPuglia < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente Shibboleth Regione Puglia', 'Utenti Shibboleth Regione Puglia'
        include Autenticazione
        register_authentication :shibboleth_puglia
        element_attributes :provider, :fixed => 'shibboleth_puglia', :hidden => true

        def to_s
            "#{self.nome} #{self.cognome}"
        end
           
    end
    
end

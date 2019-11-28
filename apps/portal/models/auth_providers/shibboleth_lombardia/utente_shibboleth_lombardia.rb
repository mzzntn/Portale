# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteShibbolethLombardia < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente Shibboleth Regione Lombardia', 'Utenti Shibboleth Regione Lombardia'
        include Autenticazione
        register_authentication :shibboleth_lombardia
        element_attributes :provider, :fixed => 'shibboleth_lombardia', :hidden => true

        def to_s
            "#{self.nome} #{self.cognome}"
        end
           
    end
    
end

# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteShibboleth < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente Shibboleth Cinisello', 'Utenti Shibboleth Cinisello'
        include Autenticazione
        register_authentication :shibboleth
        element_attributes :provider, :fixed => 'shibboleth', :hidden => true

        def to_s
            "#{self.nome} #{self.cognome}"
        end
           
    end
    
end

# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteRegioneLombardia < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente CRS Regione Lombardia', 'Utenti CRS Regione Lombardia'
        include Autenticazione
        register_authentication :regione_lombardia
        element_attributes :provider, :fixed => 'regione_lombardia', :hidden => true
        element :origine_dati_utente, String
        element :cns_carta_reale, Spider::DataTypes::Bool
        element :cns_subject, String
        element :cns_issuer, String
        
    end
    
    
end

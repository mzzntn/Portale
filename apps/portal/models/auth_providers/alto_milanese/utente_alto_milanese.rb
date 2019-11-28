# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteAltoMilanese< Portal::UtenteEsterno
        class_table_inheritance
        label 'Utente portale Alto Milanese', 'Utenti portale Alto Milanese'
        include Autenticazione
        register_authentication :alto_milanese
        element_attributes :provider, :fixed => 'alto_milanese', :hidden => true
        element :tipo_soggetto, String

        def to_s
            "#{self.nome} #{self.cognome}"
        end
        
    end
    
    
end

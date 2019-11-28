# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteSpidAgid < Portal::UtenteEsterno
        extend_model superclass
        label 'Utente Spid', 'Utenti Spid'
        include Autenticazione
        register_authentication :spid
        #element_attributes :provider, :fixed => 'spid', :hidden => true
        element :spid_code, String
        element :expiration_date, String
        element :gestore_identita, String

        def to_s
            "#{self.nome} #{self.cognome}"
        end
           
        def get_attributi_eidas
            return nil if self.info_extra.nil?
            return nil unless self.provider == 'eidas'
            hash_info_extra = JSON.parse(self.info_extra)
            if hash_info_extra.blank?
                return {}
            else
                return hash_info_extra['attributi_scelti']
            end
        end


    end
    
end

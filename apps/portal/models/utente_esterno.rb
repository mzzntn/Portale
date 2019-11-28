# -*- encoding : utf-8 -*-
module Portal
    
    class UtenteEsterno < Spider::Model::Managed
        label 'Utente Esterno', 'Utenti Esterni'
        # remove_element :id
        element :provider, String, :hidden => true # , :primary_key => true
        element :chiave, String, :hidden => true #, :primary_key => true
        #json: serve ad es per tenere memorizzati i dati che sono scelti dal cittadino in fase di login con eidas
        element :info_extra, String, :hidden => true 

        def identifier
            "#{self.provider}_#{self.chiave}"
        end
        

    end
    
    
end

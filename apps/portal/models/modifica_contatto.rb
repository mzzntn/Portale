# -*- encoding : utf-8 -*-
module Portal
   
   class ModificaContatto < Spider::Model::Managed 
        element :tipo, String, :choices => ['email', 'cellulare']
        element :prima, String
        element :dopo, String
        element :chiave_conferma, String
        element :confermata, Spider::Bool, :computed_from => [:chiave_conferma]
        element_attributes :obj_created, :hidden => false
        element :conferme_mandate, Integer, :hidden => true
       
        def confermata
            self.chiave_conferma.blank?
        end
       
        alias :confermata? :confermata
           
       
   end
    
end

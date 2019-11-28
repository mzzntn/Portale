# -*- encoding : utf-8 -*-
module Portal
   
    class Gruppo < Spider::Model::Managed 
        label 'Gruppo utente', 'Gruppi utente'
        element :nome, String
        element :descrizione, String
        element :lingua, String
    end
    
end

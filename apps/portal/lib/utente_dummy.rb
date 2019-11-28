# -*- encoding : utf-8 -*-
module Portal
    
    class Utente
        include UtenteVirtuale
        include Spider::Auth::Authenticable
        
    end
    
end

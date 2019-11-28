# -*- encoding : utf-8 -*-
require 'uuidtools'

module Portal
    
    class UtenteLogin < Spider::Auth::LoginUser
        include Autenticazione
        label 'Utente Login', 'Utenti Login'
        
        def before_delete
            str_today = DateTime.now.strftime("%d%m%Y%H%M%S")
            unless self.utente_portale.blank?
                #aggiungo 'cancellato-' nella mail per poter registrare la stessa mail
                unless self.utente_portale.email.blank?
                    self.utente_portale.email = "cancellato-#{str_today}-"+self.utente_portale.email 
                end
                
                if self.utente_portale.respond_to?(:utente_newsletter) && !self.utente_portale.utente_newsletter.blank?
                    self.utente_portale.utente_newsletter.delete
                end
                #metto a true il flag cancellato
                self.utente_portale.cancellato = true
                #metto lo stato a disabilitato
                self.utente_portale.stato = 'disabilitato'
                self.utente_portale.save
            end
        end

    end
    
end

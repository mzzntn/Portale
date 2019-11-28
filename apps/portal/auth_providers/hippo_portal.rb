# -*- encoding : utf-8 -*-
require 'apps/portal/lib/auth_provider'
require 'apps/hippo_portal/models/utente'
require 'apps/portal/lib/utente_virtuale'
# require 'php_serialize'

HippoPortal::Utente.class_eval do
    include Portal::Autenticazione
    register_authentication :hippo_portal
    include Portal::UtenteVirtuale
    
    def utente_portale
        self
    end
    
    def chiavi_servizi_privati
        @chiavi_servizi_privati ||= self.siti.map{ |s| Spider::Inflector.underscore(s.servizio_privato.nome)}
    end
    
    def servizio_privato?(id)
        self.chiavi_servizi_privati.include?(id)
    end
    
    
    def self.restore(request)
        user_id = ::HippoPortal.user_id_hippo(request)
        return self.new(user_id) if user_id
    end
    
    
end


module Portal
    
    class HippoPortal < Spider::PageController
        include HTTPMixin
        include AuthProvider
        auth_provider({
            :label => 'hippo_portal',
            :nome => 'OpenWeb 1.0',
            :descrizione => 'Servizio di autenticazione attraverso il portale OpenWeb 1.0',
            :user_model => ::HippoPortal::Utente
        })
        
        
        def index
            redirect Portal.http_s_url
        end

    end
    
    class SuperUser

        def self.restore(request)
            user_id = ::HippoPortal.user_id_hippo(request)
            return self.new if user_id.to_i == -1
            return nil
        end
    end
    
end

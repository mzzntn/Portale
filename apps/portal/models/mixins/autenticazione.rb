# -*- encoding : utf-8 -*-
module Portal
    
    module Autenticazione
        
        def self.included(mod)
            mod.send :include, Spider::Auth::Authenticable
            if Portal::Utente < Spider::Model::BaseModel
                mod.element(:utente_portale, Portal::Utente, :integrate => true, :add_reverse => {
                    :name => "#{mod.short_name}".to_sym, :autenticazione_portale => true})
            else
                mod.class_eval do
                    attr_accessor :utente_portale
                end
            end
        end
        
    end
end

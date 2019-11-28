# -*- encoding : utf-8 -*-
module Portal
    
    module AuthProvider
        
        def self.included(klass)
            klass.extend(ClassMethods)
            Portal.add_auth_provider(klass)
        end
        
        module ClassMethods
            
            # Definisce i dettagli del provider. details è un Hash nella forma
            #   {
            #     :label => 'label_provider',
            #     :nome => 'Nome Descrittivo',
            #     :descrizione => 'Descrizione estesa',
            #     :controller => ClasseController (opzionale, di default è la classe del provider),
            #     :user_model => ClasseModelUtente (opzionale, di default è Utente[NomeProvider], se esiste)
            #   }
            #   :label deve essere uguale al nome del file che definisce il provider
            def auth_provider(details)
                details[:controller] ||= self
                unless details[:user_model]
                    user_model_name = "Utente#{self.name.split(':')[-1]}"
                    details[:user_model] = Portal.const_get(user_model_name) if Portal.const_defined?(user_model_name)
                end
                unless details[:logo].blank?
                    details[:logo] = File.join('../public/img/auth_providers/',details[:logo])
                end
                @auth_provider_details = details
            end
            
            # Ritorna i dettagli del provider.
            def details
                @auth_provider_details
            end

            def verifica_presenza_configurazioni(provider, configurazioni)
                configurazioni.each{ |configurazione| 
                    raise "Inserire le configurazioni per #{provider}: portal.#{provider}.#{configurazione}" if Spider.conf.get("portal.#{provider}.#{configurazione}").blank?
                }
                

        end
            
        end
        
    end
    
    
end

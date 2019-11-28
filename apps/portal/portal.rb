# -*- encoding : utf-8 -*-
require 'json'

module Portal

   def self.app_startup
       Spider.logger.info("Servizi portale: \n"+Spider::HomeController.print_app_routes(Portal.servizi))
   end
   
   def self.add_auth_provider(klass) 
       self.auth_providers << klass
   end
   
   def self.auth_providers
       @auth_providers ||= []
   end
   
   def self.servizi
       @servizi ||= {}
   end

   def self.aggiungi_servizio(id, controller)
       self.servizi[id] = controller
       Portal::ControllerServizi.aggiungi_servizio(id, controller)
   end
   
   def self.auth_provider(label)
       self.auth_providers.each do |prov|
           return prov if prov.auth_provider.label == label
       end
   end
   
   def self.scarica_utenti(url, modificati_dopo=nil)
       if modificati_dopo
           url += "?#{modificati_dopo}"
       end
       res = Spider.http_client.get_response(URI.parse(url))
       utenti = JSON.parse(res)
       utenti.each do |u|
           utente = Utente.from_hash_dump(u)
           if utente.in_storage?
               utente.update
           else
               utente.insert
           end
       end
   end

   def self.controlla_campi_obbligatori(obj_utente, array_campi_required=nil)

        array_campi_required ||= ['nome','cognome','codice_fiscale','sesso','provincia_nascita','comune_nascita','data_nascita','provincia_residenza','comune_residenza',\
            'indirizzo_residenza','civico_residenza']
            # tolto il cap 'cap_residenza' dai campi obbligatori per problemi con siti vecchi che non hanno il cap nella maschera
        array_campi_required << 'cellulare' if Spider.conf.get('portal.cellulare_obbligatorio')
        array_campi_required << 'pec' if Spider.conf.get('portal.pec_obbligatoria')
        array_campi_required.concat(['numero_documento','data_documento','documento_rilasciato']) if Spider.conf.get('portal.richiedi_documento')
        array_campi_required = array_campi_required.uniq
        unless obj_utente.modifiche_contatti.blank?
            mod_email_pendente = false
            obj_utente.modifiche_contatti.each{ |modifica_pendente|
                if modifica_pendente.tipo == "email"
                    mod_email_pendente = true
                    break
                end 
            } 
        end
        array_campi_required << 'email' unless mod_email_pendente
        tutti_campi_presenti = true
        
        array_campi_required.each{ |id_campo|
            if obj_utente[id_campo.to_sym].blank?
                tutti_campi_presenti = false 
                break
            end 
        }
        tutti_campi_presenti
    end

    def self.par_ext_app(id_servizio,sid,id_utente)
        if !Spider.conf.get("portal.client_id_oauth2").blank? && !Spider.conf.get("portal.secret_oauth2").blank?
            #controllo se il servizio ha l'oauth attivo
            servizio = Portal::Servizio.load(:id => id_servizio)
            return "" if (servizio.blank? || !servizio.respond_to?(:usa_oauth))
            return "&c_id=#{Spider.conf.get("portal.client_id_oauth2")}&sid=#{sid}&u_id=#{id_utente}" if servizio.usa_oauth
        end
        return ""
    end
    
end

Spider.register_resource_type(:portal_auth_providers, :extensions => ['rb'], :path => 'auth_providers')

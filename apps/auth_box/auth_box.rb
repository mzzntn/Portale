# -*- encoding : utf-8 -*-
module AuthBox
  

        def self.auth_user_class(parametri={})
                #in base ai parametri restituisco la classe dello user che deve esserci per essere autenticati
                identificatore  = parametri[:id].to_s.downcase
                #prendo da configurazione l'hash con i dati dell'autenticazione scelta
                hash_dati_autenticazione = Spider.conf.get('auth_box.'+identificatore)
                required_class = self.const_get(hash_dati_autenticazione['provider']+"User")
                required_class
        end

        #questo metodo restituisce l'eventuale url esterno del servizio per rifare il login
        def self.redirect_auth_url(parametri={})
                identificatore  = parametri[:id].to_s.downcase
                hash_dati_autenticazione = Spider.conf.get('auth_box.'+identificatore)
                controller_auth = self.const_get(hash_dati_autenticazione['provider']+"Controller")
                controller_auth.http_s_url("login/#{identificatore}")
        end

        def self.verifica_presenza_configurazioni(id_auth)
                hash_dati_autenticazione = Spider.conf.get("auth_box.#{id_auth}")
                raise "Inserire il 'provider' per l'identificatore #{id_auth}: 
                     auth_box.#{id_auth}:
                        auth_box.#{id_auth}.provider: provider_type" if hash_dati_autenticazione['provider'].blank?
        end

end

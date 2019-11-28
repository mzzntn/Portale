# -*- encoding : utf-8 -*-
require 'apps/auth_box/models/twitter_user'

module AuthBox
    
    class TwitterController < Spider::PageController
        
    	
        layout 'auth_box'
   
        __.action
        def login(id_auth_box_config)
            #salvo in sessione il path da dove arrivo e l'id della configurazione di auth_box
            hash_dati_autenticazione = Spider.conf.get('auth_box.'+id_auth_box_config)
            @request.session["twitter_back_controller"] = hash_dati_autenticazione['controller_chiamante']
            @request.session["id_controller"] = id_auth_box_config
            #chiamo il metodo di autenticazione di omniauth-twitter
            url_callback = self.class.http_s_url('callback')
            url_callback = Spider.conf.get('site.domain')+"#{url_callback}" unless url_callback.include?(Spider.conf.get('site.domain'))
            redirect "/auth/twitter?callback_url=#{url_callback}"
        end    

        __.action
        def logout
            id_auth_box_config = @request.params['id_auth_box_config']
            #salvo in sessione il path da dove arrivo e l'id della configurazione di auth_box
            hash_dati_autenticazione = Spider.conf.get('auth_box.'+id_auth_box_config)
            redirect_path = hash_dati_autenticazione['controller_chiamante']
            provider = hash_dati_autenticazione['provider']
            #cancello dalla sessione l'user di twitter
            @request.session[:auth]['AuthBox::'+provider+'User'] = nil
            @request.env["omniauth.auth"] = nil

            klass = redirect_path.split('::').inject(Object) {|o,c| o.const_get c}
            redirect klass.http_s_url
        end 
   
        __.action
        def callback
            #se ho dato le autorizzazioni per l'applicazione salvo in sessione l'utente per twitter
                
            #recupero dalla sessione il path per fare il redirect
            controller_chiamante_class = @request.session["twitter_back_controller"]
        	#salvo i parametri in sessione
            external_user = TwitterUser.new
            external_user.data_login = DateTime.now
            external_user.id_controller = @request.session["id_controller"]
            #salvo nell'user tutti i dati che vengono giù dall'autenticazione
            external_user.auth_data = @request.env["omniauth.auth"]

            external_user.save_to_session(@request.session)

            #pulisco la sessione usata per fare il login
            @request.session["twitter_back_controller"] = nil
            @request.session["id_controller"] = nil
        	redirect const_get_full(controller_chiamante_class).http_s_url
        end


    end
    
end

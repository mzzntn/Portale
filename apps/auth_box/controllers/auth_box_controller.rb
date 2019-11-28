# -*- encoding : utf-8 -*-
require 'omniauth'
require 'omniauth-twitter'


module AuthBox
    
    class AuthBoxController < Spider::PageController
        
        #route per omniauth
        
        route 'twitter', TwitterController
        #route per auth esterna, es da app php
    	route 'simple_external', SimpleExternalController
        #route per app interna in ruby
        route 'civilia_open', CiviliaOpenController
        route 'co_websi', COWebsiController
        
        route 'failure', :failure

        layout 'auth_box', :assets => 'auth_box' 
    
        #DA TESTARE
        #__.html :template => "simple_external/login"
    	__.html :template => "login"
        def login
    		errori = @request.params['er']
            #il codice applicazione mi serve per individuare l'app nel config.yml
            cod_applicazione = @request.params['cod']
    		#ricavo il controller a cui mandare il post del form di login
            hash_dati_autenticazione = Spider.conf.get('auth_box.'+cod_applicazione)
            controller_auth = AuthBox.const_get(hash_dati_autenticazione['provider']+"Controller")
            #ricavo la route del controller per sapere dove mandare i dati del post del login
            route_controller = controller_auth.c_route
            unless errori.blank?
    			case errori
	    			when "parametri" #parametri mancanti in configurazione
	    				@scene.errore = "Parametri mancanti"
                    when "vuoti" #campi username o password vuoti 
                        @scene.errore = "Campi obbligatori! Inserire Username e Password."
                    when "no_auth" #username o password errati
                        @scene.errore = "Username o Password errati!" 
                    when "no_credential" #non ho ottenuto le credenziali per accedere all'applicativo chiamante
                        @scene.errore = "Accesso negato"  
                    when "err_db"  #problemi di connessione al db: timeout, etc
                        @scene.errore = "Mancata connessione al database"
	    			end    			 
    		end
            @scene.cod_applicazione = cod_applicazione unless cod_applicazione.blank?
    		@scene.route_controller = route_controller
    	end

        __.action
        def failure
            #ritorno dall'autenticazione fatta con omniauth-twitter con response
            #{"origin"=>"http://127.0.0.1:8080/admin/comunicazioni/", "message"=>"invalid_credentials", "strategy"=>"twitter"} 
            if @request.params["message"] == "invalid_credentials"
                @request.session["autenticazione_rifiutata"] = @request.params["strategy"]
                redirect @request.params["origin"]
            end
        end
      


    end
    
end

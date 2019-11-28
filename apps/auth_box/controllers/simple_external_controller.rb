# -*- encoding : utf-8 -*-
require 'apps/auth_box/models/simple_external_user'

module AuthBox

	class SimpleExternalController < Spider::PageController

		route /login/, :login

    layout 'auth_box'



    __.action
    def login(id_controller_chiamante)
      qssha1_inviato = @request.params['hqs']
      stringa_datetime_inviata = @request.params['dt']
      if !id_controller_chiamante.blank? && !qssha1_inviato.blank? && !stringa_datetime_inviata.blank?

        #pezzo da togliere, è per prove
        # external_user = SimpleExternalUser.new
        # external_user.data_login = stringa_datetime_inviata
        # external_user.id_controller = id_controller_chiamante
        # external_user.save_to_session(@request.session)
        # redirect "http://#{@request.http_host}#{@request.params['redirect']}"
        #pezzo da togliere, è per prove

        hash_dati_autenticazione = Spider.conf.get('auth_box.'+id_controller_chiamante)
        datetime_inviata = DateTime.strptime(stringa_datetime_inviata+'+02:00', '%Y%m%d%H%M%S%z')
        #controllo che la datetime sia di +-10 minuti
        data_valida = (datetime_inviata < (DateTime.now+(1.0/(24*6))) && datetime_inviata > (DateTime.now-(1.0/(24*6))))
        unless data_valida
          Spider.logger.debug("Autenticazione SimpleExternal, redirect per datetime non valida")
          redirect hash_dati_autenticazione['login_url']
        end
        qs = stringa_datetime_inviata+"3ur0s3rv1z1"
        qssha1 = OpenSSL::Digest::SHA1.new(qs)
        if qssha1 != qssha1_inviato 
            #non valida l'autenticazione
            Spider.logger.debug("Autenticazione SimpleExternal, redirect per sha1 calcolato diverso da quello mandato")
            redirect hash_dati_autenticazione['login_url']
        else    
            #creo in sessione l'user SimpleExternalUser
            external_user = SimpleExternalUser.new
            external_user.data_login = stringa_datetime_inviata
            external_user.id_controller = id_controller_chiamante
            #salvo come chiave una stringa vuota per errore in http_controller in log_done
            external_user.primary_keys = ""
            external_user.save_to_session(@request.session)
            #tengo traccia in db degli utenti loggati con questo metodo?
            #external_user.save
            redirect "http://#{@request.http_host}#{@request.params['redirect']}"
            done
        end    
      else
          #redirect all'url inserito in configurazione per rifare il login esterno
          Spider.logger.debug("Autenticazione SimpleExternal, redirect per parametri mancanti")
          #redirect hash_dati_autenticazione['login_url']
          redirect AuthBoxController.http_s_url('login')+"?er=parametri"
      end
      
    end


	end



end


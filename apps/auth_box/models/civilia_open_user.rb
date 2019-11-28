# -*- encoding : utf-8 -*-
module AuthBox

	class CiviliaOpenUser 
        include Spider::Auth::Authenticable

		attr_accessor :id_controller, :data_login, :primary_keys


		#restituisce un hash con i vari attributi della classe
		def to_session_hash
			h = {}
			h[:id_controller] = @id_controller
			h[:data_login] =  @data_login
            h[:primary_keys] = @primary_keys
			return h
        end

        #recupera dalla sessione i dati
        def self.restore_session_hash(saved)
        	#controllo se i dati in sessione li ho salvati oggi
        	datetime_inviata = DateTime.strptime(saved[:data_login], '%Y%m%d').strftime('%Y%m%d')
        	datetime_today = DateTime.now.strftime('%Y%m%d')
        	if datetime_today == datetime_inviata
        		#mi sono loggato oggi
        		user_autenticato = self.new
        		user_autenticato.id_controller = saved[:id_controller]
        		user_autenticato.data_login = saved[:data_login]
                user_autenticato.primary_keys = saved[:primary_keys]
                user_autenticato
            else
                #autenticazione vecchia, richiedo di fare di nuovo l'autenticazione
                return nil
        	end
        end


        def self.autentica(params)
            utente_civilia = CiviliaOpen::Utente.authenticate_login(params)
            return nil unless utente_civilia
            utente_civilia
        end



	end



end

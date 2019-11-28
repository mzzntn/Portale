# -*- encoding : utf-8 -*-
module AuthBox

	class TwitterUser 
		include Spider::Auth::Authenticable
		attr_accessor :id_controller, :data_login, :auth_data

		#restituisce un hash con i vari attributi della classe
		def to_session_hash
			h = {}
			h[:id_controller] = @id_controller
			h[:data_login] =  @data_login
            h[:auth_data] = @auth_data
			return h
        end

        #recupera dalla sessione i dati, saved = @request.session
        def self.restore_session_hash(saved)
            user_autenticato = self.new
            user_autenticato.id_controller = saved[:id_controller]
            user_autenticato.data_login = saved[:data_login]
            user_autenticato.auth_data = saved[:auth_data]
            user_autenticato
        end


        def get_nickname
            self.auth_data[:info][:nickname]
        end


        def get_access_token
            self.auth_data[:credentials][:token]
        end

        def get_access_token_secret
            self.auth_data[:credentials][:secret]
        end


	end



end

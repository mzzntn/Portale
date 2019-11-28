require 'digest/sha1'
require 'grape'
#ruby 2.1.2
#gem install grape -v 0.19.0
#gem install grape-swagger -v 0.25.0
#gem install grape-swagger-entity -v 0.1.5
#gem install grape-entity -v 0.6.0
#gem install grape-swagger-representable -v 0.1.6


#ruby 2.4
#usare gem install grape-swagger -v 0.27.3
#gem install grape-entity -v 0.6.0
#gem install grape-swagger-entity -v 0.1.5
#gem install grape-swagger-representable

require 'grape-swagger'
require 'grape-swagger/entity'
require 'grape-swagger/representable'



module Portal

	class UtenteApi < Utente 
		def authorize!(env)
			
			a=3
		end
	end
    
    class NewApi < Grape::API

    	logger Spider.logger.new
    	#self.logger.level = Logger::INFO

    	#version 'v1', using: :path #passi la versione nel path
    	format :json
    	prefix :new_api #prefisso iniziale

    	before do
    		header['Access-Control-Allow-Origin'] = '*'
    		header['Access-Control-Request-Method'] = '*'
  		end

		helpers do
			def logger
		      	API.logger
		    end

	      	def current_user
	        	@current_user ||= User.authorize!(@request.env)
	      	end

	      	def authenticate!
	        	error!('401 Unauthorized', 401) unless current_user
	      	end
    	end

		resource :utenti do
			#usare con logger.info "#{current_user} has statused"

			#prima della risorsa mettere v1 -> http://xxxxxxx/v1/utenti/ricerca_utente
			version 'v1', :using => :path do
	      		desc 'Ricerca un utente usando i parametri: nome, cognome, data di nascita'
	      		params do

        			optional :nome, type: String, desc: "Nome dell'utente da ricercare."
        			optional :cognome, type: String, desc: "Cognome dell'utente da ricercare."
        			optional :data_nascita, type: String, desc: "Data di nascita dell'utente da ricercare. (Formato gg/mm/aaaa)"
      			end

			    get :ricerca_utente do
			    	APIController._ricerca_utente(params)
			    end
	    	
	    	end

	    	version 'v2', :using => :path do
	      	
	    	
	    	end


	    end

	    add_swagger_documentation

    end
end

# https://roialty.com/the-grape-swagger-stack/

# http://artsy.github.io/blog/2013/06/21/adding-api-documentation-with-grape-swagger/

# https://github.com/adammartak/rack-swagger-ui

# http://localhost:9292/new_api/swagger_doc   mancano gli stili
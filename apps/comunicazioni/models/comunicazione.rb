# -*- encoding : utf-8 -*-
require 'fileutils'
require 'active_record' unless Spider.conf.get('comunicazioni.db_push_connection').blank?

module Comunicazioni

	class Comunicazione < Spider::Model::Managed

		element :titolo, String
		element :testo_breve, Text
		element :immagine, String
		element :dir_immagine, String
	    element :testo, Text
	    element :lingua, String
	    element :data_da, Date
	    element :data_a, Date
	    element :data_invio, Date
	    #canali possibili tipo: portale, sms, email
	    element :canali_pubblicazione, String
	    many :utenti, Portal::Utente, :add_multiple_reverse => :comunicazioni do 
			element :letta, Spider::Bool, :default => false
	    end
	    many :gruppi, Portal::Gruppo
	    #lo stato può essere: 
	    # -salvata: salvata nel db ma non visibile agli utenti
	    # -pubblicata: pubblicata con almeno uno dei canali di comunicazione
	    element :stato, Spider::OrderedHash[
	            'salvata', 'Salvata',
	            'pubblicata', 'Pubblicata'
	        ], :default => 'salvata'
	    element :pubblica, Spider::Bool    
	    element :mostra_in_portal, Spider::Bool
	    element :invia_da_newsletter, Spider::Bool
	    #salvo dei parametri in json per: newslist del cms, 
	    element :extra_params, String

	    def pubblica?
	    	self.pubblica == true
	    end

	    def visualizzato_in_portale?
	    	self.mostra_in_portal == true
	    end
	    
	    #quando cancello la news cancello anche la cartella con l'immagine se presente
	    def before_delete
	    	if !self.dir_immagine.blank? && !self.immagine.blank?
	    		save_dir = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+self.dir_immagine+"/"
	    		FileUtils.rm_rf(save_dir)
	    	end
	    end

	    #faccio le migrate con active_record delle tabelle di rpush
		def self.after_sync
			canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
        	if canali_pubblicazione.include?('push_notification') || Spider.conf.get('comunicazioni.db_push_connection')
				begin
					#controllo se sono definite le classi e se non ci sono le tabelle in db
					if defined?(Rpush::Apns::App) && ActiveRecord::Base.connection.tables.grep(/^rpush/).blank?
						#eseguo la migrate
		        		AddRpush.migrate(:up)
		        		Rpush200Updates.migrate(:up)
		        		Rpush210Updates.migrate(:up)
					end               
	            rescue Exception => e
	    			Spider.logger.error "Errore in migrate per notifiche push: #{e.message} \n\n #{e.backtrace}"        
				end 
    		end
    	end	


	end
end

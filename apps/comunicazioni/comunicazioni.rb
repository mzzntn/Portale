# -*- encoding : utf-8 -*-
module Comunicazioni

    def self.verifica_presenza_configurazioni
        canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
        if canali_pubblicazione.include?('push_notification') || Spider.conf.get('comunicazioni.db_push_connection')
            hash_dati_autenticazione = Spider.conf.get("comunicazioni.db_push_connection")
            raise "Inserire i parametri di connessione al db per le connessioni push." if hash_dati_autenticazione.blank?
            #parametri singoli
            raise "Inserire il parametro adapter di connessione al db per le connessioni push." if hash_dati_autenticazione['adapter'].blank?
            raise "Inserire il parametro database di connessione al db per le connessioni push." if hash_dati_autenticazione['database'].blank?
            raise "Inserire il parametro username di connessione al db per le connessioni push." if hash_dati_autenticazione['username'].blank?
            raise "Inserire il parametro password di connessione al db per le connessioni push." if hash_dati_autenticazione['password'].blank?
            raise "Inserire il parametro host di connessione al db per le connessioni push." if hash_dati_autenticazione['host'].blank?
            #auth key google
            raise "Inserire il parametro comunicazioni.app_auth_key per le notifiche push su Android" if Spider.conf.get('comunicazioni.app_auth_key').blank?
        end
    end


	def self.app_startup
        #controllo se settata la notifica push come canale
        
        canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
        if canali_pubblicazione.include?('push_notification') || Spider.conf.get('comunicazioni.db_push_connection')
            require 'rpush'
            require 'active_record'
            #creo in db un record per l'app su sistemi ios se non presente
            #TOGLIERE IL COMMENTO
            # ios_app = Rpush::Apns::App.find_by_name(Spider.conf.get('comunicazioni.app_name_ios'))
            # if ios_app.blank?
            #     app = Rpush::Apns::App.new
            #     app.name = "ios_app_comunicazioni"
            #     app.certificate = File.read(File.join(Comunicazioni.path,"certs/cert.pem"))
            #     app.environment = "sandbox" # APNs environment.
            #     app.password = "sviluppopa"
            #     app.connections = 5
            #     app.save!
            # end
            android_app = Rpush::Gcm::App.find_by_name(Spider.conf.get('comunicazioni.app_name_android'))
            if android_app.blank?
                #creo il record per l'app di tipo android
                app = Rpush::Gcm::App.new
                app.name = Spider.conf.get('comunicazioni.app_name_android')
                app.auth_key = Spider.conf.get('comunicazioni.app_auth_key')
                app.connections = 5
                app.save!
            end
            
        end
        
	end	    
	

	#ritorno tutti i canali di pubblicazione o array vuoto
    def self.canali_comunicazione
       @canali_comunicazione ||= []
    end
   
    
    #ritorno il canale di pubblicazione passato dato l'id
    def self.canale_comunicazione(id)
        self.canali_comunicazione.each do |canale|
            return canale if canale.dettagli_canale_comunicazione[:id] == id
        end
    end

    #aggiungo un processor, passo un'istanza della classe sistema_pagamento
    def self.add_canale_comunicazione(klass) 
       self.canali_comunicazione << klass
       #route dinamica, klass è un sistema_pagamento, ricavo i dettagli e prendo l'id come route
       # e poi la classe del sistema_pagamento
       ComunicazioniController.route klass.dettagli_canale_comunicazione[:id], klass
       Spider.logger.info("canali di comunicazione configurati #{self.canali_comunicazione.inspect}")
    end


    #ritorno tutti i gestori segnalazioni o array vuoto
    def self.gestori_segnalazioni
       @gestori_segnalazioni ||= []
    end
   
    #ritorno il gestore di segnalazioni passato dato l'id
    def self.gestore_segnalazioni(id)
        self.gestori_segnalazioni.each do |gestore|
            return gestore if gestore.dettagli_gestore_segnalazioni[:id] == id
        end
    end

    #aggiungo un processor, passo un'istanza della classe sistema_pagamento
    def self.add_gestore_segnalazioni(klass) 
       self.gestori_segnalazioni << klass
       #route dinamica, klass è un gestore_segnalazione, ricavo i dettagli e prendo l'id come route
       #ComunicazioniController.route klass.dettagli_canale_comunicazione[:id], klass
       #Spider.logger.info("canali di comunicazione configurati #{self.canali_comunicazione.inspect}")
    end












end

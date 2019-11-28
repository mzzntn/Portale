# -*- encoding : utf-8 -*-
require 'twitter'
require 'cgi'

module Comunicazioni; module CanaleComunicazione

    class Twitter < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare le mail
        include Spider::Messenger::MessengerHelper rescue NameError
        #includo l'helper per l'autenticazione con auth_box
        include Spider::Auth::AuthHelper

        if defined?(AuthBox) && Spider.conf.get('auth_box.twitter_admin') 
            AuthBox.verifica_presenza_configurazioni('twitter_admin')
            require_user AuthBox.auth_user_class(:id => "twitter_admin"), :redirect => AuthBox.redirect_auth_url(:id => "twitter_admin"), :unless => [:callback]
        end

        
        #route nil, Comunicazioni.GestioneComunicazioniController.http_s_url

        #chiamo il metodo che è stato aggiunto con CanaleComunicazione e passo i dettagli
        canale_comunicazione( 
            :id => "twitter",
            :nome => "Twitter",
            :immagine => "twitter.png"
        )
        
        def self.canale_attivo(request_da_chiamante)
            request = request_da_chiamante
            user_in_session = ( request.session[:auth]['AuthBox::TwitterUser'].blank? ? nil : request.session[:auth]['AuthBox::TwitterUser'])
            return false if user_in_session.blank?
            true            
        end
      
        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            twitter_user = AuthBox::TwitterUser.restore_session_hash(session_user)
            client = ::Twitter::REST::Client.new do |config|
                config.consumer_key         = Spider.config.get('comunicazioni.api_key_twitter')
                config.consumer_secret      = Spider.config.get('comunicazioni.api_secret_twitter')
                config.access_token        =  twitter_user.get_access_token
                config.access_token_secret = twitter_user.get_access_token_secret
            end
            #cancello tutto il codice html e i ritorni a capo e converto gli apostrofi
            #messaggio = CGI.unescapeHTML(comunicazione.testo.gsub(/<\/?[^>]*>/, "").strip.slice(0,137)+"...")
            link = Comunicazioni::ComunicazioniController.http_s_url("#{comunicazione.id.to_s}/pubblica")
            short_link = open('http://tinyurl.com/api-create.php?url=' + link, "UserAgent" => "Ruby Script").read
            text_length = 140-short_link.length
            #-5 perchè arriva a n-1, -3 chars per puntini e -1 per spazio prima del link
            messaggio = comunicazione.testo_breve.strip
            messaggio = (messaggio.respond_to?(:force_encoding) ? messaggio.force_encoding('UTF-8') : messaggio )
            #la gemma pubblica messaggi fino a 116 caratteri, aggiungo una lunghezza da togliere per questo problema
            n_car_bug_gemma_twitter = 35
            messaggio = messaggio.slice(0,text_length-(10+n_car_bug_gemma_twitter))+"..."+short_link
            
            # #VECCHIO METODO, IMMAGINE ESTRATTA DALL EDITOR HTML
            # uuid_image = comunicazione.testo.gsub("\"","'")[/.<img.+src='\/spider\/images\/(.+)('\s.+)'/,1]
            # img = Spider::Images::Image.load(:uuid => uuid_image) unless uuid_image.blank?
            # unless img.blank?   
            #     client.update_with_media(messaggio, img.file_open)
            # else
            #     client.update(messaggio)
            # end
            unless comunicazione.immagine.blank?
                path_image_dir = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+comunicazione.dir_immagine+"/"
                path_immagine = File.join(path_image_dir, 'img_resized', comunicazione.immagine)
                client.update_with_media(messaggio, File.open(path_immagine, "r"))
            else
                client.update(messaggio)
            end


            #pubblico la comunicazione anche sul portale
            comunicazione.mostra_in_portal = true
            comunicazione.canali_pubblicazione = comunicazione.canali_pubblicazione+"portale,"
            comunicazione.save
            comunicazione
        end


    end
end;end

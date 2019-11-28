# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Newsletter < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare le mail
        include Spider::Messenger::MessengerHelper rescue NameError

        canale_comunicazione( 
            :id => "newsletter",
            :nome => "Newsletter",
            :immagine => "newsletter.png"
        )

        def self.canale_attivo(request_da_chiamante)
           true
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            #rendo disponibile la comunicazione nella newsletter
            comunicazione.invia_da_newsletter = true
            comunicazione
        end


    end
end;end

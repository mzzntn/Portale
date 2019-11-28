# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Portale < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare le mail
        include Spider::Messenger::MessengerHelper rescue NameError
        #chiamo il metodo che è stato aggiunto con SistemaPagamento e passo i dettagli
        canale_comunicazione( 
            :id => "portale",
            :nome => "Portale",
            :immagine => "portale.png"
        )
    
        def self.canale_attivo(request_da_chiamante)
            true
        end
    
        def self.pubblica_comunicazione(comunicazione, session_user=nil)    
            comunicazione.mostra_in_portal = true
            #se attive le segnalazioni push e non sono messe come canale di comunicazione
            canali_pubblicazione = (Spider.conf.get('comunicazioni.canali_comunicazione') || [])
            if !canali_pubblicazione.include?('push_notification') && Spider.conf.get('comunicazioni.db_push_connection')
                Comunicazioni::CanaleComunicazione::PushNotification.pubblica_comunicazione(comunicazione, session_user) if Comunicazioni::CanaleComunicazione::PushNotification.canale_attivo
            end
            comunicazione
        end


    end
end;end

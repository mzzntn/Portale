# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Facebook < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
        #includo il messenger helper per mandare le mail
        include Spider::Messenger::MessengerHelper rescue NameError
        #chiamo il metodo che è stato aggiunto con SistemaPagamento e passo i dettagli
        canale_comunicazione( 
            :id => "facebook",
            :nome => "Facebook",
            :immagine => "facebook.png"
        )

        def self.canale_attivo(request_da_chiamante)
           true
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            #pubblico anche sul portale per non aver problemi col link
            comunicazione.mostra_in_portal = true
            comunicazione
        end


    end
end;end

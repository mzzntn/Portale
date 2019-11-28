# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Prisma < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
    
        
        canale_comunicazione( 
            :id => "prisma",
            :nome => "Prisma",
            :immagine => "prisma.png"
        )

        def self.canale_attivo(request_da_chiamante)
            !defined?(Prisma).blank?
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
            comunicazione
        end


    end
end;end;

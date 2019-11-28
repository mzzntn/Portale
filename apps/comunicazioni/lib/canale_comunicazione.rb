# -*- encoding : utf-8 -*-
module Comunicazioni

    module CanaleComunicazione
        #quando viene incluso il modulo estendo la classe che include il modulo con il modulo sotto
        def self.included(klass)
            klass.extend(ClassMethods)
        end

        module ClassMethods

            # Ritorna i dettagli del canale di comunicazione.
            def dettagli_canale_comunicazione
                @canale_comunicazione_details
            end
            
            def canale_comunicazione(details)
                @canale_comunicazione_details = details
                #faccio l'add canale dell'istanza della classe sistema_pagamento
                Comunicazioni.add_canale_comunicazione(self)
            end
            
        end

    end

end

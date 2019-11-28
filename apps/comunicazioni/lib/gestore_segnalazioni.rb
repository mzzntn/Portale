# -*- encoding : utf-8 -*-
module Comunicazioni

    module GestoreSegnalazioni
        #quando viene incluso il modulo estendo la classe che include il modulo con il modulo sotto
        def self.included(klass)
            klass.extend(ClassMethods)
        end

        module ClassMethods

            # Ritorna i dettagli del gestore_segnalazioni.
            def dettagli_gestore_segnalazioni
                @gestore_segnalazioni_details
            end
            
            def gestore_segnalazioni(details)
                @gestore_segnalazioni_details = details
                #faccio l'add gestore dell'istanza della classe 
                Comunicazioni.add_gestore_segnalazioni(self)
            end
            
        end

    end

end

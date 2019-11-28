# -*- encoding : utf-8 -*-
module Comunicazioni

	class Segnalazione < Spider::Model::Managed

		element :nome, String
		element :cognome, String
		element :email, String
		element :indirizzo, String
		element :testo_segnalazione, Text
		element :foto, String
		element :latitudine, String
		element :longitudine, String
		#ci possono essere varie tipologie, sono gestite dal gestore specifico
	    element :tipologia_richiesta, String
        element :data_chiusura, Date
	    element :note_operatore, Text
	    element :token_device, String
	    element :stato, Spider::OrderedHash[
            'inserita', 'Inserita in Backoffice',
            'in_lavorazione', 'In Lavorazione',
            'chiusa', 'Chiusa',
            
        ], :default => 'inserita'

        element :gestore_segnalazione, String
        #campo che contiene un json con i vari campi specifici per 
        #le segnalazioni del gestore indicato
        element :extra_params, Text
        #zona e tipologia rifiuto per gestore ambiente_servizi



	end
end

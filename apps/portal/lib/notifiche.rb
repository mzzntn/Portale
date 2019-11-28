# -*- encoding : utf-8 -*-
module Portal; module Notifiche
	def self.invia_comunicazione(titolo, testo, data_da, data_a, canale_pubblicazione, utente, stato, pubblica, mostra_in_portal)
		if Spider.apps.include?('Comunicazioni')
            comunicazione = ::Comunicazioni::Comunicazione.new
            comunicazione.titolo = titolo
           	comunicazione.testo = testo
            comunicazione.data_da = data_da
            comunicazione.data_a = data_a
            comunicazione.canali_pubblicazione = canale_pubblicazione
            comunicazione.utenti = utente
            comunicazione.stato = stato
            comunicazione.pubblica = pubblica
            comunicazione.mostra_in_portal = mostra_in_portal
            comunicazione.save
       	end
	end
end; end

# -*- encoding : utf-8 -*-
require "erb"

module Portal; module Notifiche
	class Demografici < Spider::PageController
		begin
			include Spider::Messenger::MessengerHelper 
		rescue NameError
		end

		def self.trova_carta_valida(notifica)
			master = notifica.utente.attributo('codice_master')
			codice_fiscale ||= notifica.utente.codice_fiscale
			residente = CiviliaOpen::Residente.load{ |r| (r.persona == master) | (r.codice_fiscale == codice_fiscale) }
			carta = CiviliaOpen::CartaIdentita.where{ |ci| (ci.annullata .not true) & ((ci.persona == residente) & (ci.scadenza == Date.today+180)) }.order_by(:progressivo, :desc) if residente
			return carta
		end

		def self.esegui_invio(notifica)
			carta = trova_carta_valida(notifica)
			if carta && carta.length == 1
				scene = Spider::Scene.new
				scene << {
					:utente_portale => notifica.utente,
					:carta_identita => carta.first
				}
	            if notifica.notifica_email
	                headers = {'Subject' => "#{Spider.conf.get('ente.nome')} - Carta d'Identità in scadenza"}    
	                Spider::Messenger::MessengerHelper.send_email(self.app, 'notifiche/carte_identita/scadenza', scene, Spider.conf.get('portal.email_from'), notifica.utente.email, headers)
	            end
	            if notifica.notifica_sms
					scene_binding = scene.instance_eval{ binding }
					path = self.find_resource_path(:sms, 'notifiche/carte_identita/scadenza')
                	text = ERB.new( IO.read(path) ).result(scene_binding)
	                Spider::Messenger::MessengerHelper.send_sms(notifica.utente.cellulare, text)
	            end
	            scene_binding = scene.instance_eval{ binding }
	            path = self.find_resource_path(:email, 'notifiche/carte_identita/scadenza')
	            text = ERB.new( IO.read(path) ).result(scene_binding)
	            ::Portal::Notifiche.invia_comunicazione("Carta d'Identità in scadenza", text, Date.today, carta.first.scadenza, "portal", notifica.utente, "pubblicata", false, true)   
        	end
		end
	end
end; end

# -*- encoding : utf-8 -*-
require "erb"

module Portal; module Notifiche
	class Pagamenti < Spider::PageController
		begin
			include Spider::Messenger::MessengerHelper 
		rescue NameError
		end

		def self.esegui_invio(applicazione)
			#estraggo i flussi di pagamento (FlussoPagamenti) che hanno il flag invia_notifiche a true
			#mi estraggo i pagamenti e li invio a Messenger per inoltrare la mail al cittadino di presenza di un nuovo pagamento a suo carico
			fl_pagamenti = ::Pagamenti::FlussoPagamenti.where{|fl| (fl.invia_notifiche == 1) & (fl.stato == 'confermato')}
			fl_pagamenti.each do |flusso|
				flusso.invia_notifiche = 3 #Imposto a 3 ovvero 'in corso' così non riprocesso il flusso che può essere ancora in corso da precedente scheduling
				flusso.save
				flusso.pagamenti.each do |row|
					notifica = nil
					utente = nil
					email = nil
					utenti = Portal::Utente.where{ |n| (n.codice_fiscale == row.dovuto.codice_fiscale) & (n.stato .not 'disabilitato') & (n.notifiche.applicazione == applicazione) }
					utenti.each do |u|
						utente = u
                        utente.notifiche.each do |n|
                            notifica = n if ((n.notifica_email) || (n.notifica_sms)) && n.applicazione.codice == applicazione
                        end
                    end
                    scene = Spider::Scene.new
					scene.nome = row.dovuto.nome
					scene.cognome = row.dovuto.cognome
					scene.cf_versante = row.dovuto.codice_fiscale
					scene.iuv = row.iuv
					scene.scadenza = nil
					scene.scadenza = row.dovuto.data_scadenza.lformat(:short) if row.dovuto.data_scadenza
					scene.short_url = ::Pagamenti::PagoPa.avviso_short_link(row.id)
                    #La notifica la invio:
                    #1. sia se la notifica via email è attiva x l'utente in questione
                    #2. sia se l'utente non esiste a portale ma è stata impostata la mail nel flusso csv
                    if (notifica && notifica.notifica_email) || (!row.dovuto.email.blank?)
						if !utente.blank? && notifica.notifica_email
							email = utente.email 
							row.dovuto.email = email
							row.dovuto.save
						end
						email ||= row.dovuto.email unless row.dovuto.email.blank?
						#sviluppo futuro? Mail non accetta stream di dati, eventualmente salvare il contenuto e poi
						#usare la modalità qui sotto per attaccharlo
						#rpt_name, rpt_file = ::Pagamenti::SistemiPagamento::PagoPa.genera_avviso_jasper(row.iuv)
						#attachments = {:filename => rpt_name, :content => rpt_file,:mime_type => 'application/pdf'}
						if email
							headers = {'Subject' => "#{Spider.conf.get('ente.nome')} - Avviso di pagamento"}    
			                Spider::Messenger::MessengerHelper.send_email(self.app, 'notifiche/pagamenti/pagopa_modello3', scene, Spider.conf.get('portal.email_from'), email, headers) #, attachments)
			            end
		            end
		            if !Spider.conf.get('messenger.sms.backend').blank? && notifica && notifica.notifica_sms && notifica.utente.cellulare
		            	row.dovuto.sms = notifica.utente.cellulare
		            	row.dovuto.save
						scene_binding = scene.instance_eval{ binding }
						path = self.find_resource_path(:sms, 'notifiche/pagamenti/nuovo_pagamento')
		            	text = ERB.new( IO.read(path) ).result(scene_binding)
		                Spider::Messenger::MessengerHelper.send_sms(notifica.utente.cellulare, text)
			        end
				end
				flusso.invia_notifiche = 2 #Imposto a 2 ovvero 'fatto' così non reinvio più le notifiche dei pagamenti associati al flusso in questione
				flusso.save
			end
		end
	end
end;end


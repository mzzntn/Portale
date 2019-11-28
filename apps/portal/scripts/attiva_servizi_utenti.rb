# -*- encoding : utf-8 -*-
module Portal

	class PortalScript

		def self.aggiungi_servizio(servizio,stati=nil,gruppi=nil,permessi=nil)
			begin
				servizio_da_attivare = Portal::Servizio.find(:id => servizio)
				unless servizio_da_attivare.blank?
					#cerco tutti gli utenti con gli stati voluti e che non sono disabilitati
					utenti_registrati = Portal::Utente.all

					c1_or = Spider::Model::Condition.new
		            c2_or = Spider::Model::Condition.new
		            #condizione sullo stato utente
		            unless stati.blank?
		                #salvo nella scene gli stati utente per mostrarli dopo aver fatto una ricerca
		                stati.each{ |stato|
		                    c1_or = c1_or.or{ |u| u.stato == stato } 
		                }
		            end
		            #condizione sul gruppo
		            unless gruppi.blank?
		                gruppi_utente = Portal::Gruppo.all
		                #salvo nella scene gli stati utente per mostrarli dopo aver fatto una ricerca
		                gruppi.each{ |gruppo|
		                    c2_or = c2_or.or{ |u| u.gruppi.nome == gruppo } 
		                }    
		            end
		            #faccio l'and delle due condizioni che hanno l'or
		            utenti_registrati.query.condition = Spider::Model::Condition.and(c1_or, c2_or)

					utenti_registrati.query.condition = utenti_registrati.query.condition.and{|utente| (utente.stato .not 'disabilitato')}
					#ciclo sugli utenti, se hanno il servizio associato lo attivo, altrimenti glielo associo
					utenti_registrati.each{ |utente_registrato|
						trovato = false
						#[{:servizio=>{:id=>"posizione_ici"}}, {:servizio=>{:id=>"pe"}}]
						utente_registrato.servizi_privati.each{ |srv_privato|
							trovato = true if srv_privato[:servizio][:id] == servizio
							#setto il servizio come attivo se l'ho trovato tra quelli presenti
							srv_privato.stato = 'attivo'
							break if trovato 
						}
						unless trovato
							#inserisco il servizio come attivo
							utente_registrato.servizi_privati << {
		                            :servizio => servizio_da_attivare[0],
		                            :stato => { :id => 'attivo', :desc => 'Attivo'}
		                        }
						end
						#se ho passato un permesso lo aggiungo
						unless permessi.blank?
							#cancello i permessi presenti
							unless utente_registrato.utente_portale_permissions.blank?
								utente_registrato.utente_portale_permissions = permessi
							else
								utente_registrato.utente_portale_permissions << permessi 
							end
							
						end

						utente_registrato.save
					}		
				else
					puts "Servizio indicato non presente"
					return "servizo_non_presente"
				end
				return "ok"
			rescue Exception => exc
				Spider.logger.error "Errore Associazione Servizio Utenti: #{exc.message}"
				return "error"
			end
			

			
		end
	end
end

# -*- encoding : utf-8 -*-
require "erb"

module Portal; module Notifiche
	class Muse < Spider::PageController
		begin
			include Spider::Messenger::MessengerHelper 
		rescue NameError
		end

        def self.carica_componenti_nucleo(utente)
            componenti = []
            if Spider.app?('demografici')
                residente = CiviliaOpen::Residente.load{|p| (p.codice_fiscale == 'utente.codice_fiscale') & ((p.persona.flag_residente == true) | (p.persona.flag_aire == true))}
                if residente && residente.codice_famiglia
                    nucleo = CiviliaOpen::Residente.where{ (codice_famiglia == residente.codice_famiglia) }
                    componenti = ::Demografici::Funzioni.componenti_famiglia_validi(nucleo, 'residente')
                    componenti.reject!{ |c| c.master == residente.master }
                end
            end
            unless utente.figli_muse.empty?
                utente.figli_muse.each do |figlio|
                    componenti << figlio 
                end
            end
            return componenti
        end

		def self.esegui_invio(notifica)
			limite1 = Spider.config.get('notifiche.muse.limite_credito_1') #20 euro
            limite2 = Spider.config.get('notifiche.muse.limite_credito_2') #10 euro
            anno_scolastico = Spider.config.get('muse.anno_scolastico')
            #definisco gli elementi di visualizzazione del queryset
            request = Spider::Model::Request.strict([:id, :anagrafica, :id_badge, Spider::QueryFuncs::Sum(:importo).as(:somma)])

            #estraggo solo gli iscritti dell'anno in corso e che usufruiscono del servizio Mensa
            iscr = MuSe::Iscrizione::IscrizionePresenza.count
            if iscr > 0
                cond = Spider::Model::Condition.new
                cond.set('anagrafica.iscrizioni_alunno.anno', '=', anno_scolastico)
                cond.set('anagrafica.iscrizioni_alunno.prestazione','=','Mensa')
                c0 = Spider::Model::Condition.new
                c0.set('anagrafica.iscrizioni_alunno.data_inizio','<=', Date.today)
                c0.set('anagrafica.iscrizioni_alunno.data_fine','=', nil)
                c0.and{ |r| ((r.anagrafica.iscrizioni_alunno.data_sospensione .not nil) & (r.anagrafica.iscrizioni_alunno.data_riattivazione <= Date.today)) | (r.anagrafica.iscrizioni_alunno.data_sospensione == nil) }
            end
            #definisco le condition in or per il titolare ed eventuali nominativi connessi con il titolare
            c = Spider::Model::Condition.new
            c.conjunction = :or	
            c.set('anagrafica.codice_fiscale','=',notifica.utente.codice_fiscale)
            componenti = carica_componenti_nucleo(notifica.utente)
            componenti.each do |figlio|
                c.set('anagrafica.codice_fiscale', '=', figlio.codice_fiscale)
            end if componenti
            #definisco la sottocondizione per effettuare il raggruppamento per id_badge e il range di credito ancora disponibile
            c1 = Spider::Model::Condition.new
            c1.set(Spider::QueryFuncs::Sum(:importo), '<', limite2)
            c1.partition
            c.conjunction = :and
            c.subconditions << c1
            if iscr > 0
                cond.conjunction = :and
                cond.subconditions << c0
                cond.subconditions << c
                q = Spider::Model::Query.new(cond, request)
            else
                q = Spider::Model::Query.new(c, request)
            end
            q.group_by(:id_badge)

            stati_attivi = []
            #eseguo la query e invio le eventuali notifiche
	        portafoglio = ::MuSe::Portafoglio.where(q)

            portafoglio.each do |pf|
                stato = ::MuSe::Stato.load(:id_badge => pf.id_badge)
                if !stato.blank? && stato.livello_notifica == 2
                    #Con la riga qui sotto specifico se inviare la notifica tutte le volte o una sola per limite
                    if Spider.config.get("notifiche.muse.notifica_onetime_per_limit")
                        #se inferiore o uguale rispetto al precedente stato non invio mail
                        #altrimenti c'è stata una ricarica che comunque potrebbe non aver superato la soglia precedente, 
                        #quindi azzero comunque lo stato affinchè una nuova variazione in negativo possa scatenare 
                        #il reinvio di una comunicazione 
                        stati_attivi << stato.id if BigDecimal.new(pf[:somma]) <= stato.credito
                        #aggiorno l'importo nella tabella stato 
                        stato.credito = pf[:somma]
                        stato.save
                    else
                        #Effettuo invio in ogni caso ovvero anche se è già con livello_notifica a 1 in quanto "notifica_onetime_per_limit" è a false 
                        #quindi si vuole inviare le notifiche sempre al variare dell'importo tra portafoglio e stato
                        stato = invia_notifica(notifica, stato, pf, 2) 
                        stati_attivi << stato.id
                    end
                else
                    #Effettuo invio notifica in quanto non c'è un record in Muse::Stato relativa alla situazione in questione, quindi è nuovo!
                    stato = invia_notifica(notifica, stato, pf, 2)
                    stati_attivi << stato.id
                end
            end

            #estraggo solo gli iscritti dell'anno in corso e che usufruiscono del servizio Mensa
            if iscr > 0
                cond = Spider::Model::Condition.new
                cond.set('anagrafica.iscrizioni_alunno.anno', '=', anno_scolastico)
                cond.set('anagrafica.iscrizioni_alunno.prestazione','=','Mensa')
                c0 = Spider::Model::Condition.new
                c0.set('anagrafica.iscrizioni_alunno.data_inizio','<=', Date.today)
                c0.set('anagrafica.iscrizioni_alunno.data_fine','=', nil)
                c0.and{ |r| ((r.anagrafica.iscrizioni_alunno.data_sospensione .not nil) & (r.anagrafica.iscrizioni_alunno.data_riattivazione <= Date.today)) | (r.anagrafica.iscrizioni_alunno.data_sospensione == nil) }
            end
            #definisco le condition in or per il titolare ed eventuali nominativi connessi con il titolare
            c = Spider::Model::Condition.new
            c.conjunction= :or	
            c.set('anagrafica.codice_fiscale','=',notifica.utente.codice_fiscale)
            componenti.each do |figlio|
                c.set('anagrafica.codice_fiscale', '=', figlio.codice_fiscale)
            end if componenti
            #definisco la sottocondizione per effettuare il raggruppamento per id_badge e il range di credito ancora disponibile
            c1 = Spider::Model::Condition.new
            c1.set(Spider::QueryFuncs::Sum(:importo), '>=', limite2)
            c1.set(Spider::QueryFuncs::Sum(:importo), '<', limite1)
            c1.partition
            c.conjunction= :and
            c.subconditions << c1
            if iscr > 0
                cond.conjunction = :and
                cond.subconditions << c0
                cond.subconditions << c
                q = Spider::Model::Query.new(cond, request)
            else
                q = Spider::Model::Query.new(c, request)
            end
            q.group_by(:id_badge)

            #eseguo la query e invio le eventuali notifiche
            ::MuSe::Portafoglio.where(q).each do |pf|
                stato = ::MuSe::Stato.load(:id_badge => pf.id_badge)
                if !stato.blank? && stato.livello_notifica == 1
                    #Con la riga qui sotto specifico se inviare la notifica tutte le volte o una sola per limite
                    if Spider.config.get("notifiche.muse.notifica_onetime_per_limit")
                        #se inferiore o uguale rispetto al precedente stato non invio mail
                        #altrimenti c'è stata una ricarica che comunque potrebbe non aver superato la soglia precedente, 
                        #quindi azzero comunque lo stato affinchè una seconda variazione in negativo possa scatenare 
                        #il reinvio di una comunicazione
                        stati_attivi << stato.id if BigDecimal.new(pf[:somma]) <= stato.credito
                        #aggiorno l'importo nella tabella stato 
                        stato.credito = pf[:somma]
                        stato.save
                    else
                        #Effettuo invio in ogni caso ovvero anche se è già con livello_notifica a 1 in quanto "notifica_onetime_per_limit" è a false 
                        #quindi si vuole inviare le notifiche sempre al variare dell'importo tra portafoglio e stato
                        stato = invia_notifica(notifica, stato, pf, 1) 
                        stati_attivi << stato.id
                    end
                else
                    #Effettuo invio notifica in quanto non c'è un record in Muse::Stato relativa alla situazione in questione, quindi è nuovo!
                    stato = invia_notifica(notifica, stato, pf, 1)
                    stati_attivi << stato.id
                end
                
            end
            #Ritorno l'elenco degli stati che NON devono essere azzerati
            #affinché con la prossima schedulazione venga eventualmente emesso una nuova notifica se ricade
            #nei limiti
            return stati_attivi
		end

		def self.invia_notifica(notifica, stato, portafoglio, livello)
            scene = Spider::Scene.new
            scene << {
            	:nome => portafoglio.anagrafica.nome,
            	:cognome => portafoglio.anagrafica.cognome,
                :badge => portafoglio.id_badge,
                :credito => BigDecimal.new(portafoglio[:somma].to_s.gsub(',','.'),4)
            }
            if notifica.notifica_email && notifica.utente.email
                subj = scene.credito <= 0 ? "Credito CityCard esaurito" : "Credito CityCard in esaurimento"
            	headers = {'Subject' => "#{Spider.conf.get('ente.nome')} - #{subj}"}    
	            Spider::Messenger::MessengerHelper.send_email(self.app, "notifiche/muse/credito1", scene, Spider.conf.get('portal.email_from'), notifica.utente.email, headers)
	        end
	        if notifica.notifica_sms && notifica.utente.cellulare
				scene_binding = scene.instance_eval{ binding }
				path = self.find_resource_path(:sms, 'notifiche/muse/credito1')
            	text = ERB.new( IO.read(path) ).result(scene_binding)
                Spider::Messenger::MessengerHelper.send_sms(notifica.utente.cellulare, text)
	        end

            stato ||= ::MuSe::Stato.create(
                :anagrafica => portafoglio.anagrafica,
                :id_badge => portafoglio.id_badge
            )
            stato.credito = portafoglio[:somma]
            stato.livello_notifica = livello
            stato.save
            scene_binding = scene.instance_eval{ binding }
            path = self.find_resource_path(:email, 'notifiche/muse/credito1')
            text = ERB.new( IO.read(path) ).result(scene_binding)
            ::Portal::Notifiche.invia_comunicazione("Credito CityCard in esaurimento", text, Date.today, Date.today+10, "portal", notifica.utente, "pubblicata", false, true)   
            return stato
        end

	end
end; end

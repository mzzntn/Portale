# -*- encoding : utf-8 -*-
module Moduli

	class GestoreAttestazioneAgibilita

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        	
	        #carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)
			
			#data ultimazione lavori
			chiave_tag = hash_conf['configurazione']['metadato1']
			hsh_output['data_ult_lavori'] = ( (!hash_pratica['dati_aggiuntivi'].blank? && !hash_pratica['dati_aggiuntivi'][chiave_tag].blank?) ? hash_pratica['dati_aggiuntivi'][chiave_tag] : chiave_tag )
			#carico il tipo di uso dell'attivita
			unless hsh_output["oggetto_territoriale_1"]['destinazione_uso'].blank?
				tipo_ot_uso = hsh_output["oggetto_territoriale_1"]['destinazione_uso'].downcase
				hsh_output['tipo_uso_attivita'] = { tipo_ot_uso => {'check' => '1'}}
			end
			hsh_output
		end		

		#carico i dati nel caso di uso di Moduli senza Pratiche Edilizie
		def self.carica_dati_registrazione(dati_registrazione)
			hsh_output = {}
			hsh_output['dati_utente'] = {}
			hsh_output['dati_utente']['cognome'] = dati_registrazione['cognome']
			hsh_output['dati_utente']['nome'] = dati_registrazione['nome']
			hsh_output['dati_utente']['ragione_sociale'] = dati_registrazione['ditta']['ragione_sociale'] unless dati_registrazione['ditta'].blank?
			hsh_output['dati_utente']['codice_fiscale'] = dati_registrazione['codice_fiscale'] 
			hsh_output['dati_utente']['piva'] = dati_registrazione['ditta']['partita_iva'] unless dati_registrazione['ditta'].blank?
			hsh_output['dati_utente']['citta_residenza'] = dati_registrazione['comune_residenza']
			hsh_output['dati_utente']['prov_residenza'] = dati_registrazione['provincia_residenza'].blank? || dati_registrazione['provincia_residenza_tab']
			hsh_output['dati_utente']['indirizzo_residenza'] = dati_registrazione['indirizzo_residenza'] 
			hsh_output['dati_utente']['civico_residenza'] = dati_registrazione['civico_residenza']
			hsh_output['dati_utente']['cap_residenza'] = dati_registrazione['cap_residenza']
			hsh_output['dati_utente']['email'] = dati_registrazione['email']
			hsh_output['dati_utente']['telefono'] = dati_registrazione['telefono']
			hsh_output
		end

		def self.controlla_dati_modulo(dati_post)
			errori = []
			#controllo errori: DA FARE
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:rel_intervento], 'rel_intervento', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:agib_opere][:scelta_agib_opere], 'scelta_agib_opere', 'Selezionare una opzione.')
			
			errori = errori.compact
			#converto gli errori in json per passarlo al js
			errori.blank? ? nil : errori.to_json
		end

		#ritorna un array con gli url degli allegati
		def self.get_url_allegati(id_pratica)
			res = Moduli::Funzioni.url_allegati(id_pratica)
			res
		end

		def self.set_scene_widget(scene, hash_dati=nil)
			#carico id e data pratica
			unless hash_dati.blank?
				scene.id_pratica = hash_dati['id_pratica']
				scene.data_pratica = hash_dati['data_pratica']
			end
			scene			
		end
		
	end

end

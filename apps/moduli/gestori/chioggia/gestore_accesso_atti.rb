# -*- encoding : utf-8 -*-
module Moduli

	class GestoreAccessoAtti

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        
			#carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)


			# path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	  #       xml_pratica = File.read(path)
	  #       #carico in un hash i dati dall'xml
	  #       hash_pratica = Crack::XML.parse(xml_pratica)['pratica']
	  #       #File.open(File.join(Spider.paths[:tmp], 'moduli_dinamici','test.txt'), 'w+') { |file| file.write(hash_pratica.to_json) }
			# #creo un hash per caricare i dati nel modulo		
			# hsh_output = {}

			# if hash_pratica['referente'].length > 0
			# 	if hash_pratica['referente'].is_a?(Hash)
			# 		richiedente = hash_pratica['referente']
			# 	else
			# 		#il primo dei referenti 
			# 		richiedente = hash_pratica['referente'][0]
			# 	end
				
			# end
			# hsh_output['dati_utente'] = {}

			# hsh_output['dati_utente']['nome_cognome'] = "#{richiedente['nome']} #{richiedente['cognome']}"
			# hsh_output['dati_utente']['codice_fiscale'] = richiedente['codice_fiscale']
			# hsh_output['dati_utente']['luogo_nascita'] = richiedente['nascita_comune']['descrizione']
			# hsh_output['dati_utente']['provincia_nascita'] = richiedente['nascita_comune']['provincia']
			# hsh_output['dati_utente']['data_nascita'] = (DateTime.strptime(richiedente['nascita_data'], '%Y-%m-%d')).strftime("%d/%m/%Y") unless richiedente['nascita_data'].blank?
			# #controllo indirizzo per evitare errori
			# hsh_output['dati_utente']['citta_residenza'] = richiedente['indirizzo']['residenza_comune']['descrizione'] if !richiedente['indirizzo'].blank? && !richiedente['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_utente']['prov_residenza'] = richiedente['indirizzo']['residenza_comune']['provincia'] if !richiedente['indirizzo'].blank? && !richiedente['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_utente']['cap_residenza'] = richiedente['indirizzo']['residenza_comune']['cap'] if !richiedente['indirizzo'].blank? && !richiedente['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_utente']['indirizzo_residenza'] = richiedente['indirizzo']['via'] if !richiedente['indirizzo'].blank?
			# hsh_output['dati_utente']['civico_residenza'] = "#{richiedente['indirizzo']['civico']} #{richiedente['indirizzo']['barrato']} #{richiedente['indirizzo']['interno']} #{richiedente['indirizzo']['scala']} #{richiedente['indirizzo']['piano']}"
			# hsh_output['dati_utente']['email'] = richiedente['email']
			# hsh_output['dati_utente']['telefono'] = richiedente['telefono1']
			# hsh_output['dati_utente']['cellulare'] = richiedente['telefono2']
			# hsh_output['dati_utente']['fax'] = '' #non presente
			
			#la tipologia di referente potrebbe essere un hash o un array di referenze
			hsh_output['tipo_referenza'] = hsh_output['titolare_1']['tipo_referenza']
			
			#hsh_output['allegati'] = {}
			#FATTO CON PRE CHECK DA PRATICHE OPENWEB
			# #leggo l'array degli allegati
			# unless hash_pratica['allegato'].blank?
			# 	if hash_pratica['allegato'].is_a?(Hash)
			# 		hash_pratica['allegato'] = [hash_pratica['allegato']]
			# 	end
			# 	hash_pratica['allegato'].each{ |all|
			# 		if all['tipo'] =~ /documentazione atta/ && all['flag_file'] == 'Si'
			# 			hsh_output['allegati']['documentazione_legittima'] = { 'check' => '1'}
			# 		end
			# 		if all['tipo'] == "delega dell'interessato" && all['flag_file'] == 'Si'
			# 			hsh_output['allegati']['delega'] = { 'check' => '1' }
			# 		end
			# 	}
			# end
			
			hsh_output['documenti'] = hash_pratica['descrizione']
			hsh_output
		end		


		def self.controlla_dati_modulo(dati_post)
			errori = []
			#tipo_richiesta deve essere obbligatorio
			errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:tipo_referenza], 'tipo_referenza')
			errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:dichiarazione_posizione], 'dichiarazione_posizione')
			checked = false
			dati_post[:tipo_richiesta].each_pair{ |k,v|
				unless v['check'].blank?
					checked = true 
				end
			}
			errori << { 'campo' => 'tipo_richiesta', 'msg' => 'Campo obbligatorio' } unless checked		
			
			errori = errori.compact
			#converto gli errori in json per passarlo al js
			errori.blank? ? nil : errori.to_json
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

# -*- encoding : utf-8 -*-
module Moduli

	class GestoreCila

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        
			#carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)

	        #NON CARICATI
			# hsh_output['dati_commit_tit'] = {}
			# hsh_output['dati_commit_tit']['nome_cognome_prog'] = "#{progettista['nome']} #{progettista['cognome']}"
			# hsh_output['dati_commit_tit']['ordine_commit_tit'] = progettista['descrizione_albo']
			# hsh_output['dati_commit_tit']['citta_ordine_commit_tit'] = "" #non presente
			# hsh_output['dati_commit_tit']['n_ordine_commit_tit'] = progettista['numero_iscrizione_albo'][0] unless progettista['numero_iscrizione_albo'].blank?
			
			# hsh_output['dati_commit_tit']['stato_nascita_progettista'] = "" #non presente
			# hsh_output['dati_commit_tit']['data_nascita_progettista'] = (DateTime.strptime(progettista['nascita_data'], '%Y-%m-%d')).strftime("%d/%m/%Y") unless progettista['nascita_data'].blank?
			# hsh_output['dati_commit_tit']['citta_residenza_progettista'] = progettista['indirizzo']['residenza_comune']['descrizione'] unless progettista['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_commit_tit']['prov_residenza_progettista'] = progettista['indirizzo']['residenza_comune']['provincia'] unless progettista['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_commit_tit']['stato_residenza_progettista'] = "" #non presente
			# hsh_output['dati_commit_tit']['indirizzo_residenza_progettista'] = progettista['indirizzo']['via']
			# hsh_output['dati_commit_tit']['civico_residenza_progettista'] = "#{progettista['indirizzo']['civico']} #{progettista['indirizzo']['barrato']} #{progettista['indirizzo']['interno']} #{progettista['indirizzo']['scala']} #{progettista['indirizzo']['piano']}"
			# hsh_output['dati_commit_tit']['cap_residenza_progettista'] = progettista['indirizzo']['residenza_comune']['cap'] unless progettista['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_commit_tit']['citta_studio_progettista'] = progettista['indirizzo']['residenza_comune']['descrizione'] unless progettista['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_commit_tit']['prov_studio_progettista'] = progettista['indirizzo']['residenza_comune']['provincia'] unless progettista['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_commit_tit']['stato_studio_progettista'] = "" #non presente
			# hsh_output['dati_commit_tit']['indirizzo_studio_progettista'] = progettista['indirizzo']['via']
			# hsh_output['dati_commit_tit']['civico_studio_progettista'] = "#{progettista['indirizzo']['civico']} #{progettista['indirizzo']['barrato']} #{progettista['indirizzo']['interno']} #{progettista['indirizzo']['scala']} #{progettista['indirizzo']['piano']}"
			# hsh_output['dati_commit_tit']['cap_studio_progettista'] = progettista['indirizzo']['residenza_comune']['cap'] unless progettista['indirizzo']['residenza_comune'].blank?
			# hsh_output['dati_commit_tit']['ordine_progettista'] = progettista['descrizione_albo']
			# hsh_output['dati_commit_tit']['citta_ordine_progettista'] = progettista['provincia_albo']
			# hsh_output['dati_commit_tit']['n_ordine_progettista'] = progettista['numero_iscrizione_albo'][0] unless progettista['numero_iscrizione_albo'].blank?
			# hsh_output['dati_commit_tit']['tel_progettista'] = progettista['telefono1']
			# hsh_output['dati_commit_tit']['fax_progettista'] = "" #non presente
			# hsh_output['dati_commit_tit']['cel_progettista'] = progettista['telefono2']
			# hsh_output['dati_commit_tit']['pec_email_progettista'] = progettista['email']

			hsh_output['titolarita_intervento'] = {}
			chiave_tag = hash_conf['configurazione']['metadato1']
			hsh_output['titolarita_intervento']['titolo_persona'] = ( (!hash_pratica['dati_aggiuntivi'].blank? && !hash_pratica['dati_aggiuntivi'][chiave_tag].blank?) ? hash_pratica['dati_aggiuntivi'][chiave_tag] : chiave_tag )
			
			hsh_output['descrizione_intervento'] = hash_pratica['descrizione']
			
			hsh_output['tipo_interv_sint'] = {}
			hsh_output['tipo_interv_sint']['tipo_interv_nuovo_rist'] = {}
			hsh_output['tipo_interv_sint']['tipo_interv_nuovo_rist']['descrizione_tipo_interv_sint_2'] = hash_pratica['descrizione']
			#carico nome e cognome nella firma dell'asseverazione	
			hsh_output['data_firma_2'] = "#{hsh_output['progettista_1']['nome_progettista']} #{hsh_output['progettista_1']['cognome_progettista']}" unless hsh_output['progettista_1'].blank?

			hsh_output
		end		


		def self.controlla_dati_modulo(dati_post)
			errori = []
			#controllo errori: DA FARE
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:titolarita_intervento][:altro_titolo], 'altro_titolo', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:parti_comuni], 'parti_comuni', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:com_inizio_lav][:scelta_inizio_lav], 'scelta_inizio_lav', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:qual_interv][:tipo_qual_interv], 'tipo_qual_interv', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:tipo_interv_sint][:tipo_interv_nuovo_rist], 'tipo_interv_nuovo_rist', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:contributi_costruzione][:tipo_interv_realiz], 'tipo_interv_realiz', 'Selezionare una opzione.')
		
			#TOLTO PER AVERE PDF COMPILABILE
			# errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:commit_tit_1][:tut_salute], 'tut_salute', 'Selezionare una opzione.')
			# errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:commit_tit_1][:dati_commit_tit_3_], 'dati_commit_tit_3_', 'Selezionare una opzione.')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:cognome_nome_commit_tit], 'cognome_nome_commit_tit')
			# #errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:in_qualita], 'in_qualita')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:ordine_commit_tit], 'ordine_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:citta_ordine_commit_tit], 'citta_ordine_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:n_ordine_commit_tit], 'n_ordine_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:citta_residenza_commit_tit], 'citta_residenza_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:prov_residenza_commit_tit], 'prov_residenza_commit_tit')
			# #errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:stato_residenza_commit_tit], 'stato_residenza_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:indirizzo_residenza_commit_tit], 'indirizzo_residenza_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:civico_residenza_commit_tit], 'civico_residenza_commit_tit')
			# errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_post[:commit_tit_1][:cap_residenza_commit_tit], 'cap_residenza_commit_tit')

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

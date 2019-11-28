# -*- encoding : utf-8 -*-
module Moduli

	class Funzioni


		def self.carica_referente(chiave_referente, hash_pratica)
			if hash_pratica['referente'].length > 0
				if hash_pratica['referente'].is_a?(Hash) 
					return hash_pratica['referente']
				else
					#ciclo sui referenti e creo un hash di array per le varie tipologie di referenti
					hash_referenti = {}
					hash_pratica['referente'].each{ |ref|
						tipo_ref = ref["tipo_referenza"]
						if hash_referenti[tipo_ref].blank?
							hash_referenti[tipo_ref] = []
						end
						hash_referenti[tipo_ref] << ref
					}
					#inserisco l'array di array per i referenti nell'hash
					hash_pratica['hash_referenti'] = hash_referenti
					#carico da configurazione la chiave per il titolare
					referente_return = hash_pratica['hash_referenti'][chiave_referente]
				end	
				return referente_return
			end
			return nil
		end

		def self.carica_xml_iscrizioni(path_xml, allegati_associati=nil)
			xml_iscrizione = File.read(path_xml)
	        #carico in un hash i dati dall'xml
	        hash_iscrizione = Crack::XML.parse(xml_iscrizione)['hash']
			#creo un hash per caricare i dati nel modulo		
			hsh_output = {}
			
			hsh_output['dati_iscrizioni_online'] = {}
			hsh_iscrizioni = hsh_output['dati_iscrizioni_online']
			hsh_iscrizioni['richiedente_nome'] = hash_iscrizione['richiedente_nome']
			hsh_iscrizioni['richiedente_cognome'] = hash_iscrizione['richiedente_cognome']
			hsh_iscrizioni['richiedente_codice_fiscale'] = hash_iscrizione['richiedente_codice_fiscale']
			hsh_iscrizioni['richiedente_comune_nascita'] = hash_iscrizione['richiedente_comune_nascita']
			hsh_iscrizioni['richiedente_data_nascita'] = hash_iscrizione['richiedente_data_nascita']
			hsh_iscrizioni['richiedente_email'] = hash_iscrizione['richiedente_email']
			hsh_iscrizioni['richiedente_comune_residenza'] = hash_iscrizione['richiedente_comune_residenza']
			hsh_iscrizioni['richiedente_via'] = hash_iscrizione['richiedente_via_template']
			hsh_iscrizioni['richiedente_civico'] = hash_iscrizione['richiedente_civico']
			hsh_iscrizioni['richiedente_bis'] = hash_iscrizione['richiedente_bis']
			hsh_iscrizioni['richiedente_piano'] = hash_iscrizione['richiedente_piano']
			hsh_iscrizioni['richiedente_scala'] = hash_iscrizione['richiedente_scala']
			hsh_iscrizioni['richiedente_interno'] = hash_iscrizione['richiedente_interno']
			hsh_iscrizioni['richiedente_cellulare'] = hash_iscrizione['richiedente_cellulare']
			hsh_iscrizioni['richiedente_cellulare2'] = hash_iscrizione['richiedente_cellulare2']
			hsh_iscrizioni['richiedente_telefono'] = hash_iscrizione['richiedente_telefono']
			hsh_iscrizioni['richiedente_telefono2'] = hash_iscrizione['richiedente_telefono2']
			hsh_iscrizioni['reddito_isee'] = hash_iscrizione['reddito_isee']
			hsh_iscrizioni['riduzione_isee'] = hash_iscrizione['riduzione_isee'] == true ? "Si" : "No"
			hsh_iscrizioni['richiedente_note'] = hash_iscrizione['richiedente_note']
			hsh_iscrizioni['servizi'] = hash_iscrizione['servizi']
			hsh_iscrizioni['fruitore_nome'] = hash_iscrizione['fruitore_nome']
			hsh_iscrizioni['fruitore_cognome'] = hash_iscrizione['fruitore_cognome']
			hsh_iscrizioni['fruitore_codice_fiscale'] = hash_iscrizione['fruitore_codice_fiscale']
			hsh_iscrizioni['fruitore_comune_nascita'] = hash_iscrizione['fruitore_comune_nascita']
			hsh_iscrizioni['fruitore_data_nascita'] = hash_iscrizione['fruitore_data_nascita']
			hsh_iscrizioni['fruitore_comune_residenza'] = hash_iscrizione['fruitore_comune_residenza']
			hsh_iscrizioni['fruitore_via'] = hash_iscrizione['fruitore_via_template']
			hsh_iscrizioni['fruitore_civico'] = hash_iscrizione['fruitore_civico']
			hsh_iscrizioni['fruitore_bis'] = hash_iscrizione['fruitore_bis']
			hsh_iscrizioni['fruitore_piano'] = hash_iscrizione['fruitore_piano']
			hsh_iscrizioni['fruitore_scala'] = hash_iscrizione['fruitore_scala']
			hsh_iscrizioni['fruitore_interno'] = hash_iscrizione['fruitore_interno']
			hsh_iscrizioni['fruitore_scuola'] = hash_iscrizione['fruitore_scuola']
			hsh_iscrizioni['fruitore_classe'] = hash_iscrizione['fruitore_classe']
			hsh_iscrizioni['fruitore_sezione'] = hash_iscrizione['fruitore_sezione']
			return hsh_output, hash_iscrizione
		end

		#nel gestore del modulo singolo viene fatta la gestione dei metadati
		#vecchio comportamento: Se nel tag metadato1 c'è la scritta LAV_FINE, si va a prendere il valore del tag LAV_FINE in 97.xml. Se il tag LAV_FINE non ha contenuto, si scrive nel campo del modulo 'LAV_FINE'
		#nuovo comportamento: all'interno del template ci sono i campi con id metadato1, metadato2 ecc e vanno a finire sempre li dentro i valori
		def self.carica_xml_pratiche(path_xml, hash_conf, allegati_associati=nil)
			xml_pratica = File.read(path_xml)
	        #carico in un hash i dati dall'xml
	        hash_pratica = Crack::XML.parse(xml_pratica)['pratica']
	        #creo un txt con l'hash a partire dal file xml
	        #File.open(File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.txt'), 'w+') { |file| file.write(hash_pratica.to_json) }
			
			#creo un hash per caricare i dati nel modulo		
			hsh_output = {}
			#gestione dei referenti in base all'hash_conf (confix.xml)		
			chiave_richiedente = hash_conf['configurazione']['titolare']
			chiave_progettista = hash_conf['configurazione']['progettista']
			chiave_asseverante = hash_conf['configurazione']['asseverante']

			referenti = []
			unless chiave_richiedente.blank?
				richiedente = carica_referente(chiave_richiedente, hash_pratica)
				richiedente = (richiedente.is_a?(Hash) ? [richiedente] : richiedente)
				referenti << { 'titolare' => richiedente} unless richiedente.blank?
				#per CILA e CIL carico anche i dati del committente titolare nella sezione
				#TUTELA DELLA SALUTE E DELLA SICUREZZA NEI LUOGHI DI LAVORO
				referenti << { 'commit_tit' => richiedente} unless richiedente.blank?
				#carico nel campo data firma il nome e cognome del richiedente
				rich_per_firma = richiedente[0]
				testo_data_firma = "#{rich_per_firma['tipo_persona_id'] == 'G' ? rich_per_firma['lr_nome'] : rich_per_firma['nome']} #{rich_per_firma['tipo_persona_id'] == 'G' ? rich_per_firma['lr_cognome'] : rich_per_firma['cognome']}"
				"1".upto("10") do |indice| 
					hsh_output["data_firma_#{indice}"] = testo_data_firma
				end
			end

			unless chiave_progettista.blank?
				progettista = carica_referente(chiave_progettista, hash_pratica)
				progettista = (progettista.is_a?(Hash) ? [progettista] : progettista)
				referenti << { 'progettista' => progettista} unless progettista.blank?
			end

			unless chiave_asseverante.blank?
				asseverante = carica_referente(chiave_asseverante, hash_pratica)
				asseverante = (asseverante.is_a?(Hash) ? [asseverante] : asseverante)
				referenti << { 'asseverante' => asseverante} unless asseverante.blank?
			end

			referenti.each do |tipo_ref|
				tipo_ref.each_pair do |key, array_ref| 
					#cicliamo sugli N referenti di un certo tipo
					array_ref.each_with_index do |rich, index|
						index += 1 
						chiave = "#{key}_#{index}"
						#crea delle chiavi del tipo titolare_1, progettista_1, asseverante_1 che sono i moduli:gruppo
						#poi i moduli:dato hanno id del tipo cognome_titolare, cognome_progettista ecc

						hsh_output[chiave] = {}
						hsh_output[chiave]["tipo_persona_#{key}"] = rich['tipo_persona_id']
						hsh_output[chiave]["cognome_#{key}"] = rich['cognome']
						hsh_output[chiave]["cognome_#{key}"] ||= ""
						hsh_output[chiave]["nome_#{key}"] = rich['nome']
						hsh_output[chiave]["nome_#{key}"] ||= ""
						hsh_output[chiave]["cognome_nome_#{key}"] = hsh_output[chiave]["cognome_#{key}"] +" "+ hsh_output[chiave]["nome_#{key}"]
						hsh_output[chiave]["nome_cognome_#{key}"] = hsh_output[chiave]["nome_#{key}"] +" "+ hsh_output[chiave]["cognome_#{key}"]
						hsh_output[chiave]["ragione_sociale_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['cognome'] : ""
						hsh_output[chiave]["codice_fiscale_#{key}"] = "#{rich['codice_fiscale']}"
						hsh_output[chiave]["piva_#{key}"] = rich['partita_iva']

						hsh_output[chiave]["qualifica_persona_ditta_#{key}"] = 'Legale Rappresentante' if rich['tipo_persona_id'] == 'G'
						hsh_output[chiave]["nome_ditta_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['cognome'] : ""
						hsh_output[chiave]["cf_ditta_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['codice_fiscale'] : ""
						hsh_output[chiave]["piva_ditta_#{key}"] = rich['partita_iva']
						#campi per compatibilita con vecchi moduli
						hsh_output[chiave]["luogo_nascita_#{key}"] = rich['nascita_comune']['descrizione'] if !rich['nascita_comune'].blank?
						hsh_output[chiave]["provincia_nascita_#{key}"] = rich['nascita_comune']['provincia'] if !rich['nascita_comune'].blank?
						#nuovi campi
						hsh_output[chiave]["comune_nascita_#{key}"] = rich['nascita_comune']['descrizione'] if !rich['nascita_comune'].blank? && rich['tipo_persona_id'] != 'G'
						hsh_output[chiave]["prov_nascita_#{key}"] = rich['nascita_comune']['provincia'] if !rich['nascita_comune'].blank? && rich['tipo_persona_id'] != 'G'

						
						hsh_output[chiave]["cap_nascita_#{key}"] = rich['nascita_comune']['cap'] unless rich['nascita_comune'].blank?
						data_nascita = (Date.strptime(rich['nascita_data'], "%Y-%m-%d")).strftime("%d/%m/%Y") unless rich['nascita_data'].blank?
						data_nascita ||= ''
						hsh_output[chiave]["data_nascita_#{key}"] = rich['tipo_persona_id'] == 'G' ? "" : data_nascita
						
						hsh_output[chiave]["citta_residenza_#{key}"] = rich['indirizzo']['residenza_comune']['descrizione'] if !rich['indirizzo'].blank? && !rich['indirizzo']['residenza_comune'].blank?
						hsh_output[chiave]["prov_residenza_#{key}"] = rich['indirizzo']['residenza_comune']['provincia'] if !rich['indirizzo'].blank? && !rich['indirizzo']['residenza_comune'].blank?
						hsh_output[chiave]["indirizzo_residenza_#{key}"] = rich['indirizzo']['via'] unless rich['indirizzo'].blank?
						hsh_output[chiave]["civico_residenza_#{key}"] = "#{rich['indirizzo']['civico']}"
						hsh_output[chiave]["civico_residenza_#{key}"] += "/#{rich['indirizzo']['barrato']}" unless rich['indirizzo']['barrato'].blank?
						hsh_output[chiave]["civico_residenza_#{key}"] += " i. #{rich['indirizzo']['interno']}" unless rich['indirizzo']['interno'].blank?
						hsh_output[chiave]["civico_residenza_#{key}"] += " s. #{rich['indirizzo']['scala']}" unless rich['indirizzo']['scala'].blank?
						hsh_output[chiave]["civico_residenza_#{key}"] += " p.  #{rich['indirizzo']['piano']}" unless rich['indirizzo']['piano'].blank?
						hsh_output[chiave]["solo_civico_residenza_#{key}"] = rich['indirizzo']['civico']
						hsh_output[chiave]["bis_residenza_#{key}"] = rich['indirizzo']['barrato']
						hsh_output[chiave]["interno_residenza_#{key}"] = rich['indirizzo']['interno']
						hsh_output[chiave]["scala_residenza_#{key}"] = rich['indirizzo']['scala']
						hsh_output[chiave]["piano_residenza_#{key}"] = rich['indirizzo']['piano']
						hsh_output[chiave]["cap_residenza_#{key}"] = rich['indirizzo']['residenza_comune']['cap'] if !rich['indirizzo'].blank? && !rich['indirizzo']['residenza_comune'].blank?
						
						hsh_output[chiave]["email_#{key}"] = rich['email']
						hsh_output[chiave]["pec_#{key}"] = rich['pec']
						hsh_output[chiave]["telefono_#{key}"] = rich['telefono1']
						hsh_output[chiave]["cellulare_#{key}"] = rich['telefono2']
						hsh_output[chiave]["tel_fisso_cellulare_#{key}"] = "#{rich['telefono1']} / #{rich['telefono2']}"
						#dati albo per progettista
						hsh_output[chiave]["num_albo_#{key}"] = (rich['numero_iscrizione_albo'].is_a?(Array) ? rich['numero_iscrizione_albo'].first : rich['numero_iscrizione_albo'])
						hsh_output[chiave]["descr_albo_#{key}"] = rich['descrizione_albo']
						hsh_output[chiave]["prov_albo_#{key}"] = rich['provincia_albo']
						
						#caso per suap puglia con dati che sono sempre del legale rappresentante
						hsh_output[chiave]["cognome_leg_rap_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['lr_cognome'] : rich['cognome']
						hsh_output[chiave]["nome_leg_rap_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['lr_nome'] : rich['nome']
						hsh_output[chiave]["codice_fiscale_leg_rap_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['lr_codice_fiscale'] : rich['codice_fiscale']

						if rich['tipo_persona_id'] == 'G'
							hsh_output['dati_ditta_'+chiave] = {}
							hsh_output['dati_ditta_'+chiave]["qualifica_persona_ditta_#{key}"] = 'Legale Rappresentante' if rich['tipo_persona_id'] == 'G'
							hsh_output['dati_ditta_'+chiave]["nome_ditta_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['cognome'] : ""
							hsh_output['dati_ditta_'+chiave]["cf_piva_ditta_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['codice_fiscale'] : ""
							hsh_output['dati_ditta_'+chiave]["cod_fiscale_ditta_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['codice_fiscale'] : ""
							hsh_output['dati_ditta_'+chiave]["p_iva_ditta_#{key}"] = rich['tipo_persona_id'] == 'G' ? rich['partita_iva'] : ""
							hsh_output['dati_ditta_'+chiave]["cciaa_#{key}"] = rich['CCIAA_comune']
							hsh_output['dati_ditta_'+chiave]["provincia_cciaa_#{key}"] = rich['CCIAA_provincia']
							hsh_output['dati_ditta_'+chiave]["numero_cciaa_#{key}"] = rich['CCIAA_numero']
							hsh_output['dati_ditta_'+chiave]["sede_ditta_#{key}"] = rich['indirizzo']['residenza_comune']['descrizione'] if !rich['indirizzo'].blank? && !rich['indirizzo']['residenza_comune'].blank?
							hsh_output['dati_ditta_'+chiave]["provincia_ditta_#{key}"] = rich['indirizzo']['residenza_comune']['provincia'] if !rich['indirizzo'].blank? && !rich['indirizzo']['residenza_comune'].blank?
							hsh_output['dati_ditta_'+chiave]["indirizzo_ditta_#{key}"] = rich['indirizzo']['via'] unless rich['indirizzo'].blank?
							hsh_output['dati_ditta_'+chiave]["civico_ditta_#{key}"] = rich['indirizzo']['civico'] unless rich['indirizzo'].blank?
							hsh_output['dati_ditta_'+chiave]["cap_ditta_#{key}"] = rich['indirizzo']['residenza_comune']['cap'] if !rich['indirizzo'].blank? && !rich['indirizzo']['residenza_comune'].blank?
							hsh_output['dati_ditta_'+chiave]["pec_email_ditta_#{key}"] = rich['pec']
							hsh_output['dati_ditta_'+chiave]["tel_fisso_cellulare_ditta_#{key}"] = rich['telefono1']
							data_nascita = (Date.strptime(rich['nascita_data'], "%Y-%m-%d")).strftime("%d/%m/%Y") unless rich['nascita_data'].blank?

							#DA FARE CON DATI LEGALE RAPPRESENTANTE PER TEMPLATE RICHIEDENTE
							hsh_output['dati_ditta_'+chiave]["cognome_leg_rap"] = rich['tipo_persona_id'] == 'G' ? rich['lr_cognome'] : ""
							hsh_output['dati_ditta_'+chiave]["nome_leg_rap"] = rich['tipo_persona_id'] == 'G' ? rich['lr_nome'] : ""
							hsh_output['dati_ditta_'+chiave]["cod_fisc_leg_rap"] = rich['tipo_persona_id'] == 'G' ? rich['lr_codice_fiscale'] : ""
							hsh_output['dati_ditta_'+chiave]["data_nascita_leg_rap"] = rich['tipo_persona_id'] == 'G' ? data_nascita : ""
							hsh_output['dati_ditta_'+chiave]["comune_nascita_leg_rap"] = (rich['tipo_persona_id'] == 'G' && !rich['nascita_comune'].blank?) ? rich['nascita_comune']['descrizione']  : ""
 
							hsh_output['dati_ditta_'+chiave]["prov_nascita_leg_rap"] = (rich['tipo_persona_id'] == 'G' && !rich['nascita_comune'].blank?) ? rich['nascita_comune']['provincia']  : ""

							#sulle 3 persone dei template che sono tutte con leg_rap
							hsh_output['dati_ditta_'+chiave]["cciaa_leg_rap"] = rich['CCIAA_comune']
							hsh_output['dati_ditta_'+chiave]["provincia_cciaa_leg_rap"] = rich['CCIAA_provincia']
							hsh_output['dati_ditta_'+chiave]["numero_cciaa_leg_rap"] = rich['CCIAA_numero']

						end
						
						if ( (rich['tipo_persona_id'] == 'M' && rich['tipo_persona'] == 'fisica') || ( rich['lr_tipo_persona_id'] == 'M' ))
							#maschio
							hsh_output[chiave]['sesso_m'] = 1 # = { 'check' => 1 }
						elsif ( (rich['tipo_persona_id'] == 'F' && rich['tipo_persona'] == 'fisica') || ( rich['lr_tipo_persona_id'] == 'F' ))
							#femmina
							hsh_output[chiave]['sesso_f'] = 1 # = { 'check' => 1 }
						end

						hsh_output[chiave]["citta_studio_#{key}"] = rich['indirizzo']['residenza_comune']['descrizione'] unless rich['indirizzo']['residenza_comune'].blank?
						hsh_output[chiave]["prov_studio_#{key}"] = rich['indirizzo']['residenza_comune']['provincia'] unless rich['indirizzo']['residenza_comune'].blank?
						hsh_output[chiave]["stato_studio_#{key}"] = "" #non presente
						hsh_output[chiave]["indirizzo_studio_#{key}"] = rich['indirizzo']['via']
						hsh_output[chiave]["civico_studio_#{key}"] = "#{rich['indirizzo']['civico']}"
						hsh_output[chiave]["civico_studio_#{key}"] += "/#{rich['indirizzo']['barrato']}" unless rich['indirizzo']['barrato'].blank?
						hsh_output[chiave]["civico_studio_#{key}"] += " i. #{rich['indirizzo']['interno']}" unless rich['indirizzo']['interno'].blank?
						hsh_output[chiave]["civico_studio_#{key}"] += " s. #{rich['indirizzo']['scala']}" unless rich['indirizzo']['scala'].blank?
						hsh_output[chiave]["civico_studio_#{key}"] += " p.  #{rich['indirizzo']['piano']}" unless rich['indirizzo']['piano'].blank?
						hsh_output[chiave]["cap_studio_#{key}"] = rich['indirizzo']['residenza_comune']['cap'] unless rich['indirizzo']['residenza_comune'].blank?
						hsh_output[chiave]["ordine_#{key}"] = rich['descrizione_albo']
						hsh_output[chiave]["citta_ordine_#{key}"] = rich['provincia_albo']
						hsh_output[chiave]["n_ordine_#{key}"] = rich['numero_iscrizione_albo'][0] unless rich['numero_iscrizione_albo'].blank?
						hsh_output[chiave]["tel_#{key}"] = rich['telefono1']
						hsh_output[chiave]["fax_#{key}"] = "" #non presente

					end
				end
				
			end

			#carico id pratica e data pratica
			hsh_output['id_pratica'] = hash_pratica['id']
			hsh_output['data_pratica'] = hash_pratica['data_presentazione']

			#carico i dati della pratica nel template Dati della pratica e Evento/Intervento
			hsh_output['numero_istanza_pratica'] = hash_pratica['id']
			hsh_output['data_istanza_pratica'] = hash_pratica['data_presentazione']
			hsh_output['oggetto_della_pratica'] = hash_pratica['descrizione']

			hsh_output['tipo_evento_pratica'] = hash_pratica['evento']
			hsh_output['tipo_intervento_pratica'] = hash_pratica['intervento']
			
			#carico i dati degli oggetti territoriali
			oggetto_territoriale = hash_pratica['oggetto_territoriale']
			unless oggetto_territoriale.blank?
				oggetto_territoriale = (oggetto_territoriale.is_a?(Hash) ? [oggetto_territoriale] : oggetto_territoriale)
				oggetto_territoriale.each_with_index do |ot, index|
					index += 1
					hsh_output["oggetto_territoriale_#{index}"] = {}
					hsh_output["oggetto_territoriale_#{index}"]['tipo_catasto'] = ot['tipo']
					case ot['tipo']
						when 'U'
							hsh_output["oggetto_territoriale_#{index}"]['tipo_catasto_descrizione'] = "Catasto Fabbricati"
						when 'T'
							hsh_output["oggetto_territoriale_#{index}"]['tipo_catasto_descrizione'] = "Catasto Terreni"
						else
							hsh_output["oggetto_territoriale_#{index}"]['tipo_catasto_descrizione'] = "Catasto #{ot['tipo']}"
					end
					hsh_output["oggetto_territoriale_#{index}"]['destinazione_uso'] = ot['destinazioneUso']
					hsh_output["oggetto_territoriale_#{index}"]['destinazione_uso_codice'] = ot['destinazioneUsoCodice']
					hsh_output["oggetto_territoriale_#{index}"]['destinazione_prg'] = ot['destinazioneprg']
					hsh_output["oggetto_territoriale_#{index}"]['catasto_sez'] = ot['sezione']
					hsh_output["oggetto_territoriale_#{index}"]['catasto_foglio'] = ot['foglio']
					hsh_output["oggetto_territoriale_#{index}"]['catasto_barrato'] = ot['barrato']
					hsh_output["oggetto_territoriale_#{index}"]['catasto_map'] = ot['mappale']
					hsh_output["oggetto_territoriale_#{index}"]['catasto_sub'] = ot['subalterno']
					hsh_output["oggetto_territoriale_#{index}"]['catasto_sez_urb'] = "" #boh! non presente, cosa sarebbe...
					
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_loc'] = "#{ot['indirizzo']['descrizione']}" unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_via'] = "#{ot['indirizzo']['via']}" unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_n'] = ot['indirizzo']['civico'] unless ot['indirizzo'].blank? #retrocompatibilita
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_civico'] = ot['indirizzo']['civico'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_bis'] = ot['indirizzo']['barrato'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_scala'] = ot['indirizzo']['scala'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_piano'] = ot['indirizzo']['piano'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_interno'] = ot['indirizzo']['interno'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['loc_int_cap'] = "" #non presente
					#nuovi id per editor
					hsh_output["oggetto_territoriale_#{index}"]['localita_oggetto_territoriale'] = "#{ot['indirizzo']['descrizione']}" unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['indirizzo_oggetto_territoriale'] = "#{ot['indirizzo']['via']}" unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['numero_oggetto_territoriale'] = ot['indirizzo']['civico'] unless ot['indirizzo'].blank? #retrocompatibilita
					hsh_output["oggetto_territoriale_#{index}"]['civico_oggetto_territoriale'] = ot['indirizzo']['civico'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['bis_oggetto_territoriale'] = ot['indirizzo']['barrato'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['scala_oggetto_territoriale'] = ot['indirizzo']['scala'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['piano_oggetto_territoriale'] = ot['indirizzo']['piano'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['interno_oggetto_territoriale'] = ot['indirizzo']['interno'] unless ot['indirizzo'].blank?
					hsh_output["oggetto_territoriale_#{index}"]['cap_oggetto_territoriale'] = "" #non presente

				end
			end

			#carico gli eventi, usati per le scia del SUAP per associare le sezioni a scomparsa
			unless hash_pratica['codice_evento'].blank?
				hsh_output['codice_evento'] = hash_pratica['codice_evento']
				hsh_output['descrizione_evento'] = hash_pratica['evento']
			end

			#carico gli interventi, usati per le scia del SUAP per associare le sezioni a scomparsa
			unless hash_pratica['codice_intervento'].blank?
				hsh_output['codice_intervento'] = hash_pratica['codice_intervento']
				hsh_output['descrizione_intervento'] = hash_pratica['intervento']
			end

			#carico i dati aggiuntivi che vengono da pratiche civilia, usato per suap puglia
			unless hash_pratica['dati_aggiuntivi'].blank?
				hsh_output["sezione_comune"] = {}
				settmerc = hash_pratica['dati_aggiuntivi']['SETTMERC']
				unless settmerc.blank?
					hsh_output["sezione_comune"]["SETTMERC"] = { settmerc => {} } 
					hsh_output["sezione_comune"]["SETTMERC"][settmerc]['check'] = '1'
				end
				caratt = hash_pratica['dati_aggiuntivi']['CARATT']
				unless caratt.blank?
					hsh_output["sezione_comune"]["CARATT"] = { caratt => {} } 
					hsh_output["sezione_comune"]["CARATT"][caratt]['check'] = '1'
				end
				strutt = hash_pratica['dati_aggiuntivi']['STRUTT']
				unless strutt.blank?
					hsh_output["scelta_scia"] = { "STRUTT" => { strutt => {} } }
					hsh_output["scelta_scia"]["STRUTT"][strutt]['check'] = '1'
				end
				special = hash_pratica['dati_aggiuntivi']['SPECIAL']
				unless special.blank?
					hsh_output["sezione_comune"]["SPECIAL"] = { special => {} } 
					hsh_output["sezione_comune"]["SPECIAL"][special]['check'] = '1'
				end
				
			end

			#carico nome dell'ente come titolare sue/suap
			hsh_output['tit_suap_sue'] = Spider.conf.get('ente.nome')  

			#gestione flag su allegati presenti/assenti

			all_configurati_da_conf = hash_conf['configurazione']['allegato']
			#se non sono stati passati gli allegati nel file conf.xml cerco in db se ho configurato gli allegati tramite l'editor
			if all_configurati_da_conf.blank? && !allegati_associati.blank?
				all_configurati_da_conf = []
				JSON.parse(allegati_associati).each_pair{|key, value| all_configurati_da_conf << value }
			end

			unless all_configurati_da_conf.blank?
				if all_configurati_da_conf.is_a?(::Hash)
					all_configurati = [all_configurati_da_conf]
				else
					all_configurati = all_configurati_da_conf
				end
				#se ho configurato degli allegati creo un hash con gli allegati della pratica
				all_in_pratica = hash_pratica['allegato']
				if all_in_pratica.is_a?(::Hash)
					all_in_pratica = [all_in_pratica]
				end
				unless all_in_pratica.blank?
					hash_allegati_in_pratica = {}
					all_in_pratica.each{|all_prat|
						hash_allegati_in_pratica[all_prat['codice']] = { 'tipo'=>all_prat['tipo'],
														  'flag_file'=>all_prat['flag_file'],
														  'id'=>all_prat['id']} 
					}
					all_configurati.each{|allegato|
						cod_all = allegato['codice']
						if !hash_allegati_in_pratica[cod_all].blank? #trovo in xml pratica l'allegato
							unless allegato['presente'].blank?
								array_check_presente = allegato['presente'].split(';')
								#ho un array di chiavi per singolo check
								array_check_presente.each{|chiavi_hash|
									#setto i vari flag in base all'array di chiavi
									array_chiavi = chiavi_hash.split(',').map{|val| val.strip}
									self.array_in_hash_check(hsh_output,array_chiavi,0)
								}
							end
						else #non trovo in xml pratica l'allegato
							unless allegato['assente'].blank?
								array_check_assente = allegato['assente'].split(';')
								array_check_assente.each{|chiavi_hash|
									#setto i vari flag in base all'array di chiavi
									array_chiavi = chiavi_hash.split(',')
									self.array_in_hash_check(hsh_output,array_chiavi,0)
								}
							end
						end
					}
				
				end
			end
			#gestione metadati con template da editor
			1.upto(6) { |index|
				chiave_tag = hash_conf['configurazione']['metadato'+index.to_s]
				hsh_output['metadato_'+index.to_s] = ( (!hash_pratica['dati_aggiuntivi'].blank? && !hash_pratica['dati_aggiuntivi'][chiave_tag].blank?) ? hash_pratica['dati_aggiuntivi'][chiave_tag] : chiave_tag )

			}
			#gestione vincoli e zone con template da editor
			#trasformo l'hash in array se ho un solo elemento zona
			if hash_pratica['zona'].is_a?(Hash)
				hash_pratica['zona'] = [hash_pratica['zona']]
			end
			hsh_output['zona_da_pratiche_1'] = hash_pratica['zona'][0]['nome_zona'] if !hash_pratica['zona'].blank? && !hash_pratica['zona'][0].blank?
			hsh_output['zona_da_pratiche_2'] = hash_pratica['zona'][1]['nome_zona'] if !hash_pratica['zona'].blank? && !hash_pratica['zona'][1].blank?
			hsh_output['zona_da_pratiche_3'] = hash_pratica['zona'][2]['nome_zona'] if !hash_pratica['zona'].blank? && !hash_pratica['zona'][2].blank?
			#trasformo l'hash in array se ho un solo elemento vincolo
			if hash_pratica['vincolo'].is_a?(Hash)
				hash_pratica['vincolo'] = [hash_pratica['vincolo']]
			end
			hsh_output['vincolo_da_pratiche_1'] = hash_pratica['vincolo'][0]['nome_vincolo'] if !hash_pratica['vincolo'].blank? && !hash_pratica['vincolo'][0].blank?
			hsh_output['vincolo_da_pratiche_2'] = hash_pratica['vincolo'][1]['nome_vincolo'] if !hash_pratica['vincolo'].blank? && !hash_pratica['vincolo'][1].blank?
			hsh_output['vincolo_da_pratiche_3'] = hash_pratica['vincolo'][2]['nome_vincolo'] if !hash_pratica['vincolo'].blank? && !hash_pratica['vincolo'][2].blank?
			
			hsh_output['elenco_referenti'] = hash_pratica['elencoReferenti'] if !hash_pratica['elencoReferenti'].blank?
			
			return hsh_output, hash_pratica
		end

		#funzione ricorsiva che dato un array di chiavi setta {['check'] => '1'} alla fine, per flag automatici da dati civilia
		def self.array_in_hash_check(hash_dati,array_chiavi,indice)
			if array_chiavi.length == indice
				return {'check' => '1'}
			elsif indice == 0
				return hash_dati[array_chiavi[indice].strip] = array_in_hash_check(hash_dati[array_chiavi[indice]],array_chiavi,indice+1)
			else
				if hash_dati.blank? #potrebbe esserci la stessa chiave, devo poter avere n oggetti associati ad una chiave
					return { array_chiavi[indice].strip => array_in_hash_check(hash_dati,array_chiavi,indice+1)}
				else
					#aggiungo altro hash a stessa chiave
					return hash_dati.merge({ array_chiavi[indice].strip => array_in_hash_check(hash_dati[array_chiavi[indice]],array_chiavi,indice+1) })
				end
			end

		end


		#ritorna un array di hash con gli allegati provenienti da pratiche
		def self.url_allegati(id_pratica)
			path_pratica_dir = File.join(Spider.paths[:data], 'uploaded_files/moduli/xml_pratiche', id_pratica.to_s)
			all_allegati = File.join(path_pratica_dir, 'allegato_allegati-'+id_pratica.to_s+'.pdf')
			all_referenti = File.join(path_pratica_dir, 'allegato_referenti-'+id_pratica.to_s+'.pdf')
			all_territorio = File.join(path_pratica_dir, 'allegato_territorio-'+id_pratica.to_s+'.pdf')
			all_metadati = File.join(path_pratica_dir, 'allegato_metadati-'+id_pratica.to_s+'.pdf')
			res = []
			res << { 'nome' => 'allegato_allegati-'+id_pratica.to_s+'.pdf', 'testo' => 'Allegati' } if File.exist?(all_allegati)
			res << { 'nome' => 'allegato_referenti-'+id_pratica.to_s+'.pdf', 'testo' => 'Referenze' } if File.exist?(all_referenti)
			res << { 'nome' => 'allegato_territorio-'+id_pratica.to_s+'.pdf', 'testo' => 'Territorio' } if File.exist?(all_territorio)
			res << { 'nome' => 'allegato_metadati-'+id_pratica.to_s+'.pdf', 'testo' => 'Metadati' } if File.exist?(all_metadati)
			res.blank? ? nil : res
		end

		#fa il controllo di una lista di chekbox dato il campo, il messaggio da visualizzare in caso di errore
		def self.controlla_checkbox_obbligatorio(checkbox_hash, chiave, message=nil)
			message ||= "Selezionare almeno un'opzione"
			checked = false
			unless checkbox_hash.blank?
				checkbox_hash.each_pair{ |k,v|
					if !v.blank? && !v['check'].blank? 

						checked = true 
					end
				}
			end
			return ( checked ? nil : { 'campo' => chiave, 'msg' => message, 'input_type' => 'checkbox' } )
		end

		#fa il controllo di una lista di chekbox dato l'hash dei dati in post, l'id della widget moduli:opzioni, il messaggio da visualizzare in caso di errore 
		#chiavi_scelte , array con le varie scelte su cui andare a verificare l'obbligatorieta. Se questo nil allora considero A,B,C,D
		#Se le scelte sono A,B,C,D ci saranno i checkbox
			# chiave
			# 	chiave_A => '1'/nil  : se 1 = checked questa scelta, quindi ci deve essere un figlio selezionato
			# 	tipi_A => '1'/nil  : se 1 = checked questa scelta
			# 	chiave_B
			# 	tipi_B
			#   .. tipi_D
		#chiave contiene l'id della widget moduli:opzioni
		def self.controlla_checkbox_obbligatorio_secondo_livello(checkbox_hash, chiave, message, chiavi_scelte=nil)
			unless checkbox_hash.blank?
				if chiavi_scelte.blank?
					chiavi_scelte = []
					"A".upto("F") do |scelta| 
						chiavi_scelte << scelta
					end
				end
				chiavi_scelte.each{ |key|
					if !checkbox_hash[(chiave+'_'+key).to_sym].blank? && checkbox_hash[(chiave+'_'+key).to_sym]['check'] == '1'
						checked = true
						unless checkbox_hash[('tipi_'+key).to_sym].blank?
							checked = false
							checkbox_hash[('tipi_'+key).to_sym].each_pair{ |k,v|
								unless v['check'].blank?
									checked = true 
								end
							}
						end
						return ( checked ? nil : { 'campo' => chiave, 'msg' => message, 'input_type' => 'checkbox' } )
					end
				}
				return nil
			else
				return nil
			end
			
		end



		#fa il controllo di un campo text dato il campo, il messaggio da visualizzare in caso di errore
		def self.controlla_campo_text_obbligatorio(input_hash, chiave, message=nil)
			message ||= 'Campo obbligatorio'
			if input_hash.blank?
				return { 'campo' => chiave, 'msg' => message, 'input_type' => 'text' }
			else
				return nil
			end
		end

		#metodi usati nel caso il modulo sia stato fatto con l'editor
		#in hash_dati_utente_vengono passati i dati provenienti da pratiche
		def self.controlla_campi_obbligatori_editor(tipo_modulo, dati_inseriti,hash_dati_utente=nil)
			errori = []
			hash_input_obbligatori = JSON.parse(tipo_modulo.campi_obbligatori)
			#verifico se i campi obbligatori sono in riquadri visualizzati in base a evento e intervento
			eventi_associati = tipo_modulo.eventi_associati
			interventi_associati = tipo_modulo.interventi_associati
			#se ci sono eventi o interventi associati
			if (!eventi_associati.blank? || !interventi_associati.blank? ) && !hash_dati_utente.blank?
				hash_eventi = !eventi_associati.blank? ? JSON.parse(eventi_associati) : {}
				hash_interventi = !interventi_associati.blank? ? JSON.parse(interventi_associati) : {}
				
				#hash_eventi = {"1"=>{"codice"=>"AMP"}, "3"=>{"codice"=>"AP"}}
				#hash_interventi = { "2"=>{"codice"=>"14"} }
				#tolgo dall'hash_input_obbligatori i gruppi che non vengono mostrati
				#trovo hash con gruppo che si può non controllare
				array_id_gruppo_da_non_controllare = []
				cod_evento = hash_dati_utente['codice_evento']
				cod_intervento = hash_dati_utente['codice_intervento']
				hash_eventi.each_pair{|id_gruppo,obj_codice|
					if obj_codice['codice'] != cod_evento && !cod_evento.blank?
						array_id_gruppo_da_non_controllare << "gruppo_"+id_gruppo
					end
				}
				hash_interventi.each_pair{|id_gruppo,obj_codice|
					if obj_codice['codice'] != cod_intervento && !cod_intervento.blank?
						array_id_gruppo_da_non_controllare << "gruppo_"+id_gruppo
					end
				}
			end
			#controllo i riquadri opzionali
			array_id_riquadri_opzionali = dati_inseriti['array_id_riquadri_opzionali'] 


			if hash_input_obbligatori['text'].length > 0
				hash_input_obbligatori['text'].each{ |campo_text_obbl|
					#se ho un campo contenuto in un riquadro (gruppo) presente in array_id_gruppo_da_non_controllare salto il controllo
					# if campo_text_obbl.length > 1 && !array_id_gruppo_da_non_controllare.blank?
					# 	next if array_id_gruppo_da_non_controllare.include?(campo_text_obbl[0]) 
					# end

					next if ( campo_text_obbl.length > 1 && !array_id_gruppo_da_non_controllare.blank? && array_id_gruppo_da_non_controllare.include?(campo_text_obbl[0]) )

					#se ho un campo contenuto in un riquadro opzionale deve essere nell'array array_id_riquadri_opzionali
					# if campo_text_obbl.length > 1 && !array_id_riquadri_opzionali.blank?
					# 	next unless array_id_riquadri_opzionali.include?(campo_text_obbl[0]) 
					# end

					next if ( campo_text_obbl.length > 1 && ( (!array_id_riquadri_opzionali.blank? && !(campo_text_obbl[0] =~ /fieldset_riquadro_opzionale/).nil? && !array_id_riquadri_opzionali.include?(campo_text_obbl[0])) || (array_id_riquadri_opzionali.blank? && !(campo_text_obbl[0] =~ /fieldset_riquadro_opzionale/).nil?) ) )

					#se non passo dati in post inserisco errore
					if dati_inseriti.blank?
						errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(nil, campo_text_obbl)
					else
						if campo_text_obbl.is_a?(Array)
							valore_inserito = nil
							campo_text_obbl.each{ |chiave_dati_inseriti|
								valore_inserito = valore_inserito.blank? ? dati_inseriti[chiave_dati_inseriti.to_sym] : valore_inserito[chiave_dati_inseriti.to_sym]
							}
							errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(valore_inserito, campo_text_obbl.last)
						else #campo singolo
							errori << Moduli::Funzioni.controlla_campo_text_obbligatorio(dati_inseriti[campo_text_obbl.to_sym], campo_text_obbl)
						end
					end
				}
			end
			require 'byebug'
			if hash_input_obbligatori['checkbox'].length > 0
				hash_input_obbligatori['checkbox'].each{ |campo_check_obbl|
				
					#se ho un campo contenuto in un riquadro (gruppo) presente in array_id_gruppo_da_non_controllare salto il controllo
					# if campo_check_obbl.length > 1 && !array_id_gruppo_da_non_controllare.blank?
					# 	next if array_id_gruppo_da_non_controllare.include?(campo_check_obbl[0]) 
					# end
					next if ( campo_check_obbl.length > 1 && !array_id_gruppo_da_non_controllare.blank? && array_id_gruppo_da_non_controllare.include?(campo_check_obbl[0]) )
					#se ho un campo contenuto in un riquadro opzionale deve essere nell'array array_id_riquadri_opzionali
					# if campo_check_obbl.length > 1 && !array_id_riquadri_opzionali.blank?
					# 	next unless array_id_riquadri_opzionali.include?(campo_check_obbl[0]) 
					# end

					next if ( campo_check_obbl.length > 1 && ( (!array_id_riquadri_opzionali.blank? && !(campo_check_obbl[0] =~ /fieldset_riquadro_opzionale/).nil? && !array_id_riquadri_opzionali.include?(campo_check_obbl[0])) || (array_id_riquadri_opzionali.blank? && !(campo_check_obbl[0] =~ /fieldset_riquadro_opzionale/).nil?) ) )
					
					#se non passo dati in post inserisco errore
					if dati_inseriti.blank?
						errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(nil, campo_check_obbl)
					else
						if campo_check_obbl.is_a?(Array)
							valore_inserito = nil
							campo_check_obbl.each{ |chiave_dati_inseriti|
								valore_inserito = valore_inserito.blank? ? dati_inseriti[chiave_dati_inseriti.to_sym] : valore_inserito[chiave_dati_inseriti.to_sym]
							}
							errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(valore_inserito, campo_check_obbl.last)
						else #campo singolo
							errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_inseriti[campo_check_obbl.to_sym], campo_check_obbl)
						end
					end
				}
			end
			#controllo il formato degli allegati
			errori = errori.concat(Moduli::Funzioni.controlla_formato_campi_allegati(dati_inseriti))

			errori = errori.compact
			#converto gli errori in json per passarlo al js
			errori.blank? ? nil : errori
			
		end


		def self.controlla_formato_campi_allegati(dati)
			errori = []
			unless dati.blank?
				dati.keys.each{|chiave| 
					#controllo se il campo è di tipo allegato
					unless (chiave =~ /^allegato_\d/).nil?
						#controllo se il file inserito ha estensione bat, sh, exe, bin, zip, gz, tar, 7z
						if !dati[chiave].blank? && ['.bat', '.sh', '.exe', '.bin', '.zip', '.gz', '.tar', '.7z'].include?(Pathname.new(dati[chiave]).extname)
							errori << { 'campo' => chiave.to_s, 'msg' => 'Formato di file allegato non consentito', 'input_type' => 'text' }
						end
					end	
				}
			end
			errori
		end


		def self.carica_dati_modulo_editor(params, hash_conf, allegati_associati)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        #carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf, allegati_associati)
			hsh_output
		end

		def self.carica_dati_modulo_editor_iscrizioni(params, allegati_associati)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_iscrizioni', params['id_iscrizione'], params['id_iscrizione']+'.xml')
	        #carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_iscrizione = Moduli::Funzioni.carica_xml_iscrizioni(path, allegati_associati)
			hsh_output
		end	
	end

	class Protocollo
		require 'savon'
		require 'httpclient'
		require 'base64'

		def initialize(*params)
			@modulo = @attachments = nil
			params.each do |p|
				@modulo = p if p.is_a?(Moduli::ModuloSalvato)
				@attachments = p if p.is_a?(Array)
			end
			@params = params
		end

		def self.facet_date(valore)
            data = nil
            data = Date.parse(DateTime.parse(valore.to_s).strftime("%Y-%m-%d")) unless valore.blank?
        end

        def invio_tramite_email?
        	return Spider.conf.get('moduli.protocollo').blank?
        end

        def genera
			#Definire qui e in options.rb i tipi di protocollo interoperabili usabili
			servizio = Spider.conf.get('moduli.protocollo_interoperabile')
			case servizio
			when 'iride_web'
				richiesta = iride_web_req
				soap_response = invia(servizio, richiesta, :inserisci_protocollo)
				risposta = iride_web_rsp(soap_response) if soap_response != 'ko'
			when 'civiliaopen'
				richiesta = civilia_req
			when 'folium'
				risposta = 'ko'
				if @modulo.protocollo_id.blank?
					richiesta = folium_protocolla_req
					soap_response = invia(servizio, richiesta, :protocolla)
					risposta = folium_protocolla_rsp(soap_response) if soap_response != 'ko'
				end
				#Inserisco gli eventuali allegati se la response di inserimento del protocollo è valida
				#oppure è già stato effettuata la richiesta di inserimento del protocollo (quindi c'è l'id)
				#e ci sono allegati oltre al primo (documento principale già inviato con la prima richiesta)
				if (soap_response != 'ko' || @modulo.protocollo_id) && !@attachments.blank? && @attachments.length > 1
					@attachments[1..-1].each do |att|
						richiesta = folium_inserisci_allegato(att)
						soap_response = invia(servizio, richiesta, :inserisci_allegato)
						risposta = folium_inserisci_allegato_rsp(soap_response) if soap_response != 'ko'
					end
				end
				return risposta
			when 'folium_test_login'
				richiesta = folium_test_login_req
				soap_response = invia(servizio, richiesta, :test_login)
				risposta = folium_auth_rsp(soap_response) if soap_response != 'ko'
			when 'other'
				#Da FARE se serve aggiungere altri tipi di protocollo
			end
			
		end

		def invia(servizio, richiesta, operation)
			retr = 0
			begin
	        	client = connessione_servizio_wsdl
	        	response = client.call(operation, :message => richiesta )
	        rescue HTTPClient::ReceiveTimeoutError => exc
                Spider.logger.error("Il servizio #{servizio} non è disponibile: #{exc}")
                return "ko"
            rescue ParametriNonImpostati => exc
                Spider.logger.error("Mancano parametri di base: #{exc}")
                return "ko"
            rescue SoapError => exc
                Spider.logger.error("Si è verificato un errore durante l'invio o il parsing della richiesta/risposta Soap #{operation}: #{exc}")
                return 'ko'
            rescue => exc
            	Spider.logger.error(exc)
        		retr += 1
                if retr <= 5
                    Spider.logger.error "Riprovo (tentativo #{retr})"
                    sleep(5)
                    retry
                else
                    Spider.logger.error "Rinuncio..."
                end
            	return 'ko'
       		end
		end

		private 

		def connessione_servizio_wsdl
			service = Spider.conf.get('moduli.protocollo')['service']
            endpnt = Spider.conf.get('moduli.protocollo')['endpoint']
            auth = Spider.conf.get('moduli.protocollo')['auth']['basic_auth'] unless Spider.conf.get('moduli.protocollo')['auth'].blank?
            unless service
                raise ParametriNonImpostati.new("È necessario impostare moduli.protocollo: service")
            end
            #Costruisco l'url del service se non ha come suffisso wsdl
            if service.rpartition(".")[-1] != 'wsdl'
                url = service + "?wsdl"
            else
                Spider.logger.error("Errata impostazione in config 'moduli.protocollo: service', necessario impostare url wsdl")
                raise ParametriNonImpostati.new("Errata impostazione in config 'moduli.protocollo: service', necessario impostare url del servizio wsdl")
            end
            endpnt ||= service
            ba = auth
            basic_auth = nil
            if ba
                realm = Base64.encode64(ba).gsub("\n","")
                basic_auth = { 'Authorization' => "Basic #{realm}" }
            end
            return client = Savon.client do
                # The WSDL document provided by the service.
                if basic_auth
                    headers basic_auth
                end
                wsdl url
                if endpnt 
                    endpoint endpnt
                end
                # Lower timeouts so these specs don't take forever when the service is not available.
                open_timeout 30
                read_timeout 30

                # Disable logging for cleaner spec output.
                log true
            end
        end

		def iride_web_req
			builder = Nokogiri::XML::Builder.new(:encoding => 'UTF-8') { |xml|
				xml.ProtoIn({'xmlns' => 'http://tempuri.org/'}) do
					xml.Data @modulo.inviato.lformat(:short)
					xml.Classifica @modulo.tipo_modulo.classifica
					xml.TipoDocumento @modulo.tipo_modulo.tipo_documento
					xml.Oggetto "Invio Dematerializzato #{@modulo.tipo_modulo.nome} nr. #{@modulo.id} del #{@modulo.inviato.lformat(:short)} da #{nominativo}"
					xml.Origine 'A'
					xml.MittenteInterno 'US-ISTANZE'
					xml.MittentiDestinatari {
						xml.MittenteDestinatario {
							if persona_giuridica?
								xml.CodiceFiscale @modulo.utente.ditta.partita_iva
								xml.CognomeNome nominativo
								#xml.Nome nil
								#xml.Indirizzo nil #"#{@modulo.utente.ditta.indirizzo_azienda} N. #{@modulo.utente.ditta.civico_azienda}"
								#xml.Localita nil
								#xml.CodiceComuneResidenza nil
								xml.TipoSogg 'S'
								xml.TipoPersona 'GI'
								#xml.Recapiti nil
							else
								xml.CodiceFiscale @modulo.utente.codice_fiscale
								xml.CognomeNome nominativo
								xml.Nome @modulo.utente.nome
								#xml.Indirizzo nil #"#{@modulo.utente.indirizzo_residenza} N. #{@modulo.utente.civico_residenza}"
								#xml.Localita nil
								#xml.CodiceComuneResidenza nil
								xml.TipoSogg 'S'
								xml.TipoPersona 'FI'
								#xml.Recapiti nil
							end
						}
					}
					xml.AggiornaAnagrafiche 'S'
					xml.InCaricoA @modulo.tipo_modulo.in_carico_a
					#xml.AnnoPratica nil
					#xml.NumeroPratica nil
					#xml.DataDocumento nil
					#xml.NumeroDocumento nil
					xml.NumeroAllegati @attachments.size
					#xml.DataEvid nil
					#xml.OggettoStandard nil
					xml.Utente 'US-ISTANZE'
					xml.Ruolo 'protocollo'
					xml.Allegati {
						@attachments.each do |a|
							xml.Allegato{
								xml.TipoFile a[:filetype] #inserire estensione
								xml.ContentType nil
								xml.Image Base64.strict_encode64(a[:content]) #inserire immagine del file in Base64Binary
								xml.Commento a[:filename].gsub(a[:filetype], '')
							}
						end
					}
				end
			}
			return builder.to_xml(:save_with => Nokogiri::XML::Node::SaveOptions::NO_DECLARATION).strip
		end	

		def iride_web_rsp(response)
			docxml = response.doc
            Spider.logger.debug(response.to_xml)
            docxml.remove_namespaces!
            doc_content = docxml.search("//InserisciProtocolloEAnagraficheResult").document unless docxml.search("//InserisciProtocolloEAnagraficheResult").blank?
            Spider.logger.debug(doc_content.to_xml)
            doc_content.remove_namespaces!
    		valore = 'ko'
            block = doc_content.xpath("//Errore")
            if !block.blank?
                Spider.logger.error("Si è verificato un errore durante il parsing della risposta Soap: #{block.search('//Errore').text }")
                valore = 'ko'
            elsif !response.http_error? && !response.soap_fault?
            	@modulo.protocollo_numero = doc_content.search('//NumeroProtocollo').text
            	@modulo.protocollo_data = DateTime.parse(doc_content.search('//DataProtocollo').text)
                valore = 'ok'
            end
            return valore
		end

		def persona_giuridica?
			if !@modulo.utente.ditta.blank? && !@modulo.utente.ditta.ragione_sociale.blank?
				true
			else
				false
			end
		end

		def nominativo
			if persona_giuridica?
				@modulo.utente.ditta.ragione_sociale 
			else
				"#{@modulo.utente.cognome} #{@modulo.utente.nome}"
			end
		end

		def civilia_req
			builder = Nokogiri::XML::Builder.new(:encoding => 'UTF-8') { |xml|
				xml.Segnatura do
					xml.Intestazione do
						xml.Identificatore do
							if persona_giuridica?
								xml.CodiceAmministrazione @modulo.utente.ditta.partita_iva #Spider.conf.get('ente.codice_ipa')
							else
								xml.CodiceAmministrazione @modulo.utente.codice_fiscale
							end
							xml.CodiceAOO Spider.conf.get('ente.codice_aoo')
							xml.CodiceRegistro nil #'REGISTRO UFFICIALE'
							xml.NumeroRegistrazione "%07d" % @modulo.id #Numero Istanza a Portale
							xml.DataRegistrazione self.class.facet_date(Date.today())
						end
						xml.Origine{
							if !@modulo.utente.pec.blank?
								xml.IndirizzoTelematico @modulo.utente.pec
							elsif !@modulo.utente.email.blank?
								xml.IndirizzoTelematico @modulo.utente.email
							end
							xml.Mittente{
								xml.Amministrazione{
									if persona_giuridica? #!@modulo.utente.ditta.blank? && !@modulo.utente.ditta.ragione_sociale.blank? #CASO RAGIONE SOCIALE
										xml.Denominazione nominativo
										xml.CodiceAmministrazione @modulo.utente.ditta.partita_iva
										# xml.UnitaOrganizzativa{
										# 	xml.Denominazione 'Persona giuridica'
										# 	xml.Identificativo  @modulo.utente.ditta.partita_iva
										# }
										xml.Persona{
											xml.Nome @modulo.utente.nome
											xml.Cognome @modulo.utente.cognome
											xml.CodiceFiscale @modulo.utente.codice_fiscale
										}
										xml.IndirizzoPostale{
											xml.Indirizzo{
												xml.Toponimo(@modulo.utente.ditta.indirizzo_azienda, 'dug' => '')
												xml.Civico @modulo.utente.ditta.civico_azienda
												xml.CAP @modulo.utente.ditta.cap_azienda
												xml.Comune @modulo.utente.ditta.comune_azienda
												xml.Provincia @modulo.utente.ditta.provincia_azienda
												xml.Nazione (@modulo.utente.ditta.provincia_azienda.blank? ? nil : 'IT') #Non ho le info della ditta se Italiana o Estera
											}
										}
										if !@modulo.utente.ditta.pec_azienda.blank?
											xml.IndirizzoTelematico @modulo.utente.ditta.pec_azienda
										elsif !@modulo.utente.ditta.email_azienda.blank?
											xml.IndirizzoTelematico @modulo.utente.ditta.email_azienda
										end
										xml.Telefono @modulo.utente.ditta.telefono_azienda
										xml.Fax @modulo.utente.ditta.fax_azienda
									else #CASO PERSONA
										xml.Denominazione nominativo
										xml.CodiceAmministrazione @modulo.utente.codice_fiscale
										# xml.UnitaOrganizzativa{
										# 	xml.Denominazione 'Persona fisica'
										# 	xml.Identificativo  @modulo.utente.codice_fiscale
										# }
										xml.Persona{
											xml.Nome @modulo.utente.nome
											xml.Cognome @modulo.utente.cognome
											xml.CodiceFiscale @modulo.utente.codice_fiscale
										}
										xml.IndirizzoPostale{
											#xml.Toponimo @modulo.utente.indirizzo_residenza
											xml.Toponimo(@modulo.utente.indirizzo_residenza, 'dug' => '')
											xml.Civico @modulo.utente.civico_residenza
											xml.CAP @modulo.utente.cap_residenza
											xml.Comune @modulo.utente.comune_residenza
											xml.Provincia @modulo.utente.provincia_residenza
											xml.Nazione (@modulo.utente.provincia_residenza.blank? ? nil : 'IT') #Non ho le info della persona se Italiana o Estera
										}
										if !@modulo.utente.pec.blank?
											xml.IndirizzoTelematico @modulo.utente.pec
										elsif !@modulo.utente.email.blank?
											xml.IndirizzoTelematico @modulo.utente.email
										end
										xml.Telefono @modulo.utente.telefono || @modulo.utente.cellulare
										xml.Fax @modulo.utente.fax
									end
								}
								xml.AOO{
									xml.Denominazione nominativo #((@modulo.utente.ditta.blank? || @modulo.utente.ditta.ragione_sociale.blank?) ? "#{@modulo.utente.cognome} #{@modulo.utente.nome}" : @modulo.utente.ditta.ragione_sociale)
									xml.CodiceAOO Spider.conf.get('ente.codice_aoo')
								}
							} 
						}
						xml.Destinazione{
							xml.IndirizzoTelematico Spider.conf.get('moduli.mail_invio_moduli')
							xml.Destinatario do
								xml.Amministrazione{
									xml.Denominazione Spider.conf.get('ente.nome')
									xml.CodiceAmministrazione Spider.conf.get('ente.codice_ipa_dest')
									unless @modulo.tipo_modulo.settore.blank?
										xml.UnitaOrganizzativa{
											xml.Denominazione @modulo.tipo_modulo.settore.nome
											xml.Identificativo @modulo.tipo_modulo.settore.id
											xml.IndirizzoPostale{
												xml.Denominazione Spider.conf.get('ente.indirizzo')	
											}
											xml.IndirizzoTelematico Spider.conf.get('moduli.mail_invio_moduli')
										}
									end
								}
							end
						}
						xml.Oggetto "Istanza per: #{@modulo.tipo_modulo.nome} nr. #{@modulo.id} del #{@modulo.inviato.lformat(:short)} inviata da #{nominativo}"
					end
					xml.Descrizione{
						xml.Documento('nome' => @attachments[-1][:filename], 'tipoRiferimento' => 'MIME') do
							xml.TitoloDocumento @modulo.tipo_modulo.nome
						end
						xml.Allegati{
							i = 1
							@attachments[0..-2].each do |a|
								xml.Documento('id' => "I#{i}", 'nome' => a[:filename], 'tipoRiferimento' => 'MIME') do
									xml.TitoloDocumento 'Allegato'
								end
								i += 1
							end
						} if @attachments.count > 1
					} if @attachments
				end
			}
			return builder.to_xml(:save_with => Nokogiri::XML::Node::SaveOptions::AS_XML).strip
		end

		def civilia_rsp(response)
			#DA FARE nell'eventualità che necessiti reperire la risposta di acccettazione della pec
		end

		def folium_test_login_req
			builder = Nokogiri::XML::Builder.new(:encoding => 'UTF-8') { |xml|
				folium_auth(xml)
			}
			return builder.to_xml(:save_with => Nokogiri::XML::Node::SaveOptions::NO_DECLARATION).strip #(:save_with => Nokogiri::XML::Node::SaveOptions::AS_XML  | Nokogiri::XML::Node::SaveOptions::NO_DECLARATION).strip
		end

		def folium_protocolla_req
			#Uso il Fragment per avere 2 rootnode all'interno del builder
			@doc = Nokogiri::XML::DocumentFragment.parse ""
			builder = Nokogiri::XML::Builder.with(@doc) { |xml|
					folium_auth(xml)
					folium_documento_protocollato(xml)
			}
			return @doc.to_xml
		end

		def folium_protocolla_rsp(response)
			if response.soap_fault?
				raise ServizioNonDisponibile.new(response.soap_fault,'Errore SOAP')
			elsif response.http_error?
				raise ServizioNonDisponibile.new(response.http_error,'Errore HTTP di comunicazione con il service SOAP')
			else
				case response.xpath('//codiceEsito').text
				when '000'
					@modulo.protocollo_numero = response.xpath('//numeroProtocollo').text
            		@modulo.protocollo_data = DateTime.parse(response.xpath('//dataProtocollo').text)
            		@modulo.protocollo_id = response.xpath('//id').text
					return 'ok'
				when '107'
					raise ErroreApplicativo.new(response.xpath('//codiceEsito'),'Errore nella fase di login con il service SOAP')
				when '108'
					raise ErroreApplicativo.new(response.xpath('//codiceEsito'),'Errore interno del service SOAP')
				end
			end
		end

		def folium_inserisci_allegato(att)
			#Uso il Fragment per avere 2 rootnode all'interno del builder
			@doc = Nokogiri::XML::DocumentFragment.parse ""
			builder = Nokogiri::XML::Builder.with(@doc) { |xml|
					folium_auth(xml)
					folium_allegato(xml, att)
			}
			return @doc.to_xml 
		end

		def folium_inserisci_allegato_rsp(response)
			if response.soap_fault?
				raise ServizioNonDisponibile.new(response.soap_fault,'Errore SOAP')
			elsif response.http_error?
				raise ServizioNonDisponibile.new(response.http_error,'Errore HTTP di comunicazione con il service SOAP')
			else
				case response.xpath('//codiceEsito').text
				when '000'
					return 'ok'
				when '107'
					raise ErroreApplicativo.new(response.xpath('//codiceEsito'),'Errore nella fase di login con il service SOAP')
				when '108'
					raise ErroreApplicativo.new(response.xpath('//codiceEsito'),'Errore interno del service SOAP')
				end
			end
		end

		private

		def folium_auth(xml)
			ente_conf = Spider.conf.get('ente')
			proto_conf = Spider.conf.get('moduli.protocollo')
			xml.in0 do
				xml.applicazione 'Civilia'
				xml.ente ente_conf['nome']
				xml.aoo ente_conf['codice_aoo']
				unless proto_conf['auth'].blank?
					xml.username proto_conf['auth']['user'] 
					xml.password proto_conf['auth']['pwd']
				end
			end
			#return xml.to_xml(:save_with => Nokogiri::XML::Node::SaveOptions::NO_DECLARATION).strip
		end

		def folium_auth_rsp(response)
			if response.soap_fault?
				raise ServizioNonDisponibile.new(response.soap_fault,'Errore SOAP')
			elsif response.http_error?
				raise ServizioNonDisponibile.new(response.http_error,'Errore HTTP di comunicazione con il service SOAP')
			else
				case response.xpath('//codiceEsito').text
				when '000'
					return 'Operazione eseguita senza errori'
				when '107'
					raise ErroreApplicativo.new(response.xpath('//codiceEsito'),'Errore nella fase di login con il service SOAP')
				when '108'
					raise ErroreApplicativo.new(response.xpath('//codiceEsito'),'Errore interno del service SOAP')
				end
			end
		end

		def folium_documento_protocollato(xml)
			xml.in1({"xmlns:soapenc" =>"http://schemas.xmlsoap.org/soap/encoding/"}) do
				xml.contenuto Base64.strict_encode64(@attachments[0][:content]) unless @attachments.blank?
				xml.dataDocumento DateTime.parse(@modulo.confermato.to_s).strftime("%Y-%m-%dT%H:%M:%S.%L%:z")
				#xml.dataProtocollo nil #@modulo.inviato.lformat(:short)
				#xml.codiceEsito nil
				#xml.id nil
				if @attachments.blank?
					xml.isContenuto false
				else
					xml.isContenuto true
				end
				xml.mittentiDestinatari({"xsi:type" => "impl:ArrayOf_tns3_MittenteDestinatario", "soapenc:arrayType" => "type:MittenteDestinatario[]"}) do
					xml.mittentiDestinatari({"xsi:type" => "ns2:MittenteDestinatario"}) do
						xml.codiceMezzoSpedizione
						xml.denominazione nominativo
						#xml.email nil
						#xml.indirizzo nil
						#xml.invioPC nil
						#xml.codiceMittenteDestinatario nil
						#xml.citta nil
						unless persona_giuridica?
							xml.cognome @modulo.utente.cognome
							xml.nome @modulo.utente.nome
							xml.tipo 'F'
						else
							xml.tipo 'G'
						end
					end
				end
				xml.nomeFileContenuto @attachments[0][:filename] unless @attachments.blank?
				#xml.note nil
				#xml.numeroProtocollo nil
				#xml.numeroProtocolloEsterno nil
				xml.oggetto "Invio Dematerializzato #{@modulo.tipo_modulo.nome} nr. #{@modulo.id} del #{@modulo.inviato.lformat(:short)} da #{nominativo}"
				#xml.registro nil
				#xml.timbro false
				xml.tipoProtocollo 'I'
				xml.ufficioCompetente 
				#DA RIVEDERE SE L'ENTE VUOLE ASSOLUTAMENTE ANCHE LA FASCICOLAZIONE
				#QUINDI SI TRATTEREBBE DI AGGIUNGERE LE VOCI DI TITOLARIO A LIVELLO DI TIPO_MODULO
				# xml.vociTitolario({'xsi:type' => 'env:Array'}) do
				# 	xml.vociTitolario '01'
				# 	xml.vociTitolario '39'
				# 	xml.vociTitolario '03'
				# end
			end
			#return xml.to_xml(:save_with => Nokogiri::XML::Node::SaveOptions::NO_DECLARATION).strip
		end

		def folium_allegato(xml, att)
			xml.in1 do
				xml.contenuto Base64.strict_encode64(att[:content])
				xml.descrizione att[:filename].gsub(att[:filetype], '')
				xml.idProfilo @modulo.protocollo_id #ID interno di avvenuta protocollazione 
				xml.nomeFile att[:filename]
			end
		end


		class Eccezione < RuntimeError
        end
        
        class ErroreApplicativo < Eccezione
        end

        class ServizioNonDisponibile < Eccezione
        end

        class ParametriNonImpostati < Eccezione
        end 

        class SoapError < Eccezione
        end

	end

end

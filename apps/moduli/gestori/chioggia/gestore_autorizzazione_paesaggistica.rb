# -*- encoding : utf-8 -*-
module Moduli

	class GestoreAutorizzazionePaesaggistica

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        #carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)

	        #carico i dati specifici per questo modulo
			hsh_output['titolarita_intervento'] = {}
			chiave_tag = hash_conf['configurazione']['metadato1']
			hsh_output['titolarita_intervento']['titolo_persona'] = ( (!hash_pratica['dati_aggiuntivi'].blank? && !hash_pratica['dati_aggiuntivi'][chiave_tag].blank?) ? hash_pratica['dati_aggiuntivi'][chiave_tag] : chiave_tag )


			hsh_output['descrizione_intervento'] = hash_pratica['descrizione']

			hsh_output['tipo_interv_sint'] = {}
			hsh_output['tipo_interv_sint']['tipo_interv_nuovo_rist'] = {}
			hsh_output['tipo_interv_sint']['tipo_interv_nuovo_rist']['descrizione_altro_intervento'] = hash_pratica['descrizione']

			hsh_output['tab_strum_urban'] = {}
			#trasformo l'hash in array se ho un solo elemento zona
			if hash_pratica['zona'].is_a?(Hash)
				hash_pratica['zona'] = [hash_pratica['zona']]
			end
			hsh_output['tab_strum_urban']['zona_1'] = hash_pratica['zona'][0]['nome_zona'] if !hash_pratica['zona'].blank? && !hash_pratica['zona'][0].blank?
			hsh_output['tab_strum_urban']['zona_2'] = hash_pratica['zona'][1]['nome_zona'] if !hash_pratica['zona'].blank? && !hash_pratica['zona'][1].blank?
			hsh_output['tab_strum_urban']['zona_3'] = hash_pratica['zona'][2]['nome_zona'] if !hash_pratica['zona'].blank? && !hash_pratica['zona'][2].blank?
			#trasformo l'hash in array se ho un solo elemento vincolo
			if hash_pratica['vincolo'].is_a?(Hash)
				hash_pratica['vincolo'] = [hash_pratica['vincolo']]
			end
			hsh_output['tab_strum_urban']['vincolo_1'] = hash_pratica['vincolo'][0]['nome_vincolo'] if !hash_pratica['vincolo'].blank? && !hash_pratica['vincolo'][0].blank?
			hsh_output['tab_strum_urban']['vincolo_2'] = hash_pratica['vincolo'][1]['nome_vincolo'] if !hash_pratica['vincolo'].blank? && !hash_pratica['vincolo'][1].blank?
			hsh_output['tab_strum_urban']['vincolo_3'] = hash_pratica['vincolo'][2]['nome_vincolo'] if !hash_pratica['vincolo'].blank? && !hash_pratica['vincolo'][2].blank?
			hsh_output['relazione_paesaggistica_titolare'] = {}
			hsh_output['relazione_paesaggistica_titolare'] = hsh_output['titolare_1'] unless hsh_output['titolare_1'].blank?
			hsh_output['asseverazione'] = hsh_output['relazione_paesaggistica_progettista'] = {}
			hsh_output['asseverazione'] = hsh_output['relazione_paesaggistica_progettista'] = hsh_output['progettista_1'] unless hsh_output['progettista_1'].blank? 
			hsh_output['relazione_paesaggistica_ot'] = {}
			hsh_output['relazione_paesaggistica_ot'] = hsh_output['oggetto_territoriale_1'] unless hsh_output['oggetto_territoriale_1'].blank?
			
			hsh_output
		end		

		def self.controlla_dati_modulo(dati_post)
			errori = []
				
			#converto gli errori in json per passarlo al js
			errori.blank? ? nil : errori.to_json
		end

		#ritorna un array con gli url degli allegati
		def self.get_url_allegati(id_pratica)
			res = Moduli::Funzioni.url_allegati(id_pratica)
			res
		end

		def self.set_scene_widget(scene, hash_dati=nil)
			unless hash_dati.blank?
				scene.id_pratica = hash_dati['id_pratica']
				scene.data_pratica = hash_dati['data_pratica']
			end
			scene			
		end

	end

end

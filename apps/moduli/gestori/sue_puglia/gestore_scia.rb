# -*- encoding : utf-8 -*-
module Moduli

	class GestoreScia

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        
			#carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)

			hsh_output['titolarita_intervento'] = {}
			chiave_tag = hash_conf['configurazione']['metadato1']
			hsh_output['titolarita_intervento']['titolo_persona'] = "#{hash_conf['dati_aggiuntivi'][chiave_tag]}" unless hash_conf['dati_aggiuntivi'].blank?

			hsh_output['descrizione_intervento'] = hash_pratica['descrizione']
			
			hsh_output['tipo_interv_sint'] = {}
			hsh_output['tipo_interv_sint']['tipo_interv_nuovo_rist'] = {}
			hsh_output['tipo_interv_sint']['tipo_interv_nuovo_rist']['descrizione_tipo_interv_sint_9'] = hash_pratica['descrizione']

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
			
			hsh_output
		end		


		def self.controlla_dati_modulo(dati_post)
			errori = []
			#i valori della tabella in tab_dati_geom devono essere numeri
			dati_post[:tab_dati_geom].each_pair{ |chiave, valore|
				errori << { 'campo' => chiave.to_s, 'msg' => 'Il campo deve essere un numero' } if !valore.blank? && valore.to_f == 0
			}
			#converto gli errori in json per passarlo al js
			errori.blank? ? nil : errori.to_json
		end

		#ritorna un array con gli url degli allegati
		def self.get_url_allegati(id_pratica)
			res = Moduli::Funzioni.url_allegati(id_pratica)
			res
		end


	end

end

# -*- encoding : utf-8 -*-
module Moduli

	class GestoreDia

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
			
			hsh_output
		end		


		def self.controlla_dati_modulo(dati_post)
			errori = []

			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:titolarita_intervento][:altro_titolo], 'altro_titolo', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:pres_denuncia][:tip_pres_denuncia_12], 'tip_pres_denuncia_12', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:qualificazione_intervento][:tipo_intervento], 'tipo_intervento', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:opere_e_modifiche][:tipo_variazione], 'tipo_variazione', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:contributi_costruzione][:tipo_interv_realiz], 'tipo_interv_realiz', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:tecnici_incaricati][:tipi_tecnici], 'tipi_tecnici', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:impresa_esecutrice][:tipo_impresa], 'tipo_impresa', 'Selezionare una opzione.')
			#ASSEVERAZIONE
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:tipo_interv_sint][:tipo_intervento_asseverazione], 'tipo_intervento_asseverazione', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:bar_archit][:tipo_bar_archit], 'tipo_bar_archit', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:sicu_impianti][:tipo_sicu_impianti], 'tipo_sicu_impianti', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:consumi_energ][:tipo_consumi_energ], 'tipo_consumi_energ', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:consumi_energ][:rin_tipo_consumi_energ], 'rin_tipo_consumi_energ', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:inqui_acustico][:tipo_inqui_acustico], 'tipo_inqui_acustico', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:prod_materiali][:tipo_prod_materiali], 'tipo_prod_materiali', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:prev_incendi][:tipo_prev_incendio_13], 'tipo_prev_incendio_13', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:prev_incendi][:tipo_prev_incendio_46], 'tipo_prev_incendio_46', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:amianto][:tipo_amianto], 'tipo_amianto', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:igienico_san][:tipo_igienico_san], 'tipo_igienico_san', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:int_strutt][:tipo_int_strutt_12], 'tipo_int_strutt_12', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:int_strutt][:tipo_int_strutt_36], 'tipo_int_strutt_36', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:urbaniz_amb][:tipo_urbanizzaz_amb], 'tipo_urbanizzaz_amb', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:scarichi_idrici][:tipo_scarichi_idrici], 'tipo_scarichi_idrici', 'Selezionare una opzione.')			
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:aut_paesag][:tipo_aut_paesag_13], 'tipo_aut_paesag_13', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:parere_sopr][:tipo_parere_sopr], 'tipo_parere_sopr', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:area_prot][:tipo_area_prot], 'tipo_area_prot', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:vinc_idro][:tipo_vinc_idro], 'tipo_vinc_idro', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:vinc_idraulico][:tipo_vinc_idraulico], 'tipo_vinc_idraulico', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:natura][:tipo_natura2000], 'tipo_natura2000', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:risp_cim][:tipo_risp_cim], 'tipo_risp_cim', 'Selezionare una opzione.')
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio(dati_post[:inc_rilev][:tipo_inc_rilev], 'tipo_inc_rilev', 'Selezionare una opzione.')
			
			#i valori della tabella in tab_dati_geom devono essere numeri
			dati_post[:tab_dati_geom].each_pair{ |chiave, valore|
				errori << { 'campo' => chiave.to_s, 'msg' => 'Il campo deve essere un numero' } if !valore.blank? && valore.to_f == 0
			}
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

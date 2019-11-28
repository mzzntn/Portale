# -*- encoding : utf-8 -*-
module Moduli

	class GestoreCom5DomandaParchiCommerciali

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        #carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)
	  		unless hash_pratica['dati_aggiuntivi'].blank?
				hsh_output["sezione_a"] = {}
				settmerc = hash_pratica['dati_aggiuntivi']['SETTMERC']
				unless settmerc.blank?
					hsh_output["sezione_a"].merge!({ "sup_vendita" => {settmerc => {'check' => '1'} }
												 })
				end
				special = hash_pratica['dati_aggiuntivi']['SPECIAL']
				unless special.blank?
					hsh_output["sezione_a"].merge!({ "tab_speciali_a" => {special => {'check' => '1'} }
												 })
				end
				hsh_output["sezione_b"] = {}
				settmerc = hash_pratica['dati_aggiuntivi']['SETTMERC']
				unless settmerc.blank?
					hsh_output["sezione_b"].merge!({ "nuova_sup_vendita_b1" => {settmerc => {'check' => '1'} },
													 "nuova_sup_vendita_b2" => {settmerc => {'check' => '1'} }
												 })
				end
				special = hash_pratica['dati_aggiuntivi']['SPECIAL']
				unless special.blank?
					hsh_output["sezione_b"].merge!({ "tab_speciali_b1" => {special => {'check' => '1'} },
													 "tab_speciali_b2" => {special => {'check' => '1'} }
												 })
				end

			end

			#metri quadri superfici di vendita 
			#impostare i mp in
			#settore_merce_a_1_mq,settore_merce_a_2_mq,settore_merce_a_3_mq,settore_merce_a_4_mq,sup_vendita_tot_a_mq
			#settore_merce_b_1_mq,settore_merce_b_2_mq,settore_merce_b_3_mq,settore_merce_b_4_mq,sup_vendita_tot_b_mq
			
			if !hash_pratica['dati_aggiuntivi'].blank? && !hash_pratica['dati_aggiuntivi']['Alimentare_e_misto'].blank?
				alimentare_e_misto = hash_pratica['dati_aggiuntivi']['Alimentare_e_misto'].to_i
				non_alimentare_beni_persona = hash_pratica['dati_aggiuntivi']['Non_alimentare_beni_persona'].to_i
				non_alimentare_altri_beni = hash_pratica['dati_aggiuntivi']['Non_alimentare_altri_beni'].to_i
				non_alimentare_beni_a_basso_impatto = hash_pratica['dati_aggiuntivi']['Non_alimentare_beni_a_basso_impatto'].to_i
				totale = alimentare_e_misto + non_alimentare_beni_persona + non_alimentare_altri_beni + non_alimentare_beni_a_basso_impatto
				
				hsh_output["sezione_comune_a"] ||= {}
				hsh_output["sezione_comune_a"]["SETTMERC"] ||= {}
				hsh_output["sezione_comune_a"]["SETTMERC"]['settore_merce_a_1_mq'] = alimentare_e_misto
				hsh_output["sezione_comune_a"]["SETTMERC"]['settore_merce_a_2_mq'] = non_alimentare_beni_persona
				hsh_output["sezione_comune_a"]["SETTMERC"]['settore_merce_a_3_mq'] = non_alimentare_altri_beni
				hsh_output["sezione_comune_a"]["SETTMERC"]['settore_merce_a_4_mq'] = non_alimentare_beni_a_basso_impatto
				hsh_output["sezione_comune_a"]["SETTMERC"]['sup_vendita_tot_a_mq'] = totale


			end
	        
			hsh_output
		end		


		def self.controlla_dati_modulo(dati_post)
			errori = []
			#se checked il checkbox padre (passato da pratiche) controllo che almeno uno dei figli sia ceccato
			errori << Moduli::Funzioni.controlla_checkbox_obbligatorio_secondo_livello(dati_post[:scia_trasmessa][:scia_tipo], 'scia_tipo', 'Selezionare una opzione.')
			errori = errori.compact
			#converto gli errori in json per passarlo al js
			errori.blank? ? nil : errori.to_json
		end

		#ritorna un array con gli url degli allegati
		def self.get_url_allegati(id_pratica)
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

		def self.set_scene_widget(scene, hash_dati=nil)
			scene.id_modulo_com = '5'
			scene.nome_modulo = 'Parco Commerciale'
			#passo il codice dell'evento per abilitare la sezione
			unless hash_dati.blank?
				case hash_dati['codice_evento']
					when 'RINN'
					   scene.sezione_attiva = 'A'
					when 'VAR'
					   scene.sezione_attiva = 'B'
					else
						scene.sezione_attiva = nil
					end
			end
			scene			
		end


	end

end

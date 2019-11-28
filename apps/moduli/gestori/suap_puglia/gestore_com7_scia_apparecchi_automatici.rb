# -*- encoding : utf-8 -*-
module Moduli

	class GestoreCom7SciaApparecchiAutomatici

		def self.carica_dati_modulo(params, hash_conf)
			path = File.join(Spider.paths[:data], 'uploaded_files', 'moduli', 'xml_pratiche', params['id_pratica'], params['id_pratica']+'.xml')
	        #carico i dati comuni con la funzione carica_xml_pratiche
	        hsh_output, hash_pratica = Moduli::Funzioni.carica_xml_pratiche(path, hash_conf)
	        #setto i metadati provenienti da pratiche sul modulo, modificabili
	        unless hash_pratica['dati_aggiuntivi'].blank?
				hsh_output["sezione_comune"] = {}
				hsh_output["sezione_c"] = {}
				hsh_output["sezione_d"] = {}
				settmerc = hash_pratica['dati_aggiuntivi']['SETTMERC']
				unless settmerc.blank?
					hsh_output["sezione_comune"].merge!({ "settore_merce" => {settmerc => {'check' => '1'} }
												 })
					hsh_output["sezione_c"].merge!({ "settore_merce_sez_c" => {settmerc => {'check' => '1'} },
													 "settore_merce_trasferito" => {settmerc => {'check' => '1'} },
													 "settore_merce_trasferito_c2" => {settmerc => {'check' => '1'} }
												 })
					hsh_output["sezione_d"].merge!({ "settore_merce_d" => {settmerc => {'check' => '1'} }
												 })

				end
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
			scene.id_modulo_com = '7'
			scene.nome_modulo = 'Forme speciali di vendita al dettaglio <br/ > COMMERCIO PRODOTTI PER MEZZO DI APPARECCHI AUTOMATICI '
			#passo il codice dell'evento per abilitare la sezione
			unless hash_dati.blank?
				case hash_dati['codice_evento']
					when 'AP'
					   scene.sezione_attiva = 'A'
					when 'SUB'
					   scene.sezione_attiva = 'B'
					when 'VAR'
					   scene.sezione_attiva = 'C'
					when 'CESS'
					   scene.sezione_attiva = 'D'
					when 'AGG'
						scene.sezione_attiva = 'E'
					else
						scene.sezione_attiva = nil
					end
			end
			scene			
		end


	end

end

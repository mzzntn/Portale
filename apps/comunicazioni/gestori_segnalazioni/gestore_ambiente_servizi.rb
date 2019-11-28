# -*- encoding : utf-8 -*-
module Comunicazioni
	module GestoreSegnalazioni
		require 'rmagick'

		class AmbienteServizi

			include GestoreSegnalazioni
			
			gestore_segnalazioni( 
	            :id => "ambiente_servizi",
	            :nome => "Ambiente Servizi",
	            :immagine => "ambiente_servizi.png"
	        )

			def inserisci_segnalazione(params)
				begin
					segnalazione = Comunicazioni::Segnalazione.new
					#salvo i parametri diretti
					segnalazione.nome = params['nome']
					segnalazione.cognome = params['cognome']
					segnalazione.email = params['email']
					segnalazione.indirizzo = params['indirizzo']
					segnalazione.testo_segnalazione = params['testo_segnalazione']
					#salvo la foto su file system con ridimensione
					save_dir = Spider::SecureRandom.hex(10)
                    save_dir_path = Spider.paths[:data]+'/uploaded_files/segnalazioni/'+save_dir+"/"
                    #creo la cartella per il salvataggio dell'immagine con l'hash e la cartella img_resized per le immagini piccole
                    unless File.directory?(save_dir_path)
                        FileUtils.mkdir_p(save_dir_path) 
                    end
                    nome_file = params['foto'].filename.to_s
                    path_immagine = File.join(save_dir_path, nome_file)
                    #scrivo il file originale
                    File.open(path_immagine, "wb") { |f| f.write(params['foto'].read) }
                    #se viene caricata una immagine la ridimensiono
                    unless (params['foto'].content_type =~ /image/).blank?
                    	#creo la cartella img_resized e copio il file che se serve viene ridimensionato
	                    path_immagine_mini = File.join(save_dir_path, 'img_resized', nome_file)
	                    FileUtils.mkdir_p(save_dir_path+'img_resized') unless File.directory?(save_dir_path+'img_resized')
	                    FileUtils.copy(path_immagine,path_immagine_mini)
	                    #ridimensiono le immagini e le salvo nella cartella img_resized
	                    img = Magick::Image::read(path_immagine_mini).first
	                    x_resolution = Spider.conf.get('segnalazioni.max_risoluzione_immagini')[0].to_i
	                    y_resolution = Spider.conf.get('segnalazioni.max_risoluzione_immagini')[1].to_i
	                    if img.columns > x_resolution || img.rows > y_resolution
	                        mini = img.resize_to_fit(x_resolution, y_resolution)
	                    else 
	                        mini = img
	                    end
	                    mini.write path_immagine_mini
                    end
                    segnalazione.foto = File.join(save_dir,nome_file)
					segnalazione.latitudine = params['latitudine']
					segnalazione.longitudine = params['longitudine']
					segnalazione.tipologia_richiesta = params['tipologia_richiesta']
					segnalazione.token_device = params['token_device']
					segnalazione.gestore_segnalazione = params['gestore_segnalazione']
					#salvo gli extra params: 
					#zona, tipologia rifiuto
					hash_extra_params = {}
					hash_extra_params['zona'] = params['zona']
					hash_extra_params['tipologia_rifiuto'] = params['tipologia_rifiuto']
					hash_extra_params['comune'] = params['comune']
					segnalazione.extra_params = hash_extra_params.to_json
					segnalazione.save
				rescue Exception => e
					Spider.logger.error("Errore nel salvataggio della segnalazione: #{e.message} \n\n #{e.backtrace}")
					return false	
				end
				return true
			end



		end



	end
end

# -*- encoding : utf-8 -*-
module Comunicazioni; module CanaleComunicazione

    class Cms < Spider::PageController
        #includo il modulo CanaleComunicazione
        include CanaleComunicazione
    
        
        canale_comunicazione( 
            :id => "cms",
            :nome => "Sito",
            :immagine => "cms.png"
        )

        def self.canale_attivo(request_da_chiamante)
            !defined?(Cms).blank?
        end

        def self.pubblica_comunicazione(comunicazione, session_user=nil)
        	#ricavo l'id della newslist passata
	        extra_params_hash = JSON.parse(comunicazione.extra_params)
	        cms_extra_params = extra_params_hash['cms']
	        id_newslist = cms_extra_params['newslist'].to_i
        	news_list_selezionata = ::Cms::NewsList.load(:id => id_newslist)
        	
        	id_news = cms_extra_params['news']
        	#controllo se la news e ancora presente nel cms
        	news_presente = ::Cms::News.load(:id => id_news)

        	id_pagina = cms_extra_params['pagina']
        	pagina_presente = ::Cms::Page.load(:id => id_pagina)

        	#controllo se sono in modifica
        	azione = 'nuova'
        	if comunicazione.canali_pubblicazione.include?('cms') && comunicazione.stato.id == 'pubblicata'
        		azione = 'modifica'
        	end
        	
        	#nuova o modifica cms::news
			if azione == 'nuova' || news_presente.nil?
	    		news = ::Cms::News.new(:title => comunicazione.titolo, 
	            				:template => ( comunicazione.immagine.blank? ? 'no_img' : 'default'), 
	            				:publish_from => comunicazione.data_da,
	            				:publish_until => comunicazione.data_a,
	            				:created_at => Date.today)
	    		news.save
	    	else
	    		#modifica della news
	    		id_news = cms_extra_params['news']
	    		news = ::Cms::News.load(:id => id_news)
	    		news.template = ( comunicazione.immagine.blank? ? 'no_img' : 'default')
	    		news.title = comunicazione.titolo
	    		news.publish_from = comunicazione.data_da
	    		news.publish_until = comunicazione.data_a
	    	end

	    	#salvo l'id della news negli extra params se nuova o l'ho ricreata
	    	cms_extra_params['news'] = news.id if ( azione == 'nuova' || ( azione == 'modifica' && news_presente.nil? ))

	    	pagina = nil
	    	immagine_in_images = nil
	    	Spider::Model.in_unit do |uow|
	    		begin
		    		pagina = Spider::Model.identity_mapper.put(pagina)
		    		news_presente = Spider::Model.identity_mapper.put(news_presente)
		    		pagina_presente = Spider::Model.identity_mapper.put(pagina_presente)
		    		news_list_selezionata = Spider::Model.identity_mapper.put(news_list_selezionata)
		    		

		    		#creo la pagina nella sezione 'News'
		    			
		    			sezione_pagina = ::Cms::Section.load(:label => 'News')
		    			if sezione_pagina.nil?
							sezione = ::Cms::Section.new 
							sezione.name = 'News'
							sezione.label = 'News'
							sezione.save
							sezione_pagina = sezione
						end	
						#se non presente il menu lo creo
						menu = ::Cms::Menu.load(:title => sezione_pagina.name) unless sezione_pagina.nil?
						if menu.nil? && sezione_pagina
							menu = ::Cms::Menu.create(:title => sezione_pagina.name, :section => sezione_pagina)
							##NON LEGO IL MENU A QUELLO RADICE PER NON VISUALIZZARLO SUL SITO
							# #carica il menu radice
							# menu_homepage_sx = Menu.load(:identifier => 'left_menu')
							# #salvo il nuovo menu come sottomenu di quello generale
							# menu_homepage_sx.submenus << menu
							# menu_homepage_sx.save
						end
						#nuova o modifica cms::page
						if azione == 'nuova' || pagina_presente.nil?
							uuid_pag = Spider::DataTypes::UUID.generate
							#se presente una pagina con stessa label aggiungo datetime
							label = ::Cms::Page.name_to_label(comunicazione.titolo)
							pagina_con_label_presente  = ::Cms::Page.load(:label => label)
							if pagina_con_label_presente.nil?
								pagina = ::Cms::Page.create(:title => comunicazione.titolo, :uuid => uuid_pag, :label => label, :template => "default", :site => ::Cms::Site.load[:id], :section => sezione_pagina)
							else
								label_nuova = label+DateTime.now.strftime('%Y%m%d%H%M%S')
								pagina = ::Cms::Page.create(:title => comunicazione.titolo, :uuid => uuid_pag, :label => label_nuova, :template => "default", :site => ::Cms::Site.load[:id], :section => sezione_pagina)
							end
						else
							pagina = pagina_presente
							uuid_pag = pagina.uuid
							pagina.save
						end						

		    		#creo la news
		    			#se ho caricato una immagine la salvo in db
		    			immagine = news.contents_by_id[:img1]

		    			if (azione == 'nuova' || immagine.nil? ) && ( !comunicazione.dir_immagine.blank? && !comunicazione.immagine.blank? ) 
		    				immagine = ::Cms::Image.new()
				    		immagine.identifier = 'img1'
				    		immagine.content_type = 'Cms::Image'
				    		immagine.parent_container = news
				    	else
				    		#modifico l'immagine se sto modificando la comunicazione
				    		#cancello la vecchia Spider::Images::Image
				    		immagine.img.delete unless immagine.blank? || immagine.img.blank?
				    	end    	


		    			if !comunicazione.dir_immagine.blank? && !comunicazione.immagine.blank?
		    				path_image_comunicazioni = Spider.paths[:data]+'/uploaded_files/comunicazioni/'+comunicazione.dir_immagine+"/"
		    				#controllo che nel file system ci sia la cartella delle immagini, se è stata cancellata non posso usarla
		    				if Dir.exist?(path_image_comunicazioni)
						    	#carico l'immagine				    	
					    		#creo una nuova immagine con app Images
					    		image = Spider::Images::Image.new_from_path File.join(path_image_comunicazioni,'img_resized',comunicazione.immagine)
						        image.name = "img news #{news.id} da comunicazioni"
						        image.title = "img news #{news.id} da comunicazioni"
						        image.save
						        
						       #questa variabile mi serve fuori dalla uow per avere l'uuid
						        immagine_in_images = image
						        immagine.img = image
					    		#immagine.save
					    		#salvo l'immagine come un content della news
					    		news.contents_by_id[:img1] = immagine
					    	end
		    			end
			    		#se sono in modifica e rimuovo l'immagine la cancello

			    		#salvo il testo breve della comunicazione come 
			            news.contents_by_id[:text].html = comunicazione.testo_breve+"<br /><br /><a title='Link leggi tutto' href='/cms/pages/#{uuid_pag}'>Leggi tutto >></a>"

			            news.save
			            
			            #se nuovo inserimento inserisco
			            # if azione == 'modifica' && !news_presente.nil?
			            # 	newslist_news_da_cancellare = ::Cms::NewsList::News.load(:news => news)
			            # 	newslist_news_da_cancellare.delete
			            # end
			            if azione == 'nuova' || ( azione == 'modifica' && news_presente.nil? )
			            	newslist_news = ::Cms::NewsList::News.new(:news => news, :news_list => news_list_selezionata, :position => 1)
			            	newslist_news.save
		            	else
			            	#porto in prima posizione la news modificata
			            	cnn = ::Cms::NewsList::News.where{|cms_list_news| (cms_list_news.news == news) & (cms_list_news.news_list == news_list_selezionata)}
			            	unless cnn.blank?
			            		cms_newslist_news_da_aggiornare = cnn[0]
			            		cms_newslist_news_da_aggiornare.position = 1
			            		cms_newslist_news_da_aggiornare.save
			            	end
			            	
			            end
				rescue Exception => exc
	    			messaggio =  "#{exc.message}"
                    messaggio_log = messaggio
                    exc.backtrace.each{|riga_errore| 
                        messaggio_log += "\n\r#{riga_errore}" 
                    } 
                    Spider.logger.error messaggio_log
	    		end             
		    end
	      	#FINE UOW
	      	
	      	unless immagine_in_images.blank?
	      		uuid_image = immagine_in_images.uuid
		      	x_resolution = Spider.conf.get('comunicazioni.max_risoluzione_immagini')[0].to_i
		      	str_content_news = "<div class='contenuti_comunicazione_dettaglio'>
	                <div>
	                    <p><em>#{comunicazione.testo_breve.blank? ? '' : comunicazione.testo_breve}</em></p>
	            	   #{comunicazione.testo}
	                </div>
	                <div class='spacer' style='clear: both;'></div>
	            </div>"
	        else
	        	str_content_news = "<p><em>#{comunicazione.testo_breve}</em></p><p>#{comunicazione.testo}</p>"
	        end

	      	#str_image ="<img alt='Immagine' src='/spider/images/#{uuid_image}' title='Immagine' style='float:left'/>"
	      	if azione == 'nuova'
	  			pagina.contents_by_id[:text][:html] = str_content_news
				pagina.contents_by_id[:text].save
				#salvo l'id della pagina negli extra_params
				cms_extra_params['pagina'] = pagina.id if pagina_presente.nil?
			else
				#salvo qui il titolo, nella uow non funziona
				pagina.title = comunicazione.titolo
				#modifico direttamente il cms::block che ha il testo della comunicazione
				id_blocco = pagina.contents_by_id[:text][:id]
				blocco = ::Cms::Block.load(:id => id_blocco)
				blocco.html = str_content_news
				blocco.save
				#salvo il testo esplicitamente
				pagina.contents_by_id[:text][:html] = blocco.html
				pagina.contents_by_id[:text].save
			end

			#pubblico la pagina
			res = pagina.publish#(:top => true)

			#sovrascrivo gli extra params
			comunicazione.extra_params = extra_params_hash.to_json
			comunicazione.save
	      	
	        if Spider.conf.get('comunicazioni.pubblica_home_cms')	
	            #pubblico la home page del cms
	            uuid_home_qs = ::Cms::Page.where{ |page| (page.label == 'home') }
	            if uuid_home_qs.length > 0
	                home_page = uuid_home_qs[0]
	                home_page.publish(:top => true)
	            end
	        end

	     

	        comunicazione
        end


    end
end;end;

# -*- encoding : utf-8 -*-
module Portal
    
    class Gdpr < Spider::Model::Managed
    	label 'Testi Portale', 'Testi Portale'
        element :titolo, String
        element :informativa, Text, :length => 100000000 #mette un campo longtext 
        element :autorizzazione, Text, :length => 100000000 #mette un campo longtext 
    

        def self.after_sync
	    	#Carico il modello dell'informativa e dell'autorizzazione in db
	        path_file_yml = File.join(Spider.paths[:apps],"/portal/setup/modello_gdpr.yml")
	        if self.count == 0
	            Spider::Model.load_fixtures(path_file_yml)
	        end
	    end

	    def before_save
	    	unless self.informativa.blank?
		    	html_informativa = Nokogiri::HTML(self.informativa, nil, 'utf-8')
		    	contenuto_editor_informativa = html_informativa.at('body').inner_html
	            #converto per soliti problemi di copia incolla di caratteri accentati ecc	
	            contenuto_editor_informativa = contenuto_editor_informativa.convert_from_mapped
	            contenuto_editor_informativa = contenuto_editor_informativa.force_encoding('UTF-8')
				self.informativa = contenuto_editor_informativa
			end
			unless self.autorizzazione.blank?
		    	html_autorizzazione = Nokogiri::HTML(self.autorizzazione, nil, 'utf-8') 
		    	contenuto_editor_autorizzazione = html_autorizzazione.at('body').inner_html
	            #converto per soliti problemi di copia incolla di caratteri accentati ecc	
	            contenuto_editor_autorizzazione = contenuto_editor_autorizzazione.convert_from_mapped
	            contenuto_editor_autorizzazione = contenuto_editor_autorizzazione.force_encoding('UTF-8')
				self.autorizzazione = contenuto_editor_autorizzazione
            end
            super

	    end




    end
   




end
module Moduli

    class CategoriaGiuridica < Spider::Model::Managed
    	element :nome, String
   

    	#se esiste il file yml per le categorie nella cartella config dell'installazione popolo la tabella
    	def self.after_sync
    		path_file_yml_categorie_giuridiche = File.join(Spider.paths[:config],"categorie_giuridiche_moduli.yml")
    		if self.count == 0 && File.exists?(path_file_yml_categorie_giuridiche)
                Spider::Model.load_fixtures(path_file_yml_categorie_giuridiche)
            end
    	end

    end

end
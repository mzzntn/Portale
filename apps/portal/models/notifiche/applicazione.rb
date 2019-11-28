# -*- encoding : utf-8 -*-
module Portal

module Notifiche
    class Applicazione < Spider::Model::Managed
        remove_element :id
        element :codice, String, :label => 'codice', :primary_key => true
        element :nome_breve, String, :label => 'nome_breve'
        element :descrizione, String, :label => 'descrizione' 

		def self.after_sync
			rows = num_rows_into_file
			if self.count != rows
				path = File.join(Spider.paths[:apps],"/portal/setup/applicazioni_con_notifiche.yml")
				Spider::Model.load_fixtures(path, true)
			end
		end

		def self.num_rows_into_file
			file = ::File.join(Spider.paths[:apps],"/portal/setup/applicazioni_con_notifiche.yml")
			data = []
			if (file =~ /\.([^\.]+)$/)
                extension = $1
            else
                raise ArgumentError, "Can't determine type of fixtures file #{file}"
            end
            data = {}
            case extension
            when 'yml'
                require 'yaml'
                data = ::YAML.load_file(file)
            end
            data = data[0] if data.is_a?(Array)
            num_rows = data[self.to_s].length
			return num_rows
		end
	end
end
end

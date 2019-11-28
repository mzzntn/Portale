# -*- encoding : utf-8 -*-
require 'nokogiri'
require 'saxerator'

module Portal
    
    class Comune < Spider::Model::BaseModel
        label 'Comune', 'Comuni'

        element :codice_istat, String, :primary_key => true
        element :nome, String, :order => 1
        element :data_abrogazione, Date
    
    	XPATH_RIGHE = 'xml/mysqldump/database/table_data/row'
    
	    def self.data_imp(d, klass=DateTime)
	        d ? DateTime.parse(d) : nil
	    end

     #    def self.after_sync
    	# 	if self.all.count == 0
    	# 		begin
    	# 		self.mapper.truncate!
    	# 		parser = Saxerator.parser(File.new(File.join(Spider.paths[:apps],"/portal/setup/comuni.xml"))) do |config|
     #                config.put_attributes_in_hash!
     #                config.ignore_namespaces!
     #            end    
                
     #            parser.for_tag(:row).each do |row|
     #                Comune.create(
     #                   :codice_istat => row['cod_istat'],
     #                   :nome => row['nome'],
     #                   :data_abrogazione => data_imp(row['data_abrogazione'], Date),
     #                   :provincia => row['provincia_id']
     #                )
     #            end
                   
     #            rescue Exception => e
     #    			Spider.logger.error "Errore in importazione COMUNI: #{e.message} \n\n #{e.backtrace}"        
    	# 		end 
    	# 	end
    	# end

        def to_s
            self.nome
        end

    end
end

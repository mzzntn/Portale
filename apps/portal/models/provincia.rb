# -*- encoding : utf-8 -*-
require 'nokogiri'
require 'saxerator'

module Portal
    
    class Provincia < Spider::Model::BaseModel
    
        label 'Provincia', 'Province'
        
        element :cod_provincia, Integer, :primary_key => true
        element :nome, String
        element :sigla, String
        #many :comuni, Portal::Comune, :add_reverse => :provincia
    
    	XPATH_RIGHE = 'xml/mysqldump/database/table_data/row'
    
	    def self.data_imp(d, klass=DateTime)
	        d ? DateTime.parse(d) : nil
	    end

    	def upcase
            self.nome.respond_to?(:force_encoding) ? self.nome.upcase.force_encoding('utf-8') : self.nome.upcase
        end

        #facendo lo spider model sync -m Portal si creano le righe in tabella
        def self.after_sync
    		if self.all.count == 0
    			begin
    			self.mapper.truncate!
    			parser = Saxerator.parser(File.new(File.join(Spider.paths[:apps],"/portal/setup/province.xml"))) do |config|
                    config.put_attributes_in_hash!
                    config.ignore_namespaces!
                end    
                
                parser.for_tag(:row).each do |row|
                    Provincia.create(
                       :cod_provincia => row['cod_provincia'],
                       :nome => row['nome'],
                       :sigla => row['sigla']
                    )
                end
                   
                rescue Exception => e
        			Spider.logger.error "Errore in importazione PROVINCE: #{e.message} \n\n #{e.backtrace}"        
    			end 
    		end
    	end

        def to_s
            self.nome.respond_to?(:force_encoding) ? self.nome.force_encoding('utf-8') : self.nome
        end

    end
end

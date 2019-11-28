# -*- encoding : utf-8 -*-
module Portal

	module Hippo

	    class Settore < Spider::Model::BaseModel
	    	label 'Settore', 'Settori' 
	        element :id, String
	        element :nome, String
	        element :link_esterno, String
	    end

	    def to_s
	    	self.nome
	    end

	end

end

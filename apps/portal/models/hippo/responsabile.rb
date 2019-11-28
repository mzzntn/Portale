# -*- encoding : utf-8 -*-
module Portal

	module Hippo

	    class Responsabile < Spider::Model::BaseModel
	    	label 'Responsabile', 'Responsabili' 
	        element :id, String
	        element :nome, String
	    end

	end

end
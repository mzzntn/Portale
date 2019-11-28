# -*- encoding : utf-8 -*-
module Portal

	module Hippo

	    class Procedimento < Spider::Model::BaseModel
	    	label 'Procedimento', 'Procedimenti' 
	        element :id, String
	        element :nome, String
	        #element :responsabile, String
	        element :termine, String
	    end

	end

end
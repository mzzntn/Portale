# -*- encoding : utf-8 -*-
module Portal

	module Hippo

	    class Procedimento < Spider::Model::Managed
	    	label 'Procedimento', 'Procedimenti'
	        element :nome, String
	        #element :responsabile, String
	        element :termine, String
	    end

	end

end
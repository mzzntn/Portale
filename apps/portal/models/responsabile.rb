# -*- encoding : utf-8 -*-
module Portal

	module Hippo

	    class Responsabile < Spider::Model::Managed
	    	label 'Responsabile', 'Responsabili'
	        element :nome, String
	    end

	end

end
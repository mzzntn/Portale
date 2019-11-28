# -*- encoding : utf-8 -*-
module Portal

	module Hippo

	    class Settore < Spider::Model::Managed
	    	label 'Settore', 'Settori'
	        element :nome, String
	        element :link_esterno, String
	    end

	end

end
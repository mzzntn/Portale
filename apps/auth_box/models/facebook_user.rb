# -*- encoding : utf-8 -*-
module AuthBox

	class FacebookUser 
		include Spider::Auth::Authenticable
		attr_accessor :id_controller

	end


end


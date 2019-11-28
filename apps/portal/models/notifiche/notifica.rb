# -*- encoding : utf-8 -*-

module Portal
	module Notifiche
		include Spider::Messenger::MessengerHelper rescue NameError
	    class Notifica < Spider::Model::Managed
	    	label 'Notifica', 'Notifiche'
	    	element :applicazione, Portal::Notifiche::Applicazione
	    	element :notifica_email, Bool, :label => 'Notifica via email'
	    	element :notifica_sms, Bool, :label => 'Notifica via sms'
	    	element :notifica_push, Bool, :label => 'Notifica push' #non utilizzato

		    def invia(notifica)
		    	myclass="Portal::Notifiche::#{applicazione.codice.capitalize}".split('::').inject(Object) {|o,c| o.const_get c}
		    	if self.notifica_email || self.notifica_sms
		    		stati_attivi = myclass.esegui_invio(notifica)
		    	end
		    end
	    end 
	end
end

# -*- encoding : utf-8 -*-
module Portal

	class MobileDeviceRegistrati < Spider::Model::Managed
		element :device_type, String 
		element :registrationId, String, :unique => true
		element :app_name, String
		element :lista_servizi, String
		element :utente_portale, Portal::Utente
		element :gruppo, Portal::Gruppo
	end
end

# -*- encoding : utf-8 -*-
module Portal

	class Amministratore < Spider::Auth::LoginUser
		label 'Amministratore Servizi', 'Amministratori servizi' 
		#multiple_choice :servizi_privati, Portal::Servizio
		multiple_choice :servizi, Portal::Servizio, :condition => { :gestibile => true }
		element :start_user, String, :label => 'Login Auth Unica'
		choice :settore, Portal::Hippo::Settore, :add_multiple_reverse => :amministratori
		choice :limitazione, {
            '0' => 'Nessuna',
			'1' => 'Profilo1',
			'2' => 'Profilo2',
			'3' => 'Profilo3'
        }, :default => '0', :required => true
	end

end

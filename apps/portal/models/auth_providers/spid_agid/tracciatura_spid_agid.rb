# -*- encoding : utf-8 -*-
# VEDI CAP 4.2 REGOLE TECNICHE
# Il comma 2 dell’articolo 13 del DPCM obbliga i fornitori di servizi ( service provider ) alla
# conservazione per ventiquattro mesi delle informazioni
module Portal
    
    class TracciaturaSpidAgid < Spider::Model::Managed
    	element :provider_id, String
    	element :authn_request, Text
    	element :response, Text
    	element :authn_req_id, String
    	element :authn_req_issue_instant, DateTime
    	element :response_id, String
    	element :response_issue_instant, DateTime
    	element :response_issuer, String
    	element :assertion_id, String
    	element :assertion_subject, String
    	element :assertion_subject_name_qualifier, String
        element :utente_tracciato, Portal::Utente, :add_multiple_reverse => :tracciature
     end 


end
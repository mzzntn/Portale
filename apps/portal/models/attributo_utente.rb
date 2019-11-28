# -*- encoding : utf-8 -*-
module Portal
    
    class AttributoUtente < Spider::Model::InlineModel
        element :id, String, :primary_key => true
        element :desc, String
        
        data({
            'codice_master' => 'Codice Master'
        })
    end
    
end

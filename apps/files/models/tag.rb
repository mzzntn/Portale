# -*- encoding : utf-8 -*-
module Spider; module Files
    
    class Tag < Spider::Model::Managed
        element :name, String, :label => _('Name')
    end
    
end; end

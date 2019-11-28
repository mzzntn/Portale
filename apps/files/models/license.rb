# -*- encoding : utf-8 -*-
module Spider; module Files
    
    class License < Spider::Model::Managed
        element :holder, String
        element :date, String
        element :attribution, Spider::DataTypes::Bool
        element :non_commercial, Spider::DataTypes::Bool
        element :url, String
        
    end
    
end; end

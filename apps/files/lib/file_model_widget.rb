# -*- encoding : utf-8 -*-
module Spider; module Files
    
    module FileModelWidget
        
        def self.included(klass)
            klass.extend(ClassMethods)
        end
        
        module ClassMethods
            
            def file_model
                @file_model || Spider::Files::File
            end

            def file_model=(val)
                @file_model = val
            end

            def tag_model
                @file_model.elements[:tags].type
            end

            
        end
        
        def file_model
            @file_model || self.class.file_model
        end

        def file_model=(val)
            @file_model = val
        end

        def tag_model
            file_model.elements[:tags].type
        end
        
    end
    
end; end

# -*- encoding : utf-8 -*-
module Spider; module Files
    
    class FileForm < Spider::Forms::Form
        tag 'form'
        
        def prepare
            attributes[:show_related] = true
            super
            @multipart = true unless @obj
        end
        
        def create_inputs
            @disabled << :name unless @obj
            super
        end
                
        def instantiate_obj
            if @pk && !@pk.to_s.empty?
                super
            elsif upl = params["file_upload"]
                obj = @model.new_from_path(upl.path)
                obj.name = upl.filename
                obj
            else 
                add_error(_('A file is required'))
                nil
            end
        end
        
        def save(action=nil)
            super
        end
        
        
        
    end
    
    
end; end

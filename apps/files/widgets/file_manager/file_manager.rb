# -*- encoding : utf-8 -*-
module Spider; module Files
    
    class FileManager < Spider::Widget
        tag 'manager'
        
        include FileModelWidget
        
        attribute :"no-buttons"
        attribute :"file-model"
        attribute :"url-with-name", :type => Spider::DataTypes::Bool, :default => false
        
        def prepare
            @scene.active = {}
            
            @file_model ||= const_get_full(attributes[:"file-model"]) if attributes[:"file-model"]

            if @file_model
                init_widgets
                @widgets.each do |k, v|
                    if @widgets[k].is_a?(Spider::Files::FileModelWidget)
                        @widgets[k].file_model = @file_model
                    end
                end
            end
            
            super
            
            # debugger if run?
            
            if @widgets[:viewer]
                @widgets[:viewer].attributes[:"no-buttons"] = attributes[:"no-buttons"]
                @widgets[:viewer].attributes[:"url-with-name"] = attributes[:"url-with-name"]
            end
            
            if run?
                action = params['action']
                if action
                    if action == 'archive'
                        switch_to(:archive)
                    elsif action == 'upload'
                        @widgets[:viewer].session.delete(:file)
                        switch_to(:upload)
                    end
                else
                    upl = @widgets[:upload].get_uploaded
                    if upl && ::File.exists?(upl[:path])
                        @widgets[:viewer].tmp_file = upl
                        switch_to(:viewer)
                    elsif @widgets[:archive] && @widgets[:archive].clicked?
                        @widgets[:viewer].file = @widgets[:archive].get_clicked
                        switch_to(:viewer)
                    elsif @widgets[:viewer].has_params?
                        switch_to(:viewer)
                    else
                        switch_to(:archive)
                    end
                end
            end
        end
        
        def run
            super
            if run? && @widgets[:viewer] && @widgets[:viewer].saved?
                redirect(@request.full_uri)
            end
        end
        
        def switch_to(id)
            @scene.active = {id => 'active'}
            @widgets.each do |k, v|
                @widgets[k].is_target = (k == id) ? true : false
            end
        end
        
    end
    
end; end

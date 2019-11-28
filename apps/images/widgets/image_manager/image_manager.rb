# -*- encoding : utf-8 -*-
module Spider; module Images
    
    class ImageManager < Spider::Files::FileManager
        tag 'manager'
        i_attribute :image
        attribute :"resize-saved"
        attribute :"allow-resize", :type => Spider::Bool
        attribute :"show-caption", :type => Spider::Bool
        
        def prepare
            if params['image']
                @image = Spider::Images::Image.load(:uuid => params['image'])
            end
            # debugger if run?
            
            super
            
            pass_attributes_to_widget(:viewer, [:"resize-saved", :"allow-resize", :"show-caption"])
            
            if run?
                action = params['action']
                if action
                    switch_to(:search) if action == 'web'
                else                
                    if @widgets[:search] && @widgets[:search].clicked?
                        @widgets[:viewer].tmp_file = @widgets[:search].get_clicked
                        switch_to(:viewer)
                    elsif @widgets[:archive] && @widgets[:archive].clicked?
                        @widgets[:viewer].file = @widgets[:archive].get_clicked
                        switch_to(:viewer)
                    elsif @image
                        view_img = @image
                        #view_img = view_img.original unless view_img.original?
                        @widgets[:viewer].file = view_img
                        switch_to(:viewer)
                    elsif !@widgets[:viewer].tmp_file
                        switch_to(:archive)
                    end
                end
            end
        end
        
        def pass_attributes_to_widget(w, attrs)
            return unless @widgets[w]
            attrs.each do |a|
                unless @widget_attributes[w.to_s] && @widget_attributes[w.to_s][a]
                    @widgets[w].attributes[a] = attributes[a]
                end
            end
        end
        
    end
    
end; end

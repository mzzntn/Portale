# -*- encoding : utf-8 -*-
module Spider; module Images
    
    class ImageBrowser < Spider::Widget
        tag 'browser'
        
        i_attribute :thumbs
        i_attribute :full
        # attr_accessor :images
        is_attr_accessor :images
        
        
        def prepare
            @images ||= []
            if params["image"]
                @clicked = params["image"]
            end
            super
            # @scene.tags = Images::Tag.all.order_by(:name)
            # @scene.images = Images::Image.all
        end
        
        def clicked?
            @clicked
        end
        
        def clicked
            @clicked
        end
        
        def get_clicked
            @images.select{ |i| i[:id] == @clicked }.first
        end

        
    end
    
end; end

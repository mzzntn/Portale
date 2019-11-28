# -*- encoding : utf-8 -*-
module Spider; module Images

    class ImageArchive < Spider::Widget
        tag 'archive'
        
        def prepare
            @scene.tags = Images::Tag.all.order_by(:name)
            @active_tags = session[:active_tags] || []
            if params['toggle_tag']
                if @active_tags.include?(params['toggle_tag'])
                    @active_tags.delete(params['toggle_tag'])
                else
                    @active_tags << params['toggle_tag']
                end
            end
            @scene.images = Images::Image.where{ |im|
                (im.original == nil)
            }
            unless @active_tags.empty?
                c = Spider::Model::Condition.and
                @active_tags.each do |t|
                    c.set('tags.name', '=', t)
                end
                @scene.images.condition << c
            end
            session[:active_tags] = @active_tags
            @scene.active_tags = {}
            @active_tags.each{ |t| @scene.active_tags[t] = true }
            super
        end
        
        def clicked?
            params['clicked']
        end
        
        def get_clicked
            Images::Image.load(:uuid => params['clicked'])
        end

    end

end; end

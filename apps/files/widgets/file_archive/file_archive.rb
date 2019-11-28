# -*- encoding : utf-8 -*-
module Spider; module Files

    class FileArchive < Spider::Widget
        tag 'archive'
        
        include FileModelWidget
        
        def prepare
            @scene.tags = self.tag_model.all.order_by(:name)
            @scene.files = self.file_model.all
            @active_tags = session[:active_tags] || []
            if params['toggle_tag']
                if @active_tags.include?(params['toggle_tag'])
                    @active_tags.delete(params['toggle_tag'])
                else
                    @active_tags << params['toggle_tag']
                end
            end
            unless @active_tags.empty?
                c = Spider::Model::Condition.and
                @active_tags.each do |t|
                    c.set('tags.name', '=', t)
                end
                @scene.files.condition << c
            end
            session[:active_tags] = @active_tags
            @scene.active_tags = {}
            @active_tags.each{ |t| @scene.active_tags[t] = true }
            super
        end
        
        def run
            super
        end
        
        def clicked?
            params['clicked']
        end
        
        def get_clicked
            self.file_model.load(:uuid => params['clicked'])
        end

    end

end; end

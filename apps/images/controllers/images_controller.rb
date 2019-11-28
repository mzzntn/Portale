# -*- encoding : utf-8 -*-
module Spider; module Images
    
    class ImagesController < Spider::PageController
        include StaticContent
        
        layout ['images'], :assets => 'images', :single_layout => true
        
        def execute(action, *params)
            return super unless action =~ /([\w\d]{8}-[\w\d]{4}-[\w\d]{4}-[\w\d]{4}-[\w\d]{12})(?:\/(.+))?/
            uuid = $1
            name = $2
            full_name = nil
            full_name = "#{name}.#{@request.format}" if name
            i = Image.load(:uuid => uuid)
            raise NotFound.new("image #{uuid}") unless i
            if name
                raise NotFound.new("image #{uuid} with name #{name}") unless i.name == name || i.name === full_name
            end
            var = @request.params['v']
            v = nil
            v = i.copy(var) rescue nil
            i = v if v
            if @request.params.key?('dl')
                @response.headers['Content-disposition'] = "attachment; filename=#{i.name}"
            end
            if i.respond_to?(:path)
                output_static(i.path, i.name)
            else
                # TODO
                raise NotImplemented
            end
        end
        
        __.html :template => 'manager'
        def manager
        end
    
    end
    
end; end

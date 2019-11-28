# -*- encoding : utf-8 -*-
module Spider; module Files
    
    class FilesController < Spider::PageController
    
        #layout ['/portal/portal', 'files'], :assets => 'files'
        layout ['files'], :assets => 'files', :single_layout => true
        
        def execute(action='', *params)
            if action =~ /([\w\d]{8}-[\w\d]{4}-[\w\d]{4}-[\w\d]{4}-[\w\d]{12})(?:\/(.+))?/
                return file($1, $2)
            end
            super
        end
        
        __.html :template => 'manager'
        def manager
            #render 'manager', :layout => nil
        end
        
        def file(uuid, name=nil)
            f = File.load(:uuid => uuid)
            raise NotFound.new("File #{uuid}") unless f
            full_name = "#{name}.#{@request.format}"
            raise NotFound.new("File #{uuid} with name #{name}") if name && f.name != name && f.name != full_name
            if f.respond_to?(:path)
                output_static(f.path, f.name)
            else
                # TODO
                raise NotImpemented 
            end
        end

    end

end; end

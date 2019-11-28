# -*- encoding : utf-8 -*-
module Spider; module Files

    class FileBrowser < Spider::Widget
        tag 'browser'
        is_attribute :files
        
        def prepare
            @files ||= []
            super
        end

    end

end; end

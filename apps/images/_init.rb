# -*- encoding : utf-8 -*-
module Spider
    
    module Images
        include Spider::App
        @controller = :ImagesController
    
    end
    
end

Spider::Template.register_namespace('images', Spider::Images)


require 'apps/images/controllers/images_controller'
require 'apps/images/models/image'

require 'apps/images/widgets/image_browser/image_browser'
require 'apps/images/widgets/image_archive/image_archive'
require 'apps/images/widgets/image_search/image_search'
require 'apps/images/widgets/image_viewer/image_viewer'
require 'apps/images/widgets/image_manager/image_manager'

# -*- encoding : utf-8 -*-
module AuthBox
    include Spider::App
    @controller = :AuthBoxController
end


require 'apps/auth_box/lib/dispatcher'
require 'apps/auth_box/controllers/simple_external_controller'
require 'apps/auth_box/controllers/facebook_controller'
require 'apps/auth_box/controllers/twitter_controller'
require 'apps/auth_box/controllers/civilia_open_controller'
require 'apps/auth_box/controllers/co_websi_controller'
require 'apps/auth_box/controllers/auth_box_controller'


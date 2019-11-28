# -*- encoding : utf-8 -*-
#require 'apps/auth_box/models/facebook_user'

module AuthBox
    
    class FacebookController < Spider::PageController
        
    	route /login/, :login

        layout 'auth_box'
    
    
        __.html :template => 'login_facebook'
        def login
            @scene.msg = 'Hello!'
        end    


    end
    
end

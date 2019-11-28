# -*- encoding : utf-8 -*-
module Portal

    class AdminController < Spider::Admin::AppAdminController
        layout '/core/admin/admin', :assets => 'portal_admin'

        route nil, :index

        __.html :template => 'admin', :action_to => :admin
        def index
            init_template
            if @template && @template.widgets[:admin]
                Portal.auth_providers.each do |provider|
                    @template.widgets[:admin].models << provider.details[:user_model] if provider.details[:user_model]
                end
                #se attivo i settori hippo allora non mostro la gestione, altrimenti si
                if !Spider.conf.get('portal.attiva_settori_hippo')
                    @template.widgets[:admin].models << Portal::Hippo::Settore
                    @template.widgets[:admin].models << Portal::Hippo::Procedimento
                    @template.widgets[:admin].models << Portal::Hippo::Responsabile
                end
                #aggiungo link a GDPR -> fatto su views per impostare attributi widget
                #@template.widgets[:admin].models << Portal::Gdpr
            end
        end




    end


end

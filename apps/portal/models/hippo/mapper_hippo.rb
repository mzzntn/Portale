# -*- encoding : utf-8 -*-
require 'apps/hippo/models/mixins/hippo_struct'
require 'apps/portal/models/hippo/modelli_beneficio'


Portal.models.each do |m|
    m.send(:include, Hippo::HippoStruct) if m.to_s.include?("Hippo")
    m.remove_element(:cr_user)
    m.remove_element(:mod_user)
end

module Portal

    module Hippo

    Settore.send(:include, Portal::Hippo::ModelliBeneficio)

    Settore.binding({
        :table => 'benefsettore',
        :elements => {
            "id" => {:field => "ID"},
            "nome" => {:field => "NOME"},
            "link_esterno" => {:field => "LINKESTERNO"}
        }
    })

    Procedimento.send(:include, Portal::Hippo::ModelliBeneficio)

    Procedimento.binding({
        :table => 'benefprocedimento',
        :elements => {
            "id" => {:field => "ID"},
            "nome" => {:field => "NOME"},
            "termine" => {:field => "TERMINE"}
        }
    })

    Responsabile.send(:include, Portal::Hippo::ModelliBeneficio)

    Responsabile.binding({
        :table => 'benefresponsabile',
        :elements => {
            "id" => {:field => "ID"},
            "nome" => {:field => "NOME"}
        }
    })

    end

end
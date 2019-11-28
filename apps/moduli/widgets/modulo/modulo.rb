# -*- encoding : utf-8 -*-
module Moduli

    class Modulo < WidgetModulo
        tag 'modulo'
        is_attribute :codice
        is_attribute :titolo
        is_attr_accessor :sub_widgets
       
        def self.parse_content_xml(xml)
            super("<tpl:append search='#contenuto'>#{xml}</tpl:append>")
        end

        def prepare
          	@scene.torna_indietro = Moduli::ModuliController.http_s_url
            super
        end

    end

end

